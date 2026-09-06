@props(['status'])

@php
    $classes = match ($status->color()) {
        'amber' => 'bg-amber-100 text-amber-700',
        'blue' => 'bg-blue-100 text-blue-700',
        'indigo' => 'bg-indigo-100 text-indigo-700',
        'green' => 'bg-green-100 text-green-700',
        'red' => 'bg-red-100 text-red-700',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp

<span {{ $attributes->class(['admin-badge capitalize', $classes]) }}>
    {{ $status->label() }}
</span>
