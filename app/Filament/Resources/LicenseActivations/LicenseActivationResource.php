<?php

namespace App\Filament\Resources\LicenseActivations;

use App\Filament\Resources\LicenseActivations\Pages\ListLicenseActivations;
use App\Models\LicenseActivation;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class LicenseActivationResource extends Resource
{
    protected static ?string $model = LicenseActivation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static string|UnitEnum|null $navigationGroup = 'Licensing & Delivery';

    protected static ?string $navigationLabel = 'Device & Domain Activations';

    protected static ?string $modelLabel = 'Device Activation';

    protected static ?string $pluralModelLabel = 'Device & Domain Activations';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('hostname')
                    ->label('Domain / Hostname')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->copyable(),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable(),

                TextColumn::make('license.license_key_masked')
                    ->label('License Key')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('license.customer.email')
                    ->label('Customer')
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('activated_at')
                    ->label('Activated At')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('deactivated_at')
                    ->label('Deactivated At')
                    ->dateTime()
                    ->placeholder('Active')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active Devices')
                    ->falseLabel('Deactivated Devices'),
            ])
            ->recordActions([
                Action::make('forceDeactivate')
                    ->label('Force Deactivate')
                    ->icon(Heroicon::OutlinedPower)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Force Deactivate Installation')
                    ->modalDescription('Immediately deactivate this device seat and free up an activation for this license.')
                    ->visible(fn (LicenseActivation $record): bool =>
                        $record->is_active && (auth()->user()?->can('licenses.reset_revoke') ?? false)
                    )
                    ->action(function (LicenseActivation $record) {
                        $record->update([
                            'is_active' => false,
                            'deactivated_at' => now(),
                        ]);

                        $license = $record->license;
                        if ($license) {
                            $license->update([
                                'current_activations_count' => max(0, $license->current_activations_count - 1),
                            ]);
                        }

                        Notification::make()
                            ->title('Device Deactivated')
                            ->body("Device {$record->hostname} has been deactivated.")
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
            'index' => ListLicenseActivations::route('/'),
        ];
    }
}
