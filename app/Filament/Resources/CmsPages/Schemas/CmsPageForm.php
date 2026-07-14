<?php

namespace App\Filament\Resources\CmsPages\Schemas;

use App\Enums\CmsPageStatus;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CmsPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // title/body bind to the current app locale's translation
                // (spatie HasTranslations). Editing here updates the "en" copy.
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set, string $operation) => $operation === 'create'
                        ? $set('slug', Str::slug((string) $state))
                        : null),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    // Must match the public route charset ([a-z0-9] then
                    // [a-z0-9-]*) and not collide with a real top-level route,
                    // or the page would be silently unreachable.
                    ->rule('regex:/^[a-z0-9][a-z0-9\-]*$/')
                    ->rule(Rule::notIn([
                        'admin', 'client', 'blog', 'services', 'cart', 'contact', 'seo-audit',
                        'checkout', 'subscribe', 'contracts', 'orders', 'invoices', 'stripe',
                        'sitemap', 'robots', 'up', 'creagia',
                    ]))
                    ->helperText('Lowercase letters, numbers and hyphens. The page lives at /{slug}.'),
                Textarea::make('excerpt')
                    ->maxLength(500)
                    ->columnSpanFull(),
                // Toolbar constrained to what HtmlSanitizer keeps — otherwise
                // tables/images/code blocks would author fine but vanish on render.
                RichEditor::make('body')
                    ->toolbarButtons([
                        'bold', 'italic', 'link', 'h2', 'h3',
                        'bulletList', 'orderedList', 'blockquote', 'undo', 'redo',
                    ])
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(CmsPageStatus::class)
                    ->default('draft')
                    ->required(),
                Toggle::make('show_in_footer')
                    ->helperText('Link this page from the storefront footer (e.g. legal pages).'),
                KeyValue::make('meta')
                    ->label('SEO metadata')
                    ->keyLabel('Field')
                    ->valueLabel('Value')
                    ->columnSpanFull(),
            ]);
    }
}
