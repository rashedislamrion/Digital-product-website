<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductType;
use App\Enums\ProductVersionStatus;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductCatalogController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::published()
            ->with(['category', 'prices', 'versions', 'media']);

        // 1. Category filter (slug or array of slugs)
        if ($request->filled('category')) {
            $categorySlugs = (array) $request->input('category');
            $categoryIds = Category::whereIn('slug', $categorySlugs)->pluck('id');
            // Include children of these categories
            $childIds = Category::whereIn('parent_id', $categoryIds)->pluck('id');
            $allCategoryIds = $categoryIds->merge($childIds)->unique();

            $query->whereIn('category_id', $allCategoryIds);
        }

        // 2. Product type filter (software, theme, ebook, bundle)
        if ($request->filled('product_type')) {
            $types = (array) $request->input('product_type');
            $validTypes = array_filter($types, fn ($t) => ProductType::tryFrom($t) !== null);
            if (! empty($validTypes)) {
                $query->whereIn('product_type', $validTypes);
            }
        }

        // 3. Price range filter (min_price & max_price in dollars)
        if ($request->filled('min_price') || $request->filled('max_price')) {
            $minMinor = $request->filled('min_price') ? (int) round(((float) $request->input('min_price')) * 100) : null;
            $maxMinor = $request->filled('max_price') ? (int) round(((float) $request->input('max_price')) * 100) : null;

            $query->whereHas('prices', function (Builder $q) use ($minMinor, $maxMinor) {
                $q->where('is_active', true);
                if ($minMinor !== null) {
                    $q->where('amount_minor', '>=', $minMinor);
                }
                if ($maxMinor !== null) {
                    $q->where('amount_minor', '<=', $maxMinor);
                }
            });
        }

        // 4. Keyword search (title / summary / compatibility)
        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            $query->where(function (Builder $q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('summary', 'like', "%{$term}%");
            });
        }

        // 5. Sorting mode
        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'bestselling' => $query->withCount('orderItems')->orderByDesc('order_items_count')->latest(),
            'price_asc' => $query->join('prices', 'products.id', '=', 'prices.product_id')
                ->where('prices.is_active', true)
                ->orderBy('prices.amount_minor', 'asc')
                ->select('products.*')
                ->distinct(),
            'price_desc' => $query->join('prices', 'products.id', '=', 'prices.product_id')
                ->where('prices.is_active', true)
                ->orderBy('prices.amount_minor', 'desc')
                ->select('products.*')
                ->distinct(),
            'relevance' => $query->latest(),
            default => $query->latest(),
        };

        $products = $query->paginate(12)->withQueryString();

        $categories = Category::visible()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->visible()->withCount('publishedProducts')])
            ->withCount('publishedProducts')
            ->get();

        $productTypes = ProductType::cases();

        return view('storefront.catalog', compact(
            'products',
            'categories',
            'productTypes',
            'sort'
        ));
    }

    /**
     * Search products using Laravel Scout across title, summary, and tags (@research.md §3.2).
     */
    public function search(Request $request): View
    {
        $term = trim((string) $request->input('q', ''));

        if ($term !== '') {
            $products = Product::search($term)
                ->query(fn (Builder $query) => $query->published()->with(['category', 'prices', 'versions', 'media']))
                ->paginate(12)
                ->withQueryString();
        } else {
            $products = Product::published()
                ->with(['category', 'prices', 'versions', 'media'])
                ->latest()
                ->paginate(12)
                ->withQueryString();
        }

        $categories = Category::visible()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->visible()->withCount('publishedProducts')])
            ->withCount('publishedProducts')
            ->get();

        return view('storefront.search', [
            'products' => $products,
            'query' => $term,
            'categories' => $categories,
        ]);
    }

    public function category(string $slug, Request $request): View
    {
        $category = Category::visible()->where('slug', $slug)->firstOrFail();

        $childIds = $category->children()->visible()->pluck('id');
        $allCategoryIds = $childIds->push($category->id);

        $query = Product::published()
            ->whereIn('category_id', $allCategoryIds)
            ->with(['category', 'prices', 'versions', 'media']);

        // Filters within category
        if ($request->filled('product_type')) {
            $types = (array) $request->input('product_type');
            $validTypes = array_filter($types, fn ($t) => ProductType::tryFrom($t) !== null);
            if (! empty($validTypes)) {
                $query->whereIn('product_type', $validTypes);
            }
        }

        if ($request->filled('min_price')) {
            $minMinor = (int) round(((float) $request->input('min_price')) * 100);
            $query->whereHas('prices', function (Builder $q) use ($minMinor) {
                $q->where('is_active', true)->where('amount_minor', '>=', $minMinor);
            });
        }

        if ($request->filled('max_price')) {
            $maxMinor = (int) round(((float) $request->input('max_price')) * 100);
            $query->whereHas('prices', function (Builder $q) use ($maxMinor) {
                $q->where('is_active', true)->where('amount_minor', '<=', $maxMinor);
            });
        }

        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'bestselling' => $query->withCount('orderItems')->orderByDesc('order_items_count')->latest(),
            'price_asc' => $query->join('prices', 'products.id', '=', 'prices.product_id')
                ->where('prices.is_active', true)
                ->orderBy('prices.amount_minor', 'asc')
                ->select('products.*')
                ->distinct(),
            'price_desc' => $query->join('prices', 'products.id', '=', 'prices.product_id')
                ->where('prices.is_active', true)
                ->orderBy('prices.amount_minor', 'desc')
                ->select('products.*')
                ->distinct(),
            default => $query->latest(),
        };

        $products = $query->paginate(12)->withQueryString();

        $categories = Category::visible()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->visible()->withCount('publishedProducts')])
            ->withCount('publishedProducts')
            ->get();

        $productTypes = ProductType::cases();

        return view('storefront.catalog', [
            'products' => $products,
            'categories' => $categories,
            'productTypes' => $productTypes,
            'activeCategory' => $category,
            'sort' => $sort,
        ]);
    }

    public function show(string $slug): View
    {
        $product = Product::published()
            ->where('slug', $slug)
            ->with([
                'category',
                'media',
                'prices' => fn ($q) => $q->where('is_active', true)->orderBy('amount_minor'),
                'versions' => fn ($q) => $q->where('status', ProductVersionStatus::Published)->orderByDesc('released_at'),
                'reviews' => fn ($q) => $q->where('status', ReviewStatus::Published)->latest(),
            ])
            ->firstOrFail();

        // 4 Related Products in same category
        $relatedProducts = Product::published()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with(['category', 'prices', 'versions', 'media'])
            ->take(4)
            ->get();

        // If less than 4, backfill with other published products
        if ($relatedProducts->count() < 4) {
            $needed = 4 - $relatedProducts->count();
            $more = Product::published()
                ->whereNotIn('id', $relatedProducts->pluck('id')->push($product->id))
                ->with(['category', 'prices', 'versions', 'media'])
                ->take($needed)
                ->get();
            $relatedProducts = $relatedProducts->merge($more);
        }

        $allCategories = Category::visible()
            ->whereNull('parent_id')
            ->with('children')
            ->get();

        $customer = null;
        if (auth()->check()) {
            $user = auth()->user();
            $customer = $user->customer ?? Customer::where('email', $user->email)->first();
        } elseif (session()->has('guest_customer_id')) {
            $customer = Customer::find(session('guest_customer_id'));
        }

        $canReview = false;
        if ($customer) {
            $canReview = OrderItem::where('product_id', $product->id)
                ->whereHas('order', function ($query) use ($customer) {
                    $query->where('customer_id', $customer->id)
                        ->where('status', \App\Enums\OrderStatus::Paid);
                })
                ->whereDoesntHave('review')
                ->exists();
        }

        return view('storefront.show', compact('product', 'relatedProducts', 'allCategories', 'canReview'));
    }
}
