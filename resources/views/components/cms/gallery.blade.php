@props(['data', 'sid' => ''])

@php
    $bg = \App\Support\Cms\Style::section($data['background'] ?? 'white');
    $items = collect($data['items'] ?? [])->filter(fn ($i) => filled($i['image'] ?? null))->values();
@endphp

@if($items->isNotEmpty())
    <section id="{{ $sid }}" class="py-14 sm:py-16 md:py-20 {{ $bg['class'] }}">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(filled($data['title'] ?? null) || filled($data['eyebrow'] ?? null))
                <x-reveal type="fade-up" class="text-center mb-10 md:mb-12">
                    @if(filled($data['eyebrow'] ?? null))<span class="{{ $bg['dark'] ? 'inline-block px-4 py-1.5 rounded-full bg-white/10 text-gold-300 text-sm font-semibold mb-4 border border-white/10' : 'section-badge' }}">{{ $data['eyebrow'] }}</span>@endif
                    @if(filled($data['title'] ?? null))<h2 class="section-title {{ $bg['dark'] ? '!text-white' : '' }}">{{ $data['title'] }}</h2>@endif
                    @if(filled($data['subtitle'] ?? null))<p class="section-subtitle mx-auto {{ $bg['dark'] ? '!text-oat-100/70' : '' }}">{{ $data['subtitle'] }}</p>@endif
                </x-reveal>
            @endif
            <div class="grid gap-3 sm:gap-5 {{ \App\Support\Cms\Style::columns($data['columns'] ?? '3') }}">
                @foreach($items as $i => $item)
                    <x-reveal type="scale" :delay="($i % 4) * 80">
                        <figure class="group overflow-hidden rounded-2xl border border-oat-200 bg-white shadow-card">
                            <div class="aspect-[4/3] overflow-hidden bg-oat-100">
                                <img src="{{ cms_image($item['image']) }}" alt="{{ $item['caption'] ?: '' }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy">
                            </div>
                            @if(filled($item['caption']))<figcaption class="p-3 text-center text-sm font-medium text-charcoal-700">{{ $item['caption'] }}</figcaption>@endif
                        </figure>
                    </x-reveal>
                @endforeach
            </div>
        </div>
    </section>
@endif
