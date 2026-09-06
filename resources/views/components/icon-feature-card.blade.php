@props([
    'icon',
    'title',
    'description' => null,
    'step' => null,
    'size' => 'md',
])

@php
    $wrapperSize = $size === 'sm' ? 'w-12 h-12 rounded-xl mb-3' : 'icon-box mb-5';
    $iconSize = $size === 'sm' ? 'w-6 h-6' : 'w-7 h-7';
    $titleClass = $size === 'sm' ? 'font-semibold text-charcoal-700' : 'text-xl font-bold text-charcoal-700 mb-2';
    $descClass = $size === 'sm' ? 'text-sm text-slate-500 mt-1' : 'text-slate-500 text-sm leading-relaxed';
@endphp

<x-store-card class="relative text-center h-full flex flex-col items-center justify-center card-glow">
    @if($step)
        <span class="absolute top-4 right-4 text-4xl font-black text-oat-200">{{ $step }}</span>
    @endif
    <div class="{{ $wrapperSize }} mx-auto bg-terracotta-50 text-terracotta-500 flex items-center justify-center transition-all duration-300 group-hover:scale-110">
        <x-icon :name="$icon" class="{{ $iconSize }}" />
    </div>
    <h3 class="{{ $titleClass }}">{{ $title }}</h3>
    @if($description)
        <p class="{{ $descClass }}">{{ $description }}</p>
    @endif
</x-store-card>
