@props(['type' => 'success', 'message'])

@php
    $styles = match($type) {
        'success' => 'bg-green-50 border-green-200 text-green-800',
        'error' => 'bg-red-50 border-red-200 text-red-800',
        'info' => 'bg-blue-50 border-blue-200 text-blue-800',
        default => 'bg-slate-50 border-slate-200 text-slate-800',
    };
@endphp

<div {{ $attributes->merge(['class' => "rounded-xl border px-4 py-3 text-sm font-medium {$styles}"]) }}>
    {{ $message }}
</div>
