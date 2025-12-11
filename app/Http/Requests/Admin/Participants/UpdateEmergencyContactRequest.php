<?php

namespace App\Http\Requests\Admin\Participants;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmergencyContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_id' => 'required|exists:emergency_contact,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'document_type' => 'nullable|exists:document,id',
            'document_number' => 'nullable|string|max:50',
            'code_phone' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:255',
        ];
    }
}


