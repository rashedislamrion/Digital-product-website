<x-storefront-layout title="Download Link Unavailable" meta-description="Download grant status and support guidance.">
    <div class="mx-auto max-w-xl px-4 py-16 sm:px-6 lg:px-8 text-center space-y-6">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>

        <div class="space-y-2">
            <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                This download link is no longer valid
            </h1>
            <p class="text-sm text-slate-400 max-w-md mx-auto">
                {{ $reason ?? 'Your download grant has expired, exceeded its allowed quota, or was revoked due to a refund.' }}
            </p>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-5 text-left text-xs space-y-3 font-mono">
            <div class="text-slate-300 font-bold uppercase tracking-wider text-[11px]">Need Help?</div>
            <p class="text-slate-400">
                If you believe this is an error or need additional download attempts, our technical support team can reset your link counter.
            </p>
            <div class="pt-2 border-t border-slate-800 flex items-center justify-between">
                <span class="text-slate-500">Contact Support:</span>
                <span class="text-indigo-400 font-semibold">support@digitalstorefront.test</span>
            </div>
        </div>

        <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a 
                href="{{ route('customer.library') }}" 
                class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-semibold text-white hover:bg-indigo-500 transition-colors shadow-lg shadow-indigo-600/20"
            >
                <span>Return to My Library</span>
                <svg class="ml-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>

            <a 
                href="{{ route('home') }}" 
                class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl border border-slate-700 bg-slate-900 px-5 py-2.5 text-xs font-semibold text-slate-300 hover:bg-slate-800 transition-colors"
            >
                Browse Storefront
            </a>
        </div>
    </div>
</x-storefront-layout>
