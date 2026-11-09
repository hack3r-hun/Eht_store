@props(['data', 'sid' => ''])

@php
    $style = $data['style'] ?? 'center';
    $hasPrimary = filled($data['primary_text'] ?? null);
    $hasSecondary = filled($data['secondary_text'] ?? null);
    $image = cms_image($data['image'] ?? null);
    $split = $style === 'split' && $image;
@endphp

@if($split)
    <section class="hero-section" id="{{ $sid }}">
        <div class="absolute inset-x-0 bottom-0 h-24 bg-mesh opacity-60 pointer-events-none animate-drift"></div>
        {{-- Ambient aurora glow — pure CSS, transform/opacity only, skipped under reduced-motion --}}
        <div class="absolute -top-24 left-1/4 w-96 h-96 rounded-full bg-terracotta-300/20 blur-3xl pointer-events-none animate-drift"></div>
        <div class="absolute top-1/3 -right-16 w-80 h-80 rounded-full bg-sage-300/20 blur-3xl pointer-events-none animate-drift animation-delay-400"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20 w-full">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-12 items-center">
                <div>
                    @if(filled($data['eyebrow'] ?? null))
                        <x-reveal type="fade-up"><span class="hero-eyebrow">{{ $data['eyebrow'] }}</span></x-reveal>
                    @endif
                    <x-reveal type="fade-up" delay="120">
                        <h1 class="hero-title" data-split-words>{{ $data['title'] }}</h1>
                    </x-reveal>
                    @if(filled($data['subtitle'] ?? null))
                        <x-reveal type="fade-up" delay="240">
                            <p class="text-base sm:text-lg text-slate-500 mb-8 leading-relaxed max-w-xl">{{ $data['subtitle'] }}</p>
                        </x-reveal>
                    @endif
                    @if($hasPrimary || $hasSecondary)
                        <x-reveal type="fade-up" delay="360">
                            <div class="flex flex-wrap gap-3 sm:gap-4">
                                @if($hasPrimary)
                                    <a href="{{ cms_link($data['primary_url'] ?? null) }}" data-magnetic class="btn-primary text-base px-8 py-4">{{ $data['primary_text'] }}</a>
                                @endif
                                @if($hasSecondary)
                                    <a href="{{ cms_link($data['secondary_url'] ?? null) }}" class="btn-outline">{{ $data['secondary_text'] }}</a>
                                @endif
                            </div>
                        </x-reveal>
                    @endif
                </div>
                <x-reveal type="fade-left" delay="200">
                    <div class="relative lg:mb-6" data-hero-parallax>
                        <div
                            class="rounded-3xl overflow-hidden border border-oat-200 shadow-soft"
                            x-data="{ rx: 0, ry: 0 }"
                            @mousemove="
                                if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                                    const r = $el.getBoundingClientRect();
                                    ry = (((($event.clientX - r.left) / r.width) - 0.5) * 6);
                                    rx = -(((($event.clientY - r.top) / r.height) - 0.5) * 6);
                                }
                            "
                            @mouseleave="rx = 0; ry = 0"
                            :style="`transform: perspective(1000px) rotateX(${rx}deg) rotateY(${ry}deg); transform-style: preserve-3d;`"
                            style="transition: transform 0.3s ease-out;"
                        >
                            <img src="{{ $image }}" alt="{{ $data['title'] }}" class="w-full h-64 sm:h-80 lg:h-[480px] object-cover" fetchpriority="high">
                        </div>
                        @if(filled($data['badge_top'] ?? null))
                            <div class="absolute -top-3 right-3 sm:-top-4 sm:right-6 bg-sage-600 text-white rounded-xl shadow-lg px-3 py-1.5 sm:px-4 sm:py-2 text-xs sm:text-sm font-semibold animate-float">{{ $data['badge_top'] }}</div>
                        @endif
                        @if(filled($data['badge_title'] ?? null))
                            <div class="absolute -bottom-5 left-3 sm:-bottom-6 sm:left-6 bg-white rounded-2xl shadow-xl border border-oat-200 px-4 py-3 sm:px-5 sm:py-4 flex items-center gap-3 animate-float animation-delay-300">
                                <span class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-terracotta-500 text-white flex items-center justify-center"><x-icon name="heart" class="w-5 h-5" /></span>
                                <div>
                                    <p class="font-bold text-charcoal-700 text-sm">{{ $data['badge_title'] }}</p>
                                    @if(filled($data['badge_text'] ?? null))<p class="text-xs text-slate-500">{{ $data['badge_text'] }}</p>@endif
                                </div>
                            </div>
                        @endif
                    </div>
                </x-reveal>
            </div>
        </div>
    </section>
@else
    @php $centered = $style !== 'left'; @endphp
    <section class="hero-section py-12 sm:py-16 md:py-20" id="{{ $sid }}">
        <div class="absolute inset-x-0 bottom-0 h-20 bg-mesh opacity-50 pointer-events-none animate-drift"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 {{ $centered ? 'text-center' : '' }}">
            <x-reveal type="fade-up">
                @if(filled($data['eyebrow'] ?? null))<span class="hero-eyebrow">{{ $data['eyebrow'] }}</span>@endif
                <h1 class="hero-title {{ $centered ? 'mx-auto' : '' }}">{{ $data['title'] }}</h1>
            </x-reveal>
            @if(filled($data['subtitle'] ?? null))
                <x-reveal type="fade-up" delay="150">
                    <p class="text-base sm:text-lg md:text-xl text-slate-500 {{ $centered ? 'max-w-3xl mx-auto' : 'max-w-2xl' }} leading-relaxed">{{ $data['subtitle'] }}</p>
                </x-reveal>
            @endif
            @if($hasPrimary || $hasSecondary)
                <x-reveal type="fade-up" delay="250">
                    <div class="mt-8 flex flex-wrap gap-3 sm:gap-4 {{ $centered ? 'justify-center' : '' }}">
                        @if($hasPrimary)<a href="{{ cms_link($data['primary_url'] ?? null) }}" class="btn-primary">{{ $data['primary_text'] }}</a>@endif
                        @if($hasSecondary)<a href="{{ cms_link($data['secondary_url'] ?? null) }}" class="btn-outline">{{ $data['secondary_text'] }}</a>@endif
                    </div>
                </x-reveal>
            @endif
        </div>
    </section>
@endif
