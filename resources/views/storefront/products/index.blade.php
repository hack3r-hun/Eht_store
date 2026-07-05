@extends('layouts.storefront')

@section('title', 'Products')
@section('meta_description', 'Browse crochet, knitted accessories, amigurumi, coasters, scrunchies, wallets, headbands, sweaters, shawls, and custom gifts at '.shop_name())

@section('content')
    <section class="relative overflow-hidden bg-gradient-to-br from-oat-100 via-white to-sage-50 py-16 md:py-20 border-b border-oat-200">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-reveal type="fade-up">
                <span class="section-badge">Full Collection</span>
                <h1 class="text-4xl md:text-5xl font-bold mb-4 text-charcoal-700">Handmade Products</h1>
                <p class="text-charcoal-500 text-lg max-w-2xl">Browse crochet, knitted accessories, amigurumi, coasters, scrunchies, wallets, headbands, cozy wearables, and custom gifts.</p>
            </x-reveal>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-reveal type="fade-up" class="lg:hidden mb-6 overflow-x-auto pb-2">
            <div class="flex gap-2 min-w-max">
                <a href="{{ route('products.index') }}" class="product-filter-pill {{ !request('category') ? 'product-filter-pill-active' : 'product-filter-pill-inactive' }}">All</a>
                @foreach($categories as $category)
                    <a href="{{ route('products.index', ['category' => $category->slug]) }}"
                       class="product-filter-pill {{ request('category') === $category->slug ? 'product-filter-pill-active' : 'product-filter-pill-inactive' }}">
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </x-reveal>

        <div class="flex flex-col lg:flex-row gap-8 items-start">
            <aside class="hidden lg:block lg:w-72 shrink-0 products-sidebar">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-6">
                    <h3 class="font-bold text-charcoal-700 mb-1 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-oat-100 text-terracotta-500 flex items-center justify-center"><x-icon name="folder" class="w-4 h-4" /></span>
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
            </aside>

            <div class="flex-1">
                <x-reveal type="fade-up">
                    <form method="GET" class="bg-white rounded-2xl border border-slate-100 shadow-card p-4 mb-8 flex flex-col sm:flex-row gap-3">
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
