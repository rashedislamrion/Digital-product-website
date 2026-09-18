<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\View\View;

class OrderConfirmationController extends Controller
{
    /**
     * Display the order confirmation screen.
     */
    public function show(string $order_number): View
    {
        $order = Order::with(['customer', 'items.product.latestPublishedVersion', 'items.price', 'items.downloadGrants', 'items.license'])
            ->where('order_number', $order_number)
            ->firstOrFail();

        // Pull raw licenses once from ephemeral cache (purges from memory on first read)
        $rawLicenses = \Illuminate\Support\Facades\Cache::pull("order_raw_licenses_{$order->id}") ?? [];

        return view('storefront.orders.confirmation', [
            'order' => $order,
            'rawLicenses' => $rawLicenses,
        ]);
    }
}
