@props(['data', 'sid' => ''])

@php
    $bg = \App\Support\Cms\Style::section($data['background'] ?? 'oat', 'oat');
    $items = collect($data['items'] ?? [])->filter(fn ($i) => filled($i['q'] ?? null))->values();
@endphp

@if($items->isNotEmpty())
    <section id="{{ $sid }}" class="py-14 sm:py-16 md:py-20 {{ $bg['class'] }}">
        <div class="relative max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(filled($data['title'] ?? null) || filled($data['eyebrow'] ?? null))
                <x-reveal type="fade-up" class="text-center mb-10 md:mb-12">
                    @if(filled($data['eyebrow'] ?? null))<span class="{{ $bg['dark'] ? 'inline-block px-4 py-1.5 rounded-full bg-white/10 text-gold-300 text-sm font-semibold mb-4 border border-white/10' : 'section-badge' }}">{{ $data['eyebrow'] }}</span>@endif
                    @if(filled($data['title'] ?? null))<h2 class="section-title {{ $bg['dark'] ? '!text-white' : '' }}">{{ $data['title'] }}</h2>@endif
                    @if(filled($data['subtitle'] ?? null))<p class="section-subtitle mx-auto {{ $bg['dark'] ? '!text-oat-100/70' : '' }}">{{ $data['subtitle'] }}</p>@endif
                </x-reveal>
            @endif

            <div class="space-y-3 sm:space-y-4" x-data="{ open: null }">
                @foreach($items as $i => $faq)
                    <x-reveal type="fade-up" :delay="$i * 80">
                        <div class="bg-white rounded-2xl border border-oat-200 shadow-card overflow-hidden card-hover">
                            <button type="button" @click="open = open === {{ $i }} ? null : {{ $i }}" :aria-expanded="open === {{ $i }}"
                                    class="w-full flex items-center justify-between gap-4 px-5 sm:px-6 py-4 sm:py-5 text-left font-semibold text-charcoal-700 hover:text-sage-700 transition-colors">
                                <span>{{ $faq['q'] }}</span>
                                <svg class="w-5 h-5 shrink-0 transition-transform duration-300" :class="open === {{ $i }} && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open === {{ $i }}" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="px-5 sm:px-6 pb-5 text-slate-600 leading-relaxed">
                                {!! nl2br(e($faq['a'])) !!}
                            </div>
                        </div>
                    </x-reveal>
                @endforeach
            </div>
        </div>
    </section>
@endif
