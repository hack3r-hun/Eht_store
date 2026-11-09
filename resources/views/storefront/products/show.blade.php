@extends('layouts.storefront')

@section('title', $product->name)
@section('meta_description', Str::limit($product->description ?? '', 160) ?: shop_config('tagline'))

@section('content')
    @php
        $variants = $product->activeVariants->values();
        $variantsJson = $variants->map(fn ($v) => [
            'id' => $v->id,
            'size' => $v->size,
            'price' => (float) $v->price,
            'sale_price' => $v->sale_price !== null ? (float) $v->sale_price : null,
            'effective_price' => $v->effective_price,
            'price_label' => shop_money($v->effective_price),
            'compare_label' => $v->is_on_sale ? shop_money($v->price) : null,
            'stock' => $v->stock_quantity,
            'in_stock' => $v->is_in_stock,
            'sku' => $v->sku,
        ])->values();
        $defaultVariant = $variants->firstWhere('is_in_stock') ?? $variants->first();
        $galleryUrls = collect($product->images->map(fn ($i) => $i->url)->all())->prepend($product->image_url)->unique()->values();
        $lowStockLimit = module_enabled('low_stock_badges') ? (int) module_value('low_stock_badges', 'threshold', config('shop.low_stock_threshold', 5)) : -1;
        $leadTime = $product->lead_time_days ? $product->lead_time_days.' days' : config('shop.default_lead_time');
        $isWishlisted = in_array($product->id, wishlist_ids(), true);
        $whatsappText = "Hi! I'm interested in {$product->name} ({$product->sku}): ".route('products.show', $product->slug);
    @endphp

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <nav class="text-sm text-slate-500 mb-8" data-reveal="fade-up">
            <a href="{{ route('home') }}" class="hover:text-sage-700 transition-colors">Home</a>
            <span class="mx-2">/</span>
            <a href="{{ route('products.index') }}" class="hover:text-sage-700 transition-colors">Products</a>
            <span class="mx-2">/</span>
            <a href="{{ route('products.index', ['category' => $product->category->slug]) }}" class="hover:text-sage-700 transition-colors">{{ $product->category->name }}</a>
            <span class="mx-2">/</span>
            <span class="text-charcoal-700 font-medium">{{ Str::limit($product->name, 40) }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-14">
            <x-reveal type="fade-right">
                <div
                    class="space-y-4"
                    x-data="{
                        activeImage: @js($product->image_url),
                        mainLoaded: false,
                        lightbox: false,
                        origin: '50% 50%',
                        images: @js($galleryUrls),
                        step(dir) {
                            if (this.images.length < 2) return;
                            const i = this.images.indexOf(this.activeImage);
                            this.activeImage = this.images[(i + dir + this.images.length) % this.images.length];
                            this.mainLoaded = false;
                        },
                        track(e) {
                            const r = e.currentTarget.getBoundingClientRect();
                            this.origin = (((e.clientX - r.left) / r.width) * 100) + '% ' + (((e.clientY - r.top) / r.height) * 100) + '%';
                        },
                    }"
                    x-init="if ($refs.mainImg?.complete) mainLoaded = true"
                    @keydown.escape.window="lightbox = false"
                    @keydown.arrow-right.window="if (lightbox) step(1)"
                    @keydown.arrow-left.window="if (lightbox) step(-1)"
                >
                    <div class="relative aspect-square bg-white rounded-3xl border border-slate-100 shadow-card overflow-hidden group cursor-zoom-in" @mousemove="track($event)" @click="lightbox = true">
                        <div class="img-skeleton absolute inset-0" x-show="!mainLoaded" x-transition:leave="transition-opacity duration-300" x-transition:leave-end="opacity-0" x-cloak></div>
                        <img x-ref="mainImg" :src="activeImage" alt="{{ $product->name }}"
                             @load="mainLoaded = true"
                             @@error="mainLoaded = true"
                             :class="mainLoaded ? 'opacity-100' : 'opacity-0'"
                             :style="'transform-origin: ' + origin"
                             class="w-full h-full object-cover transition-[opacity,transform] duration-300 md:group-hover:scale-[1.7]">
                        <span class="pointer-events-none absolute bottom-3 right-3 rounded-lg bg-charcoal-700/80 px-2.5 py-1 text-[11px] font-semibold text-white opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity">Tap to enlarge</span>
                        @if($product->is_on_sale || $variants->contains(fn ($v) => $v->is_on_sale))
                            <span class="absolute top-4 left-4 bg-terracotta-500 text-white text-sm font-bold px-4 py-1.5 rounded-xl shadow-lg">On Sale</span>
                        @endif
                    </div>
                    @if($product->images->count() > 1)
                        <div class="flex gap-3 overflow-x-auto pb-2">
                            @foreach($product->images as $image)
                                <button type="button" @click="if (activeImage !== '{{ $image->url }}') { activeImage = '{{ $image->url }}'; mainLoaded = false }"
                                        class="shrink-0 w-20 h-20 rounded-xl overflow-hidden border-2 border-transparent hover:border-sage-500 transition-all duration-300 focus:border-sage-600">
                                    <img src="{{ $image->url }}" alt="" class="w-full h-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif

                    {{-- Lightbox: teleported to <body> so the reveal wrapper's transform can't trap position:fixed --}}
                    <template x-teleport="body">
                        <div x-show="lightbox" x-cloak x-transition.opacity class="fixed inset-0 z-[120] bg-charcoal-900/95 flex items-center justify-center p-4" @click.self="lightbox = false" role="dialog" aria-modal="true" aria-label="Product image viewer">
                            <button type="button" @click="lightbox = false" class="absolute top-4 right-4 w-11 h-11 rounded-full bg-white/10 text-white text-2xl hover:bg-white/20" aria-label="Close">&times;</button>
                            <template x-if="images.length > 1">
                                <button type="button" @click="step(-1)" class="absolute left-3 sm:left-6 w-11 h-11 rounded-full bg-white/10 text-white text-2xl hover:bg-white/20" aria-label="Previous image">&lsaquo;</button>
                            </template>
                            <img :src="activeImage" alt="{{ $product->name }}" class="max-h-[90vh] max-w-full rounded-2xl object-contain shadow-2xl">
                            <template x-if="images.length > 1">
                                <button type="button" @click="step(1)" class="absolute right-3 sm:right-6 w-11 h-11 rounded-full bg-white/10 text-white text-2xl hover:bg-white/20" aria-label="Next image">&rsaquo;</button>
                            </template>
                        </div>
                    </template>
                </div>
            </x-reveal>

            <x-reveal type="fade-left" delay="150">
                <div
                    class="lg:sticky lg:top-24"
                    x-data="productSizePicker(@js($variantsJson), @js($defaultVariant?->id))"
                >
                    <a href="{{ route('products.index', ['category' => $product->category->slug]) }}"
                       class="inline-flex items-center gap-1 text-sm font-semibold text-sage-700 bg-oat-100 px-3 py-1 rounded-lg hover:bg-sage-50 transition-colors mb-4">
                        {{ $product->category->name }}
                    </a>
                    <h1 class="text-3xl md:text-4xl font-bold text-charcoal-700 mb-3 leading-tight">{{ $product->name }}</h1>
                    @if($averageRating)
                        <a href="#reviews" class="inline-flex items-center gap-2 mb-3 text-sm text-slate-500 hover:text-sage-700">
                            <span class="text-amber-500 tracking-wider" aria-hidden="true">{{ str_repeat('★', (int) round($averageRating)) }}<span class="text-slate-300">{{ str_repeat('★', 5 - (int) round($averageRating)) }}</span></span>
                            <span class="font-semibold text-charcoal-700">{{ number_format($averageRating, 1) }}</span>
                            <span>({{ $reviewCount }} {{ Str::plural('review', $reviewCount) }})</span>
                        </a>
                    @endif
                    <p class="text-sm text-slate-500 mb-6">
                        SKU:
                        <span class="font-mono font-medium text-slate-700" x-text="selected?.sku || '{{ $product->sku }}'">{{ $defaultVariant?->sku ?? $product->sku }}</span>
                    </p>

                    <div class="flex flex-wrap items-center gap-4 mb-6 p-5 bg-oat-100/70 rounded-2xl">
                        <span class="text-4xl font-bold text-charcoal-700" x-text="selected?.price_label || '{{ shop_money($product->from_price) }}'">
                            {{ shop_money($defaultVariant?->effective_price ?? $product->from_price) }}
                        </span>
                        <template x-if="selected?.compare_label">
                            <span class="text-xl text-slate-400 line-through" x-text="selected.compare_label"></span>
                        </template>
                    </div>

                    @if($variants->isNotEmpty())
                        <div class="mb-6">
                            <p class="text-sm font-semibold text-charcoal-700 mb-3">Select size</p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="variant in variants" :key="variant.id">
                                    <button
                                        type="button"
                                        @click="select(variant.id)"
                                        class="min-w-[3.25rem] px-4 py-2.5 rounded-xl border-2 text-sm font-bold transition-all"
                                        :class="selectedId === variant.id
                                            ? 'border-sage-600 bg-sage-50 text-sage-800'
                                            : (variant.in_stock ? 'border-oat-200 bg-white text-charcoal-700 hover:border-sage-400' : 'border-slate-100 bg-slate-50 text-slate-400 line-through')"
                                        :disabled="!variant.in_stock"
                                        x-text="variant.size"
                                    ></button>
                                </template>
                            </div>
                            @error('product_variant_id')
                                <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div class="mb-6" x-show="selected">
                        <template x-if="selected?.in_stock">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold"
                                      :class="selected.stock <= {{ $lowStockLimit }} ? 'text-amber-700 bg-amber-50' : 'text-green-700 bg-green-50'">
                                    <span class="w-2.5 h-2.5 rounded-full animate-pulse" :class="selected.stock <= {{ $lowStockLimit }} ? 'bg-amber-500' : 'bg-green-500'"></span>
                                    <span x-text="selected.stock <= {{ $lowStockLimit }} ? 'Only ' + selected.stock + ' left — order soon' : 'Available — ' + selected.stock + ' in stock'"></span>
                                </span>
                            </div>
                        </template>
                        <template x-if="selected && !selected.in_stock">
                            <span class="inline-flex items-center gap-2 text-red-700 bg-red-50 px-4 py-2 rounded-xl text-sm font-semibold">Out of Stock</span>
                        </template>
                    </div>

                    <p class="mb-6 flex items-center gap-2 text-sm text-slate-500">
                        <x-icon name="clock" class="w-4 h-4 text-terracotta-500 shrink-0" />
                        Handmade to order — ready to ship in about {{ $leadTime }}.
                        <a href="{{ route('policies') }}#lead-time" class="text-sage-700 underline">Details</a>
                    </p>

                    <p class="text-slate-600 leading-relaxed mb-8 text-lg">{{ $product->description }}</p>

                    @if($variants->contains(fn ($v) => $v->is_in_stock))
                        <form
                            action="{{ route('cart.store') }}"
                            method="POST"
                            class="flex flex-wrap items-stretch gap-4 mb-8"
                            @submit="if (!selectedId || !selected?.in_stock || submitting) { $event.preventDefault(); return; } submitting = true"
                        >
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="hidden" name="product_variant_id" :value="selectedId">
                            <x-quantity-stepper name="quantity" model="qty" :min="1" max-expr="selected?.stock || 1" />
                            <button
                                type="submit"
                                class="btn-primary flex-1 text-base py-4 inline-flex items-center justify-center gap-2"
                                :disabled="submitting || !selected?.in_stock"
                                :class="(submitting || !selected?.in_stock) && 'opacity-60 cursor-not-allowed'"
                            >
                                <x-icon name="cart" class="w-5 h-5" />
                                <span x-text="submitting ? 'Adding...' : 'Add to Cart'">Add to Cart</span>
                            </button>
                        </form>
                    @else
                        <span class="inline-flex items-center gap-2 text-red-700 bg-red-50 px-4 py-2 rounded-xl text-sm font-semibold mb-4">All sizes out of stock</span>
                    @endif

                    @if(module_enabled('back_in_stock') && $variants->contains(fn ($v) => ! $v->is_in_stock))
                        <form action="{{ route('stock-alerts.store') }}" method="POST" class="mb-8 rounded-2xl border border-oat-200 bg-white p-4"
                              x-show="!selected || !selected.in_stock" x-cloak data-no-loading>
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="hidden" name="product_variant_id" :value="selectedId">
                            <label class="block text-sm font-semibold text-charcoal-700 mb-2">Email me when this size is back</label>
                            <div class="flex gap-2">
                                <input type="email" name="email" required value="{{ auth()->user()?->email }}" placeholder="you@example.com" class="input-field">
                                <button type="submit" class="btn-accent shrink-0">Notify me</button>
                            </div>
                        </form>
                    @endif

                    @if(module_enabled('wishlist') || module_enabled('whatsapp'))
                    <div class="flex flex-wrap gap-3 mb-8">
                        @if(module_enabled('wishlist'))
                        <button
                            type="button"
                            class="btn-outline"
                            x-data="{ saved: @js($isWishlisted), busy: false,
                                async toggle() {
                                    if (this.busy) return;
                                    this.busy = true;
                                    try { this.saved = (await window.toggleWishlist({{ $product->id }})).added; }
                                    catch (e) { window.showToast?.('Could not update your wishlist. Please try again.', 'error'); }
                                    finally { this.busy = false; }
                                } }"
                            @click="toggle()"
                            :aria-pressed="saved"
                        >
                            <svg viewBox="0 0 24 24" class="w-5 h-5 transition-colors" :class="saved && 'text-terracotta-500'" stroke="currentColor" stroke-width="1.8" :fill="saved ? 'currentColor' : 'none'"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                            <span x-text="saved ? 'Saved to wishlist' : 'Save to wishlist'"></span>
                        </button>
                        @endif
                        @if(module_enabled('whatsapp'))
                        <a href="{{ whatsapp_url($whatsappText) }}" target="_blank" rel="noopener" class="btn-outline">
                            <x-icon name="chat" class="w-5 h-5" /> Order on WhatsApp
                        </a>
                        @endif
                    </div>
                    @endif

                    <div class="grid grid-cols-3 gap-3 mb-8">
                        @foreach([['heart', 'Handmade'], ['truck', 'Delivery'], ['check-circle', 'Quality Yarn']] as $badge)
                            <div class="text-center p-3 bg-white rounded-xl border border-slate-100 text-xs">
                                <div class="w-8 h-8 mx-auto mb-1 rounded-lg bg-terracotta-50 text-terracotta-500 flex items-center justify-center">
                                    <x-icon :name="$badge[0]" class="w-4 h-4" />
                                </div>
                                <span class="text-slate-600 font-medium">{{ $badge[1] }}</span>
                            </div>
                        @endforeach
                    </div>

                    @if($product->specifications)
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-6 card-glow">
                            <h3 class="font-bold text-charcoal-700 mb-4 flex items-center gap-2">
                                <span class="w-8 h-8 rounded-lg bg-terracotta-50 text-terracotta-500 flex items-center justify-center"><x-icon name="clipboard" class="w-4 h-4" /></span>
                                Details
                            </h3>
                            <dl class="space-y-3">
                                @foreach($product->specifications as $key => $value)
                                    <div class="flex justify-between gap-4 text-sm border-b border-slate-50 pb-2 last:border-0">
                                        <dt class="text-slate-500">{{ $key }}</dt>
                                        <dd class="font-medium text-charcoal-700 text-right">{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif
                </div>
            </x-reveal>
        </div>

        @if(module_enabled('reviews'))
        <div id="reviews" class="mt-16 scroll-mt-28" data-reveal="fade-up">
            <h2 class="text-2xl font-bold text-charcoal-700 mb-6">Customer Reviews</h2>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="space-y-6">
                    <div class="store-card p-6">
                        @if($averageRating)
                            <p class="text-5xl font-bold text-charcoal-700">{{ number_format($averageRating, 1) }}<span class="text-lg text-slate-400"> / 5</span></p>
                            <p class="text-amber-500 text-xl tracking-wider mt-1" aria-hidden="true">{{ str_repeat('★', (int) round($averageRating)) }}<span class="text-slate-300">{{ str_repeat('★', 5 - (int) round($averageRating)) }}</span></p>
                            <p class="text-sm text-slate-500 mt-1">{{ $reviewCount }} {{ Str::plural('review', $reviewCount) }}</p>
                            <div class="mt-4 space-y-1.5">
                                @foreach([5, 4, 3, 2, 1] as $star)
                                    @php $n = (int) ($ratingBreakdown[$star] ?? 0); @endphp
                                    <div class="flex items-center gap-2 text-xs text-slate-500">
                                        <span class="w-3">{{ $star }}</span><span class="text-amber-500">★</span>
                                        <div class="flex-1 h-2 rounded-full bg-oat-200 overflow-hidden"><div class="h-full rounded-full bg-amber-400" style="width: {{ $reviewCount ? round($n / $reviewCount * 100) : 0 }}%"></div></div>
                                        <span class="w-5 text-right">{{ $n }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="font-semibold text-charcoal-700">No reviews yet</p>
                            <p class="text-sm text-slate-500 mt-1">Be the first to share how your piece turned out.</p>
                        @endif
                    </div>

                    <form action="{{ route('reviews.store', $product->slug) }}" method="POST" class="store-card p-6 space-y-4" x-data="{ rating: {{ (int) old('rating', 0) }}, hover: 0 }" data-no-loading>
                        @csrf
                        <h3 class="font-bold text-charcoal-700">Write a review</h3>
                        @if($errors->hasAny(['rating', 'body', 'name', 'title']))
                            <p class="text-sm text-red-600">{{ $errors->first() }}</p>
                        @endif
                        <div>
                            <div class="flex gap-1 text-3xl leading-none" role="radiogroup" aria-label="Rating">
                                @foreach([1, 2, 3, 4, 5] as $star)
                                    <button type="button" @click="rating = {{ $star }}" @mouseenter="hover = {{ $star }}" @mouseleave="hover = 0"
                                            class="transition-transform hover:scale-125 focus:outline-none focus-visible:ring-2 ring-sage-500 rounded"
                                            :class="(hover || rating) >= {{ $star }} ? 'text-amber-400' : 'text-slate-300'"
                                            role="radio" :aria-checked="rating === {{ $star }}" aria-label="{{ $star }} star{{ $star > 1 ? 's' : '' }}">★</button>
                                @endforeach
                            </div>
                            <input type="hidden" name="rating" :value="rating">
                        </div>
                        @guest
                            <input type="text" name="name" value="{{ old('name') }}" required maxlength="80" placeholder="Your name" class="input-field">
                        @endguest
                        <input type="text" name="title" value="{{ old('title') }}" maxlength="120" placeholder="Headline (optional)" class="input-field">
                        <textarea name="body" rows="4" required minlength="10" maxlength="2000" placeholder="What did you love? How was the quality?" class="input-field">{{ old('body') }}</textarea>
                        <button type="submit" class="btn-primary w-full" :disabled="!rating" :class="!rating && 'opacity-60 cursor-not-allowed'">Submit review</button>
                        <p class="text-xs text-slate-400">Reviews from verified buyers appear instantly; others are checked first.</p>
                    </form>
                </div>

                <div class="lg:col-span-2 space-y-4">
                    @forelse($reviews as $review)
                        <article class="store-card p-6">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="text-amber-500 tracking-wider" aria-label="{{ $review->rating }} out of 5">{{ str_repeat('★', $review->rating) }}<span class="text-slate-300">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                                @if($review->title)<h3 class="font-semibold text-charcoal-700">{{ $review->title }}</h3>@endif
                                @if($review->is_verified)<span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2 py-0.5 text-[11px] font-semibold text-green-700">✓ Verified buyer</span>@endif
                            </div>
                            <p class="text-slate-600 whitespace-pre-line">{{ $review->body }}</p>
                            <p class="text-xs text-slate-400 mt-3">{{ $review->name }} · {{ $review->created_at->format('d M Y') }}</p>
                        </article>
                    @empty
                        <div class="store-card p-10 text-center text-slate-500">Reviews will show up here once customers share their thoughts.</div>
                    @endforelse
                </div>
            </div>
        </div>
        @endif

        @if($recentlyViewed->isNotEmpty())
            <div class="mt-16" data-reveal="fade-up">
                <h2 class="text-2xl font-bold text-charcoal-700 mb-6">{{ module_value('recently_viewed', 'heading', 'Recently viewed') }}</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                    @foreach($recentlyViewed->take(4) as $recent)
                        <x-product-card :product="$recent" />
                    @endforeach
                </div>
            </div>
        @endif

        @if($relatedProducts->isNotEmpty())
            <div class="mt-16" data-reveal="fade-up">
                <h2 class="text-2xl font-bold text-charcoal-700 mb-6">You may also like</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                    @foreach($relatedProducts as $related)
                        <x-product-card :product="$related" />
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    <section class="section-dark py-16 mt-4">
        <div class="absolute inset-0 bg-mesh opacity-10 pointer-events-none"></div>
        <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-center">
                @foreach([
                    ['icon' => 'heart', 'title' => '100% Handmade', 'desc' => 'Every piece hand-stitched with care in Karachi.'],
                    ['icon' => 'truck', 'title' => 'Nationwide Delivery', 'desc' => 'Packed carefully and shipped across Pakistan.'],
                    ['icon' => 'shield', 'title' => 'Quality Promise', 'desc' => 'Neat finishing and quality yarn on every order.'],
                ] as $i => $point)
                    <div data-reveal="fade-up" data-reveal-delay="{{ $i * 100 }}">
                        <div class="w-12 h-12 mx-auto mb-4 rounded-xl bg-white/10 text-gold-300 flex items-center justify-center">
                            <x-icon :name="$point['icon']" class="w-6 h-6" />
                        </div>
                        <h3 class="font-semibold text-white mb-1">{{ $point['title'] }}</h3>
                        <p class="text-sm text-oat-100/60">{{ $point['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <script nonce="{{ $cspNonce }}">
        function productSizePicker(variants, defaultId) {
            return {
                variants,
                selectedId: defaultId,
                qty: 1,
                submitting: false,
                get selected() {
                    return this.variants.find(v => v.id === this.selectedId) || null;
                },
                select(id) {
                    this.selectedId = id;
                    this.qty = 1;
                }
            }
        }
    </script>
@endsection
