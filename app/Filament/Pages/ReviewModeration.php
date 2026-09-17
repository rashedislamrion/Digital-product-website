<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class ReviewModeration extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static string|UnitEnum|null $navigationGroup = 'Customers & Support';

    protected static ?string $navigationLabel = 'Review Moderation';

    protected static ?string $title = 'Customer Review Moderation';

    protected string $view = 'filament.pages.placeholder';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('reviews.moderate_publish') ?? false;
    }
}
