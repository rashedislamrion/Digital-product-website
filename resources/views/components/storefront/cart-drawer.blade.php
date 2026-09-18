@php
    $cartService = app(\App\Domain\Commerce\Services\CartService::class);
    $initialItems = $cartService->getItems()->map(function ($item) {
        return [
            'price_id' => $item->price_id,
            'product_id' => $item->product_id,
            'product_title' => $item->product->title,
            'product_slug' => $item->product->slug,
            'tier_name' => $item->tier_name,
            'unit_amount_formatted' => $item->unit_amount_formatted,
            'quantity' => $item->quantity,
            'line_total_formatted' => $item->line_total_formatted,
            'thumbnail_url' => $item->product->thumbnail_url,
        ];
    });
    $initialCoupon = $cartService->getAppliedCoupon();
@endphp

<div 
    x-data="cartDrawerComponent({
        isOpen: {{ session('open_cart') ? 'true' : 'false' }},
        items: {{ Js::from($initialItems) }},
        count: {{ $cartService->getCount() }},
        subtotal: '{{ $cartService->getSubtotalFormatted() }}',
        discount: '{{ $cartService->getDiscountFormatted() }}',
        total: '{{ $cartService->getTotalFormatted() }}',
        coupon: {{ Js::from($initialCoupon ? ['code' => $initialCoupon->code, 'discount_type' => $initialCoupon->discount_type, 'discount_value' => $initialCoupon->discount_value] : null) }},
        routes: {
            add: '{{ route('cart.add') }}',
            remove: '{{ route('cart.remove') }}',
            coupon: '{{ route('cart.coupon') }}',
            couponRemove: '{{ route('cart.coupon.remove') }}',
            data: '{{ route('cart.data') }}',
            checkout: '{{ route('checkout') }}'
        }
    })"
    @open-cart.window="open()"
    @cart-updated.window="refresh()"
    @keydown.escape.window="close()"
    class="relative z-50"
    role="dialog" 
    aria-modal="true"
    x-cloak
>
    <!-- Backdrop -->
    <div 
        x-show="isOpen"
        x-transition:enter="ease-in-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in-out duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="close()"
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity"
        style="display: none;"
    ></div>

    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute inset-0 overflow-hidden">
            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                <!-- Slide-over panel -->
                <div 
                    x-show="isOpen"
                    x-transition:enter="transform transition ease-in-out duration-300 sm:duration-400"
                    x-transition:enter-start="translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transform transition ease-in-out duration-300 sm:duration-400"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="translate-x-full"
                    class="pointer-events-auto w-screen max-w-md border-l border-slate-800 bg-[#0b0f19] shadow-2xl flex flex-col justify-between"
                    style="display: none;"
                >
                    <!-- Drawer Header -->
                    <div class="px-6 py-5 border-b border-slate-800/80 flex items-center justify-between bg-slate-900/40">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-white tracking-tight flex items-center gap-2">
                                    <span>Shopping Cart</span>
                                    <span class="rounded bg-slate-800 px-2 py-0.5 text-[11px] font-mono text-emerald-400" x-text="count + ' item' + (count === 1 ? '' : 's')"></span>
                                </h2>
                                <p class="text-[11px] text-slate-400">Immediate digital license provisioning</p>
                            </div>
                        </div>

                        <button 
                            type="button" 
                            @click="close()" 
                            class="rounded-lg p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Drawer Body / Line Items -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-4">
                        
                        <!-- Empty Cart State -->
                        <template x-if="items.length === 0">
                            <div class="py-16 text-center space-y-4">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-slate-800 bg-slate-900/60 text-slate-500">
                                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                </div>
                                <div class="space-y-1">
                                    <h3 class="text-sm font-semibold text-slate-300">Your cart is empty</h3>
                                    <p class="text-xs text-slate-500 max-w-xs mx-auto">
                                        Select a developer kit, theme, or microservice architecture from the catalog.
                                    </p>
                                </div>
                                <a 
                                    href="{{ route('products.index') }}" 
                                    @click="close()"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-2 text-xs font-semibold text-emerald-400 hover:bg-emerald-500/20 transition-colors"
                                >
                                    <span>Browse Catalog</span>
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </template>

                        <!-- Line Items List -->
                        <template x-if="items.length > 0">
                            <div class="divide-y divide-slate-800/80">
                                <template x-for="item in items" :key="item.price_id">
                                    <div class="py-4 first:pt-0 last:pb-0 flex items-start justify-between gap-3 group">
                                        <div class="flex items-start gap-3 min-w-0">
                                            <!-- Mini thumbnail -->
                                            <div class="h-12 w-12 rounded-lg border border-slate-800 bg-slate-900/80 overflow-hidden shrink-0 flex items-center justify-center">
                                                <template x-if="item.thumbnail_url">
                                                    <img :src="item.thumbnail_url" :alt="item.product_title" class="h-full w-full object-cover">
                                                </template>
                                                <template x-if="!item.thumbnail_url">
                                                    <svg class="h-5 w-5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polyline points="16 18 22 12 16 6"></polyline>
                                                        <polyline points="8 6 2 12 8 18"></polyline>
                                                    </svg>
                                                </template>
                                            </div>

                                            <!-- Info -->
                                            <div class="min-w-0 flex-1">
                                                <a :href="'/products/' + item.product_slug" class="block text-xs font-semibold text-white hover:text-emerald-400 transition-colors truncate" x-text="item.product_title"></a>
                                                <div class="mt-1 flex items-center gap-2">
                                                    <span class="inline-flex rounded border border-slate-800 bg-slate-900 px-1.5 py-0.5 text-[10px] font-mono text-slate-300" x-text="item.tier_name"></span>
                                                    <span class="text-[11px] font-mono text-slate-400" x-text="'Qty: ' + item.quantity"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Price & Remove -->
                                        <div class="text-right shrink-0">
                                            <div class="text-xs font-mono font-bold text-emerald-400" x-text="item.line_total_formatted"></div>
                                            <button 
                                                type="button" 
                                                @click="removeItem(item.price_id)"
                                                class="mt-2 text-[11px] text-slate-500 hover:text-rose-400 transition-colors flex items-center gap-1 justify-end ml-auto"
                                                title="Remove item"
                                            >
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Remove</span>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                    </div>

                    <!-- Drawer Footer (Subtotal, Coupon & Checkout CTA) -->
                    <template x-if="items.length > 0">
                        <div class="border-t border-slate-800 bg-slate-900/60 p-6 space-y-4">
                            
                            <!-- Coupon Entry / Badge -->
                            <div class="space-y-2">
                                <template x-if="!coupon">
                                    <form @submit.prevent="applyCoupon()" class="flex gap-2">
                                        <input 
                                            type="text" 
                                            x-model="couponCode" 
                                            placeholder="Coupon (e.g. LAUNCH30)" 
                                            class="flex-1 rounded-lg border border-slate-800 bg-slate-900/90 px-3 py-1.5 text-xs text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500/30 uppercase font-mono"
                                        >
                                        <button 
                                            type="submit" 
                                            :disabled="isApplyingCoupon || !couponCode.trim()"
                                            class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-700 disabled:opacity-50 transition-colors"
                                        >
                                            <span x-show="!isApplyingCoupon">Apply</span>
                                            <span x-show="isApplyingCoupon" style="display: none;">...</span>
                                        </button>
                                    </form>
                                </template>

                                <template x-if="coupon">
                                    <div class="flex items-center justify-between rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5 text-xs">
                                        <div class="flex items-center gap-2">
                                            <svg class="h-3.5 w-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                            </svg>
                                            <span class="font-mono font-semibold text-emerald-400" x-text="coupon.code"></span>
                                            <span class="text-[11px] text-emerald-300">applied</span>
                                        </div>
                                        <button 
                                            type="button" 
                                            @click="removeCoupon()" 
                                            class="text-emerald-400 hover:text-emerald-200 p-1"
                                            title="Remove coupon"
                                        >
                                            &times;
                                        </button>
                                    </div>
                                </template>

                                <template x-if="couponFeedback">
                                    <p class="text-[11px]" :class="couponError ? 'text-rose-400' : 'text-emerald-400'" x-text="couponFeedback"></p>
                                </template>
                            </div>

                            <!-- Financial Breakdown -->
                            <div class="space-y-1.5 text-xs border-t border-slate-800/80 pt-3">
                                <div class="flex items-center justify-between text-slate-400">
                                    <span>Subtotal</span>
                                    <span class="font-mono text-slate-300" x-text="subtotal"></span>
                                </div>

                                <template x-if="coupon">
                                    <div class="flex items-center justify-between text-emerald-400">
                                        <span>Discount</span>
                                        <span class="font-mono" x-text="'-' + discount"></span>
                                    </div>
                                </template>

                                <div class="flex items-center justify-between pt-2 border-t border-slate-800 text-sm font-bold text-white">
                                    <span>Total Due</span>
                                    <span class="font-mono text-emerald-400 text-base" x-text="total"></span>
                                </div>
                            </div>

                            <!-- Checkout Action Button -->
                            <a 
                                :href="routes.checkout"
                                class="w-full flex items-center justify-center gap-2 rounded-lg bg-emerald-500 px-4 py-3 text-xs font-bold text-slate-950 hover:bg-emerald-400 transition-colors shadow-lg shadow-emerald-500/20"
                            >
                                <span>Proceed to Checkout</span>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>

                            <!-- Trust Icons Footnote -->
                            <p class="text-center text-[10px] text-slate-500">
                                256-Bit SSL &bull; Instant Signed Delivery &bull; 30-Day Guarantee
                            </p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('cartDrawerComponent', (config) => ({
            isOpen: config.isOpen,
            items: config.items,
            count: config.count,
            subtotal: config.subtotal,
            discount: config.discount,
            total: config.total,
            coupon: config.coupon,
            routes: config.routes,
            couponCode: '',
            couponFeedback: '',
            couponError: false,
            isApplyingCoupon: false,

            open() {
                this.isOpen = true;
                this.refresh();
            },

            close() {
                this.isOpen = false;
            },

            async refresh() {
                try {
                    const res = await fetch(this.routes.data);
                    if (res.ok) {
                        const data = await res.json();
                        this.items = data.items;
                        this.count = data.count;
                        this.subtotal = data.subtotal_formatted;
                        this.discount = data.discount_formatted;
                        this.total = data.total_formatted;
                        this.coupon = data.coupon;
                    }
                } catch (e) {
                    console.error('Failed to refresh cart data', e);
                }
            },

            async removeItem(priceId) {
                try {
                    const res = await fetch(this.routes.remove, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ price_id: priceId })
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.items = data.cart.items;
                        this.count = data.cart.count;
                        this.subtotal = data.cart.subtotal_formatted;
                        this.discount = data.cart.discount_formatted;
                        this.total = data.cart.total_formatted;
                        this.coupon = data.cart.coupon;
                        window.dispatchEvent(new CustomEvent('cart-count-updated', { detail: this.count }));
                    }
                } catch (e) {
                    console.error('Failed to remove cart item', e);
                }
            },

            async applyCoupon() {
                if (!this.couponCode.trim()) return;
                this.isApplyingCoupon = true;
                this.couponFeedback = '';
                this.couponError = false;

                try {
                    const res = await fetch(this.routes.coupon, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ code: this.couponCode })
                    });

                    const data = await res.json();
                    if (res.ok && data.success) {
                        this.items = data.cart.items;
                        this.count = data.cart.count;
                        this.subtotal = data.cart.subtotal_formatted;
                        this.discount = data.cart.discount_formatted;
                        this.total = data.cart.total_formatted;
                        this.coupon = data.cart.coupon;
                        this.couponFeedback = data.message;
                        this.couponError = false;
                        this.couponCode = '';
                    } else {
                        this.couponFeedback = data.message || 'Invalid coupon code.';
                        this.couponError = true;
                    }
                } catch (e) {
                    this.couponFeedback = 'Error applying coupon code.';
                    this.couponError = true;
                } finally {
                    this.isApplyingCoupon = false;
                }
            },

            async removeCoupon() {
                try {
                    const res = await fetch(this.routes.couponRemove, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'Accept': 'application/json'
                        }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.items = data.cart.items;
                        this.count = data.cart.count;
                        this.subtotal = data.cart.subtotal_formatted;
                        this.discount = data.cart.discount_formatted;
                        this.total = data.cart.total_formatted;
                        this.coupon = null;
                        this.couponFeedback = '';
                    }
                } catch (e) {
                    console.error('Failed to remove coupon', e);
                }
            }
        }));
    });
</script>
