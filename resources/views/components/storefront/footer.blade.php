<footer class="w-full border-t border-slate-800 bg-[#06080e] text-slate-400">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 lg:gap-12">
            
            <!-- Col 1: Brand & Bio (Spans 2 cols on desktop) -->
            <div class="lg:col-span-2 space-y-4">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-400 to-emerald-600 p-0.5 shadow-md shadow-emerald-500/20">
                        <div class="flex h-full w-full items-center justify-center rounded-[6px] bg-slate-950">
                            <svg class="h-4 w-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="16 18 22 12 16 6"></polyline>
                                <polyline points="8 6 2 12 8 18"></polyline>
                            </svg>
                        </div>
                    </div>
                    <span class="text-base font-bold tracking-tight text-white">DevStore<span class="text-emerald-400">.</span></span>
                </a>
                <p class="text-xs leading-relaxed text-slate-400 max-w-sm">
                    Production-grade software architectures, developer starter kits, and full-stack components. Built for engineers seeking clean codebases, perpetual commercial licensing, and zero vendor lock-in.
                </p>

                <!-- Security & Tech Stack Badges -->
                <div class="pt-2 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1 rounded border border-slate-800 bg-slate-900/60 px-2 py-1 text-[11px] font-mono text-slate-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        Laravel 13.x
                    </span>
                    <span class="inline-flex items-center gap-1 rounded border border-slate-800 bg-slate-900/60 px-2 py-1 text-[11px] font-mono text-slate-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        SHA-256 Verified
                    </span>
                    <span class="inline-flex items-center gap-1 rounded border border-slate-800 bg-slate-900/60 px-2 py-1 text-[11px] font-mono text-slate-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        Encrypted S3
                    </span>
                </div>
            </div>

            <!-- Col 2: Products Directory -->
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-200">Catalog</h3>
                <ul class="mt-4 space-y-2.5 text-xs">
                    <li>
                        <a href="{{ route('products.index') }}" class="hover:text-emerald-400 transition-colors">All Products</a>
                    </li>
                    <li>
                        <a href="{{ route('products.index', ['product_type[]' => 'software']) }}" class="hover:text-emerald-400 transition-colors">Developer Starter Kits</a>
                    </li>
                    <li>
                        <a href="{{ route('products.index', ['product_type[]' => 'theme']) }}" class="hover:text-emerald-400 transition-colors">Tailwind CSS Themes</a>
                    </li>
                    <li>
                        <a href="{{ route('products.index', ['sort' => 'bestselling']) }}" class="hover:text-emerald-400 transition-colors">Bestseller Scripts</a>
                    </li>
                    <li>
                        <a href="{{ route('products.index', ['sort' => 'newest']) }}" class="hover:text-emerald-400 transition-colors">Recent Updates</a>
                    </li>
                </ul>
            </div>

            <!-- Col 3: Customer Portal & Licensing -->
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-200">Customer Center</h3>
                <ul class="mt-4 space-y-2.5 text-xs">
                    <li>
                        <a href="{{ route('customer.library') }}" class="hover:text-emerald-400 transition-colors">My Download Library</a>
                    </li>
                    <li>
                        <a href="{{ route('magic-link.create') }}" class="hover:text-emerald-400 transition-colors">Passwordless Magic Link</a>
                    </li>
                    <li>
                        <a href="{{ route('login') }}" class="hover:text-emerald-400 transition-colors">Sign In</a>
                    </li>
                    <li>
                        <span class="text-slate-500 cursor-default">License Key Sentinel</span>
                    </li>
                    <li>
                        <span class="text-slate-500 cursor-default">Documentation & Guides</span>
                    </li>
                </ul>
            </div>

            <!-- Col 4: Newsletter / Release Alerts -->
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-200">Release Radar</h3>
                <p class="mt-4 text-xs leading-relaxed text-slate-400">
                    Get semantic version changelogs, architecture deep-dives, and private release discounts.
                </p>
                <form onsubmit="event.preventDefault(); alert('Thank you for subscribing to DevStore release updates!');" class="mt-3 space-y-2">
                    <div class="relative">
                        <input 
                            type="email" 
                            required 
                            placeholder="developer@company.com" 
                            class="w-full rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-2 text-xs text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500/30 font-mono"
                        >
                    </div>
                    <button 
                        type="submit" 
                        class="w-full rounded-lg bg-emerald-500 px-3 py-2 text-xs font-semibold text-slate-950 hover:bg-emerald-400 transition-colors shadow-sm shadow-emerald-500/20"
                    >
                        Subscribe to Releases
                    </button>
                    <p class="text-[11px] text-slate-500">Zero spam. Instant one-click unsubscribe.</p>
                </form>
            </div>
        </div>

        <!-- Bottom Bar: Legal & Currency Notice -->
        <div class="mt-12 border-t border-slate-800/80 pt-8 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-slate-500">
            <div class="flex flex-wrap items-center gap-4">
                <span>&copy; {{ date('Y') }} DevStore Digital Products. All rights reserved.</span>
                <span class="hidden sm:inline">&bull;</span>
                <span class="text-slate-400">Terms of Service</span>
                <span class="text-slate-400">Privacy Policy</span>
                <span class="text-slate-400">Refund Policy (30-Day Guarantee)</span>
            </div>

            <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                <span class="text-slate-400 font-mono">USD Default &bull; Global Tax Auto-Calculated</span>
            </div>
        </div>
    </div>
</footer>
