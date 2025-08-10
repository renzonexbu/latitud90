<?php

namespace App\Http\Requests\Admin\Programs;

use Illuminate\Foundation\Http\FormRequest;

class BulkProgramsActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:activate,deactivate,delete',
            'program_ids' => 'required|array',
            'program_ids.*' => 'exists:programs,id',
        ];
    }
}


