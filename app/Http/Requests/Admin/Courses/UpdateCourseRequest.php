<?php

namespace App\Http\Requests\Admin\Courses;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'institutionId' => 'required|exists:institutions,id',
            'educationLevel' => 'required|string|max:255',
            'year' => 'required|string|max:4',
            'grade' => 'required|string|max:10',
            'shift' => 'required|string|max:50',
            'contactEmail' => 'nullable|email|max:255',
            'contactPhone' => 'nullable|string|max:20',
            'associatedProgram' => 'nullable|exists:programs,id',
            'endDate' => 'nullable|date',
        ];
    }
}


