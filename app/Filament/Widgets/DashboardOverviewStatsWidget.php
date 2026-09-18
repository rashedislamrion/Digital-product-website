<?php

namespace App\Filament\Widgets;

use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Enums\SupportTicketStatus;
use App\Models\License;
use App\Models\Order;
use App\Models\Review;
use App\Models\SupportTicket;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardOverviewStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $revenue30d = Order::where('status', OrderStatus::Paid)
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('total_minor') / 100;

        $paidOrdersCount = Order::where('status', OrderStatus::Paid)->count();
        $activeLicenses = License::where('status', LicenseStatus::Active)->count();
        $pendingReviews = Review::where('status', ReviewStatus::Pending)->count();
        $openTickets = SupportTicket::where('status', SupportTicketStatus::Open)->count();

        return [
            Stat::make('30-Day Revenue', '$' . number_format($revenue30d, 2))
                ->description('Paid order conversions')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Paid Orders', $paidOrdersCount)
                ->description('Total customer transactions')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),

            Stat::make('Active Licenses', $activeLicenses)
                ->description('Active software seats')
                ->descriptionIcon('heroicon-m-key')
                ->color('info'),

            Stat::make('Pending Reviews', $pendingReviews)
                ->description('Awaiting moderation')
                ->descriptionIcon('heroicon-m-star')
                ->color($pendingReviews > 0 ? 'warning' : 'gray'),

            Stat::make('Open Support Tickets', $openTickets)
                ->description('Customer requests')
                ->descriptionIcon('heroicon-m-lifebuoy')
                ->color($openTickets > 0 ? 'danger' : 'gray'),
        ];
    }
}
