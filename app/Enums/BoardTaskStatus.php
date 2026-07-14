<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BoardTaskStatus: string implements HasColor, HasLabel
{
    case Backlog = 'backlog';
    case InProgress = 'in_progress';
    case ClientReview = 'client_review';
    case Done = 'done';

    public function getLabel(): string
    {
        return match ($this) {
            self::Backlog => 'Backlog',
            self::InProgress => 'In Progress',
            self::ClientReview => 'Client Review',
            self::Done => 'Done',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Backlog => 'gray',
            self::InProgress => 'info',
            self::ClientReview => 'warning',
            self::Done => 'success',
        };
    }
}
