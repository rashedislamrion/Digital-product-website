<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PricesRelationManager extends RelationManager
{
    protected static string $relationship = 'prices';

    protected static ?string $title = 'Pricing & License Tiers';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('license_tier_name')
                    ->label('License Tier Name')
                    ->placeholder('e.g., Single Application, Team License')
                    ->required()
                    ->maxLength(255),

                TextInput::make('max_activation_seats')
                    ->label('Max Activation Seats')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->helperText('Use 999999 for unlimited domain/seat activations.')
                    ->required(),

                TextInput::make('amount_minor')
                    ->label('Price (Decimal)')
                    ->numeric()
                    ->prefix('$')
                    ->placeholder('49.00')
                    ->required()
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state / 100, 2, '.', '') : null)
                    ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),

                Select::make('currency')
                    ->options([
                        'USD' => 'USD ($)',
                        'EUR' => 'EUR (€)',
                        'GBP' => 'GBP (£)',
                        'BDT' => 'BDT (৳)',
                    ])
                    ->default('USD')
                    ->required(),

                Toggle::make('is_active')
                    ->label('Active for Purchase')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('license_tier_name')
            ->columns([
                TextColumn::make('license_tier_name')
                    ->label('Tier')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('max_activation_seats')
                    ->label('Seats')
                    ->formatStateUsing(fn ($state) => $state >= 999999 ? 'Unlimited' : "{$state} seats")
                    ->sortable(),

                TextColumn::make('amount_formatted')
                    ->label('Price')
                    ->weight('semibold')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('amount_minor', $direction)),

                TextColumn::make('currency')
                    ->badge()
                    ->color('gray'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
