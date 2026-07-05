@props(['type' => 'fade-up', 'delay' => null])

<div {{ $attributes->merge(['class' => '']) }} data-reveal="{{ $type }}" @if($delay) data-reveal-delay="{{ $delay }}" @endif>
    {{ $slot }}
</div>
