<?php

namespace App\Filament\Resources\Orders;

use App\Domain\Commerce\Services\RevokeOrderAccess;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
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

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|UnitEnum|null $navigationGroup = 'Commerce & Finance';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return (auth()->user()?->can('orders.view_financials') || auth()->user()?->can('orders.issue_refund')) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Overview')
                    ->schema([
                        TextInput::make('order_number')
                            ->disabled(),
                        TextInput::make('customer.email')
                            ->label('Customer Email')
                            ->disabled(),
                        TextInput::make('status')
                            ->disabled(),
                        TextInput::make('payment_gateway')
                            ->label('Payment Gateway')
                            ->disabled(),
                        TextInput::make('total_formatted')
                            ->label('Total Billed')
                            ->disabled()
                            ->visible(fn () => auth()->user()?->can('orders.view_financials')),
                    ])->columns(2),

                Section::make('Fulfillment & Entitlements')
                    ->schema([
                        Placeholder::make('items_summary')
                            ->label('Purchased Items')
                            ->content(function (Order $record) {
                                $canViewFinancials = auth()->user()?->can('orders.view_financials');
                                $lines = [];
                                foreach ($record->items as $item) {
                                    $priceText = $canViewFinancials ? " - {$item->unit_amount_formatted} USD" : '';
                                    $licenseInfo = $item->license
                                        ? " [License: {$item->license->license_key_masked} ({$item->license->status->value})]"
                                        : '';
                                    $grantsCount = $item->downloadGrants->count();
                                    $lines[] = "• {$item->historical_product_title} ({$item->historical_tier_name}){$priceText}{$licenseInfo} [Grants: {$grantsCount}]";
                                }

                                return implode("\n", $lines);
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('customer.email')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state?->value ?? $state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'refunded' => 'danger',
                        'failed', 'disputed' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('total_formatted')
                    ->label('Total')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('total_minor', $direction))
                    ->visible(fn () => auth()->user()?->can('orders.view_financials')),

                TextColumn::make('payment_gateway')
                    ->label('Gateway')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => strtoupper($state ?? 'N/A')),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'paid' => 'Paid',
                        'pending' => 'Pending',
                        'refunded' => 'Refunded',
                        'failed' => 'Failed',
                        'disputed' => 'Disputed',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('refund')
                    ->label('Refund & Revoke')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Refund Order & Revoke Entitlements')
                    ->modalDescription('Are you sure you want to refund this order? All digital download grants and software licenses associated with this order will be immediately revoked.')
                    ->visible(fn (Order $record): bool => auth()->user()?->can('orders.issue_refund') && $record->status === OrderStatus::Paid)
                    ->action(function (Order $record, RevokeOrderAccess $revokeService) {
                        $revokeService->execute($record, auth()->user(), 'Admin refund initiated from back-office');

                        Notification::make()
                            ->title('Order Refunded')
                            ->body("Order #{$record->order_number} has been refunded and all digital access revoked.")
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
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
