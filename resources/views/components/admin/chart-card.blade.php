@props(['id', 'title', 'help' => null, 'empty' => false, 'height' => 'h-64'])

{{-- A titled card around one chart canvas, with a plain-language "how to read this" line and an empty state. --}}
<div {{ $attributes->merge(['class' => 'admin-card admin-card-pad h-full flex flex-col']) }}>
    <h3 class="font-bold text-slate-900">{{ $title }}</h3>
    @if($help)<p class="text-sm text-slate-500 mt-1 mb-4">{{ $help }}</p>@endif

    @if($empty)
        <div class="flex-1 min-h-[10rem] rounded-xl border border-dashed border-slate-200 bg-slate-50/60 flex flex-col items-center justify-center text-center px-4 py-8 text-slate-400">
            <x-icon name="chart" class="w-8 h-8 mb-2" />
            <p class="text-sm">Nothing to show for this period yet.</p>
        </div>
    @else
        <div class="relative {{ $height }}"><canvas id="{{ $id }}" role="img" aria-label="{{ $title }} chart"></canvas></div>
    @endif

    @isset($footer)<div class="mt-4">{{ $footer }}</div>@endisset
</div>
