@extends('layouts.admin')

@section('page-title', 'Navigation & Footer')

@section('content')
    <form action="{{ route('admin.site.update') }}" method="POST" class="max-w-4xl space-y-6 pb-24" x-data="siteMenuEditor(@js($config), @js($max))" @submit="dirty = false">
        @csrf @method('PUT')

        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <section class="admin-card admin-card-pad space-y-4">
            <div>
                <h2 class="font-bold text-slate-900">Top bar &amp; footer text</h2>
                <p class="text-sm text-slate-500 mt-0.5">Phone, email and address are managed in <a href="{{ route('admin.settings.index') }}" class="text-sage-700 underline">Settings</a>.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Top bar message (right side)</label>
                    <input type="text" name="topbar" value="{{ old('topbar', $topbar) }}" maxlength="120" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Footer links heading</label>
                    <input type="text" name="footer_heading" value="{{ old('footer_heading', $footerHeading) }}" maxlength="40" class="input-field">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Footer description</label>
                    <textarea name="footer_text" rows="2" maxlength="500" class="input-field">{{ old('footer_text', $footerText) }}</textarea>
                </div>
            </div>
        </section>

        @foreach([
            'nav' => ['Header menu', 'The links across the top of the website (and in the phone menu).', 'Shop', '/products'],
            'footer' => ['Footer links', 'Pages you create with “Show a link in the footer” are added automatically after these.', 'Our story', '/about'],
            'socials' => ['Social links', 'Instagram, Facebook, TikTok, YouTube… shown as small links in the footer.', 'Instagram', 'https://instagram.com/yourshop'],
        ] as $group => [$title, $help, $phLabel, $phUrl])
            <section class="admin-card admin-card-pad space-y-3">
                <div>
                    <h2 class="font-bold text-slate-900">{{ $title }}</h2>
                    <p class="text-sm text-slate-500 mt-0.5">{{ $help }}</p>
                </div>
                <template x-for="(row, i) in {{ $group }}" :key="i">
                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-2">
                        <div class="flex flex-col shrink-0">
                            <button type="button" @click="move({{ $group }}, i, -1)" :disabled="i === 0" class="w-7 h-5 text-slate-400 hover:text-sage-700 disabled:opacity-25 leading-none" aria-label="Move up">▲</button>
                            <button type="button" @click="move({{ $group }}, i, 1)" :disabled="i === {{ $group }}.length - 1" class="w-7 h-5 text-slate-400 hover:text-sage-700 disabled:opacity-25 leading-none" aria-label="Move down">▼</button>
                        </div>
                        <input type="text" :name="`{{ $group }}[${i}][label]`" x-model="row.label" placeholder="{{ $phLabel }}" maxlength="60" class="input-field sm:w-2/5" aria-label="Link text">
                        <input type="text" :name="`{{ $group }}[${i}][url]`" x-model="row.url" placeholder="{{ $phUrl }}" maxlength="300" class="input-field flex-1 min-w-0" aria-label="Link address">
                        <button type="button" @click="{{ $group }}.splice(i, 1)" class="w-10 h-10 shrink-0 rounded-xl text-red-500 hover:bg-red-50" aria-label="Remove link">✕</button>
                    </div>
                </template>
                <p x-show="{{ $group }}.length === 0" x-cloak class="text-sm text-slate-400">No links.</p>
                <button type="button" @click="add('{{ $group }}')" :disabled="{{ $group }}.length >= max.{{ $group }}" class="btn-outline !py-2 !px-4 text-sm disabled:opacity-40">+ Add link</button>
            </section>
        @endforeach

        <div class="fixed bottom-0 inset-x-0 md:left-64 lg:left-72 z-40 bg-white/95 backdrop-blur border-t border-slate-200 px-4 py-3 flex items-center justify-between gap-3">
            <p class="text-sm" :class="dirty ? 'text-amber-600 font-medium' : 'text-slate-400'" x-text="dirty ? '● Unsaved changes' : 'All changes saved'"></p>
            <button type="submit" class="btn-primary !py-2.5 !px-8">Save</button>
        </div>
    </form>

    <script nonce="{{ $cspNonce }}">
        document.addEventListener('alpine:init', () => {
            Alpine.data('siteMenuEditor', (config, max) => ({
                nav: config.nav,
                footer: config.footer,
                socials: config.socials,
                max,
                dirty: false,
                init() {
                    this.$nextTick(() => this.$watch(() => JSON.stringify([this.nav, this.footer, this.socials]), () => { this.dirty = true; }));
                },
                add(group) {
                    if (this[group].length < this.max[group]) this[group].push({ label: '', url: '' });
                },
                move(list, index, direction) {
                    const target = index + direction;
                    if (target < 0 || target >= list.length) return;
                    const [item] = list.splice(index, 1);
                    list.splice(target, 0, item);
                },
            }));
        });
    </script>
@endsection
