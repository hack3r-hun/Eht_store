@extends('layouts.storefront')

@section('title', 'Home')

@section('content')
    @php $homeMeta = \App\Models\Page::where('slug', 'home')->first()?->meta ?? []; @endphp

    <section class="hero-section">
        <div class="absolute inset-x-0 bottom-0 h-24 bg-mesh opacity-60 pointer-events-none animate-drift"></div>
        {{-- Ambient aurora glow — pure CSS, transform/opacity only, skipped under reduced-motion --}}
        <div class="absolute -top-24 left-1/4 w-96 h-96 rounded-full bg-terracotta-300/20 blur-3xl pointer-events-none animate-drift"></div>
        <div class="absolute top-1/3 -right-16 w-80 h-80 rounded-full bg-sage-300/20 blur-3xl pointer-events-none animate-drift animation-delay-400"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-20 w-full">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <x-reveal type="fade-up">
                        <span class="hero-eyebrow">Handmade in Karachi</span>
                    </x-reveal>
                    <x-reveal type="fade-up" delay="120">
                        <h1 class="hero-title">
                            {{ $homeMeta['hero_title'] ?? 'Handcrafted Crochet & Knit Pieces Made With Care' }}
                        </h1>
                    </x-reveal>
                    <x-reveal type="fade-up" delay="240">
                        <p class="text-lg text-slate-500 mb-8 leading-relaxed max-w-xl">
                            {{ $homeMeta['hero_subtitle'] ?? 'Cute amigurumi, keychains, coasters, scrunchies, wallets, headbands, cozy wearables, and custom gifts made with quality yarns.' }}
                        </p>
                    </x-reveal>
                    <x-reveal type="fade-up" delay="360">
                        <div class="flex flex-wrap gap-4">
                            <a href="{{ route('products.index') }}" class="btn-primary text-base px-8 py-4">{{ $homeMeta['hero_cta'] ?? 'Shop Handmade' }}</a>
                            <a href="{{ route('contact') }}" class="btn-outline">Request Custom Order</a>
                        </div>
                    </x-reveal>
                </div>
                <x-reveal type="fade-left" delay="200" class="hidden lg:block">
                    <div class="relative" data-hero-parallax>
                        <div
                            class="rounded-3xl overflow-hidden border border-oat-200 shadow-soft"
                            x-data="{ rx: 0, ry: 0 }"
                            @mousemove="
                                if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                                    const r = $el.getBoundingClientRect();
                                    ry = (((($event.clientX - r.left) / r.width) - 0.5) * 6);
                                    rx = -(((($event.clientY - r.top) / r.height) - 0.5) * 6);
                                }
                            "
                            @mouseleave="rx = 0; ry = 0"
                            :style="`transform: perspective(1000px) rotateX(${rx}deg) rotateY(${ry}deg); transform-style: preserve-3d;`"
                            style="transition: transform 0.3s ease-out;"
                        >
                            <img src="{{ config('media.hero') }}" alt="Colorful yarns and crochet hooks at {{ shop_name() }}" class="w-full h-[480px] object-cover" fetchpriority="high">
                        </div>
                        <div class="absolute -top-4 right-6 bg-sage-600 text-white rounded-xl shadow-lg px-4 py-2 text-sm font-semibold animate-float">Custom Orders Welcome</div>
                        <div class="absolute -bottom-6 left-6 bg-white rounded-2xl shadow-xl border border-oat-200 px-5 py-4 flex items-center gap-3 animate-float animation-delay-300">
                            <span class="w-10 h-10 rounded-xl bg-terracotta-500 text-white flex items-center justify-center"><x-icon name="heart" class="w-5 h-5" /></span>
                            <div>
                                <p class="font-bold text-charcoal-700 text-sm">100% Handmade</p>
                                <p class="text-xs text-slate-500">Made with care in Karachi</p>
                            </div>
                        </div>
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
                        <x-icon-feature-card size="sm" :icon="$badge['icon']" :title="$badge['title']" :description="$badge['desc']" />
                    </x-reveal>
                @endforeach
            </div>
        </div>
    </section>

    <x-stitch-divider class="bg-white" />

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
                        <x-icon-feature-card :icon="$step['icon']" :title="$step['title']" :description="$step['desc']" :step="$step['step']" />
                    </x-reveal>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-dark py-20">
        <div class="absolute inset-0 bg-mesh opacity-10 pointer-events-none animate-drift"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-reveal type="fade-up" class="text-center mb-14 max-w-2xl mx-auto">
                <span class="inline-block px-4 py-1.5 rounded-full bg-white/10 text-gold-300 text-sm font-semibold mb-4 border border-white/10">Why Handmade</span>
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Every Piece Made With Intention</h2>
                <p class="text-oat-100/70 text-lg">No factory lines, no shortcuts — just careful stitching, quality yarn, and attention to the details that make a gift feel personal.</p>
            </x-reveal>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                @foreach([
                    ['count' => 500, 'suffix' => '+', 'label' => 'Happy Customers'],
                    ['count' => 1000, 'suffix' => '+', 'label' => 'Pieces Handmade'],
                    ['count' => 20, 'suffix' => '+', 'label' => 'Gift Styles'],
                    ['count' => 100, 'suffix' => '%', 'label' => 'Handmade Care'],
                ] as $i => $stat)
                    <x-reveal type="scale" :delay="$i * 100">
                        <div class="text-center p-6 rounded-2xl bg-white/5 border border-white/10">
                            <div class="text-3xl md:text-4xl font-bold text-white" data-count="{{ $stat['count'] }}" data-count-suffix="{{ $stat['suffix'] }}">0</div>
                            <div class="mt-2 text-oat-100/60 text-sm font-medium">{{ $stat['label'] }}</div>
                        </div>
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

    <x-stitch-divider class="bg-white" />

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
            <div
                x-data="{
                    reviews: [
                        { name: 'Ayesha Noor', role: 'Gift Customer', text: 'The amigurumi was adorable and neatly finished. It felt personal, not mass-produced.' },
                        { name: 'Maham Tariq', role: 'Accessory Buyer', text: 'Ordered scrunchies and a headband. Colors were beautiful and delivery was smooth.' },
                        { name: 'Hina Ahmed', role: 'Custom Order', text: 'They helped me choose yarn colors for a custom gift and kept every detail exactly right.' },
                    ],
                    active: 0,
                    timer: null,
                    start() {
                        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
                        this.timer = setInterval(() => { this.active = (this.active + 1) % this.reviews.length; }, 5000);
                    },
                    select(i) {
                        this.active = i;
                        clearInterval(this.timer);
                        this.start();
                    },
                    prev() {
                        this.select((this.active - 1 + this.reviews.length) % this.reviews.length);
                    },
                    next() {
                        this.select((this.active + 1) % this.reviews.length);
                    },
                }"
                x-init="start()"
                class="max-w-2xl mx-auto relative"
            >
                <button
                    type="button"
                    @click="prev()"
                    class="hidden md:flex absolute top-1/2 -translate-y-1/2 -left-16 w-11 h-11 rounded-full bg-white shadow-card hover:bg-oat-100 active:scale-95 items-center justify-center text-charcoal-600 transition-all"
                    aria-label="Previous testimonial"
                >
                    <x-icon name="arrow-left" class="w-5 h-5" />
                </button>
                <button
                    type="button"
                    @click="next()"
                    class="hidden md:flex absolute top-1/2 -translate-y-1/2 -right-16 w-11 h-11 rounded-full bg-white shadow-card hover:bg-oat-100 active:scale-95 items-center justify-center text-charcoal-600 transition-all"
                    aria-label="Next testimonial"
                >
                    <x-icon name="arrow-right" class="w-5 h-5" />
                </button>

                <div class="relative min-h-[22rem] sm:min-h-[19rem]">
                    <template x-for="(review, i) in reviews" :key="i">
                        <div
                            x-show="active === i"
                            x-transition:enter="transition ease-out duration-500"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-300"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="absolute inset-0 flex flex-col justify-center p-8 bg-oat-100/60 rounded-2xl border border-oat-200"
                        >
                            <div class="text-gold-500 mb-4 flex gap-0.5 justify-center">
                                <x-icon name="star" class="w-5 h-5" />
                                <x-icon name="star" class="w-5 h-5" />
                                <x-icon name="star" class="w-5 h-5" />
                                <x-icon name="star" class="w-5 h-5" />
                                <x-icon name="star" class="w-5 h-5" />
                            </div>
                            <p class="text-slate-500 leading-relaxed italic text-center" x-text="'&quot;' + review.text + '&quot;'"></p>
                            <div class="mt-6 pt-6 border-t border-oat-200 text-center">
                                <p class="font-semibold text-charcoal-700" x-text="review.name"></p>
                                <p class="text-sm text-sage-700" x-text="review.role"></p>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="flex items-center justify-center gap-4 mt-6 md:hidden">
                    <button type="button" @click="prev()" class="w-11 h-11 shrink-0 rounded-full bg-white shadow-card hover:bg-oat-100 active:scale-95 flex items-center justify-center text-charcoal-600 transition-all" aria-label="Previous testimonial">
                        <x-icon name="arrow-left" class="w-4 h-4" />
                    </button>
                    <div class="flex gap-2">
                        <template x-for="(review, i) in reviews" :key="i">
                            <button
                                type="button"
                                @click="select(i)"
                                :class="active === i ? 'bg-terracotta-500 w-6' : 'bg-oat-300 w-2'"
                                class="h-2 rounded-full transition-all duration-300"
                                :aria-label="'Show testimonial ' + (i + 1)"
                            ></button>
                        </template>
                    </div>
                    <button type="button" @click="next()" class="w-11 h-11 shrink-0 rounded-full bg-white shadow-card hover:bg-oat-100 active:scale-95 flex items-center justify-center text-charcoal-600 transition-all" aria-label="Next testimonial">
                        <x-icon name="arrow-right" class="w-4 h-4" />
                    </button>
                </div>
                <div class="hidden md:flex justify-center gap-2 mt-6">
                    <template x-for="(review, i) in reviews" :key="i">
                        <button
                            type="button"
                            @click="select(i)"
                            :class="active === i ? 'bg-terracotta-500 w-6' : 'bg-oat-300 w-2'"
                            class="h-2 rounded-full transition-all duration-300"
                            :aria-label="'Show testimonial ' + (i + 1)"
                        ></button>
                    </template>
                </div>
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
            <x-cta-banner
                title="Want Something Made Just for You?"
                description="Share your colors, size, theme, or gift idea. We will help turn it into a handmade yarn piece."
                primary-label="Start Custom Order"
                :primary-href="route('contact')"
                :secondary-label="shop_config('contact_phone')"
                :secondary-href="'tel:'.preg_replace('/[^0-9+]/', '', shop_config('contact_phone'))"
                secondary-icon="phone"
            />
        </x-reveal>
    </section>
@endsection
