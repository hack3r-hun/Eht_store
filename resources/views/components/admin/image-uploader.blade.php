@props([
    'name' => 'images[]',
    'multiple' => true,
    'aspectRatio' => 1,
    'label' => 'Images',
    'hint' => 'JPG, PNG, WebP — max 5MB each. Crop before upload.',
    'existingHint' => null,
])

@php
    $config = [
        'aspectRatio' => $aspectRatio,
        'inputName' => $name,
        'multiple' => (bool) $multiple,
    ];
@endphp

<div
    x-data="adminImageCropper(@js($config))"
    class="space-y-3"
>
    <label class="block text-sm font-medium">{{ $label }}</label>

    @isset($existingImages)
        <div class="flex flex-wrap gap-3">
            {{ $existingImages }}
        </div>
        @if($existingHint)
            <p class="text-xs text-slate-500">{{ $existingHint }}</p>
        @endif
    @endisset

  {{-- Pending uploads preview --}}
    <template x-if="previews.length > 0">
        <div>
            <p class="text-xs font-semibold text-emerald-600 mb-2 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span x-text="previews.length + (previews.length === 1 ? ' image ready to upload' : ' images ready to upload')"></span>
            </p>
            <div class="flex flex-wrap gap-3">
                <template x-for="(preview, index) in previews" :key="index">
                    <div class="relative group">
                        <img :src="preview" alt="Preview" class="w-24 h-24 object-cover rounded-xl border-2 border-emerald-400 shadow-sm">
                        <span class="absolute -top-2 -left-2 text-[10px] font-bold bg-emerald-600 text-white px-1.5 py-0.5 rounded">New</span>
                        <button
                            type="button"
                            @click="removePending(index)"
                            class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-red-500 text-white text-xs leading-none opacity-0 group-hover:opacity-100 transition-opacity"
                            title="Remove"
                        >×</button>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <div>
        <input
            type="file"
            x-ref="fileInput"
            name="{{ $name }}"
            @if($multiple) multiple @endif
            accept="image/jpeg,image/png,image/webp,image/jpg"
            class="input-field"
            @change="handleFileSelect($event)"
        >
        <p class="text-xs text-slate-500 mt-1">{{ $hint }}</p>
        <template x-if="errors.length > 0">
            <ul class="mt-1 space-y-0.5 text-sm text-red-600">
                <template x-for="error in errors" :key="error">
                    <li x-text="error"></li>
                </template>
            </ul>
        </template>
    </div>

    {{ $slot }}

  {{-- Crop modal --}}
    <div
        x-show="cropModalOpen"
        x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 p-4"
        x-cloak
        @keydown.escape.window="closeCropModal()"
    >
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden" @click.outside="closeCropModal()">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-semibold text-slate-900">Crop image</h3>
                <button type="button" @click="closeCropModal()" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
            </div>
            <div class="p-4 bg-slate-50 max-h-[60vh] overflow-hidden">
                <img x-ref="cropImage" :src="cropImageSrc" alt="Crop" class="max-w-full block mx-auto">
            </div>
            <div class="px-5 py-4 border-t border-slate-100 flex justify-end gap-3">
                <button type="button" @click="skipCropQueue()" class="btn-outline">Cancel</button>
                <button type="button" @click="confirmCrop()" class="btn-primary">Use this crop</button>
            </div>
        </div>
    </div>
</div>
