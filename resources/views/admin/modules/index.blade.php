@extends('layouts.admin')

@section('page-title', 'Modules')

@section('content')
<div x-data="{ dirty: false }">
    {{-- Stays in view while scrolling so the Save button is always reachable. --}}
    <div class="sticky top-[4.5rem] z-20 -mx-1 px-1 py-3 mb-3 bg-slate-50/95 backdrop-blur flex items-center justify-between gap-4">
        <div class="min-w-0">
            <p class="text-slate-500 text-sm"><span class="font-bold text-slate-900">{{ $enabledCount }} / {{ $total }}</span> modules switched on</p>
            <p class="text-slate-500 text-sm mt-0.5">Switch features on or off here. Press Edit on a module to change its text, pictures and options on its own screen.</p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <p class="text-sm hidden md:block" :class="dirty ? 'text-amber-600 font-medium' : 'text-slate-400'" x-text="dirty ? '● Unsaved changes' : 'All changes saved'"></p>
            <button type="submit" form="modules-switch-form" class="btn-primary !py-2.5 !px-6 sm:!px-8 whitespace-nowrap">Save switches</button>
        </div>
    </div>

    <form id="modules-switch-form" action="{{ route('admin.modules.update') }}" method="POST" class="space-y-8 pb-12 max-w-5xl" @input="dirty = true" @change="dirty = true" @submit="dirty = false">
        @csrf @method('PUT')

        @foreach($groups as $group => $modules)
            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-3">{{ $group }}</h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    @foreach($modules as $module)
                        @php $key = $module['key']; $editable = count($module['fields']) > 0; @endphp
                        <div class="admin-card admin-card-pad h-full" x-data="{ on: {{ old("modules.$key.enabled", $module['enabled']) ? 'true' : 'false' }} }">
                            <div class="flex items-start gap-3">
                                <span class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center transition-colors" :class="on ? 'bg-sage-100 text-sage-700' : 'bg-slate-100 text-slate-400'">
                                    <x-icon :name="$module['icon']" class="w-5 h-5" />
                                </span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-3">
                                        <h3 class="font-bold text-slate-900 leading-snug">{{ $module['title'] }}</h3>
                                        <label class="relative inline-flex shrink-0 cursor-pointer" title="Switch {{ $module['title'] }} on or off">
                                            <input type="hidden" name="modules[{{ $key }}][enabled]" value="0">
                                            <input type="checkbox" name="modules[{{ $key }}][enabled]" value="1" x-model="on" class="sr-only peer" aria-label="{{ $module['title'] }}">
                                            <span class="w-11 h-6 rounded-full bg-slate-300 peer-checked:bg-sage-600 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:after:translate-x-5"></span>
                                        </label>
                                    </div>
                                    <p class="text-sm text-slate-500 mt-1">{{ $module['description'] }}</p>
                                    <p class="mt-2 text-xs font-semibold" :class="on ? 'text-green-600' : 'text-slate-400'" x-text="on ? '● On' : '○ Off'"></p>

                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        @if($editable)
                                            <a href="{{ route('admin.modules.edit', $module['key']) }}" class="btn-outline !py-1.5 !px-4 text-sm inline-flex items-center gap-1.5">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.768-6.768a2 2 0 112.828 2.828L11.828 15.83a4 4 0 01-1.414.943L7 18l1.227-3.414A4 4 0 019 13z"/></svg>
                                                Edit
                                            </a>
                                        @endif

                                        @if(! empty($module['manage']) && \Illuminate\Support\Facades\Route::has($module['manage']))
                                            <a href="{{ route($module['manage']) }}" class="admin-action-link">{{ $module['manage_label'] ?? 'Manage' }} →</a>
                                        @endif

                                        @unless($editable)
                                            <span class="text-xs text-slate-400">Nothing else to edit — just the switch.</span>
                                        @endunless
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

    </form>
</div>
@endsection
