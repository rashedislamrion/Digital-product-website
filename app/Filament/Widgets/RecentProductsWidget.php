<?php

namespace App\Filament\Widgets;

use App\Enums\ProductVisibility;
use App\Models\Product;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentProductsWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Products in Catalog')
            ->query(Product::query()->with(['category', 'prices'])->latest()->limit(5))
            ->paginated(false)
            ->columns([
                ImageColumn::make('thumbnail_path')
                    ->label('Thumbnail')
                    ->disk('public')
                    ->defaultImageUrl(fn ($record) => $record->thumbnail_url ?? null)
                    ->square()
                    ->size(40),

                TextColumn::make('title')
                    ->label('Product Title')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('info'),

                TextColumn::make('visibility')
                    ->badge()
                    ->color(fn (ProductVisibility $state): string => match ($state) {
                        ProductVisibility::Published => 'success',
                        ProductVisibility::Draft => 'gray',
                        ProductVisibility::Unlisted => 'warning',
                        ProductVisibility::Archived => 'danger',
                    }),

                TextColumn::make('price_range')
                    ->label('Price Range'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}
