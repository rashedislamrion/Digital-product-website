<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Orders & Digital Library - {{ config('app.name', 'Digital Storefront') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 antialiased min-h-screen">
    <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 py-4 px-6 sm:px-12 flex justify-between items-center">
        <a href="/" class="text-xl font-bold tracking-tight text-indigo-600 dark:text-indigo-400">
            Digital Storefront
        </a>
        <div class="flex items-center space-x-4 text-sm">
            @auth
                <span>{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">Log Out</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-gray-600 dark:text-gray-300 hover:underline">Log in</a>
                <a href="{{ route('register') }}" class="text-indigo-600 dark:text-indigo-400 font-medium hover:underline">Register</a>
            @endauth
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <!-- Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">Your Digital Library & Orders</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Showing purchases associated with <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $customer->email }}</span>
                </p>
            </div>
            @if($isGuest)
                <div class="mt-4 sm:mt-0">
                    <a href="{{ route('customer.set-password') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Set Password & Create Account
                    </a>
                </div>
            @endif
        </div>

        @if($isGuest)
            <div class="mb-8 p-4 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 rounded-lg flex items-start space-x-3">
                <div class="text-indigo-600 dark:text-indigo-400 text-xl font-bold">ℹ</div>
                <div class="text-sm text-indigo-900 dark:text-indigo-200">
                    <span class="font-semibold">Guest access session active:</span> You are accessing your purchases through a verified magic link. To log in anytime with a password, you can <a href="{{ route('customer.set-password') }}" class="underline font-bold hover:text-indigo-700 dark:hover:text-indigo-100">set a password for your account</a>.
                </div>
            </div>
        @endif

        @if(session('status'))
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 text-sm text-green-800 dark:text-green-200 rounded-lg">
                {{ session('status') }}
            </div>
        @endif

        @if($orders->isEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center border border-gray-200 dark:border-gray-700 shadow-sm">
                <p class="text-gray-500 dark:text-gray-400">No orders found for this profile yet.</p>
                <a href="/" class="mt-4 inline-block text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                    Browse Storefront &rarr;
                </a>
            </div>
        @else
            <div class="space-y-6">
                @foreach($orders as $order)
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
                        <!-- Order Summary Top Bar -->
                        <div class="bg-gray-100 dark:bg-gray-700/50 px-6 py-4 flex flex-wrap justify-between items-center gap-4 text-sm">
                            <div>
                                <span class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Order</span>
                                <span class="font-mono font-bold">{{ $order->order_number }}</span>
                            </div>
                            <div>
                                <span class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Date</span>
                                <span>{{ $order->created_at->format('M d, Y') }}</span>
                            </div>
                            <div>
                                <span class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Total</span>
                                <span class="font-semibold">{{ $order->total_formatted }}</span>
                            </div>
                            <div>
                                <span class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Status</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/60 dark:text-green-300">
                                    {{ ucfirst($order->status->value) }}
                                </span>
                            </div>
                        </div>

                        <!-- Order Items List -->
                        <div class="divide-y divide-gray-100 dark:divide-gray-700 px-6 py-2">
                            @foreach($order->items as $item)
                                <div class="py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                    <div>
                                        <h3 class="font-bold text-base text-gray-900 dark:text-white">
                                            {{ $item->historical_product_title }}
                                        </h3>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 space-x-2">
                                            <span>Tier: {{ $item->historical_tier_name }}</span>
                                            <span>&bull;</span>
                                            <span>Amount: {{ $item->unit_amount_formatted }}</span>
                                        </div>

                                        @if($item->license)
                                            <div class="mt-2 p-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded text-xs font-mono">
                                                <span class="text-gray-500 dark:text-gray-400">License:</span>
                                                <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $item->license->license_key_masked }}</span>
                                                <span class="text-gray-500 dark:text-gray-400 ml-2">({{ $item->license->current_activations_count }} / {{ $item->license->max_activations }} seats active)</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex items-center space-x-3">
                                        @forelse($item->downloadGrants as $grant)
                                            <div class="text-right">
                                                <span class="text-xs text-gray-500 block mb-1">
                                                    {{ $grant->download_count }} of {{ $grant->max_download_attempts }} downloads used
                                                </span>
                                                <button class="px-3 py-1.5 bg-indigo-600 text-white rounded text-xs font-semibold hover:bg-indigo-700">
                                                    Download Release
                                                </button>
                                            </div>
                                        @empty
                                            <span class="text-xs text-gray-400">Delivery via Email</span>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </main>
</body>
</html>
