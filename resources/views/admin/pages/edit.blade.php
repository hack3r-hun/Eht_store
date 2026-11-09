@extends('layouts.admin')

@section('page-title', 'Edit: '.$page->title)

@section('content')
    @php
        $builderConfig = [
            'sections' => $sections,
            'schema' => $schema,
            'categories' => $categories,
            'icons' => \App\Support\Cms\SectionSchema::ICONS,
            'uploadUrl' => route('admin.pages.upload-image'),
            'storageBase' => asset('storage').'/',
        ];
        $hours = old('meta.hours', $page->metaValue('hours', [
            ['label' => 'Monday - Friday', 'value' => '9:00 AM - 7:00 PM'],
            ['label' => 'Saturday', 'value' => '10:00 AM - 5:00 PM'],
            ['label' => 'Sunday', 'value' => 'Closed'],
        ]));
    @endphp

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.pages.index') }}" class="text-sm text-slate-500 hover:text-sage-700">&larr; All pages</a>
        <div class="flex flex-wrap items-center gap-3">
            @if($page->isSystem() && ! $usingDefaults)
                <x-confirm-delete :action="route('admin.pages.reset', $page)" method="POST" variant="neutral" title="Reset to original design?"
                    :item="$page->title" message="Your custom layout and edits for this page will be replaced by the original design. This cannot be undone."
                    confirm-label="Reset layout" trigger-icon="arrow-left">
                    Reset to original
                </x-confirm-delete>
            @endif
            <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="btn-outline !py-2 !px-4 text-sm">View live page ↗</a>
        </div>
    </div>

    @if($usingDefaults)
        <div class="mb-5 rounded-xl border border-sage-200 bg-sage-50 px-4 py-3 text-sm text-sage-800">
            This page is showing its <strong>original design</strong>. Everything below is editable — change anything and press <strong>Save</strong> to make it your own layout.
        </div>
    @endif

    <form
        id="page-form"
        action="{{ route('admin.pages.update', $page) }}"
        method="POST"
        x-data="pageBuilder(@js($builderConfig))"
        @submit="prepare($event)"
        class="max-w-5xl space-y-6 pb-28"
    >
        @csrf @method('PUT')
        <input type="hidden" name="sections" :value="payload">

        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        {{-- Page details --}}
        <section class="admin-card admin-card-pad space-y-4">
            <h2 class="font-bold text-slate-900">Page details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Page name</label>
                    <input type="text" name="title" value="{{ old('title', $page->title) }}" required maxlength="255" class="input-field">
                </div>
                @unless($page->isSystem())
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Web address</label>
                        <div class="flex items-center rounded-xl border border-slate-200 bg-white overflow-hidden focus-within:border-sage-500">
                            <span class="px-3 text-sm text-slate-400 bg-slate-50 self-stretch flex items-center">/p/</span>
                            <input type="text" name="slug" value="{{ old('slug', $page->slug) }}" required class="flex-1 min-w-0 border-0 focus:ring-0 py-3">
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-x-8 gap-y-3 md:col-span-2">
                        <input type="hidden" name="is_published" value="0">
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                            <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->is_published)) class="rounded text-sage-600 focus:ring-sage-500">
                            Published (visible to visitors)
                        </label>
                        <input type="hidden" name="meta[show_in_footer]" value="0">
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                            <input type="checkbox" name="meta[show_in_footer]" value="1" @checked(old('meta.show_in_footer', $page->metaValue('show_in_footer'))) class="rounded text-sage-600 focus:ring-sage-500">
                            Show a link in the website footer
                        </label>
                    </div>
                @endunless
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Browser / Google title <span class="text-slate-400 font-normal">(optional)</span></label>
                    <input type="text" name="meta[meta_title]" value="{{ old('meta.meta_title', $page->metaValue('meta_title')) }}" maxlength="70" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Google description <span class="text-slate-400 font-normal">(optional)</span></label>
                    <input type="text" name="meta[meta_description]" value="{{ old('meta.meta_description', $page->metaValue('meta_description')) }}" maxlength="200" class="input-field">
                </div>
            </div>
        </section>

        @if($page->slug === 'contact')
            <section class="admin-card admin-card-pad space-y-4">
                <div>
                    <h2 class="font-bold text-slate-900">Contact form &amp; opening hours</h2>
                    <p class="text-sm text-slate-500 mt-0.5">The phone, email and address come from <a href="{{ route('admin.settings.index') }}" class="text-sage-700 underline">Settings</a>. The form itself is built in.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Form heading</label>
                        <input type="text" name="meta[form_title]" value="{{ old('meta.form_title', $page->metaValue('form_title')) }}" maxlength="120" placeholder="Send us a message" class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Form sub-heading</label>
                        <input type="text" name="meta[form_subtitle]" value="{{ old('meta.form_subtitle', $page->metaValue('form_subtitle')) }}" maxlength="250" placeholder="Fill out the form and we'll get back to you shortly." class="input-field">
                    </div>
                </div>
                <div class="space-y-2" x-data="{ hours: @js(array_values($hours)) }">
                    <p class="text-sm font-medium text-slate-700">Business hours</p>
                    <template x-for="(row, i) in hours" :key="i">
                        <div class="flex gap-2">
                            <input type="text" :name="`meta[hours][${i}][label]`" x-model="row.label" placeholder="Monday - Friday" maxlength="60" class="input-field">
                            <input type="text" :name="`meta[hours][${i}][value]`" x-model="row.value" placeholder="9:00 AM - 7:00 PM" maxlength="60" class="input-field">
                            <button type="button" @click="hours.splice(i, 1)" class="w-10 shrink-0 rounded-xl text-red-500 hover:bg-red-50" aria-label="Remove row">✕</button>
                        </div>
                    </template>
                    <button type="button" @click="hours.length < 10 && hours.push({ label: '', value: '' })" class="btn-outline !py-2 !px-4 text-sm">+ Add row</button>
                </div>
            </section>
        @endif

        {{-- Layout & content --}}
        <section class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="font-bold text-slate-900">Page layout &amp; content</h2>
                    <p class="text-sm text-slate-500 mt-0.5">Sections appear on the page in this order. Move them, switch them off, change their style, or add new ones.</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="collapseAll(false)" class="px-3 py-2 rounded-xl text-sm text-slate-600 hover:bg-slate-100">Collapse all</button>
                    <button type="button" @click="adding = true" class="btn-primary !py-2 !px-4 text-sm">+ Add section</button>
                </div>
            </div>

            @if(in_array($page->slug, ['products', 'contact', 'custom-order', 'track-order'], true))
                <p class="rounded-xl bg-oat-100 border border-oat-200 px-4 py-3 text-sm text-slate-600">
                    The <strong>first hero banner</strong> appears above this page's built-in {{ match ($page->slug) {
                        'products' => 'product listing',
                        'contact' => 'contact form',
                        'custom-order' => 'request form',
                        default => 'tracking form',
                    } }}. All other sections appear <strong>below</strong> it.
                </p>
            @endif

            <template x-if="sections.length === 0">
                <div class="admin-card p-10 text-center text-slate-500">No sections yet. Press <strong>+ Add section</strong> to start building this page.</div>
            </template>

            <template x-for="(section, index) in sections" :key="section.id">
                <div data-section-card class="admin-card !overflow-visible" :class="!section.enabled && 'opacity-70'">
                    {{-- header --}}
                    <div class="flex items-center gap-2 sm:gap-3 px-3 sm:px-4 py-3">
                        <div class="flex flex-col">
                            <button type="button" @click="move(index, -1)" :disabled="index === 0" class="w-7 h-6 text-slate-400 hover:text-sage-700 disabled:opacity-25 leading-none" aria-label="Move section up">▲</button>
                            <button type="button" @click="move(index, 1)" :disabled="index === sections.length - 1" class="w-7 h-6 text-slate-400 hover:text-sage-700 disabled:opacity-25 leading-none" aria-label="Move section down">▼</button>
                        </div>
                        <button type="button" @click="section.open = !section.open" class="flex-1 min-w-0 text-left">
                            <p class="text-xs font-semibold uppercase tracking-wide text-sage-700" x-text="typeLabel(section)"></p>
                            <p class="text-sm font-medium text-slate-800 truncate" x-text="summary(section) || '(no title yet)'"></p>
                        </button>
                        <label class="inline-flex items-center gap-2 text-xs text-slate-500 cursor-pointer shrink-0" :title="section.enabled ? 'Shown on the page' : 'Hidden from the page'">
                            <input type="checkbox" x-model="section.enabled" class="sr-only peer">
                            <span class="relative inline-block w-10 h-6 rounded-full bg-slate-300 peer-checked:bg-sage-600 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5 after:rounded-full after:bg-white after:transition-transform peer-checked:after:translate-x-4"></span>
                            <span class="hidden sm:inline" x-text="section.enabled ? 'Shown' : 'Hidden'"></span>
                        </label>
                        <button type="button" @click="section.open = !section.open" class="w-9 h-9 shrink-0 rounded-lg text-slate-500 hover:bg-slate-100" :aria-expanded="section.open" aria-label="Toggle section">
                            <span class="inline-block transition-transform" :class="section.open && 'rotate-180'">▾</span>
                        </button>
                    </div>

                    {{-- body --}}
                    <div x-show="section.open" x-cloak x-transition.opacity.duration.150ms class="border-t border-slate-100 p-4 sm:p-5 space-y-5">
                        <template x-if="def(section.type).fields.length === 0">
                            <p class="text-sm text-slate-500">This block has no settings — it is just a decorative divider.</p>
                        </template>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <template x-for="field in def(section.type).fields" :key="field.key">
                                <div class="contents">
                                    @include('admin.pages._field', ['obj' => 'section.data', 'f' => 'field', 'nested' => false])
                                </div>
                            </template>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-slate-100">
                            <button type="button" @click="duplicate(index)" class="px-3 py-2 rounded-xl text-sm text-slate-600 hover:bg-slate-100">⧉ Duplicate</button>
                            <div x-show="confirmDelete !== section.id" class="contents">
                                <button type="button" @click="confirmDelete = section.id" class="px-3 py-2 rounded-xl text-sm text-red-600 hover:bg-red-50">Delete section</button>
                            </div>
                            <div x-show="confirmDelete === section.id" x-cloak class="flex items-center gap-2">
                                <span class="text-sm text-slate-600">Delete this section?</span>
                                <button type="button" @click="remove(index)" class="px-3 py-2 rounded-xl text-sm font-semibold bg-red-600 text-white hover:bg-red-700">Yes, delete</button>
                                <button type="button" @click="confirmDelete = null" class="px-3 py-2 rounded-xl text-sm text-slate-600 hover:bg-slate-100">Keep</button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </section>

        {{-- Add-section picker (teleported so no transformed ancestor can trap position:fixed) --}}
        <template x-teleport="body">
            <div x-show="adding" x-cloak x-transition.opacity class="fixed inset-0 z-[120] bg-slate-900/50 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-6" @click.self="adding = false" @keydown.escape.window="adding = false">
                <div class="w-full sm:max-w-3xl max-h-[88vh] overflow-y-auto bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl p-5 sm:p-7" role="dialog" aria-modal="true" aria-label="Add a section">
                    <div class="flex items-start justify-between gap-4 mb-5">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900">Add a section</h3>
                            <p class="text-sm text-slate-500">Pick a building block — it is added to the bottom and can be moved anywhere.</p>
                        </div>
                        <button type="button" @click="adding = false" class="w-9 h-9 rounded-lg text-slate-400 hover:bg-slate-100" aria-label="Close">✕</button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <template x-for="type in schema" :key="type.type">
                            <button type="button" @click="addSection(type.type)" class="text-left p-4 rounded-2xl border border-slate-200 hover:border-sage-500 hover:bg-sage-50 transition-colors">
                                <p class="font-semibold text-slate-900" x-text="type.label"></p>
                                <p class="text-sm text-slate-500 mt-0.5" x-text="type.description"></p>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        {{-- Floating save bar --}}
        <div class="fixed bottom-0 inset-x-0 md:left-64 lg:left-72 z-40 bg-white/95 backdrop-blur border-t border-slate-200 px-4 py-3 flex flex-wrap items-center justify-between gap-3 shadow-[0_-4px_24px_-8px_rgba(15,23,42,0.12)]">
            <p class="text-sm" :class="dirty ? 'text-amber-600 font-medium' : 'text-slate-400'">
                <span x-text="isUploading() ? 'Uploading image…' : (dirty ? '● Unsaved changes' : 'All changes saved')"></span>
                <span x-show="uploadError" x-cloak class="text-red-600 ml-2" x-text="uploadError"></span>
            </p>
            <button type="submit" class="btn-primary !py-2.5 !px-8" :disabled="isUploading()">Save page</button>
        </div>
    </form>
@endsection
