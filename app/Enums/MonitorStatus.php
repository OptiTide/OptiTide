<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MonitorStatus: string implements HasColor, HasLabel
{
    case Up = 'up';
    case Down = 'down';
    case Unknown = 'unknown';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Up => 'success',
            self::Down => 'danger',
            self::Unknown => 'gray',
        };
    }
}
