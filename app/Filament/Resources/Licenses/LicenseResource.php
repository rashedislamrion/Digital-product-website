<?php

namespace App\Filament\Resources\Licenses;

use App\Enums\LicenseStatus;
use App\Filament\Resources\Licenses\Pages\ListLicenses;
use App\Filament\Resources\Licenses\Pages\ViewLicense;
use App\Models\License;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class LicenseResource extends Resource
{
    protected static ?string $model = License::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Licensing & Delivery';

    protected static ?string $navigationLabel = 'License Keys';

    protected static ?string $modelLabel = 'License Key';

    protected static ?string $pluralModelLabel = 'License Keys';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('License Information')
                    ->schema([
                        TextInput::make('license_key_masked')
                            ->label('License Key (Masked)')
                            ->disabled(),

                        TextInput::make('status')
                            ->disabled(),

                        TextInput::make('customer.email')
                            ->label('Customer Email')
                            ->disabled(),

                        TextInput::make('product.title')
                            ->label('Product Title')
                            ->disabled(),

                        TextInput::make('current_activations_count')
                            ->label('Active Seats')
                            ->disabled(),

                        TextInput::make('max_activations')
                            ->label('Max Seat Allowance')
                            ->disabled(),

                        TextInput::make('valid_until')
                            ->label('Valid Until')
                            ->disabled()
                            ->placeholder('Perpetual'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('license_key_masked')
                    ->label('License Key')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->fontFamily('mono'),

                TextColumn::make('customer.email')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product.title')
                    ->label('Product')
                    ->searchable()
                    ->limit(25),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state?->value ?? $state) {
                        'active' => 'success',
                        'issued' => 'info',
                        'suspended' => 'warning',
                        'revoked', 'expired' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('seats_usage')
                    ->label('Seats Used')
                    ->state(fn (License $record): string => "{$record->current_activations_count} / {$record->max_activations}"),

                TextColumn::make('valid_until')
                    ->label('Expiration')
                    ->dateTime()
                    ->placeholder('Perpetual'),

                TextColumn::make('created_at')
                    ->label('Issued At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'issued' => 'Issued',
                        'suspended' => 'Suspended',
                        'revoked' => 'Revoked',
                        'expired' => 'Expired',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),

                // Admin State Machine Action: Suspend (Active -> Suspended)
                Action::make('suspend')
                    ->label('Suspend')
                    ->icon(Heroicon::OutlinedPause)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Suspend License Key')
                    ->modalDescription('Temporarily disable verification and seat activations for this license.')
                    ->visible(fn (License $record): bool =>
                        (auth()->user()?->can('licenses.reset_revoke') ?? false)
                        && in_array($record->status, [LicenseStatus::Active, LicenseStatus::Issued])
                    )
                    ->action(function (License $record) {
                        $record->update(['status' => LicenseStatus::Suspended]);

                        Notification::make()
                            ->title('License Suspended')
                            ->body("License {$record->license_key_masked} has been suspended.")
                            ->warning()
                            ->send();
                    }),

                // Admin State Machine Action: Reinstate (Suspended -> Active)
                Action::make('reinstate')
                    ->label('Reinstate')
                    ->icon(Heroicon::OutlinedPlay)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Reinstate License Key')
                    ->modalDescription('Reactivate this suspended license and allow client activations.')
                    ->visible(fn (License $record): bool =>
                        (auth()->user()?->can('licenses.reset_revoke') ?? false)
                        && $record->status === LicenseStatus::Suspended
                    )
                    ->action(function (License $record) {
                        $record->update(['status' => LicenseStatus::Active]);

                        Notification::make()
                            ->title('License Reinstated')
                            ->body("License {$record->license_key_masked} is now Active.")
                            ->success()
                            ->send();
                    }),

                // Admin State Machine Action: Revoke (Active/Suspended -> Revoked)
                Action::make('revoke')
                    ->label('Revoke')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Revoke License Key')
                    ->modalDescription('Permanently revoke this license key and terminate all active installations. This action cannot be undone.')
                    ->visible(fn (License $record): bool =>
                        (auth()->user()?->can('licenses.reset_revoke') ?? false)
                        && in_array($record->status, [LicenseStatus::Active, LicenseStatus::Issued, LicenseStatus::Suspended])
                    )
                    ->action(function (License $record) {
                        $record->update([
                            'status' => LicenseStatus::Revoked,
                            'current_activations_count' => 0,
                        ]);

                        $record->activations()->where('is_active', true)->update([
                            'is_active' => false,
                            'deactivated_at' => now(),
                        ]);

                        Notification::make()
                            ->title('License Revoked')
                            ->body("License {$record->license_key_masked} has been permanently revoked.")
                            ->danger()
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
            'index' => ListLicenses::route('/'),
            'view' => ViewLicense::route('/{record}'),
        ];
    }
}
