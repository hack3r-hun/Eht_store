@props(['data', 'sid' => ''])

<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-5">
    <x-reveal type="fade-up">
        <article @if(filled($data['anchor'] ?? null)) id="{{ $data['anchor'] }}" @endif class="store-card p-5 sm:p-6 md:p-8 scroll-mt-28">
            <h2 class="text-lg sm:text-xl font-bold text-charcoal-700 flex items-center gap-3 mb-4">
                <span class="w-10 h-10 shrink-0 rounded-xl bg-terracotta-50 text-terracotta-500 flex items-center justify-center"><x-icon :name="$data['icon'] ?: 'info'" class="w-5 h-5" /></span>
                {{ $data['title'] }}
            </h2>
            <div class="cms-prose">{!! $data['content'] !!}</div>
        </article>
    </x-reveal>
</section>
