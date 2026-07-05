@extends('layouts.storefront')

@section('title', 'Home')

@section('content')
    @php $homeMeta = \App\Models\Page::where('slug', 'home')->first()?->meta ?? []; @endphp

    <section class="relative overflow-hidden bg-gradient-to-br from-oat-100 via-white to-sage-50 min-h-[78vh] flex items-center border-b border-oat-200">
        <div class="absolute inset-x-0 bottom-0 h-24 bg-mesh opacity-60 pointer-events-none"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 w-full">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <x-reveal type="fade-up">
                        <span class="inline-block px-4 py-1.5 rounded-full bg-white text-sage-700 text-sm font-semibold mb-6 border border-sage-200 shadow-card">Handmade in Karachi</span>
                        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold leading-[1.1] mb-6 text-charcoal-700">
                            {{ $homeMeta['hero_title'] ?? 'Handcrafted Crochet & Knit Pieces Made With Care' }}
                        </h1>
                        <p class="text-lg text-charcoal-500 mb-8 leading-relaxed max-w-xl">
                            {{ $homeMeta['hero_subtitle'] ?? 'Cute amigurumi, keychains, coasters, scrunchies, wallets, headbands, cozy wearables, and custom gifts made with quality yarns.' }}
                        </p>
                        <div class="flex flex-wrap gap-4">
                            <a href="{{ route('products.index') }}" class="btn-accent text-base px-8 py-4">{{ $homeMeta['hero_cta'] ?? 'Shop Handmade' }}</a>
                            <a href="{{ route('contact') }}" class="btn-outline">Request Custom Order</a>
                        </div>
                    </x-reveal>
                </div>
                <x-reveal type="fade-left" delay="200" class="hidden lg:block">
                    <div class="grid grid-cols-2 gap-4">
                        @foreach([
                            ['icon' => 'heart', 'label' => 'Amigurumi & Plushies', 'bg' => 'bg-white border-oat-200'],
                            ['icon' => 'gift', 'label' => 'Custom Gifts', 'bg' => 'bg-sage-50 border-sage-100'],
                            ['icon' => 'sparkles', 'label' => 'Accessories', 'bg' => 'bg-oat-100 border-oat-200'],
                            ['icon' => 'shield', 'label' => 'Quality Yarn', 'bg' => 'bg-white border-oat-200'],
                        ] as $i => $card)
                            <div class="p-6 rounded-2xl {{ $card['bg'] }} border card-hover {{ $i % 2 === 1 ? 'mt-8' : '' }}">
                                <x-icon :name="$card['icon']" class="w-10 h-10 text-terracotta-500 mb-3" />
                                <p class="text-sm font-semibold text-charcoal-700">{{ $card['label'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-reveal>
            </div>
        </div>
    </section>

    <section class="bg-white relative z-10 -mt-8 mx-4 md:mx-8 lg:mx-auto max-w-6xl rounded-2xl shadow-xl shadow-charcoal-900/5 border border-oat-200">
        <div class="px-6 py-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                @foreach([
                    ['icon' => 'check-circle', 'title' => 'Handmade Care', 'desc' => 'Detailed finishing'],
                    ['icon' => 'sparkles', 'title' => 'Custom Pieces', 'desc' => 'Personalized gifts'],
                    ['icon' => 'truck', 'title' => 'Nationwide Delivery', 'desc' => 'From Karachi to you'],
                    ['icon' => 'chat', 'title' => 'Friendly Support', 'desc' => 'Order help anytime'],
                ] as $i => $badge)
                    <x-reveal type="fade-up" :delay="$i * 80">
                        <x-store-card class="text-center h-full flex flex-col items-center justify-center">
                            <div class="w-12 h-12 mb-3 rounded-xl bg-oat-100 text-terracotta-500 flex items-center justify-center group-hover:scale-110 transition-all duration-300">
                                <x-icon :name="$badge['icon']" class="w-6 h-6" />
                            </div>
                            <h3 class="font-semibold text-charcoal-700">{{ $badge['title'] }}</h3>
                            <p class="text-sm text-charcoal-500 mt-1">{{ $badge['desc'] }}</p>
                        </x-store-card>
                    </x-reveal>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-20 bg-oat-100/70 bg-mesh">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-reveal type="fade-up" class="text-center mb-14">
                <span class="section-badge">How It Works</span>
                <h2 class="section-title">From Idea to Handmade Piece</h2>
                <p class="section-subtitle mx-auto">Pick a ready-made item or tell us what you want customized.</p>
            </x-reveal>
            <div class="store-grid-3">
                @foreach([
                    ['step' => '01', 'icon' => 'search', 'title' => 'Browse or Share an Idea', 'desc' => 'Choose from accessories, home pieces, wearables, and cute gifts, or message us for a custom concept.'],
                    ['step' => '02', 'icon' => 'chat', 'title' => 'Confirm Details', 'desc' => 'We align on color, size, quantity, and delivery timing before the item is prepared.'],
                    ['step' => '03', 'icon' => 'truck', 'title' => 'Receive With Care', 'desc' => 'Your handmade order is packed neatly and delivered locally or nationwide.'],
                ] as $i => $step)
                    <x-reveal type="fade-up" :delay="$i * 120">
                        <x-store-card class="relative text-center h-full card-glow">
                            <span class="absolute top-4 right-4 text-4xl font-black text-oat-200">{{ $step['step'] }}</span>
                            <div class="icon-box mx-auto mb-5 bg-oat-100 text-terracotta-500">
                                <x-icon :name="$step['icon']" class="w-7 h-7" />
                            </div>
                            <h3 class="text-xl font-bold text-charcoal-700 mb-2">{{ $step['title'] }}</h3>
                            <p class="text-charcoal-500 text-sm leading-relaxed">{{ $step['desc'] }}</p>
                        </x-store-card>
                    </x-reveal>
                @endforeach
            </div>
        </div>
    </section>

    @if($categories->isNotEmpty())
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-reveal type="fade-up" class="text-center mb-12">
                <span class="section-badge">Collections</span>
                <h2 class="section-title">Shop by Collection</h2>
                <p class="section-subtitle mx-auto">Find crochet, knit, accessories, home decor, and gift-ready pieces.</p>
            </x-reveal>
            <div class="store-grid-6">
                @foreach($categories as $i => $category)
                    <x-reveal type="scale" :delay="($i % 6) * 60">
                        <a href="{{ route('products.index', ['category' => $category->slug]) }}"
                           class="group block bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden card-glow card-hover">
                            <div class="aspect-[4/3] overflow-hidden bg-oat-100">
                                <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy">
                            </div>
                            <div class="p-4 text-center">
                                <h3 class="font-semibold text-charcoal-700 text-sm group-hover:text-sage-700 transition-colors">{{ $category->name }}</h3>
                                <p class="text-xs text-slate-500 mt-1">{{ $category->active_products_count }} items</p>
                            </div>
                        </a>
                    </x-reveal>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    @if($featuredProducts->isNotEmpty())
    <section class="py-20 bg-gradient-to-b from-oat-100/70 to-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-reveal type="fade-up" class="flex flex-wrap items-end justify-between gap-4 mb-10">
                <div>
                    <span class="section-badge">Customer Favorites</span>
                    <h2 class="section-title">Featured Handmade Picks</h2>
                    <p class="section-subtitle">Thoughtfully made pieces selected for gifting and everyday use.</p>
                </div>
                <a href="{{ route('products.index') }}" class="text-sage-700 font-semibold hover:text-sage-800 transition-colors flex items-center gap-1 group">
                    View all <span class="group-hover:translate-x-1 transition-transform">-&gt;</span>
                </a>
            </x-reveal>
            <div class="store-grid-4">
                @foreach($featuredProducts as $i => $product)
                    <x-reveal type="fade-up" :delay="($i % 4) * 80">
                        <x-product-card :product="$product" />
                    </x-reveal>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-reveal type="fade-up" class="text-center mb-14">
                <span class="section-badge">Testimonials</span>
                <h2 class="section-title">Loved by Handmade Gift Shoppers</h2>
            </x-reveal>
            <div class="store-grid-3">
                @foreach([
                    ['name' => 'Ayesha Noor', 'role' => 'Gift Customer', 'text' => 'The amigurumi was adorable and neatly finished. It felt personal, not mass-produced.', 'stars' => 5],
                    ['name' => 'Maham Tariq', 'role' => 'Accessory Buyer', 'text' => 'Ordered scrunchies and a headband. Colors were beautiful and delivery was smooth.', 'stars' => 5],
                    ['name' => 'Hina Ahmed', 'role' => 'Custom Order', 'text' => 'They helped me choose yarn colors for a custom gift and kept every detail exactly right.', 'stars' => 5],
                ] as $i => $review)
                    <x-reveal type="fade-up" :delay="$i * 100">
                        <div class="h-full p-8 bg-oat-100/60 rounded-2xl border border-oat-200 card-hover relative">
                            <div class="text-terracotta-400 mb-4 flex gap-0.5 justify-center">
                                @for($s = 0; $s < $review['stars']; $s++)
                                    <x-icon name="star" class="w-5 h-5" />
                                @endfor
                            </div>
                            <p class="text-charcoal-500 leading-relaxed italic">"{{ $review['text'] }}"</p>
                            <div class="mt-6 pt-6 border-t border-oat-200">
                                <p class="font-semibold text-charcoal-700">{{ $review['name'] }}</p>
                                <p class="text-sm text-sage-700">{{ $review['role'] }}</p>
                            </div>
                        </div>
                    </x-reveal>
                @endforeach
            </div>
        </div>
    </section>

    @if($latestProducts->isNotEmpty())
    <section class="py-20 bg-oat-100/70">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-reveal type="fade-up" class="flex flex-wrap items-end justify-between gap-4 mb-10">
                <div>
                    <span class="section-badge">Just Added</span>
                    <h2 class="section-title">New Handmade Arrivals</h2>
                    <p class="section-subtitle">Freshly added crochet and knit creations.</p>
                </div>
                <a href="{{ route('products.index', ['sort' => 'latest']) }}" class="text-sage-700 font-semibold hover:text-sage-800 transition-colors flex items-center gap-1 group">
                    View all <span class="group-hover:translate-x-1 transition-transform">-&gt;</span>
                </a>
            </x-reveal>
            <div class="store-grid-4">
                @foreach($latestProducts as $i => $product)
                    <x-reveal type="fade-up" :delay="($i % 4) * 80">
                        <x-product-card :product="$product" />
                    </x-reveal>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
        <x-reveal type="scale">
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-oat-100 via-white to-sage-50 border border-oat-200 p-10 md:p-16 text-center">
                <div class="relative z-10">
                    <h2 class="text-3xl md:text-4xl font-bold mb-4 text-charcoal-700">Want Something Made Just for You?</h2>
                    <p class="text-charcoal-500 mb-8 max-w-xl mx-auto text-lg">Share your colors, size, theme, or gift idea. We will help turn it into a handmade yarn piece.</p>
                    <div class="flex flex-wrap justify-center gap-4">
                        <a href="{{ route('contact') }}" class="btn-primary">Start Custom Order</a>
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', shop_config('contact_phone')) }}" class="btn-outline inline-flex items-center gap-2">
                            <x-icon name="phone" class="w-5 h-5" /> {{ shop_config('contact_phone') }}
                        </a>
                    </div>
                </div>
            </div>
        </x-reveal>
    </section>
@endsection
