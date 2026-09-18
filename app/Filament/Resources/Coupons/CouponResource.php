<?php

namespace App\Filament\Resources\Coupons;

use App\Filament\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Resources\Coupons\Pages\EditCoupon;
use App\Filament\Resources\Coupons\Pages\ListCoupons;
use App\Models\Coupon;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'Commerce & Finance';

    protected static ?string $navigationLabel = 'Discounts & Coupons';

    protected static ?string $modelLabel = 'Coupon';

    protected static ?string $pluralModelLabel = 'Discounts & Coupons';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Coupon Rules & Configuration')
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase']),

                        Select::make('discount_type')
                            ->options([
                                'percent' => 'Percentage (%)',
                                'fixed' => 'Fixed Amount (Minor Units / Cents)',
                            ])
                            ->default('percent')
                            ->required(),

                        TextInput::make('discount_value')
                            ->label('Discount Value')
                            ->helperText('For percent: e.g. 20 for 20%. For fixed: e.g. 500 for $5.00')
                            ->numeric()
                            ->required(),

                        DateTimePicker::make('expires_at')
                            ->label('Expiry Date & Time')
                            ->nullable(),

                        TextInput::make('max_uses')
                            ->label('Usage Limit')
                            ->helperText('Leave empty for unlimited redemptions')
                            ->numeric()
                            ->nullable(),

                        TextInput::make('times_used')
                            ->label('Redemption Count')
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('discount_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'percent' => 'success',
                        'fixed' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('discount_value')
                    ->label('Value')
                    ->formatStateUsing(function (Coupon $record): string {
                        return $record->discount_type === 'percent'
                            ? "{$record->discount_value}%"
                            : '$' . number_format($record->discount_value / 100, 2);
                    }),

                TextColumn::make('times_used')
                    ->label('Usage')
                    ->formatStateUsing(fn (Coupon $record): string => "{$record->times_used} / " . ($record->max_uses ?? '∞')),

                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCoupons::route('/'),
            'create' => CreateCoupon::route('/create'),
            'edit' => EditCoupon::route('/{record}/edit'),
        ];
    }
}
