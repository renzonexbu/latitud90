<?php

namespace App\Http\Requests\Admin\Passengers;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price_adjustment' => 'required|numeric',
            'adjustment_reason' => 'required|string',
        ];
    }
}


