@props(['data', 'sid' => ''])

@php
    $bg = \App\Support\Cms\Style::section($data['background'] ?? 'white');
    $center = ($data['align'] ?? 'left') === 'center';
    $width = ($data['width'] ?? 'narrow') === 'wide' ? 'max-w-5xl' : 'max-w-3xl';
@endphp

<section id="{{ $sid }}" class="py-12 sm:py-16 md:py-20 {{ $bg['class'] }}">
    <div class="relative {{ $width }} mx-auto px-4 sm:px-6 lg:px-8 {{ $center ? 'text-center' : '' }}">
        <x-reveal type="fade-up">
            @if(filled($data['eyebrow'] ?? null))<span class="{{ $bg['dark'] ? 'inline-block px-4 py-1.5 rounded-full bg-white/10 text-gold-300 text-sm font-semibold mb-4 border border-white/10' : 'section-badge' }}">{{ $data['eyebrow'] }}</span>@endif
            @if(filled($data['title'] ?? null))<h2 class="section-title {{ $bg['dark'] ? '!text-white' : '' }}">{{ $data['title'] }}</h2>@endif
            @if(filled($data['subtitle'] ?? null))<p class="section-subtitle {{ $center ? 'mx-auto' : '' }} {{ $bg['dark'] ? '!text-oat-100/70' : '' }}">{{ $data['subtitle'] }}</p>@endif
            @if(filled($data['content'] ?? null))
                <div class="cms-prose mt-6 {{ $bg['dark'] ? 'cms-prose-dark' : '' }}">{!! $data['content'] !!}</div>
            @endif
        </x-reveal>
    </div>
</section>
