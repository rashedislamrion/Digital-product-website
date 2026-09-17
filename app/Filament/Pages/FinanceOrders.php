<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class FinanceOrders extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Commerce & Finance';

    protected static ?string $navigationLabel = 'Finance & Orders';

    protected static ?string $title = 'Financial Reports & Orders';

    protected string $view = 'filament.pages.placeholder';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('orders.view_financials') ?? false;
    }
}
