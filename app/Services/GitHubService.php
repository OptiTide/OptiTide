<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Creates a private GitHub repository for a delivered order and pushes the
 * generated code. Config-gated: no-ops until a GITHUB_TOKEN is set.
 */
class GitHubService
{
    public function isConfigured(): bool
    {
        return filled(config('services.github.token'));
    }

    /**
     * Create a private repo and commit the given files.
     *
     * @param  array<string, ?string>  $files  path => content
     * @return string|null the repo URL, or null if not configured
     */
    public function createAndPush(Order $order, array $files): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $http = Http::withToken(config('services.github.token'))
            ->withHeaders([
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ]);

        $owner = config('services.github.owner');
        $repo = 'optitide-'.Str::slug($order->order_number);

        // Idempotent: reuse the repo if a prior (partial) push already created
        // it, so a retry after a mid-push failure doesn't 422 on the name.
        $existing = $owner ? $http->get("https://api.github.com/repos/{$owner}/{$repo}") : null;

        if ($existing?->ok()) {
            $created = $existing;
        } else {
            $created = $http->post('https://api.github.com/user/repos', [
                'name' => $repo,
                'private' => true,
                'auto_init' => true,
                'description' => "OptiTide delivery for {$order->order_number}",
            ]);

            if ($created->failed()) {
                throw new RuntimeException("GitHub repository creation failed ({$created->status()}).");
            }
        }

        $fullName = $created->json('full_name');

        foreach ($files as $path => $content) {
            if (blank($content)) {
                continue;
            }

            // Include the current blob sha when the file already exists, so a
            // re-push updates it in place instead of 422-ing.
            $current = $http->get("https://api.github.com/repos/{$fullName}/contents/{$path}");
            $payload = [
                'message' => "Add {$path}",
                'content' => base64_encode($content),
            ];
            if ($current->ok() && $current->json('sha')) {
                $payload['sha'] = $current->json('sha');
            }

            $http->put("https://api.github.com/repos/{$fullName}/contents/{$path}", $payload)->throw();
        }

        return $created->json('html_url');
    }
}
