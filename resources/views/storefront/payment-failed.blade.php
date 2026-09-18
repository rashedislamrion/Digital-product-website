<x-storefront-layout title="Payment Failed" meta-description="Payment transaction could not be completed.">
    
    <div class="mx-auto max-w-2xl px-4 py-16 sm:px-6 lg:px-8 text-center">
        
        <div class="rounded-2xl border border-rose-500/30 bg-slate-900/60 p-8 sm:p-12 space-y-6 shadow-2xl">
            
            <!-- Icon -->
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-rose-500/10 text-rose-400 border border-rose-500/20">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <!-- Heading & Notice -->
            <div class="space-y-2">
                <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Payment Authorization Failed</h1>
                <p class="text-xs sm:text-sm text-slate-400 max-w-md mx-auto">
                    The payment transaction was declined by your issuing institution, cancelled, or failed server-side validation. Your account has not been charged.
                </p>
            </div>

            @if($orderNumber)
                <div class="rounded-xl border border-slate-800 bg-slate-950/80 p-3.5 max-w-sm mx-auto flex items-center justify-between text-xs">
                    <span class="text-slate-400">Order Reference:</span>
                    <span class="font-mono font-bold text-slate-200">{{ $orderNumber }}</span>
                </div>
            @endif

            @if($reason)
                <p class="text-xs text-amber-400/90 font-mono bg-amber-500/10 py-1.5 px-3 rounded-lg max-w-md mx-auto border border-amber-500/20">
                    {{ $reason }}
                </p>
            @endif

            <!-- Next Steps / CTAs -->
            <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a 
                    href="{{ route('products.index') }}" 
                    class="w-full sm:w-auto rounded-xl bg-emerald-500 px-6 py-3 text-xs font-bold text-slate-950 hover:bg-emerald-400 transition-colors shadow-lg shadow-emerald-500/20"
                >
                    Browse Catalog & Try Again
                </a>
                
                <a 
                    href="{{ route('home') }}" 
                    class="w-full sm:w-auto rounded-xl border border-slate-800 bg-slate-900 px-6 py-3 text-xs font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition-colors"
                >
                    Return to Homepage
                </a>
            </div>

            <p class="text-[11px] text-slate-500 pt-2">
                Need assistance? Our support engineers are available at <span class="font-mono text-slate-400">support@example.com</span>.
            </p>

        </div>

    </div>

</x-storefront-layout>
