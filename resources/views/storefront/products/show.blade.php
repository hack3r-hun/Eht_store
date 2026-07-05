@extends('layouts.storefront')

@section('title', $product->name)
{{-- Fall back to the tagline: a null value here would make Blade open a
     content-block buffer that is never closed. --}}
@section('meta_description', Str::limit($product->description ?? '', 160) ?: shop_config('tagline'))

@section('content')
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
            {{-- Image gallery --}}
            <x-reveal type="fade-right">
                <div class="space-y-4" x-data="{ activeImage: '{{ $product->image_url }}' }">
                    <div class="relative aspect-square bg-white rounded-3xl border border-slate-100 shadow-card overflow-hidden group">
                        <img :src="activeImage" alt="{{ $product->name }}"
                             class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                        @if($product->is_on_sale)
                            <span class="absolute top-4 left-4 bg-terracotta-500 text-white text-sm font-bold px-4 py-1.5 rounded-xl shadow-lg">On Sale</span>
                        @endif
                    </div>
                    @if($product->images->count() > 1)
                        <div class="flex gap-3 overflow-x-auto pb-2">
                            @foreach($product->images as $image)
                                <button type="button" @click="activeImage = '{{ $image->url }}'"
                                        class="shrink-0 w-20 h-20 rounded-xl overflow-hidden border-2 border-transparent hover:border-sage-500 transition-all duration-300 focus:border-sage-600">
                                    <img src="{{ $image->url }}" alt="" class="w-full h-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </x-reveal>

            {{-- Product info --}}
            <x-reveal type="fade-left" delay="150">
                <div class="lg:sticky lg:top-24">
                    <a href="{{ route('products.index', ['category' => $product->category->slug]) }}"
                       class="inline-flex items-center gap-1 text-sm font-semibold text-sage-700 bg-oat-100 px-3 py-1 rounded-lg hover:bg-sage-50 transition-colors mb-4">
                        {{ $product->category->name }}
                    </a>
                    <h1 class="text-3xl md:text-4xl font-bold text-charcoal-700 mb-3 leading-tight">{{ $product->name }}</h1>
                    <p class="text-sm text-slate-500 mb-6">SKU: <span class="font-mono font-medium text-slate-700">{{ $product->sku }}</span></p>

                    <div class="flex flex-wrap items-center gap-4 mb-6 p-5 bg-oat-100/70 rounded-2xl">
                        <span class="text-4xl font-bold text-charcoal-700">{{ shop_money($product->effective_price) }}</span>
                        @if($product->is_on_sale)
                            <span class="text-xl text-slate-400 line-through">{{ shop_money($product->price) }}</span>
                            @php $discount = round((1 - $product->effective_price / $product->price) * 100); @endphp
                            <span class="bg-red-100 text-red-700 text-sm font-bold px-3 py-1 rounded-lg">-{{ $discount }}%</span>
                        @endif
                    </div>

                    @if($product->is_in_stock)
                        <div class="flex items-center gap-3 mb-6">
                            <span class="inline-flex items-center gap-2 text-green-700 bg-green-50 px-4 py-2 rounded-xl text-sm font-semibold">
                                <span class="w-2.5 h-2.5 bg-green-500 rounded-full animate-pulse"></span>
                                Available - {{ $product->stock_quantity }} in stock
                            </span>
                            @if($product->is_low_stock)
                                <span class="text-terracotta-500 text-sm font-medium">Only a few left!</span>
                            @endif
                        </div>
                    @else
                        <span class="inline-flex items-center gap-2 text-red-700 bg-red-50 px-4 py-2 rounded-xl text-sm font-semibold mb-6">Out of Stock</span>
                    @endif

                    <p class="text-slate-600 leading-relaxed mb-8 text-lg">{{ $product->description }}</p>

                    @if($product->is_in_stock)
                        <form
                            action="{{ route('cart.store') }}"
                            method="POST"
                            class="flex flex-wrap items-stretch gap-4 mb-8"
                            x-data="{ submitting: false }"
                            @submit="if (submitting) { $event.preventDefault(); return; } submitting = true"
                        >
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <div class="flex items-center border-2 border-oat-200 rounded-xl overflow-hidden bg-white focus-within:border-sage-500 transition-colors">
                                <button type="button" onclick="const i=document.getElementById('qty');if(i.value>1)i.value--"
                                        class="px-4 py-3 text-charcoal-500 hover:bg-oat-100 hover:text-sage-700 transition-colors text-xl font-bold">-</button>
                                <input type="number" name="quantity" id="qty" value="1" min="1" max="{{ $product->stock_quantity }}"
                                       class="w-16 py-3 text-center border-0 focus:ring-0 font-semibold">
                                <button type="button" onclick="const i=document.getElementById('qty');if(i.value<{{ $product->stock_quantity }})i.value++"
                                        class="px-4 py-3 text-charcoal-500 hover:bg-oat-100 hover:text-sage-700 transition-colors text-xl font-bold">+</button>
                            </div>
                            <button type="submit" class="btn-primary flex-1 text-base py-4 inline-flex items-center justify-center gap-2" :disabled="submitting" :class="submitting && 'opacity-60 cursor-not-allowed'">
                                <x-icon name="cart" class="w-5 h-5" />
                                <span x-text="submitting ? 'Adding...' : 'Add to Cart'">Add to Cart</span>
                            </button>
                        </form>
                    @endif

                    {{-- Trust mini badges --}}
                    <div class="grid grid-cols-3 gap-3 mb-8">
                        @foreach([['heart', 'Handmade'], ['truck', 'Delivery'], ['check-circle', 'Quality Yarn']] as $badge)
                            <div class="text-center p-3 bg-white rounded-xl border border-slate-100 text-xs">
                                <div class="w-8 h-8 mx-auto mb-1 rounded-lg bg-oat-100 text-terracotta-500 flex items-center justify-center">
                                    <x-icon :name="$badge[0]" class="w-4 h-4" />
                                </div>
                                <span class="text-slate-600 font-medium">{{ $badge[1] }}</span>
                            </div>
                        @endforeach
                    </div>

                    @if($product->specifications)
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-6 card-glow">
                            <h3 class="font-bold text-charcoal-700 mb-4 flex items-center gap-2">
                                <span class="w-8 h-8 rounded-lg bg-oat-100 text-terracotta-500 flex items-center justify-center"><x-icon name="clipboard" class="w-4 h-4" /></span>
                                Details
                            </h3>
                            <dl class="space-y-3">
                                @foreach($product->specifications as $key => $value)
                                    <div class="flex justify-between text-sm py-2 border-b border-slate-50 last:border-0">
                                        <dt class="text-slate-500">{{ $key }}</dt>
                                        <dd class="font-semibold text-charcoal-700">{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif
                </div>
            </x-reveal>
        </div>

        @if($relatedProducts->isNotEmpty())
            <div class="mt-24">
                <x-reveal type="fade-up" class="mb-10">
                    <span class="section-badge">You May Also Like</span>
                    <h2 class="section-title">Related Products</h2>
                </x-reveal>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($relatedProducts as $i => $related)
                        <x-reveal type="fade-up" :delay="$i * 80">
                            <x-product-card :product="$related" />
                        </x-reveal>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
@endsection
