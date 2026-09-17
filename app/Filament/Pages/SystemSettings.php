<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class SystemSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'System & Audit';

    protected static ?string $navigationLabel = 'System Settings';

    protected static ?string $title = 'System Settings & Webhooks';

    protected string $view = 'filament.pages.placeholder';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('system.manage_settings') ?? false;
    }
}
