<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\AI\AdminAssistantService;
use App\Services\Api\ApiCreditService;
use App\Services\Audit\AuditLog;
use App\Services\Billing\CreditService;
use App\Services\Billing\LateFeeService;

/** Admin-only AI copilot: reads the platform via tools, proposes edits to confirm. */
class AssistantController extends Controller
{
    private const ACTIONS = ['add_account_credit', 'adjust_api_credit', 'waive_late_fee'];

    public function index(Request $request): Response
    {
        $this->authorize(Auth::isAdmin(), 'The AI assistant is available to administrators only.');

        return $this->view('admin.assistant.index', [
            'title'     => 'AI Assistant',
            'available' => (new AdminAssistantService())->available(),
        ]);
    }

    public function message(Request $request): Response
    {
        $this->authorize(Auth::isAdmin(), 'Administrators only.');

        $service = new AdminAssistantService();
        if (! $service->available()) {
            return Response::json(['ok' => false, 'error' => 'The AI assistant isn\'t configured yet. Add ANTHROPIC_API_KEY to enable it.'], 200);
        }

        // The client sends the running conversation (roles user/assistant, text only).
        $history = $request->json('history');
        if (! is_array($history)) {
            $history = [];
        }
        $history = array_slice($history, -20); // bound the context

        $admin = Auth::user();
        $result = $service->ask($history, $admin['name'] ?? 'there');
        if (! $result['ok']) {
            return Response::json(['ok' => false, 'error' => $result['error'] ?: 'The assistant could not answer.'], 200);
        }

        // Pull out a proposed ```action {...}``` block, if present, and stage it.
        [$reply, $action] = $this->extractAction($result['reply']);

        $payload = ['ok' => true, 'reply' => $reply];
        if ($action !== null) {
            $token = bin2hex(random_bytes(12));
            Session::put('_assistant_action_' . $token, $action);
            $payload['action'] = ['token' => $token, 'type' => $action['type'], 'summary' => $action['summary'] ?? 'Apply this change'];
        }

        return Response::json($payload);
    }

    /** Execute a previously-proposed action after the admin confirms it. */
    public function execute(Request $request): Response
    {
        $this->authorize(Auth::isAdmin(), 'Administrators only.');

        $token = (string) $request->input('token', $request->json('token', ''));
        $action = $token !== '' ? Session::pull('_assistant_action_' . $token) : null;
        if (! is_array($action) || ! in_array($action['type'] ?? '', self::ACTIONS, true)) {
            return Response::json(['ok' => false, 'error' => 'That action has expired — please ask again.'], 200);
        }

        try {
            $message = $this->perform($action);
        } catch (\Throwable $e) {
            return Response::json(['ok' => false, 'error' => 'Could not apply the change: ' . $e->getMessage()], 200);
        }

        AuditLog::record('assistant.action_executed', 'client', $action['client_id'] ?? ($action['invoice_id'] ?? null), [
            'type'    => $action['type'],
            'summary' => $action['summary'] ?? null,
        ]);

        return Response::json(['ok' => true, 'message' => $message]);
    }

    private function perform(array $action): string
    {
        $summary = 'AI assistant: ' . (string) ($action['summary'] ?? '');

        switch ($action['type']) {
            case 'add_account_credit':
                $client = Client::findOrFail((int) $action['client_id']);
                $cents = (int) $action['amount_cents'];
                (new CreditService())->add($client['id'], $cents, $cents >= 0 ? 'add' : 'adjust', $summary, Auth::id());

                return 'Account credit for ' . $client['business_name'] . ' adjusted by ' . money($cents)->format() . '.';

            case 'adjust_api_credit':
                $client = Client::findOrFail((int) $action['client_id']);
                (new ApiCreditService())->adjust($client['id'], (int) $action['amount_cents'], $summary, Auth::id());

                return 'API credit for ' . $client['business_name'] . ' adjusted by ' . money((int) $action['amount_cents'])->format() . '.';

            case 'waive_late_fee':
                $invoice = Invoice::findOrFail((int) $action['invoice_id']);
                (new LateFeeService())->waive($invoice['id'], $summary);

                return 'Late fee on invoice ' . $invoice['number'] . ' waived.';
        }

        return 'Done.';
    }

    /** @return array{0:string,1:?array} the reply text (block stripped) and the parsed action */
    private function extractAction(string $reply): array
    {
        if (! preg_match('/```action\s*(\{.*?\})\s*```/s', $reply, $m)) {
            return [$reply, null];
        }

        $action = json_decode($m[1], true);
        $clean = trim(str_replace($m[0], '', $reply));

        if (! is_array($action) || ! in_array($action['type'] ?? '', self::ACTIONS, true)) {
            return [$clean, null];
        }

        return [$clean, $action];
    }
}
