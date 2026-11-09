<?php

namespace App\Http\Requests;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $variants = collect($this->input('variants', []))
            ->map(function ($row, $size) {
                if (! is_array($row)) {
                    return null;
                }

                $row['size'] = strtoupper((string) ($row['size'] ?? $size));
                $row['enabled'] = filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

                return $row;
            })
            ->filter()
            ->values()
            ->all();

        $this->merge(['variants' => $variants]);
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)],
            'description' => ['nullable', 'string'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'lead_time_days' => ['nullable', 'integer', 'min:1', 'max:120'],
            'specifications' => ['nullable', 'array'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.size' => ['required', 'in:'.implode(',', ProductVariant::SIZES)],
            'variants.*.enabled' => ['nullable', 'boolean'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.sale_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $enabled = collect($this->input('variants', []))
                ->filter(fn ($row) => filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN));

            if ($enabled->isEmpty()) {
                $validator->errors()->add('variants', 'Select at least one size.');

                return;
            }

            $sizes = $enabled->pluck('size');
            if ($sizes->count() !== $sizes->unique()->count()) {
                $validator->errors()->add('variants', 'Each size can only be selected once.');
            }

            $productId = $this->route('product')?->id;

            foreach ($enabled as $index => $row) {
                if (! filled($row['price'] ?? null)) {
                    $validator->errors()->add("variants.{$index}.price", 'Price is required for enabled sizes.');
                }

                if (! array_key_exists('stock_quantity', $row) || $row['stock_quantity'] === null || $row['stock_quantity'] === '') {
                    $validator->errors()->add("variants.{$index}.stock_quantity", 'Stock is required for enabled sizes.');
                }

                $sku = $row['sku'] ?? null;
                if (filled($sku)) {
                    $exists = ProductVariant::query()
                        ->where('sku', $sku)
                        ->when($productId, fn ($q) => $q->where('product_id', '!=', $productId))
                        ->exists();

                    if ($exists) {
                        $validator->errors()->add("variants.{$index}.sku", 'This SKU is already in use.');
                    }
                }
            }

            $skus = $enabled->pluck('sku')->filter()->values();
            if ($skus->count() !== $skus->unique()->count()) {
                $validator->errors()->add('variants', 'Variant SKUs must be unique.');
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function enabledVariants(): array
    {
        return collect($this->input('variants', []))
            ->filter(fn ($row) => filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN))
            ->map(fn ($row) => [
                'size' => strtoupper((string) $row['size']),
                'price' => $row['price'],
                'sale_price' => $row['sale_price'] ?? null,
                'stock_quantity' => $row['stock_quantity'],
                'sku' => $row['sku'] ?? null,
                'enabled' => true,
                'is_active' => true,
            ])
            ->values()
            ->all();
    }
}
