<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Models\Customer;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Customers & Support';

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $modelLabel = 'Customer';

    protected static ?string $pluralModelLabel = 'Customers';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer Profile')
                    ->schema([
                        TextInput::make('name')
                            ->disabled(),
                        TextInput::make('email')
                            ->disabled(),
                        TextInput::make('country_code')
                            ->label('Country Code')
                            ->disabled(),
                        TextInput::make('tax_identifier')
                            ->label('Tax / VAT Identifier')
                            ->disabled()
                            ->placeholder('N/A'),
                        TextInput::make('user.id')
                            ->label('Linked User Account ID')
                            ->disabled()
                            ->placeholder('Guest Checkout (Unlinked)'),
                    ])->columns(2),

                Section::make('Purchased Orders Summary')
                    ->schema([
                        Placeholder::make('orders_list')
                            ->label('Historical Orders')
                            ->content(function (Customer $record) {
                                $orders = $record->orders()->latest()->get();
                                if ($orders->isEmpty()) {
                                    return 'No orders found for this customer.';
                                }

                                $canViewFinancials = auth()->user()?->can('orders.view_financials') ?? false;
                                $lines = [];
                                foreach ($orders as $order) {
                                    $amountStr = $canViewFinancials ? " - {$order->total_formatted}" : '';
                                    $lines[] = "• Order #{$order->order_number} [{$order->status->value}]{$amountStr} ({$order->created_at->format('M d, Y')})";
                                }

                                return implode("\n", $lines);
                            }),
                    ]),

                Section::make('Software Licenses Summary')
                    ->schema([
                        Placeholder::make('licenses_list')
                            ->label('Active Licenses')
                            ->content(function (Customer $record) {
                                $licenses = $record->licenses()->with('product')->latest()->get();
                                if ($licenses->isEmpty()) {
                                    return 'No software licenses issued.';
                                }

                                $lines = [];
                                foreach ($licenses as $lic) {
                                    $productTitle = $lic->product->title ?? 'Product';
                                    $lines[] = "• {$lic->license_key_masked} ({$productTitle}) - Status: {$lic->status->value} [Seats: {$lic->current_activations_count}/{$lic->max_activations}]";
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
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->placeholder('Guest Customer'),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('country_code')
                    ->label('Country')
                    ->badge()
                    ->color('info')
                    ->placeholder('N/A'),

                TextColumn::make('tax_identifier')
                    ->label('Tax ID')
                    ->searchable()
                    ->placeholder('None'),

                TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders')
                    ->badge(),

                TextColumn::make('licenses_count')
                    ->label('Licenses')
                    ->counts('licenses')
                    ->badge()
                    ->color('success'),

                TextColumn::make('created_at')
                    ->label('First Seen')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'view' => ViewCustomer::route('/{record}'),
        ];
    }
}
