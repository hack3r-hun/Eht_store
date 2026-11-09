@props([
    'title',
    'description' => null,
    'primaryLabel' => null,
    'primaryHref' => '#',
    'secondaryLabel' => null,
    'secondaryHref' => null,
    'secondaryIcon' => null,
])

<div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-terracotta-50 via-white to-sage-50 border border-oat-200 p-10 md:p-16 text-center">
    <div class="absolute inset-x-0 bottom-0 h-24 bg-mesh opacity-50 pointer-events-none animate-drift"></div>
    <div class="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-gold-200/40 blur-3xl pointer-events-none animate-float"></div>
    <div class="absolute -bottom-10 -left-10 w-40 h-40 rounded-full bg-sage-200/40 blur-3xl pointer-events-none animate-float animation-delay-300"></div>
    <div class="relative z-10">
        <h2 class="text-3xl md:text-4xl font-bold mb-4 text-charcoal-700">{{ $title }}</h2>
        @if($description)
            <p class="text-slate-500 mb-8 max-w-xl mx-auto text-lg">{{ $description }}</p>
        @endif
        <div class="flex flex-wrap justify-center gap-4">
            @if($primaryLabel)
                <a href="{{ $primaryHref }}" class="btn-primary">{{ $primaryLabel }}</a>
            @endif
            @if($secondaryLabel)
                <a href="{{ $secondaryHref }}" class="btn-outline inline-flex items-center gap-2">
                    @if($secondaryIcon)
                        <x-icon :name="$secondaryIcon" class="w-5 h-5" />
                    @endif
                    {{ $secondaryLabel }}
                </a>
            @endif
        </div>
    </div>
</div>
