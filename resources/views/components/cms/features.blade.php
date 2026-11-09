@props(['data', 'sid' => ''])

@php
    $variant = $data['variant'] ?? 'cards';
    $bg = \App\Support\Cms\Style::section($data['background'] ?? 'white');
    $items = $data['items'] ?? [];
    $hasHeading = filled($data['title'] ?? null) || filled($data['eyebrow'] ?? null);
@endphp

@if($variant === 'compact')
    <section id="{{ $sid }}" class="bg-white relative z-10 -mt-6 sm:-mt-8 mx-4 md:mx-8 lg:mx-auto max-w-6xl rounded-2xl shadow-xl shadow-charcoal-900/5 border border-oat-200">
        <div class="px-4 sm:px-6 py-6 sm:py-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
                @foreach($items as $i => $item)
                    <x-reveal type="fade-up" :delay="$i * 80">
                        <x-icon-feature-card size="sm" :icon="$item['icon'] ?: 'sparkles'" :title="$item['title']" :description="$item['text'] ?: null" />
                    </x-reveal>
                @endforeach
            </div>
        </div>
    </section>
@else
    <section id="{{ $sid }}" class="py-14 sm:py-16 md:py-20 {{ $bg['class'] }}">
        @if($bg['dark'])<div class="absolute inset-0 bg-mesh opacity-10 pointer-events-none animate-drift"></div>@endif
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($hasHeading)
                <x-reveal type="fade-up" class="text-center mb-10 md:mb-14">
                    @if(filled($data['eyebrow'] ?? null))<span class="{{ $bg['dark'] ? 'inline-block px-4 py-1.5 rounded-full bg-white/10 text-gold-300 text-sm font-semibold mb-4 border border-white/10' : 'section-badge' }}">{{ $data['eyebrow'] }}</span>@endif
                    @if(filled($data['title'] ?? null))<h2 class="section-title {{ $bg['dark'] ? '!text-white' : '' }}">{{ $data['title'] }}</h2>@endif
                    @if(filled($data['subtitle'] ?? null))<p class="section-subtitle mx-auto {{ $bg['dark'] ? '!text-oat-100/70' : '' }}">{{ $data['subtitle'] }}</p>@endif
                </x-reveal>
            @endif

            <div class="grid gap-5 sm:gap-6 md:gap-8 {{ \App\Support\Cms\Style::columns($data['columns'] ?? '3') }}">
                @foreach($items as $i => $item)
                    <x-reveal type="fade-up" :delay="($i % 3) * 100">
                        @if($variant === 'list')
                            <div class="group flex gap-4 p-5 sm:p-6 bg-white rounded-2xl border border-slate-100 shadow-card card-hover">
                                <div class="shrink-0 w-12 h-12 rounded-xl bg-terracotta-50 text-terracotta-500 flex items-center justify-center group-hover:bg-sage-600 group-hover:text-white group-hover:scale-110 transition-all duration-300">
                                    <x-icon :name="$item['icon'] ?: 'sparkles'" class="w-6 h-6" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-charcoal-700 group-hover:text-sage-700 transition-colors">{{ $item['title'] }}</h3>
                                    @if(filled($item['text']))<p class="text-sm text-slate-500 mt-1 leading-relaxed">{{ $item['text'] }}</p>@endif
                                </div>
                            </div>
                        @else
                            <x-icon-feature-card
                                :icon="$item['icon'] ?: 'sparkles'"
                                :title="$item['title']"
                                :description="$item['text'] ?: null"
                                :step="$variant === 'numbered' ? sprintf('%02d', $i + 1) : null"
                            />
                        @endif
                    </x-reveal>
                @endforeach
            </div>
        </div>
    </section>
@endif
