{{--
    One schema-driven input. $obj = JS expression for the data object, $f = JS expression for the field
    definition, $nested = true inside a repeater row (no repeaters inside repeaters).
--}}
@php $model = $obj.'['.$f.'.key]'; @endphp

<div :class="['textarea', 'richtext', 'repeater', 'image'].includes({{ $f }}.kind) ? 'md:col-span-2' : ''">
    <label class="block text-sm font-medium text-slate-700 mb-1.5" x-text="{{ $f }}.label"></label>

    {{-- single-line text / link / number --}}
    <template x-if="['text', 'url', 'number'].includes({{ $f }}.kind)">
        <div>
            <input :type="{{ $f }}.kind === 'number' ? 'number' : 'text'" min="0" x-model="{{ $model }}"
                   :maxlength="{{ $f }}.kind === 'number' ? null : ({{ $f }}.max || 500)"
                   :placeholder="{{ $f }}.kind === 'url' ? '/products  ·  #section  ·  https://…  ·  tel:+92…' : ''"
                   class="input-field">
        </div>
    </template>

    <template x-if="{{ $f }}.kind === 'textarea'">
        <textarea rows="3" x-model="{{ $model }}" :maxlength="{{ $f }}.max || 2000" class="input-field"></textarea>
    </template>

    <template x-if="{{ $f }}.kind === 'richtext'">
        <div data-rich-text>
            <div class="flex flex-wrap gap-1 mb-1.5">
                <button type="button" @click="format($event, 'paragraph')" class="px-2.5 py-1 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">¶ Paragraph</button>
                <button type="button" @click="format($event, 'heading')" class="px-2.5 py-1 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">H Heading</button>
                <button type="button" @click="format($event, 'bold')" class="px-2.5 py-1 rounded-lg border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50">B</button>
                <button type="button" @click="format($event, 'italic')" class="px-2.5 py-1 rounded-lg border border-slate-200 text-xs italic text-slate-600 hover:bg-slate-50">I</button>
                <button type="button" @click="format($event, 'list')" class="px-2.5 py-1 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">• List</button>
                <button type="button" @click="format($event, 'link')" class="px-2.5 py-1 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">🔗 Link</button>
            </div>
            <textarea rows="7" x-model="{{ $model }}" class="input-field font-mono text-sm leading-relaxed"></textarea>
            <p class="text-xs text-slate-400 mt-1">Select text, then use the buttons to format it. Simple HTML is supported.</p>
        </div>
    </template>

    <template x-if="{{ $f }}.kind === 'select'">
        <select x-model="{{ $model }}" class="input-field">
            <template x-for="option in {{ $f }}.optList" :key="option.value">
                <option :value="option.value" x-text="option.label" :selected="option.value === String({{ $model }})"></option>
            </template>
        </select>
    </template>

    <template x-if="{{ $f }}.kind === 'icon'">
        <select x-model="{{ $model }}" class="input-field">
            <template x-for="icon in icons" :key="icon">
                <option :value="icon" x-text="icon" :selected="icon === {{ $model }}"></option>
            </template>
        </select>
    </template>

    <template x-if="{{ $f }}.kind === 'category'">
        <select x-model="{{ $model }}" class="input-field">
            <option value="">— Choose a category —</option>
            <template x-for="category in categories" :key="category.slug">
                <option :value="category.slug" x-text="category.name" :selected="category.slug === {{ $model }}"></option>
            </template>
        </select>
    </template>

    <template x-if="{{ $f }}.kind === 'toggle'">
        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" x-model="{{ $model }}" class="rounded text-sage-600 focus:ring-sage-500"> On
        </label>
    </template>

    {{-- image: preview + upload + paste link --}}
    <template x-if="{{ $f }}.kind === 'image'">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="w-full sm:w-36 h-28 shrink-0 rounded-xl border border-dashed border-slate-300 bg-slate-50 overflow-hidden flex items-center justify-center text-xs text-slate-400">
                <template x-if="{{ $model }}">
                    <img :src="imageUrl({{ $model }})" alt="" class="w-full h-full object-cover">
                </template>
                <template x-if="!{{ $model }}"><span>No image</span></template>
            </div>
            <div class="flex-1 space-y-2 min-w-0">
                <div class="flex flex-wrap gap-2">
                    <label class="btn-outline !py-2 !px-4 text-sm cursor-pointer">
                        <span x-text="{{ $model }} ? 'Replace image' : 'Upload image'"></span>
                        <input type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="upload($event, {{ $obj }}, {{ $f }}.key)">
                    </label>
                    <button type="button" x-show="{{ $model }}" @click="{{ $model }} = ''" class="px-4 py-2 rounded-xl text-sm font-medium text-red-600 hover:bg-red-50">Remove</button>
                </div>
                <input type="text" x-model="{{ $model }}" placeholder="…or paste an image link (https://…)" class="input-field !py-2 text-sm">
            </div>
        </div>
    </template>

    {{-- repeater rows --}}
    @unless($nested)
        <template x-if="{{ $f }}.kind === 'repeater'">
            <div class="space-y-3">
                <template x-for="(item, itemIndex) in {{ $model }}" :key="itemIndex">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3 sm:p-4">
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <p class="text-sm font-semibold text-slate-700 truncate">
                                <span x-text="'#' + (itemIndex + 1)"></span>
                                <span class="font-normal text-slate-500" x-text="itemLabel(item, {{ $f }}) ? ' — ' + itemLabel(item, {{ $f }}).slice(0, 40) : ''"></span>
                            </p>
                            <div class="flex items-center gap-1 shrink-0">
                                <button type="button" @click="moveItem({{ $model }}, itemIndex, -1)" :disabled="itemIndex === 0" class="w-8 h-8 rounded-lg text-slate-500 hover:bg-white disabled:opacity-30" aria-label="Move up">↑</button>
                                <button type="button" @click="moveItem({{ $model }}, itemIndex, 1)" :disabled="itemIndex === {{ $model }}.length - 1" class="w-8 h-8 rounded-lg text-slate-500 hover:bg-white disabled:opacity-30" aria-label="Move down">↓</button>
                                <button type="button" @click="{{ $model }}.splice(itemIndex, 1)" class="w-8 h-8 rounded-lg text-red-500 hover:bg-red-50" aria-label="Remove">✕</button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <template x-for="sub in {{ $f }}.fields" :key="sub.key">
                                <div class="contents">
                                    @include('admin.pages._field', ['obj' => 'item', 'f' => 'sub', 'nested' => true])
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
                <button type="button" @click="addItem({{ $obj }}, {{ $f }})" class="btn-outline !py-2 !px-4 text-sm">+ Add</button>
            </div>
        </template>
    @endunless
</div>
