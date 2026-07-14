<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderState: string implements HasColor, HasLabel
{
    case PendingIntake = 'pending_intake';
    case AdminReview = 'admin_review';
    case GeneratingMockup = 'generating_mockup';
    case MockupQa = 'mockup_qa';
    case ClientReview = 'client_review';
    case GeneratingLogic = 'generating_logic';
    case FinalQa = 'final_qa';
    case Delivered = 'delivered';

    /**
     * Allowed transitions per pipeline stage. QA and client-review stages may
     * loop back to the preceding generation stage for another attempt.
     *
     * @return array<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PendingIntake => [self::AdminReview],
            self::AdminReview => [self::GeneratingMockup, self::PendingIntake],
            self::GeneratingMockup => [self::MockupQa],
            self::MockupQa => [self::ClientReview, self::GeneratingMockup],
            self::ClientReview => [self::GeneratingLogic, self::GeneratingMockup],
            self::GeneratingLogic => [self::FinalQa],
            self::FinalQa => [self::Delivered, self::GeneratingLogic],
            self::Delivered => [],
        };
    }

    public function canTransitionTo(self $state): bool
    {
        return in_array($state, $this->allowedTransitions(), true);
    }

    /**
     * The AI generation and internal QA stages are never exposed to clients;
     * the client portal shows the masked label instead.
     */
    public function isVisibleToClient(): bool
    {
        return in_array($this, [self::PendingIntake, self::ClientReview, self::Delivered], true);
    }

    public function clientFacingLabel(): string
    {
        return match ($this) {
            self::PendingIntake => 'Awaiting Your Project Details',
            self::AdminReview, self::GeneratingMockup, self::MockupQa => 'Design In Progress',
            self::ClientReview => 'Ready For Your Review',
            self::GeneratingLogic, self::FinalQa => 'Development In Progress',
            self::Delivered => 'Delivered',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PendingIntake => 'Intake Pending',
            self::AdminReview => 'Requirements Review',
            self::GeneratingMockup => 'AI Mockup Generation',
            self::MockupQa => 'Internal QA',
            self::ClientReview => 'Client Review',
            self::GeneratingLogic => 'AI Logic Generation',
            self::FinalQa => 'Final Admin QA',
            self::Delivered => 'Delivered',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PendingIntake => 'gray',
            self::AdminReview, self::MockupQa, self::FinalQa => 'warning',
            self::GeneratingMockup, self::GeneratingLogic => 'info',
            self::ClientReview => 'primary',
            self::Delivered => 'success',
        };
    }
}
