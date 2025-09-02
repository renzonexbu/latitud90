<?php

namespace App\Http\Requests\Admin\Participants;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicalConditionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'allergies' => 'nullable|string',
            'intolerances' => 'nullable|string',
            'dietary_restrictions' => 'nullable|string',
        ];
    }
}


