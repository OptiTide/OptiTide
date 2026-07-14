<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProductCategory: string implements HasLabel
{
    case WebDevelopment = 'web_development';
    case Seo = 'seo';
    case Smm = 'smm';
    case Hosting = 'hosting';

    public function getLabel(): string
    {
        return match ($this) {
            self::WebDevelopment => 'Web Development',
            self::Seo => 'SEO',
            self::Smm => 'Social Media Management',
            self::Hosting => 'Web Hosting',
        };
    }
}
