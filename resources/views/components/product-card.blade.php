@props(['product'])

@php
    $lowestPrice = $product->lowest_price;
    $latestVersion = $product->latest_published_version ?? $product->versions->first();
    $compatibility = $product->compatibility_list;
@endphp

<article class="group relative flex flex-col justify-between h-full rounded-xl border border-slate-800/80 bg-slate-900/50 p-4 transition-all duration-200 hover:border-slate-700/80 hover:bg-slate-900/90 hover:shadow-xl hover:shadow-black/40">
    
    <!-- Top Media & Badges -->
    <div>
        <div class="relative aspect-[16/9] w-full overflow-hidden rounded-lg border border-slate-800/60 bg-slate-950">
            @if($product->thumbnail_url)
                <img 
                    src="{{ $product->thumbnail_url }}" 
                    alt="{{ $product->title }}" 
                    loading="lazy" 
                    class="h-full w-full object-cover object-center group-hover:scale-105 transition-transform duration-300"
                >
            @else
                <!-- Developer Tool Default Aesthetic Graphic -->
                <div class="flex h-full w-full flex-col items-center justify-center bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-4 text-center">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/80 text-emerald-400 shadow-inner">
                        @if($product->product_type->value === 'theme')
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                            </svg>
                        @elseif($product->product_type->value === 'ebook')
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        @else
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                        @endif
                    </div>
                    <span class="mt-2 text-[10px] font-mono tracking-wide text-slate-500 uppercase">{{ $product->product_type->value }} package</span>
                </div>
            @endif

            <!-- Overlaid Category & SemVer Badges -->
            <div class="absolute inset-x-2 top-2 flex items-center justify-between pointer-events-none">
                <span class="inline-flex items-center rounded-md border border-slate-700/80 bg-slate-900/90 px-2 py-0.5 text-[10px] font-medium text-slate-300 backdrop-blur-md">
                    {{ $product->category->name ?? 'Software' }}
                </span>

                @if($latestVersion)
                    <span class="inline-flex items-center gap-1 rounded-md border border-emerald-500/30 bg-emerald-950/90 px-2 py-0.5 text-[10px] font-mono font-semibold text-emerald-300 backdrop-blur-md">
                        <span class="h-1 w-1 rounded-full bg-emerald-400"></span>
                        v{{ $latestVersion->version_number }}
                    </span>
                @endif
            </div>
        </div>

        <!-- Title & Description -->
        <div class="mt-3.5 space-y-1.5">
            <h3 class="text-sm font-semibold text-white group-hover:text-emerald-400 transition-colors line-clamp-1">
                <a href="{{ route('products.show', $product->slug) }}">
                    <span class="absolute inset-0 z-10" aria-hidden="true"></span>
                    {{ $product->title }}
                </a>
            </h3>
            <p class="text-xs leading-relaxed text-slate-400 line-clamp-2">
                {{ $product->summary }}
            </p>
        </div>

        <!-- Compatibility Badges -->
        @if(!empty($compatibility))
            <div class="mt-3 flex flex-wrap items-center gap-1.5">
                @foreach(array_slice($compatibility, 0, 2) as $badge)
                    <span class="inline-flex items-center rounded border border-slate-800 bg-slate-950/60 px-1.5 py-0.5 text-[10px] font-mono text-slate-400">
                        {{ $badge }}
                    </span>
                @endforeach
                @if(count($compatibility) > 2)
                    <span class="text-[10px] font-mono text-slate-500">+{{ count($compatibility) - 2 }}</span>
                @endif
            </div>
        @endif
    </div>

    <!-- Footer: Pricing, Rating & CTA link -->
    <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between">
        <div>
            <div class="text-[10px] uppercase font-mono text-slate-500">Starting At</div>
            <div class="text-sm font-bold font-mono text-white">
                @if($lowestPrice)
                    {{ $lowestPrice->amount_formatted }}
                @else
                    {{ $product->price_range }}
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Star Rating Badge Placeholder -->
            <div class="inline-flex items-center gap-1 rounded bg-slate-800/60 px-1.5 py-0.5 text-xs text-amber-400 font-medium">
                <svg class="h-3 w-3 fill-current" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                <span class="text-[11px] font-mono">{{ $product->average_rating }}</span>
            </div>

            <!-- Arrow Indicator -->
            <div class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-800 bg-slate-800/40 text-slate-400 group-hover:border-emerald-500/50 group-hover:bg-emerald-500/10 group-hover:text-emerald-400 transition-colors">
                <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
        </div>
    </div>
</article>
