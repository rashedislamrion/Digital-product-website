<x-storefront-layout :categories="$categories" title="Search Results - {{ $query ?: 'All Products' }}">
    
    <!-- Search Header Bar -->
    <div class="border-b border-slate-800/80 bg-slate-950/70 py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <nav class="flex items-center gap-2 text-xs font-mono text-slate-500 mb-3">
                <a href="{{ route('home') }}" class="hover:text-slate-300">Home</a>
                <span>/</span>
                <a href="{{ route('products.index') }}" class="hover:text-slate-300">Catalog</a>
                <span>/</span>
                <span class="text-emerald-400 font-semibold">Search</span>
            </nav>

            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                            @if($query !== '')
                                Search Results for <span class="text-emerald-400">&ldquo;{{ $query }}&rdquo;</span>
                            @else
                                All Products
                            @endif
                        </h1>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Indexed across product titles, descriptions, frameworks, and compatibility tags.
                    </p>
                </div>

                <!-- Re-Search Input in Results Header -->
                <form action="{{ route('search') }}" method="GET" class="relative w-full lg:max-w-md">
                    <input 
                        type="text" 
                        name="q" 
                        value="{{ $query }}"
                        placeholder="Search another product, version, or tech stack..."
                        class="w-full rounded-xl border border-slate-800 bg-slate-900/90 py-2.5 pl-10 pr-24 text-xs text-slate-200 placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500/20"
                    >
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <button 
                        type="submit" 
                        class="absolute inset-y-1 right-1 flex items-center rounded-lg bg-emerald-500/15 px-3 text-xs font-mono font-medium text-emerald-400 hover:bg-emerald-500/25 transition-colors"
                    >
                        Search
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        
        <!-- Results Status Bar -->
        <div class="flex items-center justify-between pb-6 mb-6 border-b border-slate-800/80">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-1 text-xs font-mono text-slate-300">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    <strong class="text-emerald-400">{{ $products->total() }}</strong>
                    <span>{{ Str::plural('product', $products->total()) }} found</span>
                </span>
                @if($query !== '')
                    <a 
                        href="{{ route('search') }}" 
                        class="text-xs text-slate-500 hover:text-slate-300 underline font-mono ml-2"
                    >
                        Clear search
                    </a>
                @endif
            </div>

            <a 
                href="{{ route('products.index') }}" 
                class="text-xs text-emerald-400 hover:text-emerald-300 font-mono flex items-center gap-1"
            >
                <span>Browse Full Catalog</span>
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>

        <!-- Products Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @forelse($products as $product)
                <x-product-card :product="$product" />
            @empty
                <!-- Empty State -->
                <div class="col-span-full rounded-2xl border border-slate-800 bg-slate-900/40 p-12 text-center space-y-4">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-slate-800 bg-slate-950 text-slate-500 shadow-inner">
                        <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">No products found matching &ldquo;{{ $query }}&rdquo;</h3>
                        <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">
                            We couldn't find any published software, themes, or ebooks matching your query. Try different keywords or browse our categories.
                        </p>
                    </div>

                    <!-- Suggested Search Terms -->
                    <div class="pt-4 border-t border-slate-800/80 max-w-md mx-auto">
                        <span class="text-[11px] font-mono text-slate-500 block mb-2">Suggested keywords:</span>
                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <a href="{{ route('search', ['q' => 'Laravel']) }}" class="rounded-lg border border-slate-800 bg-slate-900 px-2.5 py-1 text-xs font-mono text-emerald-400 hover:border-emerald-500/40">Laravel</a>
                            <a href="{{ route('search', ['q' => 'SaaS']) }}" class="rounded-lg border border-slate-800 bg-slate-900 px-2.5 py-1 text-xs font-mono text-emerald-400 hover:border-emerald-500/40">SaaS</a>
                            <a href="{{ route('search', ['q' => 'Tailwind']) }}" class="rounded-lg border border-slate-800 bg-slate-900 px-2.5 py-1 text-xs font-mono text-emerald-400 hover:border-emerald-500/40">Tailwind</a>
                            <a href="{{ route('search', ['q' => 'Vue']) }}" class="rounded-lg border border-slate-800 bg-slate-900 px-2.5 py-1 text-xs font-mono text-emerald-400 hover:border-emerald-500/40">Vue</a>
                            <a href="{{ route('search', ['q' => 'API']) }}" class="rounded-lg border border-slate-800 bg-slate-900 px-2.5 py-1 text-xs font-mono text-emerald-400 hover:border-emerald-500/40">API</a>
                        </div>
                    </div>

                    <div class="pt-2">
                        <a 
                            href="{{ route('products.index') }}" 
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2.5 text-xs font-bold text-slate-950 hover:bg-emerald-400 transition-colors shadow-lg shadow-emerald-500/20"
                        >
                            <span>Browse All Products</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination Links -->
        <div class="mt-8">
            {{ $products->links() }}
        </div>
    </div>

</x-storefront-layout>
