@props(['data', 'sid' => ''])

@php
    $bg = \App\Support\Cms\Style::section($data['background'] ?? 'gradient', 'gradient');
    $source = $data['source'] ?? 'featured';
    $limit = max(1, min(24, (int) ($data['limit'] ?? 8)));

    $query = \App\Models\Product::with(['images', 'category', 'activeVariants'])
        ->withCount('approvedReviews')
        ->withAvg('approvedReviews', 'rating')
        ->where('is_active', true);

    if ($source === 'featured') {
        $query->where('is_featured', true);
    } elseif ($source === 'category' && filled($data['category'] ?? null)) {
        $slug = $data['category'];
        $query->whereHas('category', fn ($q) => $q->where('slug', $slug)->orWhereHas('parent', fn ($p) => $p->where('slug', $slug)));
    }

    $products = $query->latest()->take($limit)->get();

    $linkUrl = match (true) {
        $source === 'category' && filled($data['category'] ?? null) => route('products.index', ['category' => $data['category']]),
        $source === 'latest' => route('products.index', ['sort' => 'latest']),
        default => route('products.index'),
    };
@endphp

@if($products->isNotEmpty())
    <section id="{{ $sid }}" class="py-14 sm:py-16 md:py-20 {{ $bg['class'] }}">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-reveal type="fade-up" class="flex flex-wrap items-end justify-between gap-4 mb-8 md:mb-10">
                <div>
                    @if(filled($data['eyebrow'] ?? null))<span class="{{ $bg['dark'] ? 'inline-block px-4 py-1.5 rounded-full bg-white/10 text-gold-300 text-sm font-semibold mb-4 border border-white/10' : 'section-badge' }}">{{ $data['eyebrow'] }}</span>@endif
                    @if(filled($data['title'] ?? null))<h2 class="section-title {{ $bg['dark'] ? '!text-white' : '' }}">{{ $data['title'] }}</h2>@endif
                    @if(filled($data['subtitle'] ?? null))<p class="section-subtitle {{ $bg['dark'] ? '!text-oat-100/70' : '' }}">{{ $data['subtitle'] }}</p>@endif
                </div>
                @if(filled($data['link_text'] ?? null))
                    <a href="{{ $linkUrl }}" class="text-sage-700 font-semibold hover:text-sage-800 transition-colors flex items-center gap-1 group">
                        {{ $data['link_text'] }} <span class="group-hover:translate-x-1 transition-transform">-&gt;</span>
                    </a>
                @endif
            </x-reveal>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5 md:gap-6">
                @foreach($products as $i => $product)
                    <x-reveal type="fade-up" :delay="($i % 4) * 80">
                        <x-product-card :product="$product" />
                    </x-reveal>
                @endforeach
            </div>
        </div>
    </section>
@endif
