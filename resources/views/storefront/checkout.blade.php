<x-storefront-layout title="Secure Checkout" meta-description="Complete your digital license purchase. Immediate private delivery.">
    
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        
        <!-- Breadcrumb / Header -->
        <div class="mb-8">
            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mb-2">
                <a href="{{ route('home') }}" class="hover:text-slate-300 transition-colors">Home</a>
                <span>/</span>
                <a href="{{ route('products.index') }}" class="hover:text-slate-300 transition-colors">Catalog</a>
                <span>/</span>
                <span class="text-emerald-400">Checkout</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white flex items-center gap-3">
                <span>Single-Step Express Checkout</span>
                <span class="rounded border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-xs font-mono font-medium text-emerald-400">
                    256-Bit SSL Encrypted
                </span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Zero friction digital goods checkout. No shipping address or physical fields required.
            </p>
        </div>

        @if($errors->any())
            <div class="mb-6 rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 text-xs text-rose-300">
                <div class="font-semibold flex items-center gap-2 mb-1 text-rose-200">
                    <svg class="h-4 w-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Please correct the following errors to proceed:</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 ml-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
            
            <!-- Left 7 Columns: Minimal Digital Checkout Form -->
            <div class="lg:col-span-7 space-y-6">
                
                <form id="checkout-form" action="{{ route('checkout.process') }}" method="POST" class="space-y-6">
                    @csrf

                    <!-- Section 1: Customer Account & Delivery Email -->
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-bold font-mono border border-emerald-500/20">
                                    1
                                </div>
                                <h2 class="text-sm font-bold text-white uppercase tracking-wider">License Delivery Destination</h2>
                            </div>
                            <span class="text-[11px] text-slate-400">Digital fulfillment</span>
                        </div>

                        <div>
                            <label for="email" class="block text-xs font-medium text-slate-300 mb-1.5">
                                Email Address <span class="text-emerald-400">*</span>
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                required
                                value="{{ old('email', $defaultEmail) }}"
                                placeholder="developer@company.com" 
                                class="w-full rounded-lg border border-slate-800 bg-slate-900/90 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500/30 font-mono"
                            >
                            <p class="mt-1.5 text-[11px] text-slate-400">
                                Your expiring S3 download grants and cryptographic license keys are delivered immediately to this address.
                            </p>
                        </div>

                        <!-- Country Selection (Digital Goods Tax & Currency Context) -->
                        <div>
                            <label for="country" class="block text-xs font-medium text-slate-300 mb-1.5">
                                Country of Residence / Registration <span class="text-emerald-400">*</span>
                            </label>
                            <select 
                                id="country" 
                                name="country" 
                                required
                                class="w-full rounded-lg border border-slate-800 bg-slate-900/90 px-3.5 py-2.5 text-xs text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500/30 font-sans"
                            >
                                @foreach($countries as $c)
                                    <option value="{{ $c }}" {{ old('country', 'Bangladesh') === $c ? 'selected' : '' }}>
                                        {{ $c }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-slate-400">
                                Used for international tax exemption and payment aggregator compliance.
                            </p>
                        </div>
                    </div>

                    <!-- Section 2: Payment Gateway Selection -->
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-6 space-y-4" x-data="{
                        country: '{{ old('country', 'Bangladesh') }}',
                        method: '{{ old('payment_method', 'sslcommerz') }}',
                        setMethod(m) {
                            this.method = m;
                        }
                    }">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-bold font-mono border border-emerald-500/20">
                                    2
                                </div>
                                <h2 class="text-sm font-bold text-white uppercase tracking-wider">Payment Method</h2>
                            </div>
                            <span class="text-[11px] font-mono text-slate-400" x-text="method === 'sslcommerz' ? 'SSLCOMMERZ v4 Aggregator' : 'Paddle Billing v2 (MoR)'"></span>
                        </div>

                        <!-- Payment Method Toggle / Selector -->
                        <div class="grid grid-cols-2 gap-2 p-1 rounded-xl bg-slate-950/60 border border-slate-800">
                            <button 
                                type="button" 
                                @click="setMethod('sslcommerz')"
                                :class="method === 'sslcommerz' ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'text-slate-400 hover:text-white border border-transparent'"
                                class="py-2 px-3 rounded-lg text-xs font-medium transition-all text-center flex items-center justify-center gap-2"
                            >
                                <span>Local (Bangladesh)</span>
                                <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-emerald-500/20 text-emerald-300">bKash</span>
                            </button>
                            <button 
                                type="button" 
                                @click="setMethod('paddle')"
                                :class="method === 'paddle' ? 'bg-indigo-500/15 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-white border border-transparent'"
                                class="py-2 px-3 rounded-lg text-xs font-medium transition-all text-center flex items-center justify-center gap-2"
                            >
                                <span>International</span>
                                <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-indigo-500/20 text-indigo-300">Paddle</span>
                            </button>
                        </div>

                        <div class="space-y-3">
                            <!-- SSLCOMMERZ Option -->
                            <label 
                                @click="setMethod('sslcommerz')"
                                :class="method === 'sslcommerz' ? 'border-emerald-500/60 bg-emerald-500/5' : 'border-slate-800 bg-slate-900/30 opacity-70 hover:opacity-100'"
                                class="relative block rounded-xl border p-4 cursor-pointer transition-all"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-3">
                                        <input 
                                            type="radio" 
                                            name="payment_method" 
                                            value="sslcommerz" 
                                            x-model="method"
                                            class="h-4 w-4 text-emerald-500 focus:ring-emerald-500 border-slate-700 bg-slate-900"
                                        >
                                        <div>
                                            <div class="text-xs font-bold text-white flex items-center gap-2">
                                                <span>SSLCOMMERZ / bKash Aggregator</span>
                                                <span class="rounded bg-emerald-500/20 px-1.5 py-0.5 text-[10px] font-mono text-emerald-400">Bangladesh Only</span>
                                            </div>
                                            <p class="text-[11px] text-slate-400 mt-0.5">
                                                Pay in BDT with bKash, Rocket, Nagad via DBBL, local Visa, Mastercard, and Bangladesh Internet Banking.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Supported Brand Badges -->
                                <div class="mt-3.5 flex flex-wrap items-center gap-2 border-t border-slate-800/80 pt-3">
                                    <span class="rounded border border-slate-800 bg-slate-900/80 px-2 py-0.5 text-[10px] font-mono text-slate-300">bKash</span>
                                    <span class="rounded border border-slate-800 bg-slate-900/80 px-2 py-0.5 text-[10px] font-mono text-slate-300">Visa</span>
                                    <span class="rounded border border-slate-800 bg-slate-900/80 px-2 py-0.5 text-[10px] font-mono text-slate-300">Mastercard</span>
                                    <span class="rounded border border-slate-800 bg-slate-900/80 px-2 py-0.5 text-[10px] font-mono text-slate-300">Rocket / DBBL</span>
                                    <span class="rounded border border-slate-800 bg-slate-900/80 px-2 py-0.5 text-[10px] font-mono text-slate-300">City Bank / Brac</span>
                                </div>
                            </label>

                            <!-- Paddle Option -->
                            <label 
                                @click="setMethod('paddle')"
                                :class="method === 'paddle' ? 'border-indigo-500/60 bg-indigo-500/5' : 'border-slate-800 bg-slate-900/30 opacity-70 hover:opacity-100'"
                                class="relative block rounded-xl border p-4 cursor-pointer transition-all"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-3">
                                        <input 
                                            type="radio" 
                                            name="payment_method" 
                                            value="paddle" 
                                            x-model="method"
                                            class="h-4 w-4 text-indigo-500 focus:ring-indigo-500 border-slate-700 bg-slate-900"
                                        >
                                        <div>
                                            <div class="text-xs font-bold text-white flex items-center gap-2">
                                                <span>Pay with Card (Paddle)</span>
                                                <span class="rounded bg-indigo-500/20 px-1.5 py-0.5 text-[10px] font-mono text-indigo-400">Global MoR</span>
                                            </div>
                                            <p class="text-[11px] text-slate-400 mt-0.5">
                                                International cards (Visa, Mastercard, Amex), Apple Pay, Google Pay, and PayPal with automated global tax/VAT remittance.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Supported Brand Badges -->
                                <div class="mt-3.5 flex flex-wrap items-center gap-2 border-t border-slate-800/80 pt-3">
                                    <span class="rounded border border-slate-800 bg-slate-900/80 px-2 py-0.5 text-[10px] font-mono text-slate-300">Credit / Debit Cards</span>
                                    <span class="rounded border border-slate-800 bg-slate-900/80 px-2 py-0.5 text-[10px] font-mono text-slate-300">Apple Pay</span>
                                    <span class="rounded border border-slate-800 bg-slate-900/80 px-2 py-0.5 text-[10px] font-mono text-slate-300">Google Pay</span>
                                    <span class="rounded border border-slate-800 bg-slate-900/80 px-2 py-0.5 text-[10px] font-mono text-slate-300">PayPal</span>
                                    <span class="rounded border border-slate-800 bg-slate-900/80 px-2 py-0.5 text-[10px] font-mono text-slate-300">VAT/GST Remitted</span>
                                </div>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button 
                            type="submit" 
                            class="w-full flex items-center justify-center gap-2 rounded-xl bg-emerald-500 py-4 px-6 text-sm font-bold text-slate-950 hover:bg-emerald-400 shadow-xl shadow-emerald-500/25 transition-all duration-150 hover:-translate-y-0.5"
                        >
                            <span x-text="method === 'sslcommerz' ? 'Authorize Payment with SSLCOMMERZ' : 'Proceed to Card Checkout (Paddle)'"></span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center justify-center gap-4 text-[11px] text-slate-500 pt-1">
                        <span class="flex items-center gap-1">
                            <svg class="h-3.5 w-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span>Mandatory Server-Side Order Validation</span>
                        </span>
                        <span>&bull;</span>
                        <span>30-Day Money-Back Guarantee</span>
                    </div>
                </form>

            </div>

            <!-- Right 5 Columns: Order Summary & Coupon Engine -->
            <div class="lg:col-span-5 space-y-6">
                
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 space-y-6 sticky top-24">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider border-b border-slate-800 pb-4">
                        Order Summary
                    </h2>

                    <!-- Items List -->
                    <div class="divide-y divide-slate-800/80 max-h-72 overflow-y-auto pr-1">
                        @foreach($items as $item)
                            <div class="py-3 first:pt-0 last:pb-0 flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-xs font-semibold text-white truncate">{{ $item->product->title }}</h4>
                                    <div class="mt-0.5 flex items-center gap-2">
                                        <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-mono text-slate-300">{{ $item->tier_name }}</span>
                                        <span class="text-[11px] font-mono text-slate-500">Qty: {{ $item->quantity }}</span>
                                    </div>
                                </div>
                                <div class="text-xs font-bold font-mono text-emerald-400 shrink-0">
                                    {{ $item->line_total_formatted }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Coupon Code Box -->
                    <div class="border-t border-slate-800 pt-4 space-y-2">
                        @if($appliedCoupon)
                            <div class="flex items-center justify-between rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-xs">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    <span class="font-mono font-bold text-emerald-400">{{ $appliedCoupon->code }}</span>
                                    <span class="text-[11px] text-emerald-300">
                                        ({{ $appliedCoupon->discount_type === 'percent' ? $appliedCoupon->discount_value.'%' : '$'.number_format($appliedCoupon->discount_value/100, 2) }} off)
                                    </span>
                                </div>
                                <form action="{{ route('cart.coupon.remove') }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-emerald-400 hover:text-emerald-200 font-bold px-1" title="Remove coupon">
                                        &times;
                                    </button>
                                </form>
                            </div>
                        @else
                            <form action="{{ route('cart.coupon') }}" method="POST" class="flex gap-2">
                                @csrf
                                <input 
                                    type="text" 
                                    name="code" 
                                    required
                                    placeholder="Coupon (e.g. LAUNCH30)" 
                                    class="flex-1 rounded-lg border border-slate-800 bg-slate-900/90 px-3 py-2 text-xs text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none uppercase font-mono"
                                >
                                <button 
                                    type="submit" 
                                    class="rounded-lg bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-200 hover:bg-slate-700 transition-colors"
                                >
                                    Apply
                                </button>
                            </form>
                        @endif
                    </div>

                    <!-- Totals Breakdown -->
                    <div class="border-t border-slate-800 pt-4 space-y-2 text-xs">
                        <div class="flex items-center justify-between text-slate-400">
                            <span>Subtotal</span>
                            <span class="font-mono text-slate-300">{{ $subtotalFormatted }}</span>
                        </div>

                        @if($appliedCoupon)
                            <div class="flex items-center justify-between text-emerald-400">
                                <span>Discount ({{ $appliedCoupon->code }})</span>
                                <span class="font-mono">-{{ $discountFormatted }}</span>
                            </div>
                        @endif

                        <div class="flex items-center justify-between text-slate-400">
                            <span>Sales Tax / VAT</span>
                            <span class="font-mono text-slate-400">$0.00 (Exempt)</span>
                        </div>

                        <div class="flex items-center justify-between border-t border-slate-800/80 pt-3 text-sm font-bold text-white">
                            <span>Total Due</span>
                            <span class="font-mono text-emerald-400 text-lg">{{ $totalFormatted }}</span>
                        </div>
                    </div>

                    <!-- Security & Entitlement Notice -->
                    <div class="rounded-xl border border-slate-800/80 bg-slate-950/60 p-3.5 space-y-2 text-[11px] text-slate-400">
                        <div class="flex items-start gap-2">
                            <svg class="h-4 w-4 text-emerald-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Immediate access via signed expiring S3 download grants</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <svg class="h-4 w-4 text-emerald-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Perpetual commercial license with source code included</span>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

</x-storefront-layout>
