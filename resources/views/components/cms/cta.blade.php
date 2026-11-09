@props(['data', 'sid' => ''])

@if(filled($data['title'] ?? null))
    <section id="{{ $sid }}" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 md:py-20">
        <x-reveal type="scale">
            <x-cta-banner
                :title="$data['title']"
                :description="$data['text'] ?: null"
                :primary-label="$data['primary_text'] ?: null"
                :primary-href="cms_link($data['primary_url'] ?? null)"
                :secondary-label="$data['secondary_text'] ?: null"
                :secondary-href="cms_link($data['secondary_url'] ?? null)"
            />
        </x-reveal>
    </section>
@endif
