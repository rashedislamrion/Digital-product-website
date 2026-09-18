<x-filament-panels::page>
    <form wire:submit.prevent="save" class="space-y-6">
        <!-- Section 1: Store Details -->
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Store Branding & Localization
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Store Name</label>
                    <input type="text" wire:model.defer="store_name" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Support Email</label>
                    <input type="email" wire:model.defer="support_email" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Default Display Currency</label>
                    <select wire:model.defer="default_currency" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500">
                        <option value="USD">USD ($)</option>
                        <option value="BDT">BDT (৳)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Section 2: Cloud Storage Telemetry -->
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                <svg class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z" />
                </svg>
                Secure Cloud Storage (Read-Only Telemetry)
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                AWS credentials and IAM secrets are protected and loaded strictly from server environment configuration.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Configured S3 Bucket</label>
                    <input type="text" value="{{ $s3_bucket }}" disabled class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-100 dark:bg-gray-800/60 px-3 py-2 text-xs font-mono text-gray-600 dark:text-gray-300 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">AWS Region</label>
                    <input type="text" value="{{ $s3_region }}" disabled class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-100 dark:bg-gray-800/60 px-3 py-2 text-xs font-mono text-gray-600 dark:text-gray-300 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Storage Endpoint</label>
                    <input type="text" value="{{ $s3_endpoint }}" disabled class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-100 dark:bg-gray-800/60 px-3 py-2 text-xs font-mono text-gray-600 dark:text-gray-300 cursor-not-allowed">
                </div>
            </div>
        </div>

        <!-- Section 3: Enabled Payment Gateways -->
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                Payment Gateways Activation
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex items-center justify-between p-4 rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 cursor-pointer">
                    <div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">SSLCOMMERZ (Bangladesh Aggregator)</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Card, bKash, Rocket, and Internet Banking</div>
                    </div>
                    <input type="checkbox" wire:model.defer="gateway_sslcommerz" class="h-5 w-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                </label>

                <label class="flex items-center justify-between p-4 rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 cursor-pointer">
                    <div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">bKash Direct URL Checkout</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Direct mobile wallet payment gateway</div>
                    </div>
                    <input type="checkbox" wire:model.defer="gateway_bkash" class="h-5 w-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                </label>

                <label class="flex items-center justify-between p-4 rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 cursor-pointer">
                    <div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">Stripe Checkout</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">International cards, Apple Pay & Google Pay</div>
                    </div>
                    <input type="checkbox" wire:model.defer="gateway_stripe" class="h-5 w-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                </label>

                <label class="flex items-center justify-between p-4 rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 cursor-pointer">
                    <div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">Paddle (Merchant of Record)</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Global sales tax & VAT liability handling</div>
                    </div>
                    <input type="checkbox" wire:model.defer="gateway_paddle" class="h-5 w-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                </label>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center justify-center px-6 py-2.5 bg-amber-600 hover:bg-amber-500 text-white font-medium text-sm rounded-lg shadow-sm transition">
                Save Store Settings
            </button>
        </div>
    </form>
</x-filament-panels::page>
