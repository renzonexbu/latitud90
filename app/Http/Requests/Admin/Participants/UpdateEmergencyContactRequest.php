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
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'code_phone' => 'required|string|max:10',
            'phone' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'birth_date' => 'nullable|date|before:today',
            'address' => 'nullable|string',
            'relationship' => 'required|string|max:100',
        ];
    }
}


