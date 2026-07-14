<?php

namespace App\Jobs;

use App\Enums\ArtifactStatus;
use App\Enums\ArtifactType;
use App\Enums\OrderState;
use App\Models\GeneratedArtifact;
use App\Services\AI\ClaudeClient;
use App\Services\AI\ClaudeGenerationException;
use App\Services\AI\LogicPromptBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

/**
 * Stage 6: once the client approves the mockup, generate the application
 * logic from the approved HTML and hand the order to final QA.
 */
class GenerateLogicJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public int $artifactId) {}

    public function handle(ClaudeClient $claude, LogicPromptBuilder $builder): void
    {
        $artifact = GeneratedArtifact::with('order')->find($this->artifactId);

        if ($artifact === null) {
            return;
        }

        $order = $artifact->order;

        $mockup = $order->artifacts()
            ->where('type', ArtifactType::MockupHtml)
            ->where('status', ArtifactStatus::Approved)
            ->latest('id')
            ->first()
            ?? $order->artifacts()->where('type', ArtifactType::MockupHtml)->latest('id')->first();

        if ($mockup === null || blank($mockup->content)) {
            $this->markFailed($artifact, 'No approved mockup found to build logic from.');

            return;
        }

        $artifact->update(['status' => ArtifactStatus::Generating]);

        try {
            $code = $claude->generate($builder->system(), $builder->user($order, $mockup));
        } catch (ClaudeGenerationException $e) {
            $this->markFailed($artifact, $e->getMessage());

            return;
        }

        // Guard against non-JS prose being stored as application logic.
        if (! Str::contains($code, ['function', '=>', 'const ', 'let ', 'addEventListener', 'document.'])) {
            $this->markFailed($artifact, 'Generated output did not look like a JavaScript module.');

            return;
        }

        $artifact->update([
            'content' => $code,
            'status' => ArtifactStatus::Ready,
        ]);

        $fresh = $order->fresh();
        $isCurrent = $fresh->latestArtifact(ArtifactType::LogicCode)?->is($artifact) ?? false;

        if ($isCurrent && $fresh->state === OrderState::GeneratingLogic) {
            $fresh->transitionTo(OrderState::FinalQa, null, 'AI logic generated for final QA.');
        }
    }

    public function failed(Throwable $e): void
    {
        GeneratedArtifact::whereKey($this->artifactId)
            ->where('status', ArtifactStatus::Generating)
            ->update([
                'status' => ArtifactStatus::Rejected,
                'prompt_context' => ['error' => 'Logic generation failed: '.$e->getMessage()],
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
