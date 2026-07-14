<?php

namespace App\Jobs;

use App\Enums\ArtifactStatus;
use App\Enums\ArtifactType;
use App\Enums\OrderState;
use App\Models\GeneratedArtifact;
use App\Services\AI\ClaudeClient;
use App\Services\AI\ClaudeGenerationException;
use App\Services\AI\MockupPromptBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

/**
 * Stage 3: generate a bespoke Tailwind HTML mockup from the client's brief,
 * then hand the order to internal QA. The artifact is created (status
 * generating) before dispatch; this job fills it and moves the pipeline.
 */
class GenerateMockupJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public int $artifactId) {}

    public function handle(ClaudeClient $claude, MockupPromptBuilder $builder): void
    {
        $artifact = GeneratedArtifact::with('order')->find($this->artifactId);

        if ($artifact === null) {
            return;
        }

        $order = $artifact->order;
        $submission = $order->submission();

        if ($submission === null) {
            $this->markFailed($artifact, 'No client brief submission found for this order.');

            return;
        }

        $artifact->update(['status' => ArtifactStatus::Generating]);

        try {
            $html = $claude->generate($builder->system(), $builder->user($order, $submission));
        } catch (ClaudeGenerationException $e) {
            $this->markFailed($artifact, $e->getMessage());

            return;
        }

        // Guard against non-HTML prose (e.g. a soft, non-refusal apology).
        if (! Str::contains(Str::lower($html), ['<!doctype', '<html'])) {
            $this->markFailed($artifact, 'Generated output was not a valid HTML document.');

            return;
        }

        $artifact->update([
            'content' => $html,
            'status' => ArtifactStatus::Ready,
            'prompt_context' => $submission->brand_assets ?? [],
        ]);

        // Advance only if this is still the order's current mockup AND the
        // order is still awaiting it — a newer regeneration or a concurrent QA
        // action must not be overwritten by a stale job finishing late.
        $fresh = $order->fresh();
        $isCurrent = $fresh->latestArtifact(ArtifactType::MockupHtml)?->is($artifact) ?? false;

        if ($isCurrent && $fresh->state === OrderState::GeneratingMockup) {
            $fresh->transitionTo(OrderState::MockupQa, null, 'AI mockup generated for internal QA.');
        }
    }

    /** Any escaped throwable (DB error, OOM) still marks the artifact failed. */
    public function failed(Throwable $e): void
    {
        GeneratedArtifact::whereKey($this->artifactId)
            ->where('status', ArtifactStatus::Generating)
            ->update([
                'status' => ArtifactStatus::Rejected,
                'prompt_context' => ['error' => 'Mockup generation failed: '.$e->getMessage()],
            ]);
    }

    protected function markFailed(GeneratedArtifact $artifact, string $reason): void
    {
        $artifact->update([
            'status' => ArtifactStatus::Rejected,
            'prompt_context' => ['error' => $reason],
        ]);
    }
}
