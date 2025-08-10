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
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'code_phone' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'document_number' => 'required|string|max:20',
            'birth_date' => 'nullable|date',
        ];
    }
}


