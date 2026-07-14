<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case VirtualAssistant = 'va';
    case Client = 'client';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::VirtualAssistant => 'Virtual Assistant',
            self::Client => 'Client',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Admin => 'danger',
            self::VirtualAssistant => 'warning',
            self::Client => 'info',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Client;
    }
}
