<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class CatalogManagement extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog Management';

    protected static ?string $navigationLabel = 'Catalog Management';

    protected static ?string $title = 'Catalog Management';

    protected string $view = 'filament.pages.placeholder';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('catalog.create_update') ?? false;
    }
}
