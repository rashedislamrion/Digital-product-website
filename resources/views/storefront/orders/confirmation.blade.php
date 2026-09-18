<x-storefront-layout title="Order Confirmation" meta-description="Order receipt and license fulfillment details.">
    
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8 space-y-8">
        
        <!-- Header Banner -->
        <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/5 p-6 sm:p-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Order Confirmed & Verified</h1>
                    <p class="text-xs sm:text-sm text-slate-300">
                        Thank you for your purchase. A copy of this receipt has been emailed to 
                        <span class="font-mono text-emerald-400 font-semibold">{{ $order->customer->email }}</span>.
                    </p>
                </div>
            </div>

            <!-- Status Badge -->
            <div class="shrink-0 flex sm:flex-col items-center sm:items-end gap-2">
                <span class="text-[11px] text-slate-400 uppercase tracking-wider font-mono">Payment Status</span>
                @if($order->status === \App\Enums\OrderStatus::Paid)
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/40 bg-emerald-500/10 px-3 py-1 text-xs font-bold font-mono text-emerald-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        PAID & VERIFIED
                    </span>
                @elseif($order->status === \App\Enums\OrderStatus::Pending)
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-500/40 bg-amber-500/10 px-3 py-1 text-xs font-bold font-mono text-amber-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                        PAYMENT PENDING
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-rose-500/40 bg-rose-500/10 px-3 py-1 text-xs font-bold font-mono text-rose-400">
                        {{ strtoupper($order->status->value) }}
                    </span>
                @endif
            </div>
        </div>

        <!-- Order Metadata Bar -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 rounded-xl border border-slate-800 bg-slate-900/40 p-4 text-xs font-mono">
            <div>
                <span class="text-slate-500 block text-[11px] uppercase tracking-wider">Order Reference</span>
                <span class="text-white font-bold">{{ $order->order_number }}</span>
            </div>
            <div>
                <span class="text-slate-500 block text-[11px] uppercase tracking-wider">Date Placed</span>
                <span class="text-slate-300">{{ $order->created_at->format('M d, Y - H:i') }}</span>
            </div>
            <div>
                <span class="text-slate-500 block text-[11px] uppercase tracking-wider">Payment Gateway</span>
                <span class="text-slate-300 uppercase">{{ $order->payment_gateway ?? 'SSLCOMMERZ' }}</span>
            </div>
            <div>
                <span class="text-slate-500 block text-[11px] uppercase tracking-wider">Total Billed</span>
                <span class="text-emerald-400 font-bold">{{ $order->total_formatted }}</span>
            </div>
        </div>

        <!-- Post-Purchase Digital Entitlements Placeholder (Phase 6 boundary) -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 sm:p-8 space-y-6">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div class="flex items-center gap-2.5">
                    <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider">Digital Assets & Entitlements</h2>
                </div>
                <span class="text-[11px] font-mono text-slate-400">Automated Provisioning Engine</span>
            </div>

            @if($order->status === \App\Enums\OrderStatus::Paid)
                @php
                    $hasEntitlements = $order->items->pluck('downloadGrants')->flatten()->isNotEmpty() 
                        || $order->items->pluck('license')->filter()->isNotEmpty() 
                        || !empty($rawLicenses);
                @endphp

                @if($hasEntitlements)
                    <!-- Fulfillment Active Items Container -->
                    <div class="space-y-4">
                        @foreach($order->items as $item)
                            @php
                                $rawKey = $rawLicenses[$item->id]['raw_key'] ?? null;
                                $displayKey = $rawKey ?? $item->license?->license_key_masked;
                            @endphp
                            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-5 space-y-4">
                                <!-- Item Header -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800 pb-3">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-base font-bold text-white">{{ $item->historical_product_title }}</h3>
                                            <span class="rounded bg-slate-800 px-2 py-0.5 text-[10px] font-mono text-emerald-400 border border-slate-700">
                                                {{ $item->historical_tier_name }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-400 mt-0.5">
                                            Perpetual license &bull; Snapshot version: {{ $item->version?->version_number ?? 'Latest' }}
                                        </p>
                                    </div>

                                    <!-- Download Button & Quota -->
                                    <div class="flex flex-wrap items-center gap-3">
                                        @forelse($item->downloadGrants as $grant)
                                            <div class="flex items-center gap-2.5">
                                                <span class="text-[11px] font-mono text-slate-400">
                                                    {{ $grant->download_count }}/{{ $grant->max_download_attempts }} used
                                                </span>
                                                <a 
                                                    href="{{ route('library.download', ['download_grant' => $grant->id]) }}" 
                                                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-emerald-400 transition-all shadow-md shadow-emerald-500/20"
                                                >
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                    </svg>
                                                    <span>Download ZIP</span>
                                                </a>
                                            </div>
                                        @empty
                                            <span class="text-xs text-slate-500">Processing digital asset...</span>
                                        @endforelse
                                    </div>
                                </div>

                                <!-- License Key Card (If software) -->
                                @if($item->license || $rawKey)
                                    <div 
                                        x-data="{ copied: false, copiedCmd: false }" 
                                        class="rounded-xl border border-indigo-500/30 bg-indigo-950/30 p-4 space-y-3"
                                    >
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                            <div class="flex items-center gap-2">
                                                <svg class="h-4 w-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                                </svg>
                                                <span class="text-xs font-bold uppercase tracking-wider text-indigo-300">
                                                    Software License Key
                                                </span>
                                                <span class="rounded bg-indigo-500/20 px-2 py-0.5 text-[10px] font-mono text-indigo-300 border border-indigo-500/30">
                                                    {{ $item->license?->max_activations ?? 1 }} seat(s)
                                                </span>
                                                @if($rawKey)
                                                    <span class="rounded bg-emerald-500/20 px-2 py-0.5 text-[10px] font-mono text-emerald-300 border border-emerald-500/30">
                                                        One-Time Display
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <button 
                                                    @click="navigator.clipboard.writeText('{{ $displayKey }}'); copied = true; setTimeout(() => copied = false, 2500)" 
                                                    type="button" 
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-500/40 bg-indigo-600/20 px-3 py-1 text-xs font-mono font-medium text-indigo-200 hover:bg-indigo-600/40 transition-colors"
                                                >
                                                    <svg x-show="!copied" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                                    </svg>
                                                    <svg x-show="copied" x-cloak class="h-3.5 w-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    <span x-text="copied ? 'Copied to Clipboard!' : 'Copy License Key'"></span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Key Display Field -->
                                        <div class="flex items-center justify-between rounded-lg border border-indigo-500/20 bg-slate-950 p-3 font-mono text-sm sm:text-base font-bold text-emerald-400 select-all tracking-wider">
                                            <span>{{ $displayKey }}</span>
                                        </div>

                                        <!-- Quick-Start Activation Snippet -->
                                        <div class="space-y-1.5 pt-1">
                                            <div class="flex items-center justify-between text-[11px] text-slate-400">
                                                <span>Quick Activation Command (REST API):</span>
                                                <button 
                                                    @click="navigator.clipboard.writeText('curl -X POST {{ url('/api/v1/licenses/activate') }} -H &quot;Content-Type: application/json&quot; -d \'{\&quot;license_key\&quot;:\&quot;{{ $displayKey }}\&quot;,\&quot;instance_fingerprint\&quot;:\&quot;' + window.location.hostname + '\&quot;,\&quot;hostname\&quot;:\&quot;' + window.location.hostname + '\&quot;}\''); copiedCmd = true; setTimeout(() => copiedCmd = false, 2500)"
                                                    type="button" 
                                                    class="text-indigo-400 hover:text-indigo-300 transition-colors"
                                                    x-text="copiedCmd ? 'Command Copied!' : 'Copy cURL'"
                                                ></button>
                                            </div>
                                            <div class="rounded-lg bg-slate-950 p-2.5 font-mono text-[11px] text-slate-300 overflow-x-auto border border-slate-800">
                                                <code>curl -X POST {{ url('/api/v1/licenses/activate') }} \<br>
&nbsp;&nbsp;-H "Content-Type: application/json" \<br>
&nbsp;&nbsp;-d '{"license_key":"{{ $displayKey }}","instance_fingerprint":"YOUR_MACHINE_HASH","hostname":"localhost"}'</code>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <!-- In-flight fulfillment state (before queue processes entitlements) -->
                    <div class="rounded-xl border border-indigo-500/20 bg-indigo-500/5 p-5 text-center space-y-3">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-sm font-bold text-white">Your files and license keys will appear here shortly.</h3>
                            <p class="text-xs text-slate-400 max-w-md mx-auto">
                                The automated fulfillment queue is generating your private expiring S3 signed download tokens and signing your cryptographic hardware license keys.
                            </p>
                        </div>
                        <div class="pt-2">
                            <a 
                                href="{{ route('customer.library') }}" 
                                class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-500 transition-colors"
                            >
                                <span>Open My Download Library</span>
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Itemized Products Snapshot -->
                    <div class="space-y-3 pt-2">
                        <h3 class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Licensed Products</h3>
                        <div class="divide-y divide-slate-800/80 rounded-xl border border-slate-800/80 bg-slate-950/40 px-4">
                            @foreach($order->items as $item)
                                <div class="py-3.5 flex items-center justify-between gap-4">
                                    <div>
                                        <h4 class="text-xs font-bold text-white">{{ $item->historical_product_title }}</h4>
                                        <div class="mt-0.5 flex items-center gap-2">
                                            <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-mono text-emerald-400">
                                                {{ $item->historical_tier_name }}
                                            </span>
                                            <span class="text-[11px] font-mono text-slate-500">Perpetual License</span>
                                        </div>
                                    </div>
                                    <div class="text-xs font-mono font-bold text-slate-200">
                                        ${{ number_format($item->unit_amount_minor / 100, 2) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="rounded-xl border border-amber-500/20 bg-amber-500/5 p-5 text-center space-y-2">
                    <h3 class="text-sm font-bold text-amber-300">Awaiting Payment Confirmation</h3>
                    <p class="text-xs text-slate-400 max-w-md mx-auto">
                        Once your payment is validated via the gateway Order Validation API, your secure download grants and license keys will unlock immediately.
                    </p>
                </div>
            @endif

            <!-- Financial Summary -->
            <div class="rounded-xl border border-slate-800/80 bg-slate-950/40 p-4 space-y-2 text-xs">
                <div class="flex items-center justify-between text-slate-400">
                    <span>Subtotal</span>
                    <span class="font-mono text-slate-300">{{ $order->subtotal_formatted }}</span>
                </div>
                @if($order->discount_minor > 0)
                    <div class="flex items-center justify-between text-emerald-400">
                        <span>Discount {{ $order->coupon_code ? '('.$order->coupon_code.')' : '' }}</span>
                        <span class="font-mono">-{{ $order->discount_formatted }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between text-slate-400">
                    <span>Tax / VAT</span>
                    <span class="font-mono text-slate-400">$0.00 (Exempt)</span>
                </div>
                <div class="flex items-center justify-between border-t border-slate-800 pt-2 text-sm font-bold text-white">
                    <span>Total Paid</span>
                    <span class="font-mono text-emerald-400">{{ $order->total_formatted }}</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-800">
                <a 
                    href="{{ route('products.index') }}" 
                    class="text-xs font-medium text-slate-400 hover:text-white flex items-center gap-1.5 transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Continue Browsing Catalog</span>
                </a>

                <a 
                    href="{{ route('customer.library') }}" 
                    class="w-full sm:w-auto rounded-xl bg-emerald-500 px-5 py-2.5 text-xs font-bold text-slate-950 hover:bg-emerald-400 transition-colors shadow-md shadow-emerald-500/20 text-center"
                >
                    View in Customer Library
                </a>
            </div>

        </div>

    </div>

</x-storefront-layout>
