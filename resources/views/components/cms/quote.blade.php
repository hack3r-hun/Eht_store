@props(['data', 'sid' => ''])

@php $bg = \App\Support\Cms\Style::section($data['background'] ?? 'dark', 'dark'); $dark = $bg['dark']; @endphp

@if(filled($data['text'] ?? null))
    <section id="{{ $sid }}" class="py-14 sm:py-16 md:py-20 {{ $bg['class'] }}">
        @if($dark)<div class="absolute inset-0 bg-mesh opacity-10 pointer-events-none animate-drift"></div>@endif
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <x-reveal type="fade-up">
                <x-icon name="sparkles" class="w-10 h-10 mx-auto mb-6 {{ $dark ? 'text-gold-300' : 'text-terracotta-500' }}" />
                <p class="text-xl sm:text-2xl md:text-3xl font-semibold leading-snug {{ $dark ? 'text-white' : 'text-charcoal-700' }}">&quot;{{ $data['text'] }}&quot;</p>
                @if(filled($data['author'] ?? null))<p class="mt-6 font-medium {{ $dark ? 'text-oat-100/70' : 'text-slate-500' }}">— {{ $data['author'] }}</p>@endif
            </x-reveal>
        </div>
    </section>
@endif
