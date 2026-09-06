@extends('layouts.storefront')

@section('title', 'Products')
@section('meta_description', 'Browse crochet, knitted accessories, amigurumi, coasters, scrunchies, wallets, headbands, sweaters, shawls, and custom gifts at '.shop_name())

@section('content')
    <section class="hero-section py-16 md:py-20">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-reveal type="fade-up">
                <span class="hero-eyebrow">Full Collection</span>
                <h1 class="hero-title">Handmade Products</h1>
            </x-reveal>
            <x-reveal type="fade-up" delay="150">
                <p class="text-slate-500 text-lg max-w-2xl">Browse crochet, knitted accessories, amigurumi, coasters, scrunchies, wallets, headbands, cozy wearables, and custom gifts.</p>
            </x-reveal>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-reveal type="fade-up" class="lg:hidden mb-6 flex items-center gap-3">
            <div class="flex-1 overflow-x-auto pb-2 -mb-2">
                <div class="flex gap-2 min-w-max">
                    <a href="{{ route('products.index') }}" class="product-filter-pill {{ !request('category') ? 'product-filter-pill-active' : 'product-filter-pill-inactive' }}">All</a>
                    @foreach($categories as $category)
                        <a href="{{ route('products.index', ['category' => $category->slug]) }}"
                           class="product-filter-pill {{ request('category') === $category->slug ? 'product-filter-pill-active' : 'product-filter-pill-inactive' }}">
                            {{ $category->name }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div x-data="{ open() { this.$refs.filterDlg.showModal(); }, close() { this.$refs.filterDlg.close(); } }" class="shrink-0">
                <button type="button" @click="open()" class="relative inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border-2 border-oat-200 bg-white text-sm font-semibold text-charcoal-700 hover:border-sage-300 active:scale-95 transition-all">
                    <x-icon name="folder" class="w-4 h-4" /> Filters
                    @if(request()->filled('min_price') || request()->filled('max_price'))
                        <span class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-terracotta-500"></span>
                    @endif
                </button>

                <template x-teleport="body">
                    <dialog x-ref="filterDlg" class="filter-drawer" aria-label="Filter products" @click.self="close()">
                        <div class="filter-drawer-panel" @click.stop>
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-bold text-charcoal-700">Filters</h3>
                                <button type="button" @click="close()" class="p-2 rounded-lg hover:bg-oat-100 text-slate-500 transition-colors" aria-label="Close">
                                    <x-icon name="x" class="w-5 h-5" />
                                </button>
                            </div>

                            <form method="GET" class="space-y-6">
                                @if(request('search'))
                                    <input type="hidden" name="search" value="{{ request('search') }}">
                                @endif

                                <div>
                                    <h4 class="text-sm font-semibold text-charcoal-700 mb-3">Collection</h4>
                                    <div class="space-y-1 max-h-48 overflow-y-auto pr-1">
                                        <label class="flex items-center gap-2 py-2 px-3 rounded-lg text-sm cursor-pointer hover:bg-oat-100">
                                            <input type="radio" name="category" value="" {{ !request('category') ? 'checked' : '' }} class="text-sage-600 focus:ring-sage-500">
                                            All Products
                                        </label>
                                        @foreach($categories as $category)
                                            <label class="flex items-center gap-2 py-2 px-3 rounded-lg text-sm cursor-pointer hover:bg-oat-100">
                                                <input type="radio" name="category" value="{{ $category->slug }}" {{ request('category') === $category->slug ? 'checked' : '' }} class="text-sage-600 focus:ring-sage-500">
                                                {{ $category->name }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div>
                                    <h4 class="text-sm font-semibold text-charcoal-700 mb-3">Sort By</h4>
                                    <select name="sort" class="input-field">
                                        <option value="latest" @selected(request('sort', 'latest') === 'latest')>Latest First</option>
                                        <option value="price_low" @selected(request('sort') === 'price_low')>Price: Low to High</option>
                                        <option value="price_high" @selected(request('sort') === 'price_high')>Price: High to Low</option>
                                        <option value="name" @selected(request('sort') === 'name')>Name A-Z</option>
                                    </select>
                                </div>

                                <div>
                                    <h4 class="text-sm font-semibold text-charcoal-700 mb-3">Price Range</h4>
                                    <div class="flex items-center gap-3">
                                        <input type="number" name="min_price" value="{{ request('min_price') }}" min="0" placeholder="Min" class="input-field">
                                        <span class="text-slate-400">to</span>
                                        <input type="number" name="max_price" value="{{ request('max_price') }}" min="0" placeholder="Max" class="input-field">
                                    </div>
                                </div>

                                <div class="flex gap-3 pt-2">
                                    <a href="{{ route('products.index') }}" class="btn-outline flex-1 text-center">Reset</a>
                                    <button type="submit" class="btn-primary flex-1">Apply Filters</button>
                                </div>
                            </form>
                        </div>
                    </dialog>
                </template>
            </div>
        </x-reveal>

        <div class="flex flex-col lg:flex-row gap-8 items-start">
            <aside class="hidden lg:block lg:w-72 shrink-0 products-sidebar">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-6">
                    <h3 class="font-bold text-charcoal-700 mb-1 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-terracotta-50 text-terracotta-500 flex items-center justify-center"><x-icon name="folder" class="w-4 h-4" /></span>
                        Collections
                    </h3>
                    <p class="text-xs text-slate-500 mb-4">Filter by handmade type</p>
                    <ul class="space-y-1">
                        <li>
                            <a href="{{ route('products.index') }}"
                               class="flex items-center gap-2 py-2.5 px-3 rounded-xl text-sm transition-all duration-300 {{ !request('category') ? 'bg-sage-600 text-white shadow-md shadow-sage-700/20' : 'text-charcoal-600 hover:bg-oat-100 hover:text-sage-700 hover:translate-x-1' }}">
                                All Products
                            </a>
                        </li>
                        @foreach($categories as $category)
                            <li>
                                <a href="{{ route('products.index', ['category' => $category->slug]) }}"
                                   class="flex items-center gap-2 py-2.5 px-3 rounded-xl text-sm transition-all duration-300 {{ request('category') === $category->slug ? 'bg-sage-600 text-white shadow-md shadow-sage-700/20' : 'text-charcoal-600 hover:bg-oat-100 hover:text-sage-700 hover:translate-x-1' }}">
                                    {{ $category->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <form method="GET" class="bg-white rounded-2xl border border-slate-100 shadow-card p-6 mt-6">
                    @if(request('category'))
                        <input type="hidden" name="category" value="{{ request('category') }}">
                    @endif
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    @if(request('sort'))
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                    @endif
                    <h3 class="font-bold text-charcoal-700 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-sage-50 text-sage-600 flex items-center justify-center"><x-icon name="currency" class="w-4 h-4" /></span>
                        Price Range
                    </h3>
                    <div class="flex items-center gap-2 mb-4">
                        <input type="number" name="min_price" value="{{ request('min_price') }}" min="0" placeholder="Min" class="input-field !py-2 text-sm">
                        <span class="text-slate-400">-</span>
                        <input type="number" name="max_price" value="{{ request('max_price') }}" min="0" placeholder="Max" class="input-field !py-2 text-sm">
                    </div>
                    <button type="submit" class="btn-outline w-full !py-2 text-sm">Apply</button>
                </form>
            </aside>

            <div class="flex-1">
                <x-reveal type="fade-up">
                    <form id="search-products" method="GET" class="scroll-mt-24 bg-white rounded-2xl border border-slate-100 shadow-card p-4 mb-8 flex flex-col sm:flex-row gap-3">
                        @if(request('category'))
                            <input type="hidden" name="category" value="{{ request('category') }}">
                        @endif
                        <div class="relative flex-1">
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search amigurumi, scrunchies, shawls..." class="input-field pl-12">
                        </div>
                        <select name="sort" class="input-field sm:w-52" onchange="this.form.submit()">
                            <option value="latest" @selected(request('sort', 'latest') === 'latest')>Latest First</option>
                            <option value="price_low" @selected(request('sort') === 'price_low')>Price: Low to High</option>
                            <option value="price_high" @selected(request('sort') === 'price_high')>Price: High to Low</option>
                            <option value="name" @selected(request('sort') === 'name')>Name A-Z</option>
                        </select>
                        <button type="submit" class="btn-primary !py-3 shrink-0">Search</button>
                    </form>
                </x-reveal>

                <div class="flex items-center justify-between mb-6">
                    <p class="text-sm text-slate-500">
                        Showing <span class="font-semibold text-charcoal-700">{{ $products->total() }}</span> products
                        @if(request('category')) in selected collection @endif
                    </p>
                </div>

                @if($products->isEmpty())
                    <x-reveal type="scale">
                        <div class="text-center py-24 bg-white rounded-3xl border border-slate-100 shadow-card">
                            <x-icon name="search" class="w-16 h-16 mx-auto mb-4 text-slate-300" />
                            <h3 class="text-xl font-semibold text-charcoal-700 mb-2">No products found</h3>
                            <p class="text-slate-500 mb-6">Try a different search or browse all collections.</p>
                            <a href="{{ route('products.index') }}" class="btn-primary">View All Products</a>
                        </div>
                    </x-reveal>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                        @foreach($products as $i => $product)
                            <x-reveal type="fade-up" :delay="($i % 6) * 60">
                                <x-product-card :product="$product" />
                            </x-reveal>
                        @endforeach
                    </div>
                    <div class="mt-10">{{ $products->links() }}</div>
                @endif
            </div>
        </div>
    </section>
@endsection
