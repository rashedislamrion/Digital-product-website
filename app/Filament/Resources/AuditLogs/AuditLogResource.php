<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Models\AuditLog;
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

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = 'System & Security';

    protected static ?string $navigationLabel = 'Audit Logs';

    protected static ?string $modelLabel = 'Audit Log';

    protected static ?string $pluralModelLabel = 'Audit Logs';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Audit Event Metadata')
                    ->schema([
                        TextInput::make('action')
                            ->disabled(),

                        TextInput::make('user.email')
                            ->label('Causer User')
                            ->disabled()
                            ->placeholder('System / Unauthenticated'),

                        TextInput::make('target_type')
                            ->disabled(),

                        TextInput::make('target_id')
                            ->disabled(),

                        TextInput::make('ip_address')
                            ->disabled(),

                        TextInput::make('created_at')
                            ->label('Timestamp')
                            ->disabled(),
                    ])->columns(2),

                Section::make('Audit Changes & Telemetry')
                    ->schema([
                        Placeholder::make('metadata_before_json')
                            ->label('State Before')
                            ->content(fn (AuditLog $record) => json_encode($record->metadata_before, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'None'),

                        Placeholder::make('metadata_after_json')
                            ->label('State After')
                            ->content(fn (AuditLog $record) => json_encode($record->metadata_after, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'None'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('action')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('user.email')
                    ->label('Actor')
                    ->searchable()
                    ->placeholder('System / Automated'),

                TextColumn::make('target_type')
                    ->label('Target Model')
                    ->formatStateUsing(fn ($state) => class_basename($state ?? ''))
                    ->searchable(),

                TextColumn::make('target_id')
                    ->label('Target ID')
                    ->limit(16)
                    ->copyable(),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
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
            'index' => ListAuditLogs::route('/'),
            'view' => ViewAuditLog::route('/{record}'),
        ];
    }
}
