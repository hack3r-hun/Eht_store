<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Account' }} — {{ shop_name() }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gradient-to-br from-brand-50 via-white to-slate-50 text-slate-800">
    <x-toast-stack />
    <div class="min-h-screen flex flex-col sm:justify-center items-center px-4 py-12">
        <a href="{{ route('home') }}" class="flex items-center gap-2 mb-8 group animate-fade-in-up">
            <div class="w-12 h-12 rounded-xl bg-brand-600 flex items-center justify-center text-white transition-transform duration-300 group-hover:scale-110">
                <x-icon name="bolt" class="w-6 h-6" />
            </div>
            <span class="text-2xl font-bold text-slate-900">{{ shop_name() }}</span>
        </a>

        <div class="w-full sm:max-w-md animate-fade-in-up animation-delay-100">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-soft p-8">
                <div class="mb-5">
                    <x-back-button :href="route('home')" label="Back" />
                </div>
                {{ $slot }}
            </div>
            <p class="text-center text-sm text-slate-500 mt-6">
                <a href="{{ route('home') }}" class="text-brand-700 font-medium hover:text-brand-800 transition-colors inline-flex items-center gap-1.5">
                    <x-icon name="home" class="w-4 h-4" /> Back to store
                </a>
            </p>
        </div>
    </div>
</body>
</html>
