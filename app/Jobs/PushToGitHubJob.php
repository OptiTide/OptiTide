<?php

namespace App\Jobs;

use App\Enums\ArtifactType;
use App\Models\Order;
use App\Services\GitHubService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * On delivery, push the generated mockup + logic to a new private GitHub
 * repository. No-ops silently until GITHUB_TOKEN is configured.
 */
class PushToGitHubJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(public int $orderId) {}

    public function handle(GitHubService $github): void
    {
        if (! $github->isConfigured()) {
            return;
        }

        $order = Order::find($this->orderId);

        if ($order === null) {
            return;
        }

        $html = $order->latestArtifact(ArtifactType::MockupHtml)?->content;
        $js = $order->latestArtifact(ArtifactType::LogicCode)?->content;

        if (blank($html)) {
            return;
        }

        $url = $github->createAndPush($order, [
            'index.html' => $html,
            'app.js' => $js,
        ]);

        if ($url !== null) {
            $order->latestArtifact(ArtifactType::LogicCode)?->update(['github_repo_url' => $url]);
            $order->update(['meta' => array_merge($order->meta ?? [], ['github_repo_url' => $url])]);
        }
    }
}
