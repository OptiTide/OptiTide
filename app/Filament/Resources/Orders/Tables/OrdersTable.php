<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\ArtifactType;
use App\Enums\OrderState;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidStateTransition;
use App\Models\Order;
use App\Services\AI\PipelineService;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            // Auto-refresh so async generation results surface without a manual
            // reload (a generating order gains its Review action when done).
            ->poll('15s')
            ->columns([
                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Client')
                    ->searchable(),
                TextColumn::make('state')
                    ->label('Pipeline stage')
                    ->badge(),
                TextColumn::make('payment_status')
                    ->badge(),
                TextColumn::make('total')
                    ->formatStateUsing(fn (?Money $state) => $state?->format())
                    ->sortable(),
                TextColumn::make('placed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->label('Pipeline stage')
                    ->options(OrderState::class),
                SelectFilter::make('payment_status')
                    ->options(PaymentStatus::class),
            ])
            ->recordActions([
                self::briefAction(),
                self::generateMockupAction(),
                self::retryGenerationAction(),
                self::reviewMockupAction(),
                self::finalReviewAction(),
                self::transitionAction(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** Read-only view of the client's submitted project brief. */
    private static function briefAction(): Action
    {
        return Action::make('brief')
            ->label('View brief')
            ->icon('heroicon-o-clipboard-document-list')
            ->color('gray')
            ->visible(fn (Order $record): bool => $record->submissions()->exists())
            ->modalHeading(fn (Order $record): string => "Project brief — {$record->order_number}")
            ->modalContent(fn (Order $record) => view('admin.brief-summary', [
                'order' => $record,
                'submission' => $record->submission(),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }

    /** Stage 3: kick off AI mockup generation once the brief is reviewed. */
    private static function generateMockupAction(): Action
    {
        return Action::make('generateMockup')
            ->label('Generate mockup')
            ->icon('heroicon-o-sparkles')
            ->color('info')
            ->visible(fn (Order $record): bool => $record->state === OrderState::AdminReview)
            ->requiresConfirmation()
            ->modalDescription('This sends the client brief to Claude to generate a bespoke mockup. The order moves to Internal QA when it completes.')
            ->action(function (Order $record): void {
                app(PipelineService::class)->generateMockup($record, Auth::user());

                Notification::make()
                    ->title('Mockup generation started')
                    ->body("{$record->order_number} is generating. It will appear in Internal QA shortly.")
                    ->success()
                    ->send();
            });
    }

    /**
     * Recovery: when a generation job fails (rejected artifact), the order is
     * left at generating_mockup / generating_logic. This re-runs it.
     */
    private static function retryGenerationAction(): Action
    {
        return Action::make('retryGeneration')
            ->label('Retry generation')
            ->icon('heroicon-o-arrow-path')
            ->color('danger')
            ->visible(fn (Order $record): bool => in_array($record->state, [OrderState::GeneratingMockup, OrderState::GeneratingLogic], true))
            ->requiresConfirmation()
            ->modalDescription('The last generation failed or is still running. This starts a fresh attempt.')
            ->action(function (Order $record): void {
                $pipeline = app(PipelineService::class);

                $record->state === OrderState::GeneratingMockup
                    ? $pipeline->generateMockup($record, Auth::user())
                    : $pipeline->generateLogic($record, Auth::user());

                Notification::make()->title('Generation restarted')->success()->send();
            });
    }

    /**
     * Stage 4: internal QA of the generated mockup in a sandboxed iframe.
     * Approve it for the client, or send it back for another attempt.
     */
    private static function reviewMockupAction(): Action
    {
        return Action::make('reviewMockup')
            ->label('Review mockup')
            ->icon('heroicon-o-eye')
            ->color('warning')
            ->visible(fn (Order $record): bool => $record->state === OrderState::MockupQa)
            ->modalHeading(fn (Order $record): string => "Internal QA — {$record->order_number}")
            ->modalContent(fn (Order $record) => view('admin.artifact-preview', ['order' => $record]))
            ->modalSubmitActionLabel('Approve for client')
            ->extraModalFooterActions(fn (Action $action): array => [
                $action->makeModalSubmitAction('regenerate', ['regenerate' => true])
                    ->label('Regenerate')
                    ->color('gray'),
            ])
            ->action(function (Order $record, array $arguments): void {
                $pipeline = app(PipelineService::class);

                if ($arguments['regenerate'] ?? false) {
                    $pipeline->regenerateMockup($record, Auth::user());
                    Notification::make()->title('Regenerating mockup')->info()->send();

                    return;
                }

                $pipeline->approveMockupForClient($record, Auth::user());
                Notification::make()->title('Mockup approved — client can now proof it')->success()->send();
            });
    }

    /**
     * Stage 7: final QA of the generated logic. Approve to deliver (and push
     * to GitHub), or regenerate.
     */
    private static function finalReviewAction(): Action
    {
        return Action::make('finalReview')
            ->label('Final QA')
            ->icon('heroicon-o-shield-check')
            ->color('success')
            ->visible(fn (Order $record): bool => $record->state === OrderState::FinalQa)
            ->modalHeading(fn (Order $record): string => "Final QA — {$record->order_number}")
            ->modalContent(function (Order $record) {
                $logic = $record->latestArtifact(ArtifactType::LogicCode);

                return view('admin.artifact-preview', ['order' => $record])
                    ->with('logicCode', $logic?->content);
            })
            ->modalSubmitActionLabel('Approve & deliver')
            ->extraModalFooterActions(fn (Action $action): array => [
                $action->makeModalSubmitAction('regenerateLogic', ['regenerate' => true])
                    ->label('Regenerate logic')
                    ->color('gray'),
            ])
            ->action(function (Order $record, array $arguments): void {
                $pipeline = app(PipelineService::class);

                try {
                    if ($arguments['regenerate'] ?? false) {
                        $pipeline->generateLogic($record, Auth::user());
                        Notification::make()->title('Regenerating logic')->info()->send();

                        return;
                    }

                    $pipeline->approveAndDeliver($record, Auth::user());
                    Notification::make()->title("{$record->order_number} delivered")->success()->send();
                } catch (InvalidStateTransition $e) {
                    Notification::make()->title('Action rejected')->body($e->getMessage())->danger()->send();
                }
            });
    }

    /**
     * Moves an order through the CRM pipeline. Only transitions allowed by
     * the OrderState machine are offered; everything is audit-logged.
     */
    private static function transitionAction(): Action
    {
        return Action::make('transition')
            ->label('Move stage')
            ->icon('heroicon-o-arrow-right-circle')
            ->color('primary')
            ->visible(fn (Order $record): bool => $record->state->allowedTransitions() !== [])
            ->schema([
                Select::make('to_state')
                    ->label('Move to')
                    ->options(fn (Order $record): array => collect($record->state->allowedTransitions())
                        ->mapWithKeys(fn (OrderState $state) => [$state->value => $state->getLabel()])
                        ->all())
                    ->required(),
                Textarea::make('notes')
                    ->label('Transition notes'),
            ])
            ->action(function (Order $record, array $data): void {
                try {
                    $record->transitionTo(
                        OrderState::from($data['to_state']),
                        Auth::user(),
                        $data['notes'] ?: null,
                    );
                } catch (InvalidStateTransition $e) {
                    // Typically a concurrent transition by another staff member.
                    Notification::make()
                        ->title('Transition rejected')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title("{$record->order_number} moved to {$record->state->getLabel()}")
                    ->success()
                    ->send();
            });
    }
}
