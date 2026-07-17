<?php

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Board;
use App\Models\BoardCard;
use App\Models\BoardColumn;
use App\Models\ClientService;
use App\Models\Invoice;
use App\Models\Meeting;
use App\Models\Quote;
use App\Models\Ticket;
use App\Support\Features;
use App\Support\Money;

/**
 * The portal landing page. It answers, at a glance: what do I owe, where is my
 * work up to, is anything waiting on me, and when are we next talking — with
 * every figure linking to the thing it counts.
 *
 * Delivery cards come from BoardCard::forClient, which filters client_visible
 * in the QUERY, so an internal card is never loaded here in the first place.
 */
class DashboardController extends Controller
{
    /** Live work items shown before "View Project" takes over. */
    private const PROJECT_PREVIEW = 3;

    /** Open quotes listed before the count alone carries it. */
    private const QUOTE_PREVIEW = 3;

    public function index(Request $request): Response
    {
        $clientId = Auth::clientId();
        $currency = config('company.currency', 'AUD');
        $zero = Money::zero($currency);

        $view = [
            'title'         => 'Dashboard',
            'outstanding'   => $zero,
            'paid'          => $zero,
            'services'      => 0,
            'overdue'       => 0,
            'next_due'      => null,
            'next_renewal'  => null,
            'recent'        => [],
            'open_quotes'   => [],
            'next_meeting'  => null,
            'open_tickets'  => 0,
            'reply_tickets' => [],
            'project_cards' => [],
            'project_open'  => 0,
            'project_done'  => 0,
            // A login not yet linked to a client account has nothing of its own
            // to show, so it gets the same "start here" guide a new client does.
            'is_new'        => true,
        ];

        if (! $clientId) {
            return $this->view('client.dashboard', $view);
        }

        $invoices = Invoice::query()
            ->where('client_id', $clientId)
            ->where('status', '!=', Invoice::STATUS_DRAFT)
            ->orderBy('id', 'desc')->get();

        $outstanding = 0;
        $paid = 0;
        $overdue = 0;
        foreach ($invoices as $invoice) {
            $paid += (int) $invoice['amount_paid_cents'];
            if (Invoice::isPayable($invoice)) {
                $outstanding += (int) $invoice['total_cents'] - (int) $invoice['amount_paid_cents'];
            }
            if ($invoice['status'] === Invoice::STATUS_OVERDUE) {
                $overdue++;
            }
        }

        $engagements = ClientService::forClient($clientId);
        $active = 0;
        foreach ($engagements as $engagement) {
            if ($engagement['status'] === ClientService::STATUS_ACTIVE) {
                $active++;
            }
        }

        $view['outstanding'] = new Money($outstanding, $currency);
        $view['paid'] = new Money($paid, $currency);
        $view['overdue'] = $overdue;
        $view['services'] = $active;
        $view['recent'] = array_slice($invoices, 0, 6);

        $view['next_due'] = Invoice::query()
            ->where('client_id', $clientId)
            ->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE])
            ->orderBy('due_date', 'asc')->first();

        $view['next_renewal'] = ClientService::query()
            ->where('client_id', $clientId)
            ->where('status', ClientService::STATUS_ACTIVE)
            ->where('billing_type', 'recurring')
            ->whereNotNull('next_invoice_date')
            ->orderBy('next_invoice_date', 'asc')->first();

        $cards = $this->projectCards($clientId);
        $view['project_cards'] = array_slice($cards['live'], 0, self::PROJECT_PREVIEW);
        $view['project_open'] = count($cards['live']);
        $view['project_done'] = $cards['done'];

        $view['open_quotes'] = $this->openQuotes($clientId);
        $view['next_meeting'] = $this->nextMeeting($clientId);

        $tickets = $this->openTickets($clientId);
        $view['open_tickets'] = $tickets['open'];
        $view['reply_tickets'] = $tickets['awaiting'];

        $view['is_new'] = $invoices === [] && $engagements === [] && $cards['total'] === 0;

        return $this->view('client.dashboard', $view);
    }

    /**
     * The client's own live delivery cards, newest board order first, plus how
     * many are already finished.
     *
     * @return array{live:array<int,array<string,mixed>>,done:int,total:int}
     */
    protected function projectCards(int|string $clientId): array
    {
        $cards = BoardCard::forClient($clientId);

        if ($cards === []) {
            return ['live' => [], 'done' => 0, 'total' => 0];
        }

        $columns = array_column(BoardColumn::all(), null, 'id');
        $progress = Board::checklistProgressMap(array_column($cards, 'id'));

        $live = [];
        $done = 0;
        foreach ($cards as $card) {
            if (! empty($card['completed_at'])) {
                $done++;

                continue;
            }

            $card['_status'] = $columns[$card['column_id']]['name'] ?? '—';
            $card['_progress'] = $progress[$card['id']] ?? ['done' => 0, 'total' => 0, 'pct' => 0];
            $live[] = $card;
        }

        return ['live' => $live, 'done' => $done, 'total' => count($cards)];
    }

    /**
     * Quotes actually awaiting a decision. Gated on Quote::isAcceptable — the
     * same gate the quote page enforces — so the dashboard never invites a
     * decision that page would then refuse.
     *
     * @return array<int,array<string,mixed>>
     */
    protected function openQuotes(int|string $clientId): array
    {
        if (! Features::enabled('quotes')) {
            return [];
        }

        $sent = Quote::query()
            ->where('client_id', $clientId)
            ->where('status', Quote::STATUS_SENT)
            ->orderBy('id', 'desc')->get();

        return array_slice(
            array_values(array_filter($sent, fn (array $quote) => Quote::isAcceptable($quote))),
            0,
            self::QUOTE_PREVIEW
        );
    }

    protected function nextMeeting(int|string $clientId): ?array
    {
        if (! Features::enabled('meetings')) {
            return null;
        }

        return Meeting::upcomingForClient($clientId)[0] ?? null;
    }

    /**
     * Open request count, and the ones actually waiting on the client.
     *
     * @return array{open:int,awaiting:array<int,array<string,mixed>>}
     */
    protected function openTickets(int|string $clientId): array
    {
        $open = 0;
        $awaiting = [];

        foreach (Ticket::forClient($clientId) as $ticket) {
            if ($ticket['status'] === Ticket::STATUS_CLOSED) {
                continue;
            }

            $open++;
            if ($ticket['status'] === Ticket::STATUS_PENDING) {
                $awaiting[] = $ticket;
            }
        }

        return ['open' => $open, 'awaiting' => $awaiting];
    }
}
