@props(['hover' => true, 'padding' => 'p-6'])

<div {{ $attributes->merge([
    'class' => trim('store-card '.$padding.($hover ? ' card-hover' : '')),
]) }}>
    {{ $slot }}
</div>
