<?php

namespace App\Http\Requests\Admin\Participants;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmergencyContactsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'emergency_contacts' => 'required|string',
        ];
    }
}


