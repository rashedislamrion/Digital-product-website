<?php

namespace App\Filament\Widgets;

use App\Enums\ProductVisibility;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TopProductsWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Top-Selling Digital Products')
            ->query(
                Product::query()
                    ->with(['category', 'prices'])
                    ->withCount('orderItems')
                    ->orderByDesc('order_items_count')
                    ->limit(5)
            )
            ->paginated(false)
            ->columns([
                ImageColumn::make('thumbnail_path')
                    ->label('Thumbnail')
                    ->disk('public')
                    ->defaultImageUrl(fn ($record) => $record->thumbnail_url ?? null)
                    ->square()
                    ->size(36),

                TextColumn::make('title')
                    ->label('Product Title')
                    ->weight('bold')
                    ->limit(35),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('info'),

                TextColumn::make('order_items_count')
                    ->label('Units Sold')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('visibility')
                    ->badge()
                    ->color(fn (ProductVisibility $state): string => match ($state) {
                        ProductVisibility::Published => 'success',
                        ProductVisibility::Draft => 'gray',
                        ProductVisibility::Unlisted => 'warning',
                        ProductVisibility::Archived => 'danger',
                    }),
            ])
            ->recordActions([
                Action::make('edit')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (Product $record): string => ProductResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
