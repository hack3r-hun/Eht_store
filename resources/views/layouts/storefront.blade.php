<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', shop_name()) - {{ shop_config('tagline') }}</title>
    <meta name="description" content="@yield('meta_description', shop_config('tagline'))">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#E2725B">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-oat-100/45 text-charcoal-700" x-data="{ mobileOpen: false }">
    <div class="scroll-progress" data-scroll-progress aria-hidden="true"></div>
    <x-toast-stack />
    {{-- Top bar --}}
    <div class="bg-charcoal-700 border-b border-charcoal-800 text-oat-100 text-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex flex-wrap items-center justify-between gap-2">
            <p>{{ shop_config('contact_phone') }} - {{ shop_config('contact_address') }}</p>
            <p class="hidden sm:block text-oat-100/90 font-medium">{{ \App\Support\SiteMenu::topbarText() }}</p>
        </div>
    </div>

    @unless(module_enabled('accepting_orders'))
        <div class="bg-amber-100 border-b border-amber-200 text-amber-900 text-sm" role="status">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5 text-center font-medium">{{ module_value('accepting_orders', 'closed_message') }}</div>
        </div>
    @endunless

    {{-- Navbar --}}
    <header data-site-header class="site-header sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-100 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 md:h-20">
                <a href="{{ route('home') }}" class="flex items-center group">
                    <img src="{{ asset('images/logo.png') }}" alt="{{ shop_name() }} logo" class="site-logo w-14 h-14 md:w-16 md:h-16 object-contain transition-all duration-300 group-hover:scale-110 group-hover:-rotate-6">
                </a>

                <nav class="hidden lg:flex items-center gap-8" aria-label="Main">
                    @foreach(\App\Support\SiteMenu::nav() as $item)
                        <a href="{{ \App\Support\SiteMenu::href($item['url']) }}" class="nav-link {{ \App\Support\SiteMenu::isActive($item['url']) ? 'nav-link-active' : '' }}">{{ $item['label'] }}</a>
                    @endforeach
                </nav>

                <div class="flex items-center gap-3">
                    @if(module_enabled('wishlist'))
                    <a
                        href="{{ route('wishlist.index') }}"
                        class="relative p-2 rounded-xl text-charcoal-600 hover:bg-oat-100 hover:text-terracotta-500 transition-all duration-200"
                        aria-label="Wishlist"
                        x-data="{ count: {{ wishlist_count() }}, pop: false }"
                        @wishlist-updated.window="count = $event.detail.count; pop = true; setTimeout(() => pop = false, 700)"
                    >
                        <x-icon name="heart" class="w-6 h-6" />
                        <span
                            x-show="count > 0"
                            x-cloak
                            class="absolute -top-1 -right-1 min-w-[1.25rem] h-5 px-1 bg-terracotta-500 text-white text-xs font-bold rounded-full flex items-center justify-center"
                            :class="pop && 'animate-pop'"
                            x-text="count"
                        ></span>
                    </a>
                    @endif
                    <a
                        href="{{ route('cart.index') }}"
                        class="relative p-2 rounded-xl text-charcoal-600 hover:bg-oat-100 hover:text-sage-700 transition-all duration-200"
                        x-data="{ pop: @js((bool) session('cart_updated')), count: {{ (int) $cartCount }} }"
                        x-init="if (pop) setTimeout(() => pop = false, 700)"
                        @cart-updated.window="if ($event.detail.cart_count !== undefined) { count = $event.detail.cart_count; pop = true; setTimeout(() => pop = false, 700); }"
                    >
                        <x-icon name="cart" class="w-6 h-6" />
                        <span
                            x-show="count > 0"
                            x-cloak
                            class="absolute -top-1 -right-1 w-5 h-5 bg-terracotta-500 text-white text-xs font-bold rounded-full flex items-center justify-center animate-fade-in"
                            :class="pop && 'animate-pop'"
                            x-text="count"
                        ></span>
                    </a>

                    @auth
                        <div class="hidden lg:flex items-center gap-3">
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
                        <a href="{{ route('login') }}" class="hidden lg:inline text-sm font-medium text-charcoal-600 hover:text-sage-700 transition-colors">Login</a>
                        <a href="{{ route('register') }}" class="btn-primary !py-2 !px-4 text-sm hidden lg:inline-block">Create Account</a>
                    @endauth

                    <button @click="mobileOpen = !mobileOpen" class="lg:hidden p-2 rounded-lg hover:bg-slate-100" aria-label="Menu" :aria-expanded="mobileOpen">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div x-show="mobileOpen" x-cloak x-transition class="lg:hidden border-t border-slate-100 bg-white animate-slide-down">
            <div class="px-4 py-4 space-y-1">
                @foreach(\App\Support\SiteMenu::nav() as $item)
                    <a href="{{ \App\Support\SiteMenu::href($item['url']) }}" class="block py-2.5 font-medium text-charcoal-700 hover:text-sage-700 {{ \App\Support\SiteMenu::isActive($item['url']) ? '!text-terracotta-500' : '' }}">{{ $item['label'] }}</a>
                @endforeach
                @if(module_enabled('wishlist'))<a href="{{ route('wishlist.index') }}" class="block py-2.5 font-medium text-charcoal-700 hover:text-sage-700">Wishlist</a>@endif
                @if(module_enabled('order_tracking'))<a href="{{ route('track-order') }}" class="block py-2.5 font-medium text-charcoal-700 hover:text-sage-700">Track Order</a>@endif
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

    @php
        $suppressMobileTabBar = request()->routeIs('cart.*') || request()->routeIs('checkout.*');
    @endphp

    <main @unless($suppressMobileTabBar) class="pb-20 md:pb-0" @endunless>
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
                    <div class="mb-4">
                        <img src="{{ asset('images/logo.png') }}" alt="{{ shop_name() }} logo" class="w-16 h-16 object-contain">
                    </div>
                    <p class="text-slate-500 max-w-md">{{ \App\Support\SiteMenu::footerText() }}</p>
                    @if(count(\App\Support\SiteMenu::socials()))
                        <ul class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-sm font-medium">
                            @foreach(\App\Support\SiteMenu::socials() as $social)
                                <li><a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="text-sage-700 hover:text-terracotta-500 transition-colors">{{ $social['label'] }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <div>
                    <h4 class="text-charcoal-700 font-semibold mb-4">{{ \App\Support\SiteMenu::footerHeading() }}</h4>
                    <ul class="space-y-2">
                        @foreach(\App\Support\SiteMenu::footerLinks() as $link)
                            <li><a href="{{ \App\Support\SiteMenu::href($link['url']) }}" class="hover:text-sage-700 transition-colors">{{ $link['label'] }}</a></li>
                        @endforeach
                        @foreach($footerPages as $footerPage)
                            <li><a href="{{ route('pages.show', $footerPage['slug']) }}" class="hover:text-sage-700 transition-colors">{{ $footerPage['title'] }}</a></li>
                        @endforeach
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

    @if(module_enabled('whatsapp'))
        <a href="{{ whatsapp_url((string) module_value('whatsapp', 'message')) }}" target="_blank" rel="noopener"
           class="whatsapp-fab {{ $suppressMobileTabBar ? '' : 'with-tabbar' }}" aria-label="Chat with us on WhatsApp">
            <x-icon name="chat" class="w-6 h-6" />
        </a>
    @endif

    @if(module_enabled('promo_popup') && ! $suppressMobileTabBar)
        <x-promo-popup />
    @endif

    @if(module_enabled('newsletter_popup') && ! $suppressMobileTabBar)
        <x-newsletter-popup />
    @endif

    <button type="button" data-back-to-top class="back-to-top {{ $suppressMobileTabBar ? '' : 'with-tabbar' }}" aria-label="Back to top">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
    </button>

    @unless($suppressMobileTabBar)
        {{-- Mobile bottom tab bar: thumb-zone navigation, hidden on cart/checkout in favor of the sticky order-total bar --}}
        <nav class="mobile-tabbar md:hidden" aria-label="Primary">
            <a href="{{ route('home') }}" class="mobile-tabbar-link {{ request()->routeIs('home') ? 'mobile-tabbar-link-active' : '' }}">
                <x-icon name="home" class="w-5 h-5" />
                <span>Home</span>
            </a>
            <a href="{{ route('products.index') }}" class="mobile-tabbar-link {{ request()->routeIs('products.*') && !request()->filled('search') ? 'mobile-tabbar-link-active' : '' }}">
                <x-icon name="cube" class="w-5 h-5" />
                <span>Shop</span>
            </a>
            <a href="{{ route('products.index') }}#search-products" class="mobile-tabbar-link {{ request()->routeIs('products.*') && request()->filled('search') ? 'mobile-tabbar-link-active' : '' }}">
                <x-icon name="search" class="w-5 h-5" />
                <span>Search</span>
            </a>
            <a
                href="{{ route('cart.index') }}"
                class="mobile-tabbar-link relative {{ request()->routeIs('cart.*') ? 'mobile-tabbar-link-active' : '' }}"
                x-data="{ pop: @js((bool) session('cart_updated')), count: {{ (int) $cartCount }} }"
                x-init="if (pop) setTimeout(() => pop = false, 700)"
                @cart-updated.window="if ($event.detail.cart_count !== undefined) { count = $event.detail.cart_count; pop = true; setTimeout(() => pop = false, 700); }"
            >
                <span class="relative inline-block">
                    <x-icon name="cart" class="w-5 h-5" />
                    <span
                        x-show="count > 0"
                        x-cloak
                        class="absolute -top-1.5 -right-2 w-4 h-4 bg-terracotta-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center"
                        :class="pop && 'animate-pop'"
                        x-text="count"
                    ></span>
                </span>
                <span>Cart</span>
            </a>
            @auth
                <a href="{{ route('profile.edit') }}" class="mobile-tabbar-link {{ request()->routeIs('profile.*') ? 'mobile-tabbar-link-active' : '' }}">
                    <x-icon name="user" class="w-5 h-5" />
                    <span>Account</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="mobile-tabbar-link {{ request()->routeIs('login') ? 'mobile-tabbar-link-active' : '' }}">
                    <x-icon name="user" class="w-5 h-5" />
                    <span>Account</span>
                </a>
            @endauth
        </nav>
    @endunless
</body>
</html>
