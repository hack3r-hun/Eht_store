@extends('layouts.storefront')

@section('title', 'My Wishlist')

@section('content')
    <section class="hero-section py-10 md:py-14">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-reveal type="fade-up">
                <span class="hero-eyebrow">Saved For Later</span>
                <h1 class="hero-title !text-3xl md:!text-4xl !mb-0">My Wishlist</h1>
            </x-reveal>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @if($products->isEmpty())
            <div class="text-center py-20 bg-white rounded-2xl border border-slate-100 shadow-card animate-fade-in">
                <x-icon name="heart" class="w-16 h-16 mx-auto mb-4 text-slate-300" />
                <h2 class="text-xl font-semibold text-charcoal-700 mb-2">Nothing saved yet</h2>
                <p class="text-slate-500 mb-8">Tap the heart on any piece you love and it will wait for you here.</p>
                <a href="{{ route('products.index') }}" class="btn-primary">Browse Products</a>
            </div>
        @else
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                @foreach($products as $product)
                    <x-reveal type="fade-up" :delay="($loop->index % 4) * 80">
                        <x-product-card :product="$product" />
                    </x-reveal>
                @endforeach
            </div>
        @endif
    </section>
@endsection
