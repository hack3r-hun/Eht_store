<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', shop_name()) - {{ shop_config('tagline') }}</title>
    <meta name="description" content="@yield('meta_description', shop_config('tagline'))">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-oat-100/45 text-charcoal-700" x-data="{ mobileOpen: false }">
    <x-toast-stack />
    {{-- Top bar --}}
    <div class="bg-charcoal-700 border-b border-charcoal-800 text-oat-100 text-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex flex-wrap items-center justify-between gap-2">
            <p>{{ shop_config('contact_phone') }} - {{ shop_config('contact_address') }}</p>
            @if((float) shop_config('free_shipping_threshold', 0) > 0)
                <p class="text-oat-100 font-medium">Nationwide delivery. Free delivery above {{ shop_money(shop_config('free_shipping_threshold')) }}</p>
            @endif
        </div>
    </div>

    {{-- Navbar --}}
    <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-100 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 md:h-20">
                <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                    <div class="w-10 h-10 rounded-xl bg-terracotta-500 flex items-center justify-center text-white font-black tracking-tight transition-transform duration-300 group-hover:scale-110">
                        EK
                    </div>
                    <div>
                        <span class="text-xl font-bold text-charcoal-700">{{ shop_name() }}</span>
                        <span class="hidden sm:block text-xs text-charcoal-500">Handmade yarn goods</span>
                    </div>
                </a>

                <nav class="hidden md:flex items-center gap-8">
                    <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'nav-link-active' : '' }}">Home</a>
                    <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'nav-link-active' : '' }}">Products</a>
                    <a href="{{ route('about') }}" class="nav-link {{ request()->routeIs('about') ? 'nav-link-active' : '' }}">About</a>
                    <a href="{{ route('contact') }}" class="nav-link {{ request()->routeIs('contact') ? 'nav-link-active' : '' }}">Contact</a>
                </nav>

                <div class="flex items-center gap-3">
                    <a href="{{ route('cart.index') }}" class="relative p-2 rounded-xl text-charcoal-600 hover:bg-oat-100 hover:text-sage-700 transition-all duration-200">
                        <x-icon name="cart" class="w-6 h-6" />
                        @if($cartCount > 0)
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-terracotta-500 text-white text-xs font-bold rounded-full flex items-center justify-center animate-fade-in">{{ $cartCount }}</span>
                        @endif
                    </a>

                    @auth
                        <div class="hidden sm:flex items-center gap-3">
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}" class="text-sm font-medium text-sage-700 hover:text-sage-800">Admin</a>
                            @endif
                            <a href="{{ route('account.orders.index') }}" class="text-sm font-medium text-charcoal-600 hover:text-sage-700">Orders</a>
                            <a href="{{ route('profile.edit') }}" class="text-sm font-medium text-charcoal-600 hover:text-sage-700">Account</a>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-sm font-medium text-slate-500 hover:text-red-600 transition-colors">Logout</button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="hidden sm:inline text-sm font-medium text-charcoal-600 hover:text-sage-700 transition-colors">Login</a>
                        <a href="{{ route('register') }}" class="btn-primary !py-2 !px-4 text-sm">Create Account</a>
                    @endauth

                    <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 rounded-lg hover:bg-slate-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div x-show="mobileOpen" x-transition class="md:hidden border-t border-slate-100 bg-white animate-slide-down">
            <div class="px-4 py-4 space-y-1">
                <a href="{{ route('home') }}" class="block py-2.5 font-medium text-charcoal-700 hover:text-sage-700">Home</a>
                <a href="{{ route('products.index') }}" class="block py-2.5 font-medium text-charcoal-700 hover:text-sage-700">Products</a>
                <a href="{{ route('about') }}" class="block py-2.5 font-medium text-charcoal-700 hover:text-sage-700">About</a>
                <a href="{{ route('contact') }}" class="block py-2.5 font-medium text-charcoal-700 hover:text-sage-700">Contact</a>
                <a href="{{ route('cart.index') }}" class="flex items-center gap-2 py-2.5 font-medium text-charcoal-700 hover:text-sage-700">
                    <x-icon name="cart" class="w-5 h-5" /> Cart @if($cartCount > 0)({{ $cartCount }})@endif
                </a>
                @auth
                    <div class="border-t border-slate-100 mt-2 pt-2">
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="block py-2.5 font-medium text-sage-700">Admin Dashboard</a>
                        @endif
                        <a href="{{ route('account.orders.index') }}" class="block py-2.5 font-medium text-charcoal-700 hover:text-sage-700">My Orders</a>
                        <a href="{{ route('profile.edit') }}" class="block py-2.5 font-medium text-charcoal-700 hover:text-sage-700">My Account</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full text-left py-2.5 font-medium text-red-600 hover:text-red-700">Logout</button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="block py-2.5 font-medium text-sage-700">Login</a>
                    <a href="{{ route('register') }}" class="block py-2.5 font-medium text-sage-700">Create Account</a>
                @endauth
            </div>
        </div>
    </header>

    @auth
        @if(auth()->user()->requiresEmailVerification())
            <div class="bg-oat-100 border-b border-sage-200 text-charcoal-700 text-sm">
                <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-2">
                    <p class="font-medium">Please verify your email to access your account features.</p>
                    <a href="{{ auth()->user()->verification_method === 'otp' ? route('verification.otp') : route('verification.notice') }}"
                       class="font-semibold text-sage-700 hover:text-sage-800 underline">Verify now</a>
                </div>
            </div>
        @endif
    @endauth

    <main>
        @unless(request()->routeIs('home') || View::hasSection('hide_back'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-5 relative z-20">
                <x-back-button
                    :href="View::hasSection('back_href') ? trim($__env->yieldContent('back_href')) : route('home')"
                    :label="View::hasSection('back_label') ? trim($__env->yieldContent('back_label')) : 'Back'"
                />
            </div>
        @endunless
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-white border-t border-slate-200 text-slate-600 mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-oat-100 text-terracotta-500 flex items-center justify-center">
                            <span class="text-sm font-black">EK</span>
                        </div>
                        <span class="text-xl font-bold text-charcoal-700">{{ shop_name() }}</span>
                    </div>
                    <p class="text-slate-500 max-w-md">{{ shop_config('tagline') }}</p>
                </div>
                <div>
                    <h4 class="text-charcoal-700 font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="{{ route('products.index') }}" class="hover:text-sage-700 transition-colors">All Products</a></li>
                        <li><a href="{{ route('about') }}" class="hover:text-sage-700 transition-colors">About Us</a></li>
                        <li><a href="{{ route('contact') }}" class="hover:text-sage-700 transition-colors">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-charcoal-700 font-semibold mb-4">Contact</h4>
                    <ul class="space-y-2 text-slate-500">
                        <li>{{ shop_config('contact_phone') }}</li>
                        <li>{{ shop_config('contact_email') }}</li>
                        <li>{{ shop_config('contact_address') }}</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-slate-100 mt-8 pt-8 text-center text-slate-400 text-sm">
                &copy; {{ date('Y') }} {{ shop_name() }}. All rights reserved.
            </div>
        </div>
    </footer>
</body>
</html>
