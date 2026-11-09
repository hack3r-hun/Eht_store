@props(['data', 'sid' => ''])

@php
    $bg = \App\Support\Cms\Style::section($data['background'] ?? 'white');
    $dark = $bg['dark'];
    $image = cms_image($data['image'] ?? null);
    $imageLeft = ($data['image_side'] ?? 'right') === 'left';
    $chips = $data['chips'] ?? [];
@endphp

<section id="{{ $sid }}" class="py-12 sm:py-16 md:py-20 {{ $bg['class'] }}">
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 {{ $image ? 'lg:grid-cols-2' : '' }} gap-8 sm:gap-12 items-center">
            <x-reveal :type="$imageLeft ? 'fade-left' : 'fade-right'" class="{{ $imageLeft && $image ? 'lg:order-2' : '' }}">
                @if(filled($data['eyebrow'] ?? null))<span class="{{ $dark ? 'inline-block px-4 py-1.5 rounded-full bg-white/10 text-gold-300 text-sm font-semibold mb-4 border border-white/10' : 'section-badge' }}">{{ $data['eyebrow'] }}</span>@endif
                @if(filled($data['title'] ?? null))<h2 class="section-title {{ $dark ? '!text-white' : '' }}">{{ $data['title'] }}</h2>@endif
                @if(filled($data['subtitle'] ?? null))<p class="section-subtitle {{ $dark ? '!text-oat-100/70' : '' }}">{{ $data['subtitle'] }}</p>@endif
                @if(filled($data['content'] ?? null))
                    <div class="cms-prose mt-6 {{ $dark ? 'cms-prose-dark' : '' }}">{!! $data['content'] !!}</div>
                @endif
                @if(filled($data['button_text'] ?? null))
                    <a href="{{ cms_link($data['button_url'] ?? null) }}" class="btn-primary mt-8">{{ $data['button_text'] }}</a>
                @endif
            </x-reveal>

            @if($image)
                <x-reveal :type="$imageLeft ? 'fade-right' : 'fade-left'" delay="150" class="{{ $imageLeft ? 'lg:order-1' : '' }}">
                    <div>
                        <div class="rounded-3xl overflow-hidden border border-oat-200 shadow-soft">
                            <img src="{{ $image }}" alt="{{ $data['image_alt'] ?: ($data['title'] ?? '') }}" class="w-full h-60 sm:h-72 md:h-80 object-cover" loading="lazy">
                        </div>
                        @if(count($chips))
                            <div class="grid grid-cols-2 gap-3 sm:gap-4 mt-6">
                                @foreach($chips as $chip)
                                    <div class="flex items-center gap-3 rounded-2xl bg-white border border-oat-200 p-3 sm:p-4 shadow-card">
                                        <x-icon :name="$chip['icon'] ?: 'heart'" class="w-6 h-6 shrink-0 text-terracotta-500" />
                                        <p class="font-semibold text-charcoal-700 text-sm">{{ $chip['label'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </x-reveal>
            @endif
        </div>
    </div>
</section>
