<?php

namespace App\Filament\Resources\DownloadGrants\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DownloadEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = 'Audit Download Events & IP Logs';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ip_address')
            ->columns([
                TextColumn::make('ip_address')
                    ->label('Client IP')
                    ->searchable()
                    ->copyable()
                    ->weight('medium'),

                TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->user_agent),

                TextColumn::make('bytes_transferred')
                    ->label('Payload Size')
                    ->formatStateUsing(function ($state) {
                        if (! $state) return '0 B';
                        if ($state >= 1048576) {
                            return round($state / 1048576, 2) . ' MB';
                        }
                        return round($state / 1024, 2) . ' KB';
                    }),

                TextColumn::make('downloaded_at')
                    ->label('Timestamp')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('downloaded_at', 'desc');
    }
}
