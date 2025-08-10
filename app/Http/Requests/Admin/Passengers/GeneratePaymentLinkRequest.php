<?php

namespace App\Http\Requests\Admin\Passengers;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePaymentLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:transbank_webpay,khipu,both',
            'description' => 'required|string',
            'expires_hours' => 'required|integer|min:1|max:168',
        ];
    }
}


