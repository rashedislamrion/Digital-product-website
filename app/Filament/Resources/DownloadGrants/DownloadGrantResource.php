<?php

namespace App\Filament\Resources\DownloadGrants;

use App\Filament\Resources\DownloadGrants\Pages\ListDownloadGrants;
use App\Filament\Resources\DownloadGrants\Pages\ViewDownloadGrant;
use App\Filament\Resources\DownloadGrants\RelationManagers\DownloadEventsRelationManager;
use App\Models\DownloadGrant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DownloadGrantResource extends Resource
{
    protected static ?string $model = DownloadGrant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static string|UnitEnum|null $navigationGroup = 'Licensing & Delivery';

    protected static ?string $navigationLabel = 'Download Grants & Logs';

    protected static ?string $modelLabel = 'Download Entitlement';

    protected static ?string $pluralModelLabel = 'Download Grants & Logs';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Grant Entitlement Overview')
                    ->schema([
                        TextInput::make('customer.email')
                            ->label('Customer Email')
                            ->disabled(),

                        TextInput::make('orderItem.historical_product_title')
                            ->label('Product Title')
                            ->disabled(),

                        TextInput::make('download_count')
                            ->label('Completed Downloads')
                            ->disabled(),

                        TextInput::make('max_download_attempts')
                            ->label('Max Download Quota')
                            ->disabled(),

                        TextInput::make('expires_at')
                            ->label('Expiration Date')
                            ->disabled()
                            ->placeholder('No Expiration'),

                        Toggle::make('is_revoked')
                            ->label('Access Revoked')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.email')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('orderItem.historical_product_title')
                    ->label('Product')
                    ->searchable()
                    ->limit(25),

                TextColumn::make('quota')
                    ->label('Downloads / Quota')
                    ->state(fn (DownloadGrant $record): string => "{$record->download_count} / {$record->max_download_attempts}")
                    ->badge()
                    ->color(fn (DownloadGrant $record): string =>
                        $record->download_count >= $record->max_download_attempts ? 'danger' : 'success'
                    ),

                IconColumn::make('is_revoked')
                    ->label('Revoked')
                    ->boolean(),

                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->placeholder('Never'),

                TextColumn::make('created_at')
                    ->label('Granted At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('resetCounter')
                    ->label('Reset Download Counter')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reset / Extend Download Quota')
                    ->modalDescription('Add +5 download attempts and restore access to this download grant.')
                    ->visible(fn (): bool => auth()->user()?->can('downloads.reset_limit') ?? false)
                    ->action(function (DownloadGrant $record) {
                        $record->update([
                            'max_download_attempts' => $record->max_download_attempts + 5,
                            'is_revoked' => false,
                        ]);

                        Notification::make()
                            ->title('Download Limit Extended')
                            ->body("Granted +5 additional download attempts for {$record->customer->email}.")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            DownloadEventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDownloadGrants::route('/'),
            'view' => ViewDownloadGrant::route('/{record}'),
        ];
    }
}
