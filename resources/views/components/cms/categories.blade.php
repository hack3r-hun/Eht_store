@props(['data', 'sid' => ''])

@php
    $bg = \App\Support\Cms\Style::section($data['background'] ?? 'white');
    $categories = \App\Models\Category::withCount('activeProducts')
        ->where('is_active', true)
        ->whereNull('parent_id')
        ->orderBy('sort_order')
        ->take(max(1, min(12, (int) ($data['limit'] ?? 6))))
        ->get();
@endphp

@if($categories->isNotEmpty())
    <section id="{{ $sid }}" class="py-14 sm:py-16 md:py-20 {{ $bg['class'] }}">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(filled($data['title'] ?? null) || filled($data['eyebrow'] ?? null))
                <x-reveal type="fade-up" class="text-center mb-10 md:mb-12">
                    @if(filled($data['eyebrow'] ?? null))<span class="{{ $bg['dark'] ? 'inline-block px-4 py-1.5 rounded-full bg-white/10 text-gold-300 text-sm font-semibold mb-4 border border-white/10' : 'section-badge' }}">{{ $data['eyebrow'] }}</span>@endif
                    @if(filled($data['title'] ?? null))<h2 class="section-title {{ $bg['dark'] ? '!text-white' : '' }}">{{ $data['title'] }}</h2>@endif
                    @if(filled($data['subtitle'] ?? null))<p class="section-subtitle mx-auto {{ $bg['dark'] ? '!text-oat-100/70' : '' }}">{{ $data['subtitle'] }}</p>@endif
                </x-reveal>
            @endif
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-6">
                @foreach($categories as $i => $category)
                    <x-reveal type="scale" :delay="($i % 6) * 60">
                        <a href="{{ route('products.index', ['category' => $category->slug]) }}"
                           class="group block bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden card-glow card-hover">
                            <div class="aspect-[4/3] overflow-hidden bg-oat-100">
                                <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy">
                            </div>
                            <div class="p-3 sm:p-4 text-center">
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
