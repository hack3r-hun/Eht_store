<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
