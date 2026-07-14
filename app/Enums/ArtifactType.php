<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ArtifactType: string implements HasLabel
{
    case MockupHtml = 'mockup_html';
    case LogicCode = 'logic_code';

    public function getLabel(): string
    {
        return match ($this) {
            self::MockupHtml => 'HTML Mockup',
            self::LogicCode => 'Application Code',
        };
    }
}
