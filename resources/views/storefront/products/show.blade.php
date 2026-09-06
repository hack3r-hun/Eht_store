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
                <div class="space-y-4" x-data="{ activeImage: '{{ $product->image_url }}', mainLoaded: false }" x-init="if ($refs.mainImg?.complete) mainLoaded = true">
                    <div class="relative aspect-square bg-white rounded-3xl border border-slate-100 shadow-card overflow-hidden group">
                        <div class="img-skeleton absolute inset-0" x-show="!mainLoaded" x-transition:leave="transition-opacity duration-300" x-transition:leave-end="opacity-0" x-cloak></div>
                        <img x-ref="mainImg" :src="activeImage" alt="{{ $product->name }}"
                             @load="mainLoaded = true"
                             @@error="mainLoaded = true"
                             :class="mainLoaded ? 'opacity-100' : 'opacity-0'"
                             class="w-full h-full object-cover transition-all duration-700 group-hover:scale-105">
                        @if($product->is_on_sale || $variants->contains(fn ($v) => $v->is_on_sale))
                            <span class="absolute top-4 left-4 bg-terracotta-500 text-white text-sm font-bold px-4 py-1.5 rounded-xl shadow-lg">On Sale</span>
                        @endif
                    </div>
                    @if($product->images->count() > 1)
                        <div class="flex gap-3 overflow-x-auto pb-2">
                            @foreach($product->images as $image)
                                <button type="button" @click="activeImage = '{{ $image->url }}'; mainLoaded = false"
                                        class="shrink-0 w-20 h-20 rounded-xl overflow-hidden border-2 border-transparent hover:border-sage-500 transition-all duration-300 focus:border-sage-600">
                                    <img src="{{ $image->url }}" alt="" class="w-full h-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
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
                                <span class="inline-flex items-center gap-2 text-green-700 bg-green-50 px-4 py-2 rounded-xl text-sm font-semibold">
                                    <span class="w-2.5 h-2.5 bg-green-500 rounded-full animate-pulse"></span>
                                    <span x-text="'Available — ' + selected.stock + ' in stock'"></span>
                                </span>
                            </div>
                        </template>
                        <template x-if="selected && !selected.in_stock">
                            <span class="inline-flex items-center gap-2 text-red-700 bg-red-50 px-4 py-2 rounded-xl text-sm font-semibold">Out of Stock</span>
                        </template>
                    </div>

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
                        <span class="inline-flex items-center gap-2 text-red-700 bg-red-50 px-4 py-2 rounded-xl text-sm font-semibold mb-8">All sizes out of stock</span>
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
