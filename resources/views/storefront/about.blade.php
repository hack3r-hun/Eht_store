@extends('layouts.storefront')

@section('title', 'About Us')
@section('meta_description', 'Learn about '.shop_name().' - handcrafted crochet, knitted gifts, and cozy yarn creations made in Karachi.')

@section('content')
    <section class="relative overflow-hidden bg-gradient-to-br from-oat-100 via-white to-sage-50 py-20 md:py-28 border-b border-oat-200">
        <div class="absolute inset-x-0 bottom-0 h-20 bg-mesh opacity-50 pointer-events-none"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <x-reveal type="fade-up">
                <span class="section-badge">About {{ shop_name() }}</span>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold leading-tight mb-6 text-charcoal-700">
                    {{ $page->meta['hero_title'] ?? 'Handmade Yarn Creations With Personality' }}
                </h1>
                <p class="text-lg md:text-xl text-charcoal-500 max-w-3xl mx-auto leading-relaxed">
                    {{ $page->meta['hero_subtitle'] ?? 'Crochet, knit, accessories, cozy wearables, and custom gifts made with care in Karachi.' }}
                </p>
            </x-reveal>
        </div>
    </section>

    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <x-reveal type="fade-right">
                    <span class="section-badge">Our Story</span>
                    <h2 class="section-title">Small-Batch Pieces Made With Care</h2>
                    <div class="mt-6 prose prose-lg prose-slate max-w-none text-slate-600 leading-relaxed">
                        {!! $page->content ?? '<p>EK Yarn Co. creates handcrafted crochet and knitted products made with creativity, quality yarns, and attention to detail. From cute amigurumi and daily accessories to cozy sweaters, shawls, and custom gifts, every piece is designed to feel personal and thoughtfully made.</p>' !!}
                    </div>
                </x-reveal>
                <x-reveal type="fade-left" delay="150">
                    <div class="relative rounded-3xl overflow-hidden shadow-soft border border-oat-200 bg-oat-100 p-8">
                        <div class="grid grid-cols-2 gap-4">
                            @foreach([
                                ['icon' => 'heart', 'label' => 'Crochet'],
                                ['icon' => 'sparkles', 'label' => 'Accessories'],
                                ['icon' => 'gift', 'label' => 'Custom Gifts'],
                                ['icon' => 'truck', 'label' => 'Nationwide Delivery'],
                            ] as $item)
                                <div class="rounded-2xl bg-white border border-oat-200 p-6 text-center shadow-card">
                                    <x-icon :name="$item['icon']" class="w-8 h-8 mx-auto text-terracotta-500 mb-3" />
                                    <p class="font-semibold text-charcoal-700">{{ $item['label'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-reveal>
            </div>
        </div>
    </section>

    <section class="py-16 bg-oat-100/70 bg-mesh">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                @foreach([
                    ['count' => 100, 'suffix' => '+', 'label' => 'Handmade Pieces'],
                    ['count' => 20, 'suffix' => '+', 'label' => 'Gift Styles'],
                    ['count' => 5, 'suffix' => '+', 'label' => 'Product Categories'],
                    ['count' => 1, 'suffix' => '', 'label' => 'Karachi Studio'],
                ] as $i => $stat)
                    <x-reveal type="scale" :delay="$i * 100">
                        <div class="text-center p-8 bg-white rounded-2xl border border-oat-200 shadow-card card-glow card-hover">
                            <div class="text-4xl md:text-5xl font-bold text-gradient" data-count="{{ $stat['count'] }}" data-count-suffix="{{ $stat['suffix'] }}">0</div>
                            <div class="mt-2 text-charcoal-500 font-medium">{{ $stat['label'] }}</div>
                        </div>
                    </x-reveal>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-reveal type="fade-up" class="text-center mb-14">
                <span class="section-badge">What We Stand For</span>
                <h2 class="section-title">Craft, Comfort & Thoughtful Details</h2>
            </x-reveal>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach([
                    ['icon' => 'heart', 'title' => 'Our Mission', 'desc' => 'To make handmade yarn products that feel personal, useful, giftable, and finished with care.'],
                    ['icon' => 'eye', 'title' => 'Our Vision', 'desc' => 'To become a trusted Karachi-based handmade brand for crochet, knitwear, and custom yarn gifts.'],
                    ['icon' => 'shield', 'title' => 'Our Values', 'desc' => 'Quality yarns, honest communication, neat finishing, and respect for every customer idea.'],
                ] as $i => $item)
                    <x-reveal type="fade-up" :delay="$i * 120">
                        <div class="group h-full p-8 rounded-3xl border border-slate-100 bg-white shadow-card card-glow card-hover text-center">
                            <div class="icon-box mx-auto mb-5 bg-oat-100 text-terracotta-500">
                                <x-icon :name="$item['icon']" class="w-7 h-7" />
                            </div>
                            <h3 class="text-xl font-bold text-charcoal-700 mb-3 group-hover:text-sage-700 transition-colors">{{ $item['title'] }}</h3>
                            <p class="text-charcoal-500 leading-relaxed">{{ $item['desc'] }}</p>
                        </div>
                    </x-reveal>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-20 bg-gradient-to-b from-oat-100 to-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-reveal type="fade-up" class="text-center mb-14">
                <span class="section-badge">Why Choose Us</span>
                <h2 class="section-title">Made for Gifting, Wearing, and Keeping</h2>
                <p class="section-subtitle mx-auto">Every order is handled like a small creative project.</p>
            </x-reveal>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach([
                    ['icon' => 'check-circle', 'title' => 'Hand-Finished Quality', 'desc' => 'Neat details, careful shaping, and quality yarn selected for each product.'],
                    ['icon' => 'sparkles', 'title' => 'Personalized Options', 'desc' => 'Colors, themes, and custom gift ideas can be discussed before ordering.'],
                    ['icon' => 'truck', 'title' => 'Local & Nationwide Delivery', 'desc' => 'Based in Karachi with delivery available beyond the city.'],
                    ['icon' => 'gift', 'title' => 'Gift-Ready Pieces', 'desc' => 'Cute, cozy, and practical items for birthdays, events, and everyday surprises.'],
                    ['icon' => 'cube', 'title' => 'Varied Collections', 'desc' => 'Amigurumi, keychains, coasters, scrunchies, wallets, headbands, sweaters, and shawls.'],
                    ['icon' => 'chat', 'title' => 'Clear Communication', 'desc' => 'We help confirm size, color, timing, and order details before dispatch.'],
                ] as $i => $feature)
                    <x-reveal type="fade-up" :delay="($i % 3) * 100">
                        <div class="group flex gap-4 p-6 bg-white rounded-2xl border border-slate-100 shadow-card card-hover">
                            <div class="shrink-0 w-12 h-12 rounded-xl bg-oat-100 text-terracotta-500 flex items-center justify-center group-hover:bg-sage-600 group-hover:text-white group-hover:scale-110 transition-all duration-300">
                                <x-icon :name="$feature['icon']" class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="font-semibold text-charcoal-700 group-hover:text-sage-700 transition-colors">{{ $feature['title'] }}</h3>
                                <p class="text-sm text-charcoal-500 mt-1 leading-relaxed">{{ $feature['desc'] }}</p>
                            </div>
                        </div>
                    </x-reveal>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-reveal type="scale">
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-oat-100 via-white to-sage-50 border border-oat-200 p-12 md:p-16 text-center">
                    <div class="relative z-10">
                        <h2 class="text-3xl md:text-4xl font-bold mb-4 text-charcoal-700">Ready to Find Your Next Handmade Piece?</h2>
                        <p class="text-charcoal-500 mb-8 max-w-xl mx-auto text-lg">Browse ready-made products or contact us for a custom crochet or knit gift.</p>
                        <div class="flex flex-wrap justify-center gap-4">
                            <a href="{{ route('products.index') }}" class="btn-primary">Browse Products</a>
                            <a href="{{ route('contact') }}" class="btn-outline">Start Custom Order</a>
                        </div>
                    </div>
                </div>
            </x-reveal>
        </div>
    </section>
@endsection
