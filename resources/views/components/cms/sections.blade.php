@props(['page', 'part' => 'all'])

@php
    // "top" = the leading hero of a page that also has built-in functionality below it (forms, grids);
    // "bottom" = everything after that hero. "all" renders the whole layout in order.
    $resolved = $page->resolvedSections();
    $firstIsHero = ($resolved[0]['type'] ?? null) === 'hero';

    $selected = match ($part) {
        'top' => $firstIsHero ? [$resolved[0]] : [],
        'bottom' => array_slice($resolved, $firstIsHero ? 1 : 0),
        default => $resolved,
    };

    $selected = array_values(array_filter($selected, fn ($s) => $s['enabled'] ?? true));
@endphp

@foreach($selected as $section)
    <x-dynamic-component
        :component="'cms.'.str_replace('_', '-', $section['type'])"
        :data="$section['data'] ?? []"
        :sid="$section['id'] ?? ''"
    />
@endforeach
