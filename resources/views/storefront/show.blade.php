<x-storefront-layout :categories="$allCategories" title="{{ $product->title }} - DevStore">
    
    @php
        $latestVersion = $product->latest_published_version ?? $product->versions->first();
        $mediaList = $product->media;
        $primaryImage = $product->thumbnail_url ?? ($mediaList->first() ? Storage::disk($mediaList->first()->disk ?? 'public')->url($mediaList->first()->file_path) : null);
        $firstPrice = $product->prices->first();
    @endphp

    <div 
        x-data="{ 
            activeImage: '{{ $primaryImage }}', 
            lightboxOpen: false,
            activeTab: 'overview',
            selectedTier: '{{ $firstPrice?->id }}',
            selectedPrice: '{{ $firstPrice?->amount_formatted ?? '$49.00' }}',
            selectedTierName: '{{ $firstPrice?->license_tier_name ?? 'Standard License' }}'
        }"
        class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 space-y-12"
    >
        
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-xs font-mono text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-slate-300">Home</a>
            <span>/</span>
            <a href="{{ route('products.index') }}" class="hover:text-slate-300">Catalog</a>
            @if($product->category)
                <span>/</span>
                <a href="{{ route('categories.show', $product->category->slug) }}" class="hover:text-slate-300">{{ $product->category->name }}</a>
            @endif
            <span>/</span>
            <span class="text-emerald-400 font-semibold truncate">{{ $product->title }}</span>
        </nav>

        <!-- Product Top Section: Media Gallery (Left) & Header + License Selector (Right) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
            
            <!-- Left Column: Media Gallery Carousel & Lightbox Trigger (7 cols) -->
            <div class="lg:col-span-7 space-y-4">
                
                <!-- Main Featured Screenshot / Preview -->
                <div class="relative aspect-[16/9] w-full overflow-hidden rounded-2xl border border-slate-800 bg-slate-950 shadow-2xl group cursor-zoom-in" @click="if (activeImage) lightboxOpen = true">
                    <template x-if="activeImage">
                        <img 
                            :src="activeImage" 
                            alt="{{ $product->title }}" 
                            class="h-full w-full object-cover object-top transition-transform duration-300 group-hover:scale-[1.02]"
                        >
                    </template>
                    <template x-if="!activeImage">
                        <div class="flex h-full w-full flex-col items-center justify-center bg-gradient-to-br from-slate-900 via-slate-950 to-[#0d121f] text-center p-8">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-slate-800 bg-slate-900 text-emerald-400">
                                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                </svg>
                            </div>
                            <span class="mt-4 text-xs font-mono text-slate-400">Production Digital Package</span>
                        </div>
                    </template>

                    <!-- Click to Expand Badge -->
                    <div class="absolute bottom-3 right-3 flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-900/80 px-2.5 py-1 text-xs text-slate-300 backdrop-blur-md">
                        <svg class="h-3.5 w-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                        <span class="text-[11px] font-mono">Zoom Screenshot</span>
                    </div>

                    <!-- Verified Codebase Badge -->
                    <div class="absolute top-3 left-3 flex items-center gap-1.5 rounded-lg border border-emerald-500/30 bg-emerald-950/80 px-2.5 py-1 text-xs text-emerald-300 backdrop-blur-md">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        <span class="text-[11px] font-mono font-medium">ClamAV & Checksum Verified</span>
                    </div>
                </div>

                <!-- Thumbnail Carousel -->
                @if($mediaList->isNotEmpty())
                    <div class="flex items-center gap-3 overflow-x-auto no-scrollbar pb-2">
                        @if($product->thumbnail_url)
                            <button 
                                type="button" 
                                @click="activeImage = '{{ $product->thumbnail_url }}'"
                                class="relative h-16 w-24 shrink-0 overflow-hidden rounded-lg border transition-all"
                                :class="activeImage === '{{ $product->thumbnail_url }}' ? 'border-emerald-500 ring-2 ring-emerald-500/30' : 'border-slate-800 opacity-60 hover:opacity-100'"
                            >
                                <img src="{{ $product->thumbnail_url }}" alt="Cover" class="h-full w-full object-cover">
                            </button>
                        @endif

                        @foreach($mediaList as $media)
                            @php
                                $imgUrl = Storage::disk($media->disk ?? 'public')->url($media->file_path);
                            @endphp
                            <button 
                                type="button" 
                                @click="activeImage = '{{ $imgUrl }}'"
                                class="relative h-16 w-24 shrink-0 overflow-hidden rounded-lg border transition-all"
                                :class="activeImage === '{{ $imgUrl }}' ? 'border-emerald-500 ring-2 ring-emerald-500/30' : 'border-slate-800 opacity-60 hover:opacity-100'"
                            >
                                <img src="{{ $imgUrl }}" alt="Screenshot {{ $loop->iteration }}" class="h-full w-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif

                <!-- Lightbox Modal (Alpine.js per 7.2) -->
                <div 
                    x-show="lightboxOpen" 
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @keydown.escape.window="lightboxOpen = false"
                    style="display: none;"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4 backdrop-blur-md"
                >
                    <div class="relative max-h-[90vh] max-w-5xl overflow-hidden rounded-2xl border border-slate-800 bg-slate-950 p-2 shadow-2xl" @click.outside="lightboxOpen = false">
                        <button 
                            type="button" 
                            @click="lightboxOpen = false"
                            class="absolute top-4 right-4 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-slate-900/80 text-white hover:bg-slate-800 transition-colors"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <img :src="activeImage" :alt="'{{ $product->title }} Preview'" class="max-h-[85vh] w-auto rounded-xl object-contain mx-auto">
                    </div>
                </div>

            </div>

            <!-- Right Column: Product Header, License Selector & Buy Box (5 cols) -->
            <div class="lg:col-span-5 space-y-6">
                
                <!-- Product Header Info -->
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('categories.show', $product->category->slug) }}" class="inline-flex items-center rounded-md border border-slate-800 bg-slate-900 px-2.5 py-1 text-xs font-medium text-emerald-400 hover:border-slate-700">
                            {{ $product->category->name }}
                        </a>

                        @if($latestVersion)
                            <span class="inline-flex items-center gap-1 rounded-md border border-emerald-500/30 bg-emerald-950/60 px-2.5 py-1 text-xs font-mono font-semibold text-emerald-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                v{{ $latestVersion->version_number }}
                            </span>
                        @endif

                        <span class="text-xs font-mono text-slate-500">
                            Updated {{ $latestVersion?->released_at?->diffForHumans() ?? $product->updated_at->diffForHumans() }}
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight leading-snug">
                        {{ $product->title }}
                    </h1>

                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                        {{ $product->summary }}
                    </p>

                    <!-- Rating Summary Placeholder -->
                    <div class="flex items-center gap-2 pt-1">
                        <div class="flex items-center text-amber-400">
                            @for($i = 0; $i < 5; $i++)
                                <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            @endfor
                        </div>
                        <span class="text-xs font-bold font-mono text-white">{{ $product->average_rating }}</span>
                        <span class="text-xs text-slate-500">({{ $product->reviews->count() }} verified reviews)</span>
                    </div>
                </div>

                <!-- License Tier Selector (Radio Cards per 3.3) -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-200 font-mono">
                            Select Commercial License
                        </h2>
                        <span class="text-[11px] text-slate-500 font-mono">Perpetual Access</span>
                    </div>

                    <div class="space-y-2.5">
                        @forelse($product->prices as $price)
                            <label 
                                class="relative flex items-center justify-between p-3.5 rounded-xl border cursor-pointer transition-all duration-150"
                                :class="selectedTier === '{{ $price->id }}' ? 'border-emerald-500 bg-emerald-950/20 shadow-md shadow-emerald-500/10' : 'border-slate-800 bg-slate-900/40 hover:border-slate-700'"
                                @click="selectedTier = '{{ $price->id }}'; selectedPrice = '{{ $price->amount_formatted }}'; selectedTierName = '{{ $price->license_tier_name }}'"
                            >
                                <div class="flex items-center gap-3">
                                    <div 
                                        class="flex h-4 w-4 items-center justify-center rounded-full border transition-colors"
                                        :class="selectedTier === '{{ $price->id }}' ? 'border-emerald-500 bg-emerald-500 text-slate-950' : 'border-slate-700 bg-slate-900'"
                                    >
                                        <div class="h-1.5 w-1.5 rounded-full bg-slate-950" x-show="selectedTier === '{{ $price->id }}'"></div>
                                    </div>

                                    <div>
                                        <div class="text-xs font-bold text-white">{{ $price->license_tier_name }}</div>
                                        <div class="text-[11px] text-slate-400 font-mono">
                                            @if($price->max_activation_seats >= 999999)
                                                Unlimited production seats & SaaS redistribution
                                            @else
                                                {{ $price->max_activation_seats }} production domain / seat{{ $price->max_activation_seats > 1 ? 's' : '' }}
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <div class="text-sm font-bold font-mono text-white">{{ $price->amount_formatted }}</div>
                                    <div class="text-[10px] text-slate-500 uppercase font-mono">one-time</div>
                                </div>
                            </label>
                        @empty
                            <div class="p-3 text-xs text-slate-400 text-center font-mono">Contact for custom enterprise pricing</div>
                        @endforelse
                    </div>

                    <!-- Primary Buy Now & Secondary Add to Cart Form -->
                    <form action="{{ route('cart.add') }}" method="POST" class="pt-2 space-y-2.5">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="price_id" :value="selectedTier">

                        <button 
                            type="submit" 
                            name="buy_now" 
                            value="1"
                            class="w-full flex items-center justify-center gap-2 rounded-xl bg-emerald-500 py-3.5 px-4 text-sm font-semibold text-slate-950 hover:bg-emerald-400 shadow-lg shadow-emerald-500/25 transition-all duration-150 hover:-translate-y-0.5"
                        >
                            <span>Buy Now &bull; <span x-text="selectedPrice"></span></span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>

                        <button 
                            type="submit" 
                            class="w-full flex items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-800/80 py-3 px-4 text-xs font-semibold text-slate-200 hover:border-slate-600 hover:bg-slate-800 hover:text-white transition-colors"
                        >
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <span>Add to Cart Drawer</span>
                        </button>
                    </form>

                    <!-- Trust & Guarantee Bullets -->
                    <div class="pt-3 border-t border-slate-800/80 space-y-2 text-[11px] text-slate-400">
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Instant private S3 delivery with expiring presigned URLs</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Includes complete source code, tests, and documentation</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>30-Day Money-Back Guarantee &bull; Risk-free evaluation</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- Tabbed Content Section (Overview, Technical Specs, Changelog, Support Policy, Reviews) -->
        <div class="border-t border-slate-800 pt-10">
            
            <!-- Tab Navigation Headers -->
            <div class="flex items-center gap-2 border-b border-slate-800 overflow-x-auto no-scrollbar pb-px">
                <button 
                    type="button" 
                    @click="activeTab = 'overview'"
                    class="py-3 px-4 text-xs font-semibold tracking-wide border-b-2 transition-all shrink-0"
                    :class="activeTab === 'overview' ? 'border-emerald-400 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200'"
                >
                    Overview
                </button>

                <button 
                    type="button" 
                    @click="activeTab = 'specs'"
                    class="py-3 px-4 text-xs font-semibold tracking-wide border-b-2 transition-all shrink-0"
                    :class="activeTab === 'specs' ? 'border-emerald-400 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200'"
                >
                    Technical Specifications
                </button>

                <button 
                    type="button" 
                    @click="activeTab = 'changelog'"
                    class="py-3 px-4 text-xs font-semibold tracking-wide border-b-2 transition-all shrink-0 flex items-center gap-1.5"
                    :class="activeTab === 'changelog' ? 'border-emerald-400 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200'"
                >
                    <span>Changelog</span>
                    <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-mono text-slate-400">{{ $product->versions->count() }}</span>
                </button>

                <button 
                    type="button" 
                    @click="activeTab = 'support'"
                    class="py-3 px-4 text-xs font-semibold tracking-wide border-b-2 transition-all shrink-0"
                    :class="activeTab === 'support' ? 'border-emerald-400 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200'"
                >
                    Support Policy & SLA
                </button>

                <button 
                    type="button" 
                    @click="activeTab = 'reviews'"
                    class="py-3 px-4 text-xs font-semibold tracking-wide border-b-2 transition-all shrink-0 flex items-center gap-1.5"
                    :class="activeTab === 'reviews' ? 'border-emerald-400 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200'"
                >
                    <span>Verified Reviews</span>
                    <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-mono text-slate-400">{{ $product->reviews->count() }}</span>
                </button>
            </div>

            <!-- Tab 1: Overview -->
            <div x-show="activeTab === 'overview'" class="py-8 space-y-6 max-w-4xl">
                <div class="prose prose-invert prose-emerald max-w-none text-xs sm:text-sm text-slate-300 leading-relaxed space-y-4">
                    @if($product->description_html)
                        {!! $product->description_html !!}
                    @else
                        <p>{{ $product->summary }}</p>
                    @endif
                </div>
            </div>

            <!-- Tab 2: Technical Specifications -->
            <div x-show="activeTab === 'specs'" style="display: none;" class="py-8 max-w-4xl space-y-6">
                <div class="rounded-2xl border border-slate-800 bg-slate-900/40 divide-y divide-slate-800/80">
                    <div class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <span class="text-xs font-mono text-slate-500 uppercase">Product Type</span>
                        <span class="sm:col-span-2 text-xs font-medium text-white capitalize">{{ $product->product_type->value }}</span>
                    </div>

                    @foreach($product->compatibility_metadata ?? [] as $specKey => $specValue)
                        <div class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <span class="text-xs font-mono text-slate-500 uppercase">{{ $specKey }}</span>
                            <span class="sm:col-span-2 text-xs font-medium text-emerald-400 font-mono">{{ $specValue }}</span>
                        </div>
                    @endforeach

                    <div class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <span class="text-xs font-mono text-slate-500 uppercase">Package Archive</span>
                        <span class="sm:col-span-2 text-xs font-mono text-slate-300">ZIP archive (verified SHA-256 integrity checksum)</span>
                    </div>

                    <div class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <span class="text-xs font-mono text-slate-500 uppercase">Updates & Delivery</span>
                        <span class="sm:col-span-2 text-xs text-slate-300">Continuous SemVer delivery via customer library portal</span>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Public Semantic Changelog -->
            <div x-show="activeTab === 'changelog'" style="display: none;" class="py-8 max-w-4xl space-y-6">
                @forelse($product->versions as $ver)
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-2.5">
                                <span class="rounded-lg border border-emerald-500/30 bg-emerald-950/60 px-2.5 py-1 text-xs font-mono font-bold text-emerald-300">
                                    v{{ $ver->version_number }}
                                </span>
                                @if($ver->min_runtime_version)
                                    <span class="text-xs font-mono text-slate-500">Requires {{ $ver->min_runtime_version }}</span>
                                @endif
                            </div>

                            <span class="text-xs font-mono text-slate-400">
                                Released {{ $ver->released_at ? $ver->released_at->format('M d, Y') : 'Pending' }}
                            </span>
                        </div>

                        <div class="prose prose-invert prose-emerald text-xs text-slate-300 pt-1">
                            @if($ver->changelog_markdown)
                                {!! \Illuminate\Support\Str::markdown($ver->changelog_markdown) !!}
                            @else
                                <p class="text-slate-500 italic">No detailed changelog notes recorded for this release.</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-slate-500">No public releases recorded yet.</div>
                @endforelse
            </div>

            <!-- Tab 4: Support Policy & SLA -->
            <div x-show="activeTab === 'support'" style="display: none;" class="py-8 max-w-4xl space-y-6">
                <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6 space-y-4 text-xs sm:text-sm text-slate-300 leading-relaxed">
                    <h3 class="text-base font-bold text-white">Commercial Support & Maintenance SLA</h3>
                    <p>
                        All commercial purchases include dedicated software support covering framework compatibility updates, critical security patches, and initial setup guidance.
                    </p>
                    <ul class="list-disc pl-5 space-y-2 text-slate-400 text-xs">
                        <li><strong>Response Time:</strong> Standard inquiries answered within 24 business hours. Priority tiers receive responses under 6 hours.</li>
                        <li><strong>Scope of Support:</strong> Verification of bugs in default codebase, documentation clarification, and bug fix releases.</li>
                        <li><strong>Exclusions:</strong> Custom feature development, third-party server infrastructure debugging, and unverified modified fork implementations.</li>
                    </ul>
                </div>
            </div>

            <!-- Tab 5: Verified Customer Reviews -->
            <div x-show="activeTab === 'reviews'" style="display: none;" class="py-8 max-w-4xl space-y-6">
                @if(session('status'))
                    <div class="rounded-xl border border-emerald-500/30 bg-emerald-950/40 p-4 text-xs text-emerald-300">
                        {{ session('status') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="rounded-xl border border-red-500/30 bg-red-950/40 p-4 text-xs text-red-300">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                @if(!empty($canReview) && $canReview)
                    <!-- Verified Customer Review Submission Form -->
                    <div class="rounded-2xl border border-emerald-500/30 bg-slate-900/60 p-6 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400 text-xs">✓</span>
                                <h3 class="text-sm font-semibold text-white">Verified Customer: Write a Review</h3>
                            </div>
                            <span class="rounded bg-emerald-950/80 border border-emerald-500/30 px-2 py-0.5 text-[10px] font-mono text-emerald-400">Verified Buyer</span>
                        </div>

                        <form action="{{ route('products.reviews.store', $product->id) }}" method="POST" class="space-y-4">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="rating" class="block text-xs font-medium text-slate-300 mb-1">Rating</label>
                                    <select id="rating" name="rating" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                                        <option value="5">★★★★★ (5 Stars - Excellent)</option>
                                        <option value="4">★★★★☆ (4 Stars - Good)</option>
                                        <option value="3">★★★☆☆ (3 Stars - Average)</option>
                                        <option value="2">★★☆☆☆ (2 Stars - Poor)</option>
                                        <option value="1">★☆☆☆☆ (1 Star - Terrible)</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label for="title" class="block text-xs font-medium text-slate-300 mb-1">Headline (Optional)</label>
                                    <input type="text" id="title" name="title" placeholder="e.g. Robust architecture, clean code" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none">
                                </div>
                            </div>

                            <div>
                                <label for="review_text" class="block text-xs font-medium text-slate-300 mb-1">Review</label>
                                <textarea id="review_text" name="review_text" rows="3" required placeholder="Share your technical feedback and experience with this release..." class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none"></textarea>
                            </div>

                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-medium text-white hover:bg-emerald-500 transition-colors">
                                Submit Review for Moderation
                            </button>
                        </form>
                    </div>
                @endif

                @forelse($product->reviews as $rev)
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex items-center text-amber-400">
                                    @for($s = 0; $s < $rev->rating; $s++)
                                        <svg class="h-3.5 w-3.5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                    @endfor
                                </div>
                                <span class="text-xs font-semibold text-white">{{ $rev->title ?? 'Verified Review' }}</span>
                                <span class="rounded bg-emerald-950/60 border border-emerald-500/30 px-1.5 py-0.5 text-[10px] font-mono text-emerald-300">Verified Buyer</span>
                            </div>
                            <span class="text-[11px] font-mono text-slate-500">{{ $rev->created_at->format('M Y') }}</span>
                        </div>

                        <p class="text-xs text-slate-300 leading-relaxed">{{ $rev->review_text }}</p>

                        @if($rev->merchant_reply)
                            <div class="mt-3 rounded-xl border border-slate-800 bg-slate-950/60 p-3.5 space-y-1">
                                <div class="text-[11px] font-semibold text-emerald-400 font-mono">Author Reply</div>
                                <p class="text-xs text-slate-400">{{ $rev->merchant_reply }}</p>
                            </div>
                        @endif
                    </div>
                @empty
                    <!-- Placeholder "No reviews yet" state per prompt -->
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/30 p-12 text-center space-y-3">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-slate-800 text-slate-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-white">No reviews yet for this release</h3>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto">
                            Reviews are strictly restricted to verified purchasers upon downloading their software packages.
                        </p>
                    </div>
                @endforelse
            </div>

        </div>

        <!-- Related Products Grid (Research 3.3) -->
        @if($relatedProducts->isNotEmpty())
            <div class="border-t border-slate-800 pt-12 space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-white">Frequently Paired & Related Products</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Explore starter kits and companion packages in the same ecosystem.</p>
                    </div>
                    <a href="{{ route('products.index') }}" class="text-xs font-medium text-emerald-400 hover:underline">
                        View Full Catalog &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    @foreach($relatedProducts as $related)
                        <x-product-card :product="$related" />
                    @endforeach
                </div>
            </div>
        @endif

    </div>

</x-storefront-layout>
