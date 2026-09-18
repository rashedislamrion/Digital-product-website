<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class RevenueChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return 'Revenue Trend (Last 30 Days)';
    }

    protected function getData(): array
    {
        $days = collect(range(29, 0))->map(function ($day) {
            return now()->subDays($day)->format('Y-m-d');
        });

        $revenuesByDate = Order::where('status', OrderStatus::Paid)
            ->where('created_at', '>=', now()->subDays(30))
            ->get()
            ->groupBy(fn ($order) => $order->created_at->format('Y-m-d'))
            ->map(fn ($orders) => $orders->sum('total_minor') / 100);

        $data = $days->map(fn ($date) => $revenuesByDate->get($date, 0))->values()->toArray();
        $labels = $days->map(fn ($date) => Carbon::parse($date)->format('M d'))->values()->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Daily Net Revenue ($)',
                    'data' => $data,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.12)',
                    'fill' => 'start',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
