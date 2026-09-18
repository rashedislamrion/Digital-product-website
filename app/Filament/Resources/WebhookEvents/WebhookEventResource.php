<?php

namespace App\Filament\Resources\WebhookEvents;

use App\Enums\WebhookEventStatus;
use App\Filament\Resources\WebhookEvents\Pages\ListWebhookEvents;
use App\Filament\Resources\WebhookEvents\Pages\ViewWebhookEvent;
use App\Jobs\FulfillOrderJob;
use App\Models\Order;
use App\Models\WebhookEvent;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class WebhookEventResource extends Resource
{
    protected static ?string $model = WebhookEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static string|UnitEnum|null $navigationGroup = 'Commerce & Finance';

    protected static ?string $navigationLabel = 'Transactions & Gateway Logs';

    protected static ?string $modelLabel = 'Gateway Event';

    protected static ?string $pluralModelLabel = 'Transactions & Gateway Logs';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Event Metadata')
                    ->schema([
                        TextInput::make('gateway')
                            ->disabled(),
                        TextInput::make('event_id')
                            ->label('Gateway Event ID')
                            ->disabled(),
                        TextInput::make('event_type')
                            ->disabled(),
                        TextInput::make('status')
                            ->disabled(),
                        TextInput::make('processed_at')
                            ->disabled(),
                        TextInput::make('created_at')
                            ->disabled(),
                    ])->columns(2),

                Section::make('Error Details')
                    ->schema([
                        Textarea::make('error_message')
                            ->disabled()
                            ->rows(3),
                    ])
                    ->visible(fn (WebhookEvent $record) => ! empty($record->error_message)),

                Section::make('Raw Ingested Payload')
                    ->collapsed()
                    ->schema([
                        Placeholder::make('raw_payload_json')
                            ->label('JSON Payload')
                            ->content(fn (WebhookEvent $record) => json_encode($record->raw_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('gateway')
                    ->label('Gateway')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => strtoupper($state ?? 'N/A')),

                TextColumn::make('event_id')
                    ->label('Event ID')
                    ->searchable()
                    ->copyable()
                    ->weight('medium'),

                TextColumn::make('event_type')
                    ->label('Event Type')
                    ->searchable()
                    ->badge(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state?->value ?? $state) {
                        'processed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Received At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('gateway')
                    ->options([
                        'sslcommerz' => 'SSLCOMMERZ',
                        'bkash' => 'bKash',
                        'paddle' => 'Paddle',
                        'stripe' => 'Stripe',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'processed' => 'Processed',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('replay')
                    ->label('Replay')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Replay Webhook Event')
                    ->modalDescription('Re-dispatch the fulfillment and reconciliation pipeline for this transaction payload.')
                    ->visible(fn (): bool => auth()->user()?->can('webhooks.view_replay') ?? false)
                    ->action(function (WebhookEvent $record) {
                        $payload = $record->raw_payload ?? [];
                        $tranId = $payload['tran_id'] ?? null;

                        if ($tranId) {
                            $order = Order::where('order_number', $tranId)->first();
                            if ($order) {
                                FulfillOrderJob::dispatch($order->id);
                            }
                        }

                        $record->update([
                            'status' => WebhookEventStatus::Processed,
                            'processed_at' => now(),
                            'error_message' => null,
                        ]);

                        Notification::make()
                            ->title('Webhook Replayed')
                            ->body("Event {$record->event_id} has been re-dispatched and marked as processed.")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebhookEvents::route('/'),
            'view' => ViewWebhookEvent::route('/{record}'),
        ];
    }
}
