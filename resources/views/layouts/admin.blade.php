<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'Admin') - {{ shop_name() }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/admin.js', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 admin-layout" x-data="{ sidebarOpen: false }" data-scroll-reveal="off">
    <x-toast-stack />

    <div class="min-h-screen flex">
        {{-- Mobile overlay --}}
        <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" class="fixed inset-0 bg-slate-900/30 z-40 md:hidden" x-cloak></div>

        {{-- Sidebar --}}
        <aside class="fixed md:static inset-y-0 left-0 z-50 w-72 bg-white border-r border-slate-200 shrink-0 flex flex-col shadow-sm transform transition-transform duration-300 md:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="p-6 border-b border-slate-100">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 group">
                    <div class="w-11 h-11 rounded-xl bg-brand-100 text-brand-600 flex items-center justify-center group-hover:bg-brand-600 group-hover:text-white transition-all duration-300">
                        <x-icon name="bolt" class="w-6 h-6" />
                    </div>
                    <div>
                        <span class="text-slate-900 font-bold text-lg block">{{ shop_name() }}</span>
                        <span class="text-xs text-slate-500">Admin Panel</span>
                    </div>
                </a>
            </div>

            <nav class="p-4 space-y-1 flex-1 overflow-y-auto">
                @php
                    $links = [
                        ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'match' => 'admin.dashboard', 'icon' => 'chart'],
                        ['route' => 'admin.statistics.index', 'label' => 'Statistics', 'match' => 'admin.statistics.*', 'icon' => 'chart'],
                        ['route' => 'admin.products.index', 'label' => 'Products', 'match' => 'admin.products.*', 'icon' => 'cube'],
                        ['route' => 'admin.categories.index', 'label' => 'Categories', 'match' => 'admin.categories.*', 'icon' => 'folder'],
                        ['route' => 'admin.orders.index', 'label' => 'Orders', 'match' => 'admin.orders.*', 'icon' => 'cart'],
                        ['route' => 'admin.customers.index', 'label' => 'Customers', 'match' => 'admin.customers.*', 'icon' => 'users'],
                        ['route' => 'admin.messages.index', 'label' => 'Messages', 'match' => 'admin.messages.*', 'icon' => 'mail'],
                        ['route' => 'admin.pages.index', 'label' => 'CMS Pages', 'match' => 'admin.pages.*', 'icon' => 'document'],
                        ['route' => 'admin.settings.index', 'label' => 'Settings', 'match' => 'admin.settings.*', 'icon' => 'cog'],
                    ];
                @endphp
                @foreach($links as $link)
                    <a href="{{ route($link['route']) }}"
                       class="admin-sidebar-link {{ request()->routeIs($link['match']) ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive' }}">
                        <x-icon :name="$link['icon']" class="w-5 h-5 shrink-0" />
                        {{ $link['label'] }}
                    </a>
                @endforeach

                <div class="pt-4 mt-4 border-t border-slate-100">
                    <a href="{{ route('home') }}" target="_blank"
                       class="admin-sidebar-link admin-sidebar-link-inactive text-brand-600">
                        <x-icon name="globe" class="w-5 h-5 shrink-0" /> View Store
                    </a>
                </div>
            </nav>

            <div class="p-4 border-t border-slate-100">
                <div class="flex items-center gap-3 px-3 py-2 mb-3">
                    <div class="w-9 h-9 rounded-full bg-brand-600 flex items-center justify-center text-white text-sm font-bold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500 truncate">Administrator</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full admin-sidebar-link admin-sidebar-link-inactive text-red-500 hover:text-red-600 hover:bg-red-50">
                        <x-icon name="logout" class="w-5 h-5 shrink-0" /> Logout
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex-1 min-w-0 flex flex-col">
            <header class="bg-white border-b border-slate-200 px-4 md:px-6 py-4 sticky top-0 z-30">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <button @click="sidebarOpen = !sidebarOpen" class="md:hidden p-2 rounded-lg hover:bg-slate-100 text-slate-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        @unless(request()->routeIs('admin.dashboard'))
                            @php
                                $adminBackHref = match (true) {
                                    // Section index pages go back to the dashboard, not themselves.
                                    request()->routeIs('admin.*.index') => route('admin.dashboard'),
                                    request()->routeIs('admin.products.*') => route('admin.products.index'),
                                    request()->routeIs('admin.categories.*') => route('admin.categories.index'),
                                    request()->routeIs('admin.orders.*') => route('admin.orders.index'),
                                    request()->routeIs('admin.messages.*') => route('admin.messages.index'),
                                    request()->routeIs('admin.pages.*') => route('admin.pages.index'),
                                    request()->routeIs('admin.customers.*') => route('admin.customers.index'),
                                    request()->routeIs('admin.settings.*') => route('admin.settings.index'),
                                    default => route('admin.dashboard'),
                                };
                            @endphp
                            <x-back-button
                                :href="$adminBackHref"
                                label="Back"
                                icon-only
                            />
                        @endunless
                        <div>
                            <p class="text-xs font-semibold text-brand-600 uppercase tracking-wider mb-0.5">Admin</p>
                            <h1 class="text-xl md:text-2xl font-bold text-slate-900">@yield('page-title', 'Dashboard')</h1>
                        </div>
                    </div>
                    <div class="text-sm text-slate-500 hidden sm:block">
                        {{ now()->format('l, M d, Y') }}
                    </div>
                </div>
            </header>

            <main class="p-4 md:p-6 flex-1">@yield('content')</main>
        </div>
    </div>
</body>
</html>
