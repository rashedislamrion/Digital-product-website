<x-storefront-layout :categories="$categories" title="DevStore - Production-Grade Digital Products & Starter Kits">
    
    <!-- Hero Section -->
    <section class="relative overflow-hidden pt-12 pb-20 lg:pt-20 lg:pb-28 border-b border-slate-800/60 bg-gradient-to-b from-[#090d16] via-[#0d121f] to-[#090d16]">
        <!-- Background Grid & Glow Accents -->
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(16,185,129,0.15),rgba(255,255,255,0))]"></div>
        <div class="pointer-events-none absolute inset-y-0 right-0 w-1/3 bg-gradient-to-l from-emerald-500/5 to-transparent blur-3xl"></div>

        <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl space-y-6">
                
                <!-- Terminal Release Pill -->
                <div class="inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-950/50 px-3 py-1 text-xs font-mono text-emerald-300 backdrop-blur-md">
                    <span class="flex h-2 w-2 rounded-full bg-emerald-400"></span>
                    <span>Q3 Release Radar: SaaS Launchpad v2.1.0 Live</span>
                    <span class="text-emerald-500/60">&bull;</span>
                    <a href="{{ route('products.index') }}" class="hover:underline flex items-center gap-1 font-sans font-medium text-emerald-400">
                        View Changelog &rarr;
                    </a>
                </div>

                <!-- Main Headline -->
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-[1.1]">
                    Production-Grade <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-teal-300 to-indigo-400">Software Starter Kits</span> & Architectures.
                </h1>

                <!-- Subheadline -->
                <p class="text-base sm:text-lg leading-relaxed text-slate-300 max-w-2xl">
                    Commercial-ready boilerplates, modular API gateways, and developer toolkits. Built on modern PHP 8.3+, Laravel 13.x, and Tailwind CSS. Full source code included, perpetual licensing, zero vendor lock-in.
                </p>

                <!-- CTA Actions -->
                <div class="pt-2 flex flex-wrap items-center gap-4">
                    <a 
                        href="{{ route('products.index') }}" 
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-6 py-3.5 text-sm font-semibold text-slate-950 hover:bg-emerald-400 shadow-lg shadow-emerald-500/25 transition-all duration-200 hover:-translate-y-0.5"
                    >
                        <span>Browse Entire Catalog</span>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>

                    <a 
                        href="#featured" 
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-800/60 px-6 py-3.5 text-sm font-medium text-slate-200 hover:border-slate-600 hover:bg-slate-800 hover:text-white backdrop-blur-md transition-all duration-200"
                    >
                        <span>Featured Releases</span>
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </a>
                </div>

                <!-- 4 Social Proof Stat Badges -->
                <div class="pt-8 border-t border-slate-800/80 grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach($heroStats as $stat)
                        <div class="space-y-1">
                            <div class="text-2xl sm:text-3xl font-bold font-mono text-white">{{ $stat['value'] }}</div>
                            <div class="text-xs font-semibold text-slate-300">{{ $stat['label'] }}</div>
                            <div class="text-[11px] text-slate-500">{{ $stat['description'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- Trust & Security Banner (Research 3.1 & 5.1) -->
    <section class="border-b border-slate-800 bg-[#06080e] py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                
                <!-- Pillar 1: Instant Delivery -->
                <div class="flex items-start gap-3.5 p-3 rounded-lg bg-slate-900/40 border border-slate-800/60">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-white">Instant Fulfillment</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5">Automated presigned download links delivered immediately upon payment.</p>
                    </div>
                </div>

                <!-- Pillar 2: Encrypted Storage -->
                <div class="flex items-start gap-3.5 p-3 rounded-lg bg-slate-900/40 border border-slate-800/60">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-white">Private S3 Delivery</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5">Secure, non-guessable UUID storage paths with strict attempt limits.</p>
                    </div>
                </div>

                <!-- Pillar 3: Scanned Safe -->
                <div class="flex items-start gap-3.5 p-3 rounded-lg bg-slate-900/40 border border-slate-800/60">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-500/10 text-teal-400 border border-teal-500/20">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-white">Malware & Checksum Verified</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5">Every package undergoes SHA-256 verification and antivirus scanning.</p>
                    </div>
                </div>

                <!-- Pillar 4: Money Back -->
                <div class="flex items-start gap-3.5 p-3 rounded-lg bg-slate-900/40 border border-slate-800/60">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-white">30-Day Money-Back Guarantee</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5">If the codebase does not fit your engineering requirements, receive a prompt refund.</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Product Shelves Section -->
    <div id="shelves" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 space-y-20">
        
        <!-- Shelf 1: Featured Releases -->
        <section id="featured">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="flex h-2 w-2 rounded-full bg-emerald-400"></span>
                        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-white">Featured Releases</h2>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">Hand-picked flagship software starter kits and core developer frameworks.</p>
                </div>
                <a href="{{ route('products.index', ['sort' => 'newest']) }}" class="text-xs font-medium text-emerald-400 hover:text-emerald-300 flex items-center gap-1 transition-colors">
                    <span>View All ({{ $totalProducts }})</span>
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>

            <!-- Horizontal Scroll on Mobile, Grid on Desktop -->
            <div class="flex gap-5 overflow-x-auto no-scrollbar pb-4 sm:grid sm:grid-cols-2 lg:grid-cols-4 sm:overflow-visible sm:pb-0">
                @forelse($featuredProducts as $product)
                    <div class="w-72 shrink-0 sm:w-auto">
                        <x-product-card :product="$product" />
                    </div>
                @empty
                    <p class="text-xs text-slate-500 col-span-4">No featured products currently listed.</p>
                @endforelse
            </div>
        </section>

        <!-- Shelf 2: Bestsellers / Most Popular -->
        <section>
            <div class="flex items-center justify-between mb-6">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="flex h-2 w-2 rounded-full bg-amber-400"></span>
                        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-white">Bestsellers & Community Favorites</h2>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">High-traction codebases most frequently licensed by professional agencies.</p>
                </div>
                <a href="{{ route('products.index', ['sort' => 'bestselling']) }}" class="text-xs font-medium text-emerald-400 hover:text-emerald-300 flex items-center gap-1 transition-colors">
                    <span>Explore Bestsellers</span>
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>

            <div class="flex gap-5 overflow-x-auto no-scrollbar pb-4 sm:grid sm:grid-cols-2 lg:grid-cols-4 sm:overflow-visible sm:pb-0">
                @forelse($bestsellerProducts as $product)
                    <div class="w-72 shrink-0 sm:w-auto">
                        <x-product-card :product="$product" />
                    </div>
                @empty
                    <p class="text-xs text-slate-500 col-span-4">Bestseller shelf updating.</p>
                @endforelse
            </div>
        </section>

        <!-- Shelf 3: Recent SemVer Updates -->
        <section>
            <div class="flex items-center justify-between mb-6">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="flex h-2 w-2 rounded-full bg-indigo-400"></span>
                        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-white">Recent Updates & Patches</h2>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">Latest published semantic releases with security patches and new features.</p>
                </div>
                <a href="{{ route('products.index', ['sort' => 'newest']) }}" class="text-xs font-medium text-emerald-400 hover:text-emerald-300 flex items-center gap-1 transition-colors">
                    <span>Changelog Feed</span>
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>

            <div class="flex gap-5 overflow-x-auto no-scrollbar pb-4 sm:grid sm:grid-cols-2 lg:grid-cols-4 sm:overflow-visible sm:pb-0">
                @forelse($recentUpdatedProducts as $product)
                    <div class="w-72 shrink-0 sm:w-auto">
                        <x-product-card :product="$product" />
                    </div>
                @empty
                    <p class="text-xs text-slate-500 col-span-4">No recent updates recorded.</p>
                @endforelse
            </div>
        </section>

    </div>

    <!-- Call to Action Lead Magnet / Community Block -->
    <section class="border-t border-slate-800 bg-gradient-to-br from-slate-950 via-[#0d121f] to-slate-950 py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center space-y-6">
            <div class="mx-auto max-w-2xl space-y-3">
                <span class="inline-flex items-center rounded-full border border-indigo-500/30 bg-indigo-950/60 px-3 py-1 text-xs font-mono text-indigo-300">
                    Zero-Friction Developer Licensing
                </span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                    Build Faster. Ship Commercial Software With Confidence.
                </h2>
                <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                    Every product is independently maintained, documented, and delivered via signed presigned URLs. Seamlessly activate seat licenses or integrate with our offline validation sentinel.
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-4 pt-2">
                <a 
                    href="{{ route('products.index') }}" 
                    class="rounded-xl bg-emerald-500 px-6 py-3 text-xs font-semibold text-slate-950 hover:bg-emerald-400 shadow-md shadow-emerald-500/20 transition-colors"
                >
                    Explore All Available Software
                </a>
                <a 
                    href="{{ route('customer.library') }}" 
                    class="rounded-xl border border-slate-700 bg-slate-900 px-6 py-3 text-xs font-medium text-slate-200 hover:border-slate-600 transition-colors"
                >
                    Already Purchased? Access Library &rarr;
                </a>
            </div>
        </div>
    </section>

</x-storefront-layout>
