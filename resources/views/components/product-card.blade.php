@props(['product'])

@php
    $fromPrice = $product->from_price;
    $hasMultipleSizes = $product->active_size_count > 1;
    $anyOnSale = $product->relationLoaded('activeVariants')
        ? $product->activeVariants->contains(fn ($v) => $v->is_on_sale)
        : $product->is_on_sale;
@endphp

<article class="group bg-white rounded-2xl border border-oat-200 shadow-card overflow-hidden card-glow card-hover h-full flex flex-col">
    <a
        href="{{ route('products.show', $product->slug) }}"
        class="block relative overflow-hidden aspect-square bg-gradient-to-br from-oat-100 to-terracotta-50"
        x-data="{
            loaded: false,
            rx: 0,
            ry: 0,
            tilt(e) {
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
                const r = $el.getBoundingClientRect();
                this.ry = (((e.clientX - r.left) / r.width) - 0.5) * 10;
                this.rx = -(((e.clientY - r.top) / r.height) - 0.5) * 10;
            },
            resetTilt() { this.rx = 0; this.ry = 0; },
        }"
        x-init="if ($refs.thumb.complete) loaded = true"
        @mousemove="tilt($event)"
        @mouseleave="resetTilt()"
        :style="`transform: perspective(700px) rotateX(${rx}deg) rotateY(${ry}deg); transform-style: preserve-3d; will-change: transform;`"
    >
        <div class="img-skeleton absolute inset-0" x-show="!loaded" x-transition:leave="transition-opacity duration-300" x-transition:leave-end="opacity-0" x-cloak></div>
        <img
            x-ref="thumb"
            src="{{ $product->thumb_url }}"
            alt="{{ $product->name }}"
            width="400"
            height="400"
            class="w-full h-full object-cover transition-all duration-700 group-hover:scale-110"
            :class="loaded ? 'opacity-100' : 'opacity-0'"
            loading="lazy"
            decoding="async"
            @load="loaded = true"
            @@error="loaded = true"
        >
        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
        @if($anyOnSale)
            <span class="absolute top-3 left-3 badge-sale">Sale</span>
        @endif
        @if($product->is_featured)
            <span class="absolute top-3 right-3 bg-sage-600 text-white text-xs font-bold px-2.5 py-1 rounded-lg shadow-lg">Featured</span>
        @elseif(!$product->is_in_stock)
            <span class="absolute top-3 right-3 bg-red-500 text-white text-xs font-bold px-2.5 py-1 rounded-lg">Out of Stock</span>
        @endif
        <div class="absolute bottom-3 left-3 right-3 opacity-100 translate-y-0 md:opacity-0 md:translate-y-2 md:group-hover:opacity-100 md:group-hover:translate-y-0 transition-all duration-300">
            <span class="block text-center text-xs font-semibold text-white bg-charcoal-700/90 backdrop-blur-sm py-2 rounded-lg">
                {{ $hasMultipleSizes ? 'Choose size' : 'View Details' }}
            </span>
        </div>
    </a>
    <div class="p-5 flex-1 flex flex-col">
        <p class="text-xs font-semibold text-sage-700 mb-1.5 uppercase tracking-wide">{{ $product->category->name }}</p>
        <a href="{{ route('products.show', $product->slug) }}">
            <h3 class="font-semibold text-charcoal-700 line-clamp-2 group-hover:text-sage-700 transition-colors duration-300">{{ $product->name }}</h3>
        </a>
        <div class="mt-auto pt-4 flex items-center justify-between gap-2">
            <div>
                @if($hasMultipleSizes)
                    <span class="text-xs text-slate-500 block">From</span>
                @endif
                <span class="text-xl font-bold text-charcoal-700">{{ shop_money($fromPrice) }}</span>
            </div>
            @if($product->is_in_stock)
                <a
                    href="{{ route('products.show', $product->slug) }}"
                    class="p-2.5 rounded-xl bg-oat-100 text-sage-700 hover:bg-terracotta-500 hover:text-white hover:scale-110 hover:shadow-lg hover:shadow-terracotta-500/20 active:scale-95 transition-all duration-300"
                    title="{{ $hasMultipleSizes ? 'Choose size' : 'View product' }}"
                >
                    <x-icon name="cart" class="w-5 h-5" />
                </a>
            @endif
        </div>
    </div>
</article>
