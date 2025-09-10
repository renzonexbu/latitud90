<?php

namespace App\Http\Requests\Admin\Courses;

use Illuminate\Foundation\Http\FormRequest;

abstract class CourseRequest extends FormRequest
{
    /**
     * Get common validation rules that apply to both create and update operations
     */
    protected function commonRules(): array
    {
        return [
            'institutionId' => ['required', 'exists:institutions,id'],
            'educationLevel' => ['required', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:50'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'courseNumber' => ['nullable', 'string', 'max:50'],
            'courseName' => ['nullable', 'string', 'max:255'],
            'contactEmail' => ['nullable', 'email', 'max:255'],
            'contactPhone' => ['nullable', 'string', 'max:50'],
            'endDate' => ['nullable', 'date'],
            'studentsFile' => ['nullable', 'file', 'mimes:csv,xls,xlsx', 'max:10240'],
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     */
    public function attributes(): array
    {
        return [
            'institutionId' => 'institución',
            'educationLevel' => 'nivel educativo',
            'courseNumber' => 'número de curso',
            'courseName' => 'nombre del curso',
            'contactEmail' => 'correo de contacto',
            'contactPhone' => 'teléfono de contacto',
            'endDate' => 'fecha de término',
            'studentsFile' => 'archivo de estudiantes',
        ];
    }
}
