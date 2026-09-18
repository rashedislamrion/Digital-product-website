<x-storefront-layout :categories="$categories" title="{{ isset($activeCategory) ? $activeCategory->name . ' - Catalog' : 'Software & Products Catalog' }}">
    
    <!-- Breadcrumb & Header -->
    <div class="border-b border-slate-800/80 bg-slate-950/60 py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <nav class="flex items-center gap-2 text-xs font-mono text-slate-500 mb-3">
                <a href="{{ route('home') }}" class="hover:text-slate-300">Home</a>
                <span>/</span>
                <a href="{{ route('products.index') }}" class="hover:text-slate-300 {{ !isset($activeCategory) ? 'text-emerald-400 font-semibold' : '' }}">Catalog</a>
                @isset($activeCategory)
                    <span>/</span>
                    <span class="text-emerald-400 font-semibold">{{ $activeCategory->name }}</span>
                @endisset
            </nav>

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                        {{ isset($activeCategory) ? $activeCategory->name : 'Developer Catalog' }}
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        {{ isset($activeCategory) ? "Browsing verified packages under {$activeCategory->name}." : 'Production software, themes, and microservices with full source code.' }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center rounded-lg border border-slate-800 bg-slate-900 px-3 py-1.5 text-xs font-mono text-slate-300">
                        <strong class="text-emerald-400 mr-1">{{ $products->total() }}</strong> Products Available
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content & Faceted Filtering Layout -->
    <div x-data="{ mobileFiltersOpen: false }" class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        
        <!-- Mobile Filter Toggle & Sort Bar -->
        <div class="flex items-center justify-between pb-6 border-b border-slate-800 lg:hidden gap-4">
            <button 
                type="button" 
                @click="mobileFiltersOpen = !mobileFiltersOpen"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-800/80 px-4 py-2 text-xs font-medium text-white shadow-sm"
            >
                <svg class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                <span>Filters & Options</span>
            </button>

            <!-- Mobile Sort Selector -->
            <form action="{{ url()->current() }}" method="GET" class="flex items-center gap-2">
                @foreach(request()->except(['sort', 'page']) as $k => $v)
                    @if(is_array($v))
                        @foreach($v as $subV)
                            <input type="hidden" name="{{ $k }}[]" value="{{ $subV }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endif
                @endforeach
                <select 
                    name="sort" 
                    onchange="this.form.submit()" 
                    class="rounded-lg border border-slate-800 bg-slate-900 py-1.5 px-3 text-xs text-slate-300 focus:border-emerald-500 font-sans"
                >
                    <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest Releases</option>
                    <option value="bestselling" {{ request('sort') === 'bestselling' ? 'selected' : '' }}>Bestselling</option>
                    <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                    <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                    <option value="relevance" {{ request('sort') === 'relevance' ? 'selected' : '' }}>Relevance</option>
                </select>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 pt-4">
            
            <!-- Sidebar Filters (Desktop & Mobile Slideout) -->
            <aside 
                class="lg:block"
                :class="{ 'fixed inset-0 z-50 overflow-y-auto bg-slate-950/95 p-6 backdrop-blur-xl lg:static lg:bg-transparent lg:p-0 lg:z-auto': mobileFiltersOpen, 'hidden': !mobileFiltersOpen }"
            >
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-800 lg:hidden">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Filters</h3>
                    <button type="button" @click="mobileFiltersOpen = false" class="text-slate-400 hover:text-white p-1">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form action="{{ isset($activeCategory) ? route('categories.show', $activeCategory->slug) : route('products.index') }}" method="GET" class="space-y-6">
                    
                    <!-- Search keyword preserve -->
                    @if(request('q'))
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase font-mono">Search Keyword</label>
                            <div class="flex items-center gap-1.5">
                                <input 
                                    type="text" 
                                    name="q" 
                                    value="{{ request('q') }}" 
                                    class="w-full rounded-lg border border-slate-800 bg-slate-900 px-3 py-1.5 text-xs text-white"
                                >
                            </div>
                        </div>
                    @endif

                    <!-- Categories Filter -->
                    @if(!isset($activeCategory))
                        <div class="border-b border-slate-800/80 pb-6">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-300 font-mono mb-3">Categories</h3>
                            <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                                @foreach($categories as $cat)
                                    <label class="flex items-center justify-between text-xs text-slate-300 hover:text-white cursor-pointer group">
                                        <div class="flex items-center gap-2">
                                            <input 
                                                type="checkbox" 
                                                name="category[]" 
                                                value="{{ $cat->slug }}"
                                                {{ in_array($cat->slug, (array) request('category', [])) ? 'checked' : '' }}
                                                class="rounded border-slate-700 bg-slate-900 text-emerald-500 focus:ring-emerald-500/20"
                                            >
                                            <span class="group-hover:text-emerald-400 transition-colors">{{ $cat->name }}</span>
                                        </div>
                                        <span class="text-[10px] font-mono text-slate-500">{{ $cat->published_products_count ?? 0 }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Product Type Filter -->
                    <div class="border-b border-slate-800/80 pb-6">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-300 font-mono mb-3">Product Type</h3>
                        <div class="space-y-2">
                            @foreach($productTypes as $type)
                                <label class="flex items-center gap-2 text-xs text-slate-300 hover:text-white cursor-pointer group">
                                    <input 
                                        type="checkbox" 
                                        name="product_type[]" 
                                        value="{{ $type->value }}"
                                        {{ in_array($type->value, (array) request('product_type', [])) ? 'checked' : '' }}
                                        class="rounded border-slate-700 bg-slate-900 text-emerald-500 focus:ring-emerald-500/20"
                                    >
                                    <span class="capitalize group-hover:text-emerald-400 transition-colors">{{ $type->value }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Price Range Inputs -->
                    <div class="border-b border-slate-800/80 pb-6">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-300 font-mono mb-3">Price Range ($ USD)</h3>
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <span class="absolute left-2.5 top-2 text-xs text-slate-500">$</span>
                                <input 
                                    type="number" 
                                    name="min_price" 
                                    value="{{ request('min_price') }}"
                                    placeholder="Min" 
                                    min="0"
                                    class="w-full rounded-lg border border-slate-800 bg-slate-900 py-1.5 pl-6 pr-2 text-xs text-white font-mono placeholder-slate-600 focus:border-emerald-500 focus:outline-none"
                                >
                            </div>
                            <span class="text-slate-600">&ndash;</span>
                            <div class="relative flex-1">
                                <span class="absolute left-2.5 top-2 text-xs text-slate-500">$</span>
                                <input 
                                    type="number" 
                                    name="max_price" 
                                    value="{{ request('max_price') }}"
                                    placeholder="Max" 
                                    min="0"
                                    class="w-full rounded-lg border border-slate-800 bg-slate-900 py-1.5 pl-6 pr-2 text-xs text-white font-mono placeholder-slate-600 focus:border-emerald-500 focus:outline-none"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Hidden Sort Parameter -->
                    <input type="hidden" name="sort" value="{{ request('sort', 'newest') }}">

                    <!-- Action Buttons -->
                    <div class="space-y-2 pt-2">
                        <button 
                            type="submit" 
                            class="w-full rounded-lg bg-emerald-500 py-2.5 text-xs font-semibold text-slate-950 hover:bg-emerald-400 shadow-sm transition-colors"
                        >
                            Apply Filters
                        </button>
                        
                        @if(request()->hasAny(['category', 'product_type', 'min_price', 'max_price', 'q']))
                            <a 
                                href="{{ isset($activeCategory) ? route('categories.show', $activeCategory->slug) : route('products.index') }}"
                                class="block w-full text-center rounded-lg border border-slate-800 py-2 text-xs text-slate-400 hover:text-white hover:bg-slate-900 transition-colors"
                            >
                                Clear All Filters
                            </a>
                        @endif
                    </div>
                </form>
            </aside>

            <!-- Product Grid & Desktop Sort Bar -->
            <div class="lg:col-span-3 space-y-6">
                
                <!-- Desktop Sort Header -->
                <div class="hidden lg:flex items-center justify-between bg-slate-900/40 p-3 rounded-xl border border-slate-800/80">
                    <!-- Active Filter Pills -->
                    <div class="flex flex-wrap items-center gap-2">
                        @if(request('q'))
                            <span class="inline-flex items-center gap-1 rounded-md border border-slate-700 bg-slate-800 px-2 py-1 text-xs text-slate-300">
                                Keyword: "{{ request('q') }}"
                            </span>
                        @endif

                        @if(request('category'))
                            @foreach((array) request('category') as $slug)
                                <span class="inline-flex items-center gap-1 rounded-md border border-slate-700 bg-slate-800 px-2 py-1 text-xs text-slate-300">
                                    Cat: {{ $slug }}
                                </span>
                            @endforeach
                        @endif

                        @if(request('product_type'))
                            @foreach((array) request('product_type') as $pt)
                                <span class="inline-flex items-center gap-1 rounded-md border border-slate-700 bg-slate-800 px-2 py-1 text-xs text-slate-300 capitalize">
                                    {{ $pt }}
                                </span>
                            @endforeach
                        @endif

                        @if(request('min_price') || request('max_price'))
                            <span class="inline-flex items-center gap-1 rounded-md border border-slate-700 bg-slate-800 px-2 py-1 text-xs text-slate-300 font-mono">
                                ${{ request('min_price', 0) }} &ndash; ${{ request('max_price', '∞') }}
                            </span>
                        @endif

                        @if(request()->hasAny(['category', 'product_type', 'min_price', 'max_price', 'q']))
                            <a 
                                href="{{ isset($activeCategory) ? route('categories.show', $activeCategory->slug) : route('products.index') }}"
                                class="text-xs text-emerald-400 hover:underline font-mono ml-2"
                            >
                                Reset Filters &times;
                            </a>
                        @else
                            <span class="text-xs text-slate-400">Showing all published catalog assets</span>
                        @endif
                    </div>

                    <!-- Sort Mode Dropdown -->
                    <form action="{{ url()->current() }}" method="GET" class="flex items-center gap-2">
                        @foreach(request()->except(['sort', 'page']) as $k => $v)
                            @if(is_array($v))
                                @foreach($v as $subV)
                                    <input type="hidden" name="{{ $k }}[]" value="{{ $subV }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                            @endif
                        @endforeach

                        <label for="desktop-sort" class="text-xs text-slate-400 font-mono">Sort by:</label>
                        <select 
                            id="desktop-sort"
                            name="sort" 
                            onchange="this.form.submit()" 
                            class="rounded-lg border border-slate-800 bg-slate-900 py-1.5 pl-3 pr-8 text-xs text-slate-200 focus:border-emerald-500 focus:outline-none font-sans"
                        >
                            <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest Releases</option>
                            <option value="bestselling" {{ request('sort') === 'bestselling' ? 'selected' : '' }}>Bestselling</option>
                            <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                            <option value="relevance" {{ request('sort') === 'relevance' ? 'selected' : '' }}>Relevance</option>
                        </select>
                    </form>
                </div>

                <!-- Product Cards Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                    @forelse($products as $product)
                        <x-product-card :product="$product" />
                    @empty
                        <!-- Empty State per Research 7.x -->
                        <div class="col-span-full rounded-2xl border border-slate-800 bg-slate-900/40 p-12 text-center space-y-4">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-slate-800 bg-slate-950 text-slate-500">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="space-y-1">
                                <h3 class="text-sm font-bold text-white">No products found</h3>
                                <p class="text-xs text-slate-400 max-w-sm mx-auto">
                                    We couldn't find any digital products matching your active filter criteria.
                                </p>
                            </div>
                            <div class="pt-2">
                                <a 
                                    href="{{ isset($activeCategory) ? route('categories.show', $activeCategory->slug) : route('products.index') }}"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-slate-800 px-4 py-2 text-xs font-medium text-slate-200 hover:bg-slate-700 transition-colors"
                                >
                                    <span>Clear All Filters</span>
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endforelse
                </div>

                <!-- Server-Side Pagination -->
                @if($products->hasPages())
                    <div class="pt-8 border-t border-slate-800">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>

</x-storefront-layout>
