@props(['data', 'sid' => ''])

@php
    $bg = \App\Support\Cms\Style::section($data['background'] ?? 'oat', 'oat');
    $dark = $bg['dark'];
    $items = $data['items'] ?? [];
@endphp

<section id="{{ $sid }}" class="{{ $dark ? 'py-14 sm:py-16 md:py-20' : 'py-12 sm:py-16' }} {{ $bg['class'] }}">
    @if($dark)<div class="absolute inset-0 bg-mesh opacity-10 pointer-events-none animate-drift"></div>@endif
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(filled($data['title'] ?? null) || filled($data['eyebrow'] ?? null))
            <x-reveal type="fade-up" class="text-center mb-10 md:mb-14 max-w-2xl mx-auto">
                @if(filled($data['eyebrow'] ?? null))<span class="{{ $dark ? 'inline-block px-4 py-1.5 rounded-full bg-white/10 text-gold-300 text-sm font-semibold mb-4 border border-white/10' : 'section-badge' }}">{{ $data['eyebrow'] }}</span>@endif
                @if(filled($data['title'] ?? null))<h2 class="text-3xl md:text-4xl font-bold mb-4 {{ $dark ? 'text-white' : 'text-charcoal-700' }}">{{ $data['title'] }}</h2>@endif
                @if(filled($data['subtitle'] ?? null))<p class="text-base sm:text-lg {{ $dark ? 'text-oat-100/70' : 'text-slate-500' }}">{{ $data['subtitle'] }}</p>@endif
            </x-reveal>
        @endif

        <div class="grid grid-cols-2 {{ count($items) === 3 ? 'md:grid-cols-3' : 'md:grid-cols-4' }} gap-4 sm:gap-6">
            @foreach($items as $i => $stat)
                <x-reveal type="scale" :delay="$i * 100">
                    @if($dark)
                        <div class="text-center p-5 sm:p-6 rounded-2xl bg-white/5 border border-white/10">
                            <div class="text-3xl md:text-4xl font-bold text-white" data-count="{{ (int) $stat['count'] }}" data-count-suffix="{{ $stat['suffix'] }}">0</div>
                            <div class="mt-2 text-oat-100/60 text-sm font-medium">{{ $stat['label'] }}</div>
                        </div>
                    @else
                        <div class="text-center p-5 sm:p-8 bg-white rounded-2xl border border-oat-200 shadow-card card-glow card-hover">
                            <div class="text-3xl sm:text-4xl md:text-5xl font-bold text-gradient" data-count="{{ (int) $stat['count'] }}" data-count-suffix="{{ $stat['suffix'] }}">0</div>
                            <div class="mt-2 text-slate-500 font-medium text-sm sm:text-base">{{ $stat['label'] }}</div>
                        </div>
                    @endif
                </x-reveal>
            @endforeach
        </div>
    </div>
</section>
