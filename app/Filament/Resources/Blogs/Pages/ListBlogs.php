<?php

namespace App\Filament\Resources\Blogs\Pages;

use App\Enums\BlogStatus;
use App\Filament\Resources\Blogs\BlogResource;
use App\Jobs\GenerateBlogJob;
use App\Models\Blog;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListBlogs extends ListRecords
{
    protected static string $resource = BlogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateWithAi')
                ->label('Generate with AI')
                ->icon('heroicon-o-sparkles')
                ->modalHeading('Generate a blog article')
                ->modalSubmitActionLabel('Queue generation')
                ->schema([
                    TextInput::make('topic')
                        ->required()
                        ->maxLength(160)
                        ->helperText('What should the article be about?'),
                    Textarea::make('keywords')
                        ->label('Target keywords')
                        ->helperText('Comma-separated, optional.')
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    $keywords = collect(explode(',', $data['keywords'] ?? ''))
                        ->map(fn ($k) => trim($k))
                        ->filter()
                        ->values()
                        ->all();

                    $blog = Blog::create([
                        'title' => $data['topic'],
                        'status' => BlogStatus::Draft,
                        'author_id' => Auth::id(),
                        'is_ai_generated' => true,
                        'meta' => ['topic' => $data['topic'], 'focus_keywords' => $keywords],
                    ]);

                    GenerateBlogJob::dispatch($blog->id);

                    Notification::make()
                        ->title('Blog generation queued')
                        ->body('It will appear as "Pending review" once generated.')
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
