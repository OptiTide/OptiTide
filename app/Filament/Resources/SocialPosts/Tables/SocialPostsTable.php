<?php

namespace App\Filament\Resources\SocialPosts\Tables;

use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Models\SocialPost;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SocialPostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.name')
                    ->label('Client')
                    ->placeholder('Agency')
                    ->searchable(),
                TextColumn::make('blog.title')
                    ->label('Article')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('platform')
                    ->badge()
                    ->searchable(),
                ImageColumn::make('image_path'),
                TextColumn::make('content')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('scheduled_for')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('external_id')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(SocialPostStatus::class),
                SelectFilter::make('platform')
                    ->options(SocialPlatform::class),
            ])
            ->recordActions([
                // VA approval: move a draft into the distribution queue. Approving
                // with no date sends on the next cron run.
                Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (SocialPost $record) => $record->status === SocialPostStatus::PendingReview)
                    ->schema([
                        DateTimePicker::make('scheduled_for')
                            ->label('Schedule for')
                            ->helperText('Leave blank to send at the next distribution run.'),
                    ])
                    ->action(function (SocialPost $record, array $data): void {
                        $record->forceFill([
                            'status' => SocialPostStatus::Approved,
                            'scheduled_for' => $data['scheduled_for'] ?? now(),
                            'error' => null,
                        ])->save();

                        Notification::make()->title('Post approved for distribution')->success()->send();
                    }),
                Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (SocialPost $record) => $record->status === SocialPostStatus::PendingReview)
                    ->action(function (SocialPost $record): void {
                        // Rejected (a review decision) is distinct from Failed (a
                        // distribution error).
                        $record->forceFill([
                            'status' => SocialPostStatus::Rejected,
                            'error' => 'Rejected in review.',
                        ])->save();

                        Notification::make()->title('Post rejected')->success()->send();
                    }),
                // Re-queue a post whose distribution failed (transient platform error).
                Action::make('retry')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (SocialPost $record) => $record->status === SocialPostStatus::Failed)
                    ->action(function (SocialPost $record): void {
                        $record->forceFill([
                            'status' => SocialPostStatus::Approved,
                            'scheduled_for' => now(),
                            'published_at' => null,
                            'error' => null,
                        ])->save();

                        Notification::make()->title('Post re-queued for distribution')->success()->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
