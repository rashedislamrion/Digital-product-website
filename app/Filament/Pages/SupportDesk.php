<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class SupportDesk extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|UnitEnum|null $navigationGroup = 'Licensing & Delivery';

    protected static ?string $navigationLabel = 'Support & Licenses';

    protected static ?string $title = 'Support & Licenses Desk';

    protected string $view = 'filament.pages.placeholder';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->can('licenses.view_keys') || $user?->can('support.manage_tickets')) ?? false;
    }
}
