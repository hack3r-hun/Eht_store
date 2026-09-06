@extends('layouts.admin')

@section('page-title', $product->exists ? 'Edit Product' : 'Add Product')

@section('content')
    @php
        $sizes = $sizes ?? \App\Models\ProductVariant::SIZES;
        $existingVariants = $product->exists
            ? $product->variants->keyBy('size')
            : collect();
        $oldVariants = old('variants', []);
    @endphp

    <form
        action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
        method="POST"
        enctype="multipart/form-data"
        class="max-w-3xl space-y-6"
        x-data="productSizeForm()"
    >
        @csrf
        @if($product->exists) @method('PUT') @endif

        <div class="admin-card admin-card-pad space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Category</label>
                <select name="category_id" class="input-field" required>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id) == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('category_id')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Name</label>
                <input type="text" name="name" value="{{ old('name', $product->name) }}" class="input-field" required>
                @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Base SKU</label>
                <input
                    type="text"
                    name="sku"
                    x-model="baseSku"
                    value="{{ old('sku', $product->sku) }}"
                    class="input-field"
                    required
                    @input="suggestSkus()"
                >
                <p class="text-xs text-slate-500 mt-1">Used to auto-suggest size SKUs (e.g. SHAWL-S).</p>
                @error('sku')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Description</label>
                <textarea name="description" rows="4" class="input-field">{{ old('description', $product->description) }}</textarea>
            </div>
        </div>

        <div class="admin-card admin-card-pad space-y-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Sizes &amp; pricing</h2>
                <p class="text-sm text-slate-500 mt-1">Select at least one size. Each size has its own price and stock.</p>
                @error('variants')<p class="text-red-500 text-sm mt-2">{{ $message }}</p>@enderror
            </div>

            <div class="space-y-3">
                @foreach($sizes as $index => $size)
                    @php
                        $existing = $existingVariants->get($size);
                        $old = $oldVariants[$index] ?? $oldVariants[$size] ?? null;
                        $enabledDefault = $old
                            ? filter_var($old['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)
                            : ($existing?->is_active ?? false);
                        $priceDefault = $old['price'] ?? $existing?->price ?? '';
                        $saleDefault = $old['sale_price'] ?? $existing?->sale_price ?? '';
                        $stockDefault = $old['stock_quantity'] ?? $existing?->stock_quantity ?? 0;
                        $skuDefault = $old['sku'] ?? $existing?->sku ?? (($product->sku ? $product->sku.'-'.$size : ''));
                    @endphp
                    <div
                        class="rounded-xl border border-slate-200 p-4"
                        x-data="{ enabled: {{ $enabledDefault ? 'true' : 'false' }} }"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <label class="flex items-center gap-3 font-semibold text-slate-900">
                                <input
                                    type="checkbox"
                                    name="variants[{{ $index }}][enabled]"
                                    value="1"
                                    class="rounded text-sage-600"
                                    x-model="enabled"
                                    @checked($enabledDefault)
                                >
                                <span class="inline-flex h-8 w-12 items-center justify-center rounded-lg bg-slate-100 text-sm">{{ $size }}</span>
                                Size {{ $size }}
                            </label>
                            <input type="hidden" name="variants[{{ $index }}][size]" value="{{ $size }}">
                        </div>

                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3" x-show="enabled" x-cloak>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Price</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="variants[{{ $index }}][price]"
                                    value="{{ $priceDefault }}"
                                    class="input-field"
                                    :required="enabled"
                                    :disabled="!enabled"
                                >
                                @error("variants.$index.price")<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Sale price</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="variants[{{ $index }}][sale_price]"
                                    value="{{ $saleDefault }}"
                                    class="input-field"
                                    :disabled="!enabled"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Stock</label>
                                <input
                                    type="number"
                                    min="0"
                                    name="variants[{{ $index }}][stock_quantity]"
                                    value="{{ $stockDefault }}"
                                    class="input-field"
                                    :required="enabled"
                                    :disabled="!enabled"
                                >
                                @error("variants.$index.stock_quantity")<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">SKU</label>
                                <input
                                    type="text"
                                    name="variants[{{ $index }}][sku]"
                                    value="{{ $skuDefault }}"
                                    class="input-field"
                                    data-size-sku="{{ $size }}"
                                    :disabled="!enabled"
                                >
                                @error("variants.$index.sku")<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="admin-card admin-card-pad space-y-4">
            <x-admin.image-uploader
                label="Product Images"
                hint="Choose photos (max 5MB). Crop each image, then click Save Product to upload."
                existing-hint="Current images on the store. Add more below — new uploads become the main image."
                :aspect-ratio="1"
            >
                @if($product->exists && $product->images->isNotEmpty())
                    <x-slot:existingImages>
                        @foreach($product->images as $image)
                            <div class="relative group">
                                <img src="{{ $image->url }}" alt="" class="w-20 h-20 object-cover rounded-xl border border-slate-100">
                                @if($image->is_primary)
                                    <span class="absolute -top-2 -left-2 text-[10px] font-bold bg-sage-600 text-white px-1.5 py-0.5 rounded">Main</span>
                                @endif
                                <x-confirm-delete
                                    :form-id="'delete-image-'.$image->id"
                                    title="Remove this image?"
                                    message="This image will be permanently removed from the product."
                                    confirm-label="Remove"
                                    :trigger-show-icon="false"
                                    trigger-class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-red-500 text-white text-xs leading-none opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"
                                    class="!inline"
                                >×</x-confirm-delete>
                            </div>
                        @endforeach
                    </x-slot:existingImages>
                @endif
                @error('images')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                @error('images.*')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </x-admin.image-uploader>
            <div class="flex gap-6">
                <input type="hidden" name="is_active" value="0">
                <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true)) class="rounded text-sage-600"> Active</label>
                <input type="hidden" name="is_featured" value="0">
                <label class="flex items-center gap-2"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured ?? false)) class="rounded text-sage-600"> Featured</label>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn-primary">Save Product</button>
            <a href="{{ route('admin.products.index') }}" class="btn-outline">Cancel</a>
        </div>
    </form>

    @if($product->exists)
        @foreach($product->images as $image)
            <form
                id="delete-image-{{ $image->id }}"
                action="{{ route('admin.products.images.destroy', [$product, $image]) }}"
                method="POST"
                class="hidden"
            >
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    @endif

    <script nonce="{{ $cspNonce }}">
        function productSizeForm() {
            return {
                baseSku: @json(old('sku', $product->sku ?? '')),
                suggestSkus() {
                    document.querySelectorAll('[data-size-sku]').forEach((input) => {
                        if (!input.value || input.value === `${this.baseSku}-${input.dataset.sizeSku}` || input.dataset.auto !== '0') {
                            const next = this.baseSku ? `${this.baseSku}-${input.dataset.sizeSku}` : '';
                            if (!input.value || input.dataset.touched !== '1') {
                                input.value = next;
                            }
                        }
                    });
                },
                init() {
                    document.querySelectorAll('[data-size-sku]').forEach((input) => {
                        input.addEventListener('input', () => {
                            input.dataset.touched = '1';
                        });
                    });
                }
            }
        }
    </script>
@endsection
