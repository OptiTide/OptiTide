<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SocialPostStatus: string implements HasColor, HasLabel
{
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Rejected = 'rejected';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::PendingReview => 'Pending Review',
            default => ucfirst($this->value),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PendingReview => 'warning',
            self::Approved => 'primary',
            self::Scheduled => 'info',
            self::Published => 'success',
            self::Rejected => 'gray',
            self::Failed => 'danger',
        };
    }
}
