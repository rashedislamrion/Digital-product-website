<?php

namespace App\Filament\Resources\Invoices;

use App\Enums\OrderStatus;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class InvoiceResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Commerce & Finance';

    protected static ?string $navigationLabel = 'Invoices & Credit Notes';

    protected static ?string $modelLabel = 'Invoice';

    protected static ?string $pluralModelLabel = 'Invoices & Credit Notes';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('status', [OrderStatus::Paid, OrderStatus::Refunded]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Invoice / Order #')
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
                        'refunded' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('total_formatted')
                    ->label('Amount')
                    ->visible(fn () => auth()->user()?->can('orders.view_financials') ?? false),

                TextColumn::make('payment_gateway')
                    ->label('Gateway')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => strtoupper($state ?? 'N/A')),

                TextColumn::make('created_at')
                    ->label('Issued Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('downloadPdf')
                    ->label('Re-generate PDF')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('primary')
                    ->action(function (Order $record) {
                        $record->load(['items.license', 'customer']);
                        $pdf = Pdf::loadView('invoices.pdf', ['order' => $record]);

                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            "invoice-{$record->order_number}.pdf",
                            ['Content-Type' => 'application/pdf']
                        );
                    }),

                Action::make('viewOrder')
                    ->label('View Order')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->url(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record])),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
        ];
    }
}
