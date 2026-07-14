<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CommissionStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Credited = 'credited';
    case Paid = 'paid';

    public function getLabel(): string
    {
        return match ($this) {
            self::Credited => 'Credited to Balance',
            self::Paid => 'Paid Out',
            default => ucfirst($this->value),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'primary',
            self::Credited => 'info',
            self::Paid => 'success',
        };
    }
}
