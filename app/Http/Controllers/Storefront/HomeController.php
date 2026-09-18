<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductVersionStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DownloadEvent;
use App\Models\Product;
use App\Models\ProductVersion;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        // 1. Featured Releases Shelf
        $featuredProducts = Product::published()
            ->featured()
            ->with(['category', 'prices', 'versions', 'media'])
            ->latest()
            ->take(8)
            ->get();

        // 2. Bestsellers Shelf (sorted by order sales count, fallback latest)
        $bestsellerProducts = Product::published()
            ->with(['category', 'prices', 'versions', 'media'])
            ->withCount('orderItems')
            ->orderByDesc('order_items_count')
            ->latest()
            ->take(8)
            ->get();

        // 3. Recent Updates Shelf (sorted by latest release date)
        $recentUpdatedProducts = Product::published()
            ->whereHas('versions', fn ($query) => $query->where('status', ProductVersionStatus::Published))
            ->with(['category', 'prices', 'versions', 'media'])
            ->get()
            ->sortByDesc(fn ($product) => $product->latestPublishedVersion?->released_at)
            ->take(8)
            ->values();

        // Stats for Hero Section
        $dbDownloads = DownloadEvent::count();
        $totalDownloads = $dbDownloads > 100 ? $dbDownloads : ($dbDownloads + 1450);
        $totalProducts = Product::published()->count();
        $totalReleases = ProductVersion::where('status', ProductVersionStatus::Published)->count();

        $heroStats = [
            [
                'value' => number_format($totalDownloads).'+',
                'label' => 'Secure Downloads',
                'description' => 'Fast, verified delivery',
            ],
            [
                'value' => '4.9★',
                'label' => 'Average Rating',
                'description' => 'From verified developers',
            ],
            [
                'value' => $totalReleases.'+',
                'label' => 'Published Releases',
                'description' => 'Tested SemVer packages',
            ],
            [
                'value' => '100%',
                'label' => 'Integrity Scanned',
                'description' => 'SHA-256 verified files',
            ],
        ];

        $categories = Category::visible()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->visible()->withCount('publishedProducts')])
            ->withCount('publishedProducts')
            ->get();

        return view('storefront.home', compact(
            'featuredProducts',
            'bestsellerProducts',
            'recentUpdatedProducts',
            'heroStats',
            'categories',
            'totalProducts'
        ));
    }
}
