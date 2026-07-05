@props(['count' => null, 'countLabel' => 'items'])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center justify-between gap-4 mb-6']) }}>
  <div>
    @if($count !== null)
      <p class="text-slate-500 text-sm"><span class="font-bold text-slate-900">{{ $count }}</span> {{ $countLabel }}</p>
    @endif
    @isset($subtitle)
      <p class="text-slate-500 text-sm mt-0.5">{{ $subtitle }}</p>
    @endisset
  </div>
  @isset($action)
    <div>{{ $action }}</div>
  @endisset
</div>
