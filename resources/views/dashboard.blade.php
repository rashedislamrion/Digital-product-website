<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900 dark:text-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Customer Digital Library</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Access your purchased digital products, license keys, PDF invoices, and submit support tickets.</p>
                    </div>
                    <a href="{{ route('customer.library') }}" class="inline-flex items-center justify-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-medium text-sm rounded-lg shadow-sm transition">
                        Open My Library &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
