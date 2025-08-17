<?php

namespace App\Http\Requests\Admin\Institutions;

use Illuminate\Foundation\Http\FormRequest;

class CreateInstitutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:institutions,name',
            'type' => 'required|string|in:school,university,other',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
        ];
    }
}


