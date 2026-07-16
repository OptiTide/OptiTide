<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\Api\ApiCreditService;
use App\Services\Api\ApiKeyService;
use App\Services\Api\WhitelabelClaudeClient;
use App\Support\Features;

/**
 * The white-label OptiTide API (POST /api/v1/messages). Bearer-authenticated
 * with a per-client key, metered against prepaid credit, and proxied to the
 * upstream model. The upstream provider is never named in the response.
 */
class MessagesController extends Controller
{
    /**
     * Machine callers get a JSON 503 rather than the HTML 404 the browser-facing
     * controllers raise — an integration parses this, so it has to stay in shape.
     * Checked before the API key so a switched-off product never authenticates.
     */
    private function unavailable(): ?Response
    {
        return Features::enabled('api_credits')
            ? null
            : $this->error(503, 'service_unavailable', 'The OptiTide API is not currently available.');
    }

    public function create(Request $request): Response
    {
        if ($stop = $this->unavailable()) {
            return $stop;
        }

        $client = (new ApiKeyService())->resolveClient($request->bearerToken() ?: $request->header('X-API-Key'));
        if (! $client) {
            return $this->error(401, 'invalid_api_key', 'Missing or invalid API key.');
        }

        $upstream = new WhitelabelClaudeClient();
        if (! $upstream->available()) {
            return $this->error(503, 'service_unavailable', 'The OptiTide API is not currently available.');
        }

        // --- Validate the request ------------------------------------------------
        $alias = (string) $request->json('model', config('api_credits.default_model'));
        $models = (array) config('api_credits.models', []);
        if (! isset($models[$alias])) {
            return $this->error(400, 'invalid_model', 'Unknown model "' . $alias . '".');
        }

        $messages = $request->json('messages');
        if (! is_array($messages) || $messages === []) {
            return $this->error(400, 'invalid_request', '"messages" must be a non-empty array.');
        }
        $clean = [];
        foreach ($messages as $m) {
            $role = ($m['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
            $content = is_string($m['content'] ?? null) ? $m['content'] : '';
            if ($content === '') {
                continue;
            }
            $clean[] = ['role' => $role, 'content' => $content];
        }
        if ($clean === [] || $clean[0]['role'] !== 'user') {
            return $this->error(400, 'invalid_request', 'The first message must be from the user.');
        }

        $cap = (int) config('api_credits.max_tokens_cap', 4096);
        $maxTokens = (int) $request->json('max_tokens', $cap);
        $maxTokens = max(1, min($maxTokens, $cap));

        $system = is_string($request->json('system')) ? $request->json('system') : null;

        // --- Credit gate ---------------------------------------------------------
        $credits = new ApiCreditService();
        if (! $credits->hasCredit($client['id'])) {
            return $this->error(402, 'insufficient_credit', 'Your API credit balance is exhausted. Top up to continue.');
        }

        // --- Call upstream -------------------------------------------------------
        $result = $upstream->complete($models[$alias], $clean, $maxTokens, $system);
        if (! $result['ok']) {
            return $this->error($result['status'] ?: 502, 'upstream_error', $result['error'] ?: 'The request could not be completed.');
        }

        // --- Meter + bill --------------------------------------------------------
        $cost = $this->costCents($alias, $result['usage']['input_tokens'], $result['usage']['output_tokens']);
        $balance = $credits->settleUsage($client['id'], $cost, 'API usage (' . $alias . ')', [
            'model'         => $alias,
            'input_tokens'  => $result['usage']['input_tokens'],
            'output_tokens' => $result['usage']['output_tokens'],
            'cost_cents'    => $cost,
        ]);

        return Response::json([
            'id'      => 'msg_' . bin2hex(random_bytes(12)),
            'model'   => $alias,
            'role'    => 'assistant',
            'content' => [['type' => 'text', 'text' => (string) $result['content']]],
            'usage'   => [
                'input_tokens'  => $result['usage']['input_tokens'],
                'output_tokens' => $result['usage']['output_tokens'],
                'cost_cents'    => $cost,
            ],
            'credit_balance_cents' => $balance,
        ]);
    }

    /** GET /api/v1/credit — the caller's current prepaid balance. */
    public function credit(Request $request): Response
    {
        if ($stop = $this->unavailable()) {
            return $stop;
        }

        $client = (new ApiKeyService())->resolveClient($request->bearerToken() ?: $request->header('X-API-Key'));
        if (! $client) {
            return $this->error(401, 'invalid_api_key', 'Missing or invalid API key.');
        }

        return Response::json([
            'credit_balance_cents' => (new ApiCreditService())->balance($client['id']),
            'currency'             => 'AUD',
        ]);
    }

    /** Billed cost in cents from token usage and the model's per-Mtok rates. */
    private function costCents(string $alias, int $inTokens, int $outTokens): int
    {
        $rates = (array) (config('api_credits.pricing')[$alias] ?? ['in' => 0, 'out' => 0]);
        $raw = ($inTokens * (float) $rates['in'] + $outTokens * (float) $rates['out']) / 1_000_000;
        $cents = (int) ceil($raw);

        // Any successful, non-trivial call bills at least 1 cent.
        return ($inTokens + $outTokens) > 0 ? max(1, $cents) : $cents;
    }

    private function error(int $status, string $type, string $message): Response
    {
        return Response::json(['error' => ['type' => $type, 'message' => $message]], $status);
    }
}
