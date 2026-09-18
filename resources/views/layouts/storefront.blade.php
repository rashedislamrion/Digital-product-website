<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'DevStore') }} - Premium Digital Products & Developer Kits</title>
    <meta name="description" content="{{ $metaDescription ?? 'Production-grade software architectures, developer starter kits, and full-stack components with perpetual commercial licensing.' }}">

    <!-- Google Fonts: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#090d16] text-slate-100 font-sans antialiased selection:bg-emerald-500 selection:text-slate-950 flex flex-col justify-between">
    
    <!-- Top Announcement Banner -->
    <div class="border-b border-emerald-500/20 bg-emerald-950/40 px-4 py-1.5 text-center text-xs text-emerald-300">
        <span class="inline-flex items-center gap-1.5">
            <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Laravel 13.x & PHP 8.3 Ready &bull; Instant Private S3 Signed Downloads &bull; Zero Vendor Lock-in</span>
        </span>
    </div>

    <!-- Header Navigation -->
    <x-storefront.header :categories="$categories ?? collect()" />

    <!-- Main Content -->
    <main class="flex-grow">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <x-storefront.footer />

    <!-- Slide-over Cart Drawer -->
    <x-storefront.cart-drawer />

</body>
</html>
