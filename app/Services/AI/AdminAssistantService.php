<?php

namespace App\Services\AI;

use App\Models\AuditEntry;
use App\Models\Client;
use App\Models\Invoice;
use App\Support\Money;

/**
 * Admin AI assistant. Reasons over the platform's live data through a set of
 * READ-ONLY tools (executed automatically), and can PROPOSE an edit by ending
 * its reply with a ```action ...``` block — which the controller stages for the
 * admin to confirm. The assistant itself never mutates anything.
 */
final class AdminAssistantService
{
    private const MAX_TURNS = 6;

    public function available(): bool
    {
        return (bool) config('ai.assistant_enabled')
            && trim((string) config('ai.anthropic_key', '')) !== '';
    }

    /**
     * @param  array  $history  [['role'=>'user'|'assistant','content'=>string], ...]
     * @return array{ok:bool,reply:string,error:?string}
     */
    public function ask(array $history, string $adminName): array
    {
        $key = trim((string) config('ai.anthropic_key', ''));
        if ($key === '') {
            return ['ok' => false, 'reply' => '', 'error' => 'The AI assistant is not configured (no API key).'];
        }

        $messages = [];
        foreach ($history as $m) {
            $role = ($m['role'] ?? 'user') === 'assistant' ? 'assistant' : 'user';
            $text = trim((string) ($m['content'] ?? ''));
            if ($text !== '') {
                $messages[] = ['role' => $role, 'content' => $text];
            }
        }
        if ($messages === [] || $messages[array_key_last($messages)]['role'] !== 'user') {
            return ['ok' => false, 'reply' => '', 'error' => 'No question to answer.'];
        }

        for ($turn = 0; $turn < self::MAX_TURNS; $turn++) {
            $response = $this->call($key, $messages, $adminName);
            if ($response === null) {
                return ['ok' => false, 'reply' => '', 'error' => 'The assistant could not be reached. Please try again.'];
            }

            $content = $response['content'] ?? [];
            $toolUses = array_values(array_filter($content, fn ($b) => ($b['type'] ?? '') === 'tool_use'));

            if (($response['stop_reason'] ?? '') !== 'tool_use' || $toolUses === []) {
                // Final answer — concatenate any text blocks.
                $text = '';
                foreach ($content as $b) {
                    if (($b['type'] ?? '') === 'text') {
                        $text .= $b['text'] ?? '';
                    }
                }

                return ['ok' => true, 'reply' => trim($text), 'error' => null];
            }

            // Execute each requested read tool and feed the results back.
            $messages[] = ['role' => 'assistant', 'content' => $content];
            $results = [];
            foreach ($toolUses as $tool) {
                $results[] = [
                    'type'        => 'tool_result',
                    'tool_use_id' => $tool['id'] ?? '',
                    'content'     => json_encode($this->runTool((string) ($tool['name'] ?? ''), (array) ($tool['input'] ?? []))),
                ];
            }
            $messages[] = ['role' => 'user', 'content' => $results];
        }

        return ['ok' => true, 'reply' => 'I looked into that but need to narrow it down — could you be more specific?', 'error' => null];
    }

    /** The read tools the model may call. */
    private function tools(): array
    {
        return [
            [
                'name'         => 'platform_stats',
                'description'  => 'Get a snapshot of the whole business: client counts, invoice totals by status, outstanding balance, API-credit balances.',
                'input_schema' => ['type' => 'object', 'properties' => (object) []],
            ],
            [
                'name'         => 'search_clients',
                'description'  => 'Find clients by business name or email (partial match). Returns id, name, email, status.',
                'input_schema' => ['type' => 'object', 'properties' => ['query' => ['type' => 'string']], 'required' => ['query']],
            ],
            [
                'name'         => 'client_overview',
                'description'  => 'Full picture of one client by id: contact, status, account credit, API credit, and their invoices with balances.',
                'input_schema' => ['type' => 'object', 'properties' => ['client_id' => ['type' => 'integer']], 'required' => ['client_id']],
            ],
            [
                'name'         => 'list_invoices',
                'description'  => 'List invoices filtered by status (draft, sent, paid, overdue, void). Returns number, client, total, balance, due date.',
                'input_schema' => ['type' => 'object', 'properties' => ['status' => ['type' => 'string']], 'required' => ['status']],
            ],
            [
                'name'         => 'recent_activity',
                'description'  => 'The most recent audit-log events across the platform (who did what).',
                'input_schema' => ['type' => 'object', 'properties' => ['limit' => ['type' => 'integer']]],
            ],
        ];
    }

    private function runTool(string $name, array $input): array
    {
        try {
            return match ($name) {
                'platform_stats'  => $this->toolStats(),
                'search_clients'  => $this->toolSearchClients((string) ($input['query'] ?? '')),
                'client_overview' => $this->toolClientOverview((int) ($input['client_id'] ?? 0)),
                'list_invoices'   => $this->toolListInvoices((string) ($input['status'] ?? 'overdue')),
                'recent_activity' => $this->toolRecentActivity((int) ($input['limit'] ?? 15)),
                default           => ['error' => 'Unknown tool.'],
            };
        } catch (\Throwable $e) {
            return ['error' => 'Tool failed: ' . $e->getMessage()];
        }
    }

    private function toolStats(): array
    {
        $clients = Client::query()->get();
        $invoices = Invoice::query()->get();

        $byStatus = [];
        $outstanding = 0;
        foreach ($invoices as $inv) {
            $byStatus[$inv['status']] = ($byStatus[$inv['status']] ?? 0) + 1;
            if (in_array($inv['status'], [Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE], true)) {
                $outstanding += (int) $inv['total_cents'] - (int) $inv['amount_paid_cents'];
            }
        }
        $apiCredit = 0;
        foreach ($clients as $c) {
            $apiCredit += (int) ($c['api_credit_cents'] ?? 0);
        }

        return [
            'clients_total'          => count($clients),
            'invoices_by_status'     => $byStatus,
            'outstanding_balance'    => money($outstanding)->format() . ' AUD',
            'api_credit_outstanding' => money($apiCredit)->format() . ' AUD',
        ];
    }

    private function toolSearchClients(string $query): array
    {
        if (trim($query) === '') {
            return ['error' => 'query required'];
        }
        $rows = Client::query()
            ->whereRaw('(LOWER(business_name) LIKE ? OR LOWER(email) LIKE ?)', ['%' . strtolower($query) . '%', '%' . strtolower($query) . '%'])
            ->limit(10)->get();

        return ['clients' => array_map(fn ($c) => [
            'id' => $c['id'], 'business_name' => $c['business_name'], 'email' => $c['email'], 'status' => $c['status'],
        ], $rows)];
    }

    private function toolClientOverview(int $clientId): array
    {
        $c = Client::find($clientId);
        if (! $c) {
            return ['error' => 'client not found'];
        }
        $invoices = Invoice::query()->where('client_id', $clientId)->orderBy('id', 'desc')->limit(20)->get();

        return [
            'id'             => $c['id'],
            'business_name'  => $c['business_name'],
            'email'          => $c['email'],
            'status'         => $c['status'],
            'account_credit' => money((int) ($c['credit_cents'] ?? 0))->format(),
            'api_credit'     => money((int) ($c['api_credit_cents'] ?? 0))->format(),
            'invoices'       => array_map(fn ($i) => [
                'id' => $i['id'], 'number' => $i['number'], 'status' => $i['status'],
                'total' => money((int) $i['total_cents'], $i['currency'])->format(),
                'balance' => money((int) $i['total_cents'] - (int) $i['amount_paid_cents'], $i['currency'])->format(),
                'late_fee' => (int) ($i['late_fee_cents'] ?? 0) > 0 ? money((int) $i['late_fee_cents'], $i['currency'])->format() : null,
                'due_date' => $i['due_date'],
            ], $invoices),
        ];
    }

    private function toolListInvoices(string $status): array
    {
        $status = strtolower(trim($status));
        $rows = Invoice::query()->where('status', $status)->orderBy('due_date')->limit(25)->get();
        $names = array_column(Client::all(), 'business_name', 'id');

        return ['status' => $status, 'invoices' => array_map(fn ($i) => [
            'id' => $i['id'], 'number' => $i['number'], 'client' => $names[$i['client_id']] ?? '—',
            'total' => money((int) $i['total_cents'], $i['currency'])->format(),
            'balance' => money((int) $i['total_cents'] - (int) $i['amount_paid_cents'], $i['currency'])->format(),
            'late_fee' => (int) ($i['late_fee_cents'] ?? 0) > 0 ? money((int) $i['late_fee_cents'], $i['currency'])->format() : null,
            'due_date' => $i['due_date'],
        ], $rows)];
    }

    private function toolRecentActivity(int $limit): array
    {
        $limit = max(1, min($limit, 40));
        $rows = AuditEntry::query()->orderBy('id', 'desc')->limit($limit)->get();

        return ['events' => array_map(fn ($r) => [
            'when' => $r['created_at'], 'who' => $r['actor_name'], 'action' => $r['action'],
            'subject' => $r['subject_type'] ? $r['subject_type'] . '#' . $r['subject_id'] : null,
        ], $rows)];
    }

    private function call(string $key, array $messages, string $adminName): ?array
    {
        $payload = json_encode([
            'model'      => (string) config('ai.assistant_model', 'claude-sonnet-5'),
            'max_tokens' => 1500,
            'system'     => $this->systemPrompt($adminName),
            'tools'      => $this->tools(),
            'messages'   => $messages,
        ]);

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER     => [
                'content-type: application/json',
                'x-api-key: ' . $key,
                'anthropic-version: 2023-06-01',
            ],
        ]);
        $res = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($res === false || $code >= 400) {
            return null;
        }
        $data = json_decode((string) $res, true);

        return is_array($data) ? $data : null;
    }

    private function systemPrompt(string $adminName): string
    {
        $brand = config('company.brand_name');
        $ccy = config('company.currency') ?: 'AUD';

        return "You are the {$brand} Admin Assistant, an expert operations copilot for staff of {$brand}, "
            . "an Australian digital agency (web design, SEO, social media, hosting) that also resells a white-label API. "
            . "You are talking to {$adminName}, a staff member, inside the private admin console. "
            . "Use the read tools to answer questions about clients, invoices, money and activity with real, current data — "
            . "never guess figures; call a tool. All money is {$ccy} and GST-inclusive. Be concise and practical, use Australian spelling.\n\n"
            . "You CANNOT change anything yourself. If the admin asks you to make a change you can PROPOSE it: put a single fenced "
            . "code block at the very end of your reply, tagged `action`, containing JSON for ONE of these action types:\n"
            . "- {\"type\":\"add_account_credit\",\"client_id\":N,\"amount_cents\":N,\"summary\":\"...\"}\n"
            . "- {\"type\":\"adjust_api_credit\",\"client_id\":N,\"amount_cents\":N,\"summary\":\"...\"}\n"
            . "- {\"type\":\"waive_late_fee\",\"invoice_id\":N,\"summary\":\"...\"}\n"
            . "amount_cents may be negative to deduct. Only propose an action when the admin has clearly asked for that change. "
            . "The admin must click Confirm before it happens, so explain what you're proposing in plain words above the block.";
    }
}
