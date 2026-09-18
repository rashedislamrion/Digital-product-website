@props(['categories' => collect()])

<header x-data="{ mobileOpen: false, categoryOpen: false }" class="sticky top-0 z-50 w-full border-b border-slate-800/80 bg-[#090d16]/90 backdrop-blur-md">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        
        <!-- Left: Logo & Nav Links -->
        <div class="flex items-center gap-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-400 to-emerald-600 p-0.5 shadow-md shadow-emerald-500/20 group-hover:scale-105 transition-transform duration-200">
                    <div class="flex h-full w-full items-center justify-center rounded-[6px] bg-slate-950">
                        <svg class="h-4.5 w-4.5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="16 18 22 12 16 6"></polyline>
                            <polyline points="8 6 2 12 8 18"></polyline>
                        </svg>
                    </div>
                </div>
                <div>
                    <span class="text-base font-bold tracking-tight text-white group-hover:text-emerald-400 transition-colors">DevStore<span class="text-emerald-400">.</span></span>
                    <span class="hidden sm:inline-block ml-1.5 rounded border border-slate-700/60 bg-slate-800/50 px-1.5 py-0.5 text-[10px] font-mono text-slate-400">PRO</span>
                </div>
            </a>

            <!-- Desktop Nav Links -->
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                <a href="{{ route('products.index') }}" class="text-slate-300 hover:text-white transition-colors">
                    Catalog
                </a>

                <!-- Category Dropdown -->
                <div class="relative" @click.outside="categoryOpen = false">
                    <button 
                        type="button" 
                        @click="categoryOpen = !categoryOpen"
                        class="flex items-center gap-1.5 text-slate-300 hover:text-white transition-colors"
                        :class="{ 'text-white': categoryOpen }"
                    >
                        <span>Categories</span>
                        <svg class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': categoryOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div 
                        x-show="categoryOpen" 
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                        style="display: none;"
                        class="absolute left-0 top-full mt-2 w-72 rounded-xl border border-slate-800 bg-slate-900/95 p-2 shadow-2xl shadow-black/50 backdrop-blur-xl z-50"
                    >
                        @forelse($categories as $cat)
                            <div class="mb-1 last:mb-0">
                                <a 
                                    href="{{ route('categories.show', $cat->slug) }}" 
                                    class="flex items-center justify-between rounded-lg px-3 py-2 text-sm text-slate-200 hover:bg-slate-800/80 hover:text-emerald-400 transition-colors"
                                >
                                    <span class="font-medium">{{ $cat->name }}</span>
                                    <span class="text-xs font-mono text-slate-500">{{ $cat->published_products_count ?? $cat->products_count ?? 0 }}</span>
                                </a>

                                @if($cat->children && $cat->children->isNotEmpty())
                                    <div class="ml-3 pl-2 border-l border-slate-800 space-y-0.5 mt-0.5">
                                        @foreach($cat->children as $child)
                                            <a 
                                                href="{{ route('categories.show', $child->slug) }}"
                                                class="block rounded px-2.5 py-1.5 text-xs text-slate-400 hover:bg-slate-800/50 hover:text-slate-200 transition-colors"
                                            >
                                                {{ $child->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="px-3 py-2 text-xs text-slate-400">All categories ready</div>
                        @endforelse

                        <div class="mt-2 border-t border-slate-800/80 pt-2">
                            <a href="{{ route('products.index') }}" class="block rounded-lg px-3 py-1.5 text-xs font-medium text-emerald-400 hover:bg-emerald-500/10 transition-colors">
                                View All Products &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <a href="#shelves" class="text-slate-400 hover:text-slate-200 transition-colors">
                    Releases
                </a>
            </nav>
        </div>

        <!-- Center/Search: Visual input with Cmd+K hint -->
        <div class="hidden lg:flex flex-1 max-w-md mx-8">
            <form action="{{ route('search') }}" method="GET" class="w-full relative">
                <div class="relative flex items-center">
                    <svg class="pointer-events-none absolute left-3.5 h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                    </svg>
                    <input 
                        type="text" 
                        name="q" 
                        value="{{ request('q') }}"
                        placeholder="Search products, scripts, themes..." 
                        class="w-full rounded-lg border border-slate-800 bg-slate-900/60 py-1.5 pl-10 pr-16 text-xs text-slate-200 placeholder-slate-500 focus:border-emerald-500 focus:bg-slate-900 focus:outline-none focus:ring-1 focus:ring-emerald-500/30 transition-all font-sans"
                    >
                    <div class="absolute right-2.5 flex items-center pointer-events-none">
                        <kbd class="rounded border border-slate-700 bg-slate-800 px-1.5 py-0.5 text-[10px] font-mono text-slate-400">⌘K</kbd>
                    </div>
                </div>
            </form>
        </div>

        <!-- Right: Actions & User -->
        <div class="flex items-center gap-3 sm:gap-4">
            <!-- Search Icon (Mobile) -->
            <a href="{{ route('search') }}" class="lg:hidden p-2 text-slate-400 hover:text-white transition-colors" title="Search">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </a>

            <!-- Cart Drawer Icon -->
            <button 
                type="button" 
                @click="$dispatch('open-cart')"
                x-data="{ cartCount: {{ app(\App\Domain\Commerce\Services\CartService::class)->getCount() }} }"
                @cart-count-updated.window="cartCount = $event.detail"
                class="relative flex h-9 w-9 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/50 text-slate-300 hover:border-slate-700 hover:text-white transition-colors"
                title="View Shopping Cart"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <span 
                    x-text="cartCount" 
                    class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-emerald-500 text-[10px] font-bold text-slate-950 font-mono"
                    :class="{ 'opacity-100': cartCount > 0, 'opacity-75': cartCount === 0 }"
                >{{ app(\App\Domain\Commerce\Services\CartService::class)->getCount() }}</span>
            </button>

            <!-- User / Library Account -->
            @auth
                <div class="flex items-center gap-2">
                    @if(auth()->user()->isStaff())
                        <a href="{{ url('/admin') }}" class="hidden sm:inline-flex items-center gap-1.5 rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-xs font-medium text-amber-300 hover:bg-amber-500/20 transition-colors">
                            <span>Admin Panel</span>
                        </a>
                    @endif
                    <a href="{{ route('customer.library') }}" class="flex items-center gap-2 rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-1.5 text-xs font-medium text-slate-200 hover:border-slate-700 hover:text-white transition-colors">
                        <svg class="h-3.5 w-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span class="hidden sm:inline">My Library</span>
                    </a>
                </div>
            @else
                <a href="{{ route('customer.library') }}" class="flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-1.5 text-xs font-medium text-slate-300 hover:border-slate-700 hover:text-white transition-colors">
                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="hidden sm:inline">Sign In / Library</span>
                </a>
            @endauth

            <!-- Mobile Hamburger Button -->
            <button 
                type="button" 
                @click="mobileOpen = !mobileOpen"
                class="md:hidden flex h-9 w-9 items-center justify-center rounded-lg border border-slate-800 text-slate-400 hover:text-white"
            >
                <svg x-show="!mobileOpen" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg x-show="mobileOpen" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile Drawer -->
    <div 
        x-show="mobileOpen" 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        style="display: none;"
        class="md:hidden border-b border-slate-800 bg-[#0d111d] px-4 py-4 space-y-3"
    >
        <form action="{{ route('search') }}" method="GET" class="mb-3">
            <div class="relative">
                <input 
                    type="text" 
                    name="q" 
                    value="{{ request('q') }}"
                    placeholder="Search software..." 
                    class="w-full rounded-lg border border-slate-700 bg-slate-900 py-2 pl-3 pr-8 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500"
                >
            </div>
        </form>

        <nav class="space-y-1 text-sm font-medium">
            <a href="{{ route('products.index') }}" class="block rounded-lg px-3 py-2 text-slate-300 hover:bg-slate-800 hover:text-white">
                All Products & Catalog
            </a>
            <div class="px-3 pt-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Categories</div>
            @foreach($categories as $cat)
                <a href="{{ route('categories.show', $cat->slug) }}" class="block rounded-lg px-3 py-1.5 text-xs text-slate-400 hover:bg-slate-800 hover:text-emerald-400">
                    {{ $cat->name }}
                </a>
            @endforeach
            <div class="border-t border-slate-800 pt-2 mt-2">
                <a href="{{ route('customer.library') }}" class="block rounded-lg px-3 py-2 text-xs text-emerald-400 hover:bg-slate-800">
                    Customer Portal & Library Access
                </a>
            </div>
        </nav>
    </div>
</header>
