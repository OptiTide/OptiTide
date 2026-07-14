<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ContractStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Signed = 'signed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting Signature',
            self::Signed => 'Signed',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Signed => 'success',
        };
    }
}
