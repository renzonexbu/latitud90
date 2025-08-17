<?php

namespace App\Http\Requests\Admin\Payments;

use Illuminate\Foundation\Http\FormRequest;

class DailyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'program_id' => 'nullable|exists:programs,id',
            'executive_id' => 'nullable|exists:users,id',
            'payment_method' => 'nullable|in:transbank_webpay,khipu,cash,transfer',
        ];
    }
}


