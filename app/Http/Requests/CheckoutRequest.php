<?php

namespace App\Http\Requests;

use App\Support\CartIdentity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address_line' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'payment_method' => ['required', Rule::in(\App\Services\CheckoutService::enabledPaymentMethods())],
            'notes' => ['nullable', 'string', 'max:1000'],
            'marketing_opt_in' => ['nullable', 'boolean'],
            'gift_wrap' => ['nullable', 'boolean'],
            'gift_message' => ['nullable', 'string', 'max:500'],
            'gift_hide_prices' => ['nullable', 'boolean'],
        ];

        // auth()->check() only looks at the default "web" guard, which a stateless
        // API bearer-token request never populates — use the same guard-aware
        // identity resolution as the cart/checkout services instead.
        if (! CartIdentity::resolve()->userId) {
            $rules['email'] = ['required', 'email', 'max:255'];
        }

        return $rules;
    }
}
