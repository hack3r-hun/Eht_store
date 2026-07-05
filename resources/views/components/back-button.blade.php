@props([
    'href' => null,
    'label' => 'Back',
    'iconOnly' => false,
])

@php
    $fallback = $href ?? route('home');
@endphp

<a
    href="{{ $fallback }}"
    data-back-nav
    {{ $attributes->merge(['class' => 'back-button group relative z-20'.($iconOnly ? ' back-button-icon-only' : '')]) }}
    aria-label="{{ $label }}"
>
    <span class="back-button-icon pointer-events-none">
        <x-icon name="arrow-left" class="w-5 h-5" />
    </span>
    @unless($iconOnly)
        <span>{{ $label }}</span>
    @endunless
</a>
