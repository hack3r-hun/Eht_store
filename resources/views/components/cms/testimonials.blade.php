@props(['data', 'sid' => ''])

@php
    $bg = \App\Support\Cms\Style::section($data['background'] ?? 'white');
    $reviews = collect($data['items'] ?? [])->filter(fn ($r) => filled($r['text'] ?? null))->values();
@endphp

@if($reviews->isNotEmpty())
    <section id="{{ $sid }}" class="py-14 sm:py-16 md:py-20 {{ $bg['class'] }}">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(filled($data['title'] ?? null) || filled($data['eyebrow'] ?? null))
                <x-reveal type="fade-up" class="text-center mb-10 md:mb-14">
                    @if(filled($data['eyebrow'] ?? null))<span class="{{ $bg['dark'] ? 'inline-block px-4 py-1.5 rounded-full bg-white/10 text-gold-300 text-sm font-semibold mb-4 border border-white/10' : 'section-badge' }}">{{ $data['eyebrow'] }}</span>@endif
                    @if(filled($data['title'] ?? null))<h2 class="section-title {{ $bg['dark'] ? '!text-white' : '' }}">{{ $data['title'] }}</h2>@endif
                    @if(filled($data['subtitle'] ?? null))<p class="section-subtitle mx-auto {{ $bg['dark'] ? '!text-oat-100/70' : '' }}">{{ $data['subtitle'] }}</p>@endif
                </x-reveal>
            @endif
            <div
                x-data="{
                    reviews: @js($reviews),
                    active: 0,
                    timer: null,
                    start() {
                        if (this.reviews.length < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
                        this.timer = setInterval(() => { this.active = (this.active + 1) % this.reviews.length; }, 5000);
                    },
                    select(i) { this.active = i; clearInterval(this.timer); this.start(); },
                    prev() { this.select((this.active - 1 + this.reviews.length) % this.reviews.length); },
                    next() { this.select((this.active + 1) % this.reviews.length); },
                }"
                x-init="start()"
                class="max-w-2xl mx-auto relative"
            >
                <template x-if="reviews.length > 1">
                    <button type="button" @click="prev()" class="hidden lg:flex absolute top-1/2 -translate-y-1/2 -left-16 w-11 h-11 rounded-full bg-white shadow-card hover:bg-oat-100 active:scale-95 items-center justify-center text-charcoal-600 transition-all" aria-label="Previous testimonial">
                        <x-icon name="arrow-left" class="w-5 h-5" />
                    </button>
                </template>
                <template x-if="reviews.length > 1">
                    <button type="button" @click="next()" class="hidden lg:flex absolute top-1/2 -translate-y-1/2 -right-16 w-11 h-11 rounded-full bg-white shadow-card hover:bg-oat-100 active:scale-95 items-center justify-center text-charcoal-600 transition-all" aria-label="Next testimonial">
                        <x-icon name="arrow-right" class="w-5 h-5" />
                    </button>
                </template>

                <div class="relative min-h-[22rem] sm:min-h-[19rem]">
                    <template x-for="(review, i) in reviews" :key="i">
                        <div x-show="active === i"
                             x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                             class="absolute inset-0 flex flex-col justify-center p-6 sm:p-8 bg-oat-100/60 rounded-2xl border border-oat-200">
                            <div class="text-gold-500 mb-4 flex gap-0.5 justify-center">
                                @for($s = 0; $s < 5; $s++)<x-icon name="star" class="w-5 h-5" />@endfor
                            </div>
                            <p class="text-slate-500 leading-relaxed italic text-center" x-text="'&quot;' + review.text + '&quot;'"></p>
                            <div class="mt-6 pt-6 border-t border-oat-200 text-center">
                                <p class="font-semibold text-charcoal-700" x-text="review.name"></p>
                                <p class="text-sm text-sage-700" x-text="review.role"></p>
                            </div>
                        </div>
                    </template>
                </div>
                <template x-if="reviews.length > 1">
                    <div class="flex items-center justify-center gap-4 mt-6">
                        <button type="button" @click="prev()" class="lg:hidden w-11 h-11 shrink-0 rounded-full bg-white shadow-card hover:bg-oat-100 active:scale-95 flex items-center justify-center text-charcoal-600 transition-all" aria-label="Previous testimonial">
                            <x-icon name="arrow-left" class="w-4 h-4" />
                        </button>
                        <div class="flex gap-2">
                            <template x-for="(review, i) in reviews" :key="i">
                                <button type="button" @click="select(i)" :class="active === i ? 'bg-terracotta-500 w-6' : 'bg-oat-300 w-2'" class="h-2 rounded-full transition-all duration-300" :aria-label="'Show testimonial ' + (i + 1)"></button>
                            </template>
                        </div>
                        <button type="button" @click="next()" class="lg:hidden w-11 h-11 shrink-0 rounded-full bg-white shadow-card hover:bg-oat-100 active:scale-95 flex items-center justify-center text-charcoal-600 transition-all" aria-label="Next testimonial">
                            <x-icon name="arrow-right" class="w-4 h-4" />
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </section>
@endif
