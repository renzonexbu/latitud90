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
            'educationLevel' => 'required|in:preescolar,basica,media,universitaria',
            'grade' => 'nullable|in:A,B,C,D,E',
            'year' => 'required|string|max:4',
            'courseNumber' => 'nullable|integer|min:1|max:12',
            'courseName' => 'nullable|string|max:50',
            'contactEmail' => 'nullable|email|max:255',
            'contactPhone' => 'nullable|string|max:20',
            'associatedProgram' => 'nullable|exists:programs,id',
            'endDate' => 'nullable|date',
        ];
    }
}


