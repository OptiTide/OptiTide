<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ArtifactStatus: string implements HasColor, HasLabel
{
    case Generating = 'generating';
    case Ready = 'ready';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Generating => 'info',
            self::Ready => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
