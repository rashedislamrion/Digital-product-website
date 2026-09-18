<?php

namespace App\Filament\Widgets;

use App\Enums\ProductVersionStatus;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductVersion;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CatalogStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalProducts = Product::count();
        $publishedVersions = ProductVersion::where('status', ProductVersionStatus::Published)->count();
        $activePrices = Price::where('is_active', true)->count();

        return [
            Stat::make('Total Products', $totalProducts)
                ->description('Catalog inventory')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Published Releases', $publishedVersions)
                ->description('Secure, verified versions live')
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('success'),

            Stat::make('Active Price Tiers', $activePrices)
                ->description('Commercial licenses available')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),
        ];
    }
}
