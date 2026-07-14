<?php

namespace App\Services\AI;

use App\Enums\ArtifactStatus;
use App\Enums\ArtifactType;
use App\Enums\OrderState;
use App\Jobs\GenerateLogicJob;
use App\Jobs\GenerateMockupJob;
use App\Jobs\PushToGitHubJob;
use App\Models\GeneratedArtifact;
use App\Models\Order;
use App\Models\User;

/**
 * Drives the CRM's AI pipeline transitions: creating artifact versions,
 * dispatching the generation jobs, and moving the order through the enforced
 * state machine. Kept out of the Filament actions so the flow is testable.
 */
class PipelineService
{
    /**
     * Kick off (or retry / regenerate) a mockup. Valid from admin_review,
     * mockup_qa, client_review, or a stuck generating_mockup (retry).
     */
    public function generateMockup(Order $order, ?User $actor = null): GeneratedArtifact
    {
        if ($order->state !== OrderState::GeneratingMockup) {
            $order->transitionTo(OrderState::GeneratingMockup, $actor, 'Requested AI mockup generation.');
        }

        $artifact = $this->newArtifact($order, ArtifactType::MockupHtml);

        GenerateMockupJob::dispatch($artifact->id);

        return $artifact;
    }

    /** Internal QA approves the mockup; hand it to the client for proofing. */
    public function approveMockupForClient(Order $order, ?User $actor = null): void
    {
        $this->markApproved($order->latestArtifact(ArtifactType::MockupHtml), $actor);

        $order->transitionTo(OrderState::ClientReview, $actor, 'Mockup approved for client proofing.');
    }

    /**
     * Kick off (or regenerate) the logic build. Valid from client_review
     * (client approved) or final_qa (regenerate).
     */
    public function generateLogic(Order $order, ?User $actor = null): GeneratedArtifact
    {
        if ($order->state !== OrderState::GeneratingLogic) {
            $order->transitionTo(OrderState::GeneratingLogic, $actor, 'Requested AI logic generation.');
        }

        // Lock in the mockup the client signed off on.
        $this->markApproved($order->latestArtifact(ArtifactType::MockupHtml), $actor);

        $artifact = $this->newArtifact($order, ArtifactType::LogicCode);

        GenerateLogicJob::dispatch($artifact->id);

        return $artifact;
    }

    /** Send the mockup back for another attempt. */
    public function regenerateMockup(Order $order, ?User $actor = null): GeneratedArtifact
    {
        return $this->generateMockup($order, $actor);
    }

    /** Final QA passes: mark delivered and push the code to GitHub. */
    public function approveAndDeliver(Order $order, ?User $actor = null): void
    {
        $this->markApproved($order->latestArtifact(ArtifactType::LogicCode), $actor);

        $order->transitionTo(OrderState::Delivered, $actor, 'Final QA passed; delivering.');

        PushToGitHubJob::dispatch($order->id);
    }

    protected function newArtifact(Order $order, ArtifactType $type): GeneratedArtifact
    {
        return $order->artifacts()->create([
            'type' => $type,
            'status' => ArtifactStatus::Generating,
            'version' => ($order->artifacts()->where('type', $type)->max('version') ?? 0) + 1,
        ]);
    }

    protected function markApproved(?GeneratedArtifact $artifact, ?User $actor): void
    {
        $artifact?->update([
            'status' => ArtifactStatus::Approved,
            'approved_by' => $actor?->getKey(),
            'approved_at' => now(),
        ]);
    }
}
