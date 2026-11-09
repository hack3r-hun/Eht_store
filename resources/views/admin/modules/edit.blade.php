@extends('layouts.admin')

@section('page-title', 'Edit module')

@section('content')
    @php $key = $module['key']; @endphp

    <div class="max-w-3xl pb-24">
        <a href="{{ route('admin.modules.index') }}" class="admin-action-link mb-4 inline-flex">← All modules</a>

        <form action="{{ route('admin.modules.update-module', $key) }}" method="POST" enctype="multipart/form-data" class="space-y-6"
              x-data="{ on: {{ old("modules.$key.enabled", $module['enabled']) ? 'true' : 'false' }}, dirty: false }"
              @input="dirty = true" @change="dirty = true" @submit="dirty = false">
            @csrf @method('PUT')

            {{-- Title + on/off --}}
            <div class="admin-card admin-card-pad">
                <div class="flex items-start gap-4">
                    <span class="w-12 h-12 shrink-0 rounded-xl flex items-center justify-center transition-colors" :class="on ? 'bg-sage-100 text-sage-700' : 'bg-slate-100 text-slate-400'">
                        <x-icon :name="$module['icon']" class="w-6 h-6" />
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $module['group'] }}</p>
                        <h2 class="text-xl font-bold text-slate-900 leading-snug">{{ $module['title'] }}</h2>
                        <p class="text-sm text-slate-500 mt-1">{{ $module['description'] }}</p>
                    </div>
                    <label class="relative inline-flex shrink-0 cursor-pointer" title="Switch {{ $module['title'] }} on or off">
                        <input type="hidden" name="modules[{{ $key }}][enabled]" value="0">
                        <input type="checkbox" name="modules[{{ $key }}][enabled]" value="1" x-model="on" class="sr-only peer" aria-label="{{ $module['title'] }}">
                        <span class="w-11 h-6 rounded-full bg-slate-300 peer-checked:bg-sage-600 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:after:translate-x-5"></span>
                    </label>
                </div>
                <p class="mt-3 text-xs font-semibold" :class="on ? 'text-green-600' : 'text-slate-400'" x-text="on ? '● On — visible on the shop' : '○ Off — hidden from the shop'"></p>
                <p class="mt-2 text-xs text-amber-600" x-show="!on" x-cloak>This module is off. You can still prepare its settings below; they go live when you switch it on.</p>
            </div>

            {{-- Settings --}}
            <div class="admin-card admin-card-pad">
                <h3 class="font-bold text-slate-900 mb-4">Settings</h3>

                <div class="grid gap-5 sm:grid-cols-2">
                    @foreach($module['fields'] as $field)
                        @php
                            $name = "modules[$key][fields][{$field['key']}]";
                            $current = old("modules.$key.fields.{$field['key']}", $module['values'][$field['key']] ?? '');
                            $full = in_array($field['kind'], ['textarea', 'image', 'toggle'], true) || in_array($field['key'], ['heading', 'button_url', 'text', 'label', 'description'], true);
                        @endphp
                        <div class="{{ $full ? 'sm:col-span-2' : '' }}">
                            @if($field['kind'] === 'toggle')
                                <input type="hidden" name="{{ $name }}" value="0">
                                <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                                    <input type="checkbox" name="{{ $name }}" value="1" @checked($current) class="rounded text-sage-600 focus:ring-sage-500"> {{ $field['label'] }}
                                </label>
                            @else
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">{{ $field['label'] }}</label>

                                @if($field['kind'] === 'textarea')
                                    <textarea name="{{ $name }}" rows="3" maxlength="{{ $field['max'] ?? 1000 }}" class="input-field">{{ $current }}</textarea>
                                @elseif($field['kind'] === 'select')
                                    <select name="{{ $name }}" class="input-field">
                                        @foreach($field['options'] as $optValue => $optLabel)
                                            <option value="{{ $optValue }}" @selected((string) $current === (string) $optValue)>{{ $optLabel }}</option>
                                        @endforeach
                                    </select>
                                @elseif($field['kind'] === 'date')
                                    <input type="date" name="{{ $name }}" value="{{ $current }}" class="input-field">
                                @elseif($field['kind'] === 'image')
                                    @php $currentImage = $module['values'][$field['key']] ?? ''; @endphp
                                    <div class="flex flex-wrap items-start gap-4">
                                        @if($currentImage)
                                            <img src="{{ cms_image($currentImage) }}" alt="Current picture" class="w-32 h-32 object-cover rounded-xl border border-slate-200">
                                        @endif
                                        <div class="flex-1 min-w-[14rem] space-y-2">
                                            <input type="file" name="modules[{{ $key }}][upload][{{ $field['key'] }}]" accept="image/jpeg,image/png,image/webp" class="input-field">
                                            <p class="text-xs text-slate-400">JPG, PNG or WebP, up to 4&nbsp;MB. Uploading a new picture replaces the old one.</p>
                                            @if($currentImage)
                                                <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                                                    <input type="checkbox" name="modules[{{ $key }}][clear][{{ $field['key'] }}]" value="1" class="rounded text-sage-600 focus:ring-sage-500"> Remove the current picture
                                                </label>
                                            @endif
                                            @error("modules.$key.upload.{$field['key']}")<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                                        </div>
                                    </div>
                                @else
                                    <input type="{{ $field['kind'] === 'number' ? 'number' : 'text' }}" name="{{ $name }}" value="{{ $current }}"
                                           @if($field['kind'] === 'number') min="{{ $field['min'] ?? 0 }}" max="{{ $field['max'] ?? 1000000 }}" step="any" @else maxlength="{{ $field['max'] ?? 255 }}" @endif
                                           @if($key === 'whatsapp' && $field['key'] === 'number') placeholder="{{ shop_config('contact_phone') }}" @endif
                                           class="input-field">
                                @endif

                                @error("modules.$key.fields.{$field['key']}")<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            @if(! empty($module['manage']) && \Illuminate\Support\Facades\Route::has($module['manage']))
                <a href="{{ route($module['manage']) }}" class="admin-action-link">{{ $module['manage_label'] ?? 'Manage' }} →</a>
            @endif

            <div class="fixed bottom-0 inset-x-0 md:left-64 lg:left-72 z-40 bg-white/95 backdrop-blur border-t border-slate-200 px-4 py-3 flex items-center justify-between gap-3">
                <p class="text-sm" :class="dirty ? 'text-amber-600 font-medium' : 'text-slate-400'" x-text="dirty ? '● Unsaved changes' : 'All changes saved'"></p>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.modules.index') }}" class="btn-outline !py-2.5 !px-6">Cancel</a>
                    <button type="submit" class="btn-primary !py-2.5 !px-8">Save</button>
                </div>
            </div>
        </form>
    </div>
@endsection
