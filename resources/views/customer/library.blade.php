<x-storefront-layout title="My Library" meta-description="Your purchased digital products, license keys, order history, and support desk.">

    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">

        {{-- Page Header --}}
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">My Digital Library</h1>
                <p class="mt-1 text-sm text-slate-400">
                    Purchases and licenses for
                    <span class="font-mono text-emerald-400 font-semibold">{{ $customer->email }}</span>
                </p>
            </div>
            <div class="flex items-center gap-3">
                @if($isGuest)
                    <a href="{{ route('customer.set-password') }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-indigo-500/40 bg-indigo-600/20 px-4 py-2 text-xs font-bold text-indigo-200 hover:bg-indigo-600/40 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        Set Password & Create Account
                    </a>
                @endif
                <a href="{{ route('products.index') }}"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-emerald-400 transition-all shadow-md shadow-emerald-500/20">
                    Browse Store
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </a>
            </div>
        </div>

        {{-- Guest Session Notice --}}
        @if($isGuest)
            <div class="mb-8 rounded-xl border border-indigo-500/20 bg-indigo-950/30 p-4 flex items-start gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-500/20 text-indigo-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div class="text-xs text-indigo-200">
                    <span class="font-bold">Guest access session active:</span> You're accessing purchases via a magic link.
                    <a href="{{ route('customer.set-password') }}" class="underline font-bold hover:text-indigo-100">Set a password</a> for permanent account access.
                </div>
            </div>
        @endif

        {{-- Flash Status --}}
        @if(session('status'))
            <div class="mb-6 rounded-xl border border-emerald-500/30 bg-emerald-500/5 p-4 text-sm text-emerald-300 flex items-center gap-2">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                {{ session('status') }}
            </div>
        @endif

        {{-- Form Validation Errors --}}
        @if($errors->any())
            <div class="mb-6 rounded-xl border border-rose-500/30 bg-rose-500/5 p-4 text-sm text-rose-300">
                <ul class="list-disc pl-5 space-y-1 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Tab Navigation (Alpine.js) --}}
        <div x-data="{ tab: '{{ request('tab', 'products') }}' }" class="space-y-6">

            {{-- Tab Buttons --}}
            <div class="flex gap-1 overflow-x-auto rounded-xl border border-slate-800 bg-slate-900/60 p-1 text-xs font-bold">
                <button @click="tab = 'products'" :class="tab === 'products' ? 'bg-slate-700/80 text-emerald-400 shadow-sm' : 'text-slate-400 hover:text-slate-200'"
                        class="flex-1 rounded-lg px-4 py-2.5 transition-all flex items-center justify-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                    <span>My Products</span>
                    <span class="rounded-full bg-slate-700 px-2 py-0.5 text-[10px] font-mono">{{ $products->count() }}</span>
                </button>
                <button @click="tab = 'licenses'" :class="tab === 'licenses' ? 'bg-slate-700/80 text-indigo-400 shadow-sm' : 'text-slate-400 hover:text-slate-200'"
                        class="flex-1 rounded-lg px-4 py-2.5 transition-all flex items-center justify-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                    <span>License Keys</span>
                    <span class="rounded-full bg-slate-700 px-2 py-0.5 text-[10px] font-mono">{{ $licenses->count() }}</span>
                </button>
                <button @click="tab = 'orders'" :class="tab === 'orders' ? 'bg-slate-700/80 text-amber-400 shadow-sm' : 'text-slate-400 hover:text-slate-200'"
                        class="flex-1 rounded-lg px-4 py-2.5 transition-all flex items-center justify-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    <span>Order History & Invoices</span>
                    <span class="rounded-full bg-slate-700 px-2 py-0.5 text-[10px] font-mono">{{ $orders->count() }}</span>
                </button>
                <button @click="tab = 'support'" :class="tab === 'support' ? 'bg-slate-700/80 text-cyan-400 shadow-sm' : 'text-slate-400 hover:text-slate-200'"
                        class="flex-1 rounded-lg px-4 py-2.5 transition-all flex items-center justify-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    <span>Support Desk</span>
                    <span class="rounded-full bg-slate-700 px-2 py-0.5 text-[10px] font-mono">{{ $supportTickets->count() }}</span>
                </button>
            </div>

            {{-- ═══════════════════════════════════════════ --}}
            {{-- TAB 1: MY PRODUCTS (Purchased Products Grid) --}}
            {{-- ═══════════════════════════════════════════ --}}
            <div x-show="tab === 'products'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">

                @if($products->isEmpty())
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-12 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-800 text-slate-400 mb-4">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-white">No purchases yet</h3>
                        <p class="mt-1 text-xs text-slate-400">Your purchased products will appear here.</p>
                        <a href="{{ route('products.index') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-emerald-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-emerald-400 transition-colors">
                            Browse Catalog →
                        </a>
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($products as $product)
                            @php
                                $grant = $product['grant'];
                                $license = $product['license'];
                                $version = $product['version'];
                                $item = $product['item'];
                            @endphp
                            <div class="group rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden hover:border-slate-700 transition-all flex flex-col justify-between">
                                <div>
                                    {{-- Product Thumbnail --}}
                                    <div class="relative aspect-[16/9] bg-gradient-to-br from-slate-800 to-slate-900 overflow-hidden">
                                        @if($item->product?->thumbnail_path)
                                            <img src="{{ Storage::disk('public')->url($item->product->thumbnail_path) }}"
                                                 alt="{{ $item->historical_product_title }}"
                                                 class="h-full w-full object-cover opacity-80 group-hover:opacity-100 transition-opacity">
                                        @else
                                            <div class="flex h-full items-center justify-center">
                                                <svg class="h-12 w-12 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                            </div>
                                        @endif
                                        {{-- Current Version Badge --}}
                                        @if($version)
                                            <div class="absolute top-3 right-3 rounded-lg bg-slate-950/80 px-2 py-0.5 text-[10px] font-mono font-bold text-emerald-400 border border-emerald-500/30 backdrop-blur-sm">
                                                Current: v{{ $version->version_number }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="p-4 space-y-3">
                                        {{-- Product Title & Tier --}}
                                        <div>
                                            <h3 class="text-sm font-bold text-white leading-tight">{{ $item->historical_product_title }}</h3>
                                            <div class="mt-1 flex items-center gap-2">
                                                <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-mono text-emerald-400 border border-slate-700">
                                                    {{ $item->historical_tier_name }}
                                                </span>
                                                <span class="text-[10px] text-slate-500 font-mono">
                                                    Purchased {{ $item->created_at->format('M d, Y') }}
                                                </span>
                                            </div>
                                        </div>

                                        {{-- Download Section (Hits Phase 6 secure download route) --}}
                                        @if($grant)
                                            <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/60 p-2.5">
                                                <div class="text-[11px] text-slate-400 font-mono">
                                                    {{ $grant->download_count }}/{{ $grant->max_download_attempts }} downloads
                                                </div>
                                                @if($grant->is_revoked)
                                                    <span class="rounded-lg bg-rose-500/10 px-2.5 py-1 text-[10px] font-bold text-rose-400 border border-rose-500/30">Revoked</span>
                                                @elseif($grant->download_count >= $grant->max_download_attempts)
                                                    <span class="rounded-lg bg-slate-700/60 px-2.5 py-1 text-[10px] font-bold text-slate-400">Limit Reached</span>
                                                @else
                                                    <a href="{{ route('library.download', ['download_grant' => $grant->id]) }}"
                                                       class="inline-flex items-center gap-1 rounded-lg bg-emerald-500 px-3 py-1 text-[11px] font-bold text-slate-950 hover:bg-emerald-400 transition-colors shadow-sm shadow-emerald-500/20">
                                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                                        Download Latest
                                                    </a>
                                                @endif
                                            </div>
                                        @endif

                                        {{-- License Badge --}}
                                        @if($license)
                                            <div class="flex items-center justify-between text-[11px]">
                                                <span class="font-mono text-indigo-400">{{ $license->license_key_masked }}</span>
                                                <span class="text-slate-500">{{ $license->current_activations_count }}/{{ $license->max_activations }} seats</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Card Footer: Links to PDP and Public Changelog Tab --}}
                                <div class="px-4 pb-4 pt-2 border-t border-slate-800/80 flex items-center justify-between text-[11px]">
                                    @if($item->product)
                                        <a href="{{ route('products.show', $item->product->slug) }}"
                                           class="flex items-center gap-1 font-medium text-slate-400 hover:text-emerald-400 transition-colors">
                                            Product Page
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                        </a>
                                        <a href="{{ route('products.show', $item->product->slug) }}#changelog"
                                           class="flex items-center gap-1 font-mono font-medium text-indigo-400 hover:text-indigo-300 transition-colors">
                                            View Changelog &rarr;
                                        </a>
                                    @else
                                        <span class="text-slate-500">Historical archive</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ═══════════════════════════════════════════ --}}
            {{-- TAB 2: LICENSE KEYS & ACTIVATION MANAGEMENT --}}
            {{-- ═══════════════════════════════════════════ --}}
            <div x-show="tab === 'licenses'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">

                @if($licenses->isEmpty())
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-12 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-800 text-slate-400 mb-4">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-white">No software licenses</h3>
                        <p class="mt-1 text-xs text-slate-400">Software license keys will appear here when you purchase a software product.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($licenses as $license)
                            <div x-data="{ expanded: false, copied: false }" class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden">
                                {{-- License Header --}}
                                <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 cursor-pointer" @click="expanded = !expanded">
                                    <div class="flex items-start gap-4">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl
                                            {{ $license->status === \App\Enums\LicenseStatus::Active ? 'bg-emerald-500/20 text-emerald-400' :
                                               ($license->status === \App\Enums\LicenseStatus::Issued ? 'bg-indigo-500/20 text-indigo-400' :
                                               ($license->status === \App\Enums\LicenseStatus::Revoked ? 'bg-rose-500/20 text-rose-400' :
                                               'bg-slate-700 text-slate-400')) }}
                                        ">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-bold text-white">{{ $license->product?->title ?? $license->orderItem?->historical_product_title ?? 'Product' }}</h3>
                                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                                <span class="font-mono text-xs text-indigo-400">{{ $license->license_key_masked }}</span>
                                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider
                                                    {{ $license->status === \App\Enums\LicenseStatus::Active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' :
                                                       ($license->status === \App\Enums\LicenseStatus::Issued ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/30' :
                                                       ($license->status === \App\Enums\LicenseStatus::Revoked ? 'bg-rose-500/10 text-rose-400 border border-rose-500/30' :
                                                       ($license->status === \App\Enums\LicenseStatus::Suspended ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30' :
                                                       'bg-slate-700 text-slate-400 border border-slate-600'))) }}
                                                ">{{ $license->status->value }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-4">
                                        {{-- Seat Counter --}}
                                        <div class="text-right">
                                            <div class="text-[10px] uppercase tracking-wider text-slate-500">Seats Used</div>
                                            <div class="font-mono text-sm font-bold
                                                {{ $license->current_activations_count >= $license->max_activations ? 'text-amber-400' : 'text-slate-200' }}
                                            ">
                                                {{ $license->current_activations_count }} / {{ $license->max_activations }} seats used
                                            </div>
                                        </div>

                                        {{-- Expand Chevron --}}
                                        <svg :class="expanded ? 'rotate-180' : ''" class="h-5 w-5 text-slate-500 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </div>
                                </div>

                                {{-- Expanded Details --}}
                                <div x-show="expanded" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                     class="border-t border-slate-800 px-5 pb-5 space-y-4">

                                    {{-- Copy Key Button --}}
                                    <div class="flex items-center gap-3 pt-4">
                                        <div class="flex-1 flex items-center rounded-lg border border-indigo-500/20 bg-slate-950 p-3 font-mono text-sm font-bold text-indigo-300 select-all tracking-wider overflow-x-auto">
                                            {{ $license->license_key_masked }}
                                        </div>
                                        <button @click="navigator.clipboard.writeText('{{ $license->license_key_masked }}'); copied = true; setTimeout(() => copied = false, 2500)"
                                                type="button"
                                                class="shrink-0 inline-flex items-center gap-1.5 rounded-lg border border-indigo-500/40 bg-indigo-600/20 px-3 py-2.5 text-xs font-mono font-medium text-indigo-200 hover:bg-indigo-600/40 transition-colors">
                                            <svg x-show="!copied" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" /></svg>
                                            <svg x-show="copied" x-cloak class="h-3.5 w-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            <span x-text="copied ? 'Copied!' : 'Copy Key'"></span>
                                        </button>
                                    </div>

                                    {{-- License Metadata --}}
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-[11px]">
                                        <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-2.5">
                                            <span class="block text-slate-500 uppercase tracking-wider mb-0.5">Tier</span>
                                            <span class="font-bold text-slate-200">{{ $license->orderItem?->historical_tier_name ?? 'Standard' }}</span>
                                        </div>
                                        <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-2.5">
                                            <span class="block text-slate-500 uppercase tracking-wider mb-0.5">Max Seats</span>
                                            <span class="font-mono font-bold text-slate-200">{{ $license->max_activations }}</span>
                                        </div>
                                        <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-2.5">
                                            <span class="block text-slate-500 uppercase tracking-wider mb-0.5">Expires</span>
                                            <span class="font-mono font-bold text-slate-200">{{ $license->valid_until ? $license->valid_until->format('M d, Y') : 'Never (Perpetual)' }}</span>
                                        </div>
                                        <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-2.5">
                                            <span class="block text-slate-500 uppercase tracking-wider mb-0.5">Issued Date</span>
                                            <span class="font-mono font-bold text-slate-200">{{ $license->created_at->format('M d, Y') }}</span>
                                        </div>
                                    </div>

                                    {{-- Active Installations Table --}}
                                    @if($license->activations->where('is_active', true)->isNotEmpty())
                                        <div>
                                            <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Active Installations & Devices</h4>
                                            <div class="rounded-xl border border-slate-800 bg-slate-950/60 overflow-hidden">
                                                <table class="w-full text-[11px]">
                                                    <thead>
                                                        <tr class="border-b border-slate-800 text-left text-slate-500 uppercase tracking-wider">
                                                            <th class="p-3">Hostname</th>
                                                            <th class="p-3 hidden sm:table-cell">Fingerprint</th>
                                                            <th class="p-3">Activated Date</th>
                                                            <th class="p-3 text-right">Self-Service Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-800/60">
                                                        @foreach($license->activations->where('is_active', true) as $activation)
                                                            <tr x-data="{ deactivating: false, deactivated: false }">
                                                                <td class="p-3 font-mono font-bold text-slate-200">
                                                                    {{ $activation->hostname ?? 'Unknown Host' }}
                                                                </td>
                                                                <td class="p-3 font-mono text-slate-400 hidden sm:table-cell">
                                                                    {{ Str::limit($activation->instance_fingerprint, 20) }}
                                                                </td>
                                                                <td class="p-3 text-slate-400 font-mono">
                                                                    {{ $activation->activated_at?->format('M d, Y') ?? '-' }}
                                                                </td>
                                                                <td class="p-3 text-right">
                                                                    <template x-if="!deactivated">
                                                                        <button
                                                                            @click="if(confirm('Deactivate this device? This will immediately free up a seat for use elsewhere.')) {
                                                                                deactivating = true;
                                                                                fetch('{{ route('customer.deactivate', $activation->id) }}', {
                                                                                    method: 'POST',
                                                                                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                                                                }).then(r => r.json()).then(d => {
                                                                                    if(d.success) { deactivated = true; }
                                                                                    deactivating = false;
                                                                                }).catch(() => { deactivating = false; });
                                                                            }"
                                                                            :disabled="deactivating"
                                                                            class="inline-flex items-center gap-1 rounded-lg border border-rose-500/30 bg-rose-500/10 px-2.5 py-1 text-[10px] font-bold text-rose-400 hover:bg-rose-500/20 transition-colors disabled:opacity-50"
                                                                        >
                                                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                                                            <span x-text="deactivating ? 'Deactivating...' : 'Deactivate this device'"></span>
                                                                        </button>
                                                                    </template>
                                                                    <template x-if="deactivated">
                                                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-400">
                                                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                                            Device Deactivated
                                                                        </span>
                                                                    </template>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @else
                                        <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4 text-center text-xs text-slate-500">
                                            No active installations. Use the license activation REST API to register your first device.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ═══════════════════════════════════════════ --}}
            {{-- TAB 3: ORDER HISTORY & BILLING (PDF Invoices) --}}
            {{-- ═══════════════════════════════════════════ --}}
            <div x-show="tab === 'orders'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">

                @if($orders->isEmpty())
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-12 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-800 text-slate-400 mb-4">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-white">No orders yet</h3>
                        <p class="mt-1 text-xs text-slate-400">Your purchase history and tax invoices will be shown here.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($orders as $order)
                            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden">
                                {{-- Order Header Bar --}}
                                <div class="grid grid-cols-2 sm:grid-cols-6 gap-3 border-b border-slate-800 bg-slate-800/30 px-5 py-3 text-[11px] items-center">
                                    <div>
                                        <span class="block text-slate-500 uppercase tracking-wider">Order</span>
                                        <span class="font-mono font-bold text-white">{{ $order->order_number }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-slate-500 uppercase tracking-wider">Date</span>
                                        <span class="text-slate-300 font-mono">{{ $order->created_at->format('M d, Y') }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-slate-500 uppercase tracking-wider">Total</span>
                                        <span class="font-mono font-bold text-emerald-400">{{ $order->total_formatted }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-slate-500 uppercase tracking-wider">Gateway</span>
                                        <span class="text-slate-300 uppercase font-mono">{{ strtoupper($order->payment_gateway ?? 'SSLCOMMERZ') }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-slate-500 uppercase tracking-wider">Status</span>
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold
                                            {{ $order->status === \App\Enums\OrderStatus::Paid ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' :
                                               ($order->status === \App\Enums\OrderStatus::Pending ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30' :
                                               ($order->status === \App\Enums\OrderStatus::Refunded ? 'bg-rose-500/10 text-rose-400 border border-rose-500/30' :
                                               'bg-slate-700 text-slate-400 border border-slate-600')) }}
                                        ">
                                            <span class="h-1.5 w-1.5 rounded-full
                                                {{ $order->status === \App\Enums\OrderStatus::Paid ? 'bg-emerald-400' :
                                                   ($order->status === \App\Enums\OrderStatus::Pending ? 'bg-amber-400 animate-pulse' :
                                                   ($order->status === \App\Enums\OrderStatus::Refunded ? 'bg-rose-400' : 'bg-slate-400')) }}
                                            "></span>
                                            {{ strtoupper($order->status->value) }}
                                        </span>
                                    </div>
                                    <div class="col-span-2 sm:col-span-1 text-right">
                                        <a href="{{ route('customer.orders.invoice', $order->id) }}"
                                           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-3 py-1 text-[11px] font-medium text-slate-200 hover:border-emerald-500/50 hover:text-emerald-400 transition-colors shadow-sm">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            <span>PDF Invoice</span>
                                        </a>
                                    </div>
                                </div>

                                {{-- Order Items --}}
                                <div class="divide-y divide-slate-800/60 px-5">
                                    @foreach($order->items as $item)
                                        <div class="py-3.5 flex items-center justify-between gap-4">
                                            <div>
                                                <h4 class="text-xs font-bold text-white">{{ $item->historical_product_title }}</h4>
                                                <div class="mt-0.5 flex items-center gap-2 text-[10px] text-slate-500">
                                                    <span class="font-mono text-slate-400">{{ $item->historical_tier_name }}</span>
                                                    <span>•</span>
                                                    <span class="font-mono text-emerald-400">${{ number_format($item->unit_amount_minor / 100, 2) }}</span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                @if($order->status === \App\Enums\OrderStatus::Paid)
                                                    <a href="{{ route('orders.confirmation', $order->order_number) }}"
                                                       class="text-[10px] font-bold text-indigo-400 hover:text-indigo-300 transition-colors whitespace-nowrap">
                                                        Online Receipt →
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Order Footer --}}
                                @if($order->coupon_code)
                                    <div class="border-t border-slate-800 px-5 py-2.5 flex items-center gap-2 text-[10px] text-emerald-400">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                        Coupon applied: <span class="font-mono font-bold">{{ $order->coupon_code }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ═══════════════════════════════════════════ --}}
            {{-- TAB 4: SUPPORT DESK (Submit & View Tickets) --}}
            {{-- ═══════════════════════════════════════════ --}}
            <div x-show="tab === 'support'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">

                <div class="grid gap-6 lg:grid-cols-12">
                    {{-- Left Column: New Ticket Form --}}
                    <div class="lg:col-span-6 rounded-2xl border border-slate-800 bg-slate-900/60 p-6 space-y-4">
                        <div>
                            <h3 class="text-sm font-bold text-white">Submit a Support Inquiry</h3>
                            <p class="mt-0.5 text-xs text-slate-400">Have a technical issue, question regarding your license, or need quota adjustments?</p>
                        </div>

                        <form method="POST" action="{{ route('customer.support-tickets.store') }}" class="space-y-4">
                            @csrf

                            <div>
                                <label for="subject" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                                    Subject <span class="text-rose-400">*</span>
                                </label>
                                <input type="text" id="subject" name="subject" required
                                       placeholder="e.g. License activation failed on AWS staging"
                                       value="{{ old('subject') }}"
                                       class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 font-sans">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label for="order_id" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                                        Related Order (Optional)
                                    </label>
                                    <select id="order_id" name="order_id"
                                            class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 font-mono">
                                        <option value="">None / General</option>
                                        @foreach($orders as $o)
                                            <option value="{{ $o->id }}" {{ old('order_id') == $o->id ? 'selected' : '' }}>
                                                {{ $o->order_number }} (${{ number_format($o->total_minor / 100, 2) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="license_id" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                                        Related License (Optional)
                                    </label>
                                    <select id="license_id" name="license_id"
                                            class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 font-mono">
                                        <option value="">None</option>
                                        @foreach($licenses as $l)
                                            <option value="{{ $l->id }}" {{ old('license_id') == $l->id ? 'selected' : '' }}>
                                                {{ $l->license_key_masked }} ({{ $l->product?->title ?? 'Product' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label for="message" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                                    Message <span class="text-rose-400">*</span>
                                </label>
                                <textarea id="message" name="message" rows="5" required
                                          placeholder="Please provide details about your environment, PHP/framework version, and any error traces..."
                                          class="w-full rounded-xl border border-slate-800 bg-slate-950 p-3 text-xs text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 font-mono">{{ old('message') }}</textarea>
                            </div>

                            <button type="submit"
                                    class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-indigo-500 transition-colors shadow-md shadow-indigo-600/20 flex items-center justify-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                                <span>Send Support Message</span>
                            </button>
                        </form>
                    </div>

                    {{-- Right Column: Previous Support Tickets --}}
                    <div class="lg:col-span-6 rounded-2xl border border-slate-800 bg-slate-900/60 p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-white">Your Tickets</h3>
                            <span class="rounded-full bg-slate-800 px-2.5 py-0.5 text-[10px] font-mono font-bold text-slate-400">
                                {{ $supportTickets->count() }} total
                            </span>
                        </div>

                        @if($supportTickets->isEmpty())
                            <div class="rounded-xl border border-slate-800/80 bg-slate-950/40 p-8 text-center space-y-2">
                                <svg class="mx-auto h-8 w-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                <p class="text-xs text-slate-400">No support tickets submitted yet.</p>
                                <p class="text-[11px] text-slate-500">Need help with an installation or license key? Fill out the form on the left.</p>
                            </div>
                        @else
                            <div class="space-y-3 max-h-[480px] overflow-y-auto pr-1">
                                @foreach($supportTickets as $ticket)
                                    <div class="rounded-xl border border-slate-800 bg-slate-950/70 p-4 space-y-2">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <h4 class="text-xs font-bold text-white">{{ $ticket->subject }}</h4>
                                                <div class="mt-0.5 flex flex-wrap items-center gap-2 text-[10px] text-slate-500 font-mono">
                                                    <span>{{ $ticket->created_at->format('M d, Y H:i') }}</span>
                                                    @if($ticket->order)
                                                        <span>•</span>
                                                        <span class="text-slate-400">Order {{ $ticket->order->order_number }}</span>
                                                    @endif
                                                    @if($ticket->license)
                                                        <span>•</span>
                                                        <span class="text-indigo-400">{{ $ticket->license->license_key_masked }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider
                                                {{ $ticket->status === \App\Enums\SupportTicketStatus::Open ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' :
                                                   ($ticket->status === \App\Enums\SupportTicketStatus::Pending ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30' :
                                                   'bg-slate-700 text-slate-400 border border-slate-600') }}
                                            ">
                                                {{ $ticket->status->value }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-300 font-mono leading-relaxed bg-slate-900/60 p-2.5 rounded-lg border border-slate-800/60">
                                            {{ Str::limit($ticket->message, 240) }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

            </div>

        </div>
    </div>

</x-storefront-layout>
