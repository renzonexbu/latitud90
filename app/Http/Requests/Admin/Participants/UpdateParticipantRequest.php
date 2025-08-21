<?php

namespace App\Http\Requests\Admin\Participants;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_last_name' => 'required|string|max:255',
            'second_last_name' => 'nullable|string|max:255',
            'first_name' => 'required|string|max:255',
            'second_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'code_phone' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'birth_date' => 'required|date',
            'address' => 'nullable|string|max:255',
            'allergies' => 'nullable|string',
            'intolerances' => 'nullable|string',
            'dietary_restrictions' => 'nullable|string|max:255',
        ];
    }
}


