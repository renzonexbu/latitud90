<?php

namespace App\Http\Requests\Admin\Courses;

class StoreCourseRequest extends CourseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge($this->commonRules(), [
            'institutionId' => ['required', 'exists:institutions,id'],
            'educationLevel' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'studentsFile' => ['required', 'file', 'mimes:csv,xls,xlsx', 'max:10240'],
        ]);
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'studentsFile.required' => 'El archivo de estudiantes es obligatorio',
            'studentsFile.mimes' => 'El archivo debe ser de tipo: csv, xls, xlsx',
            'studentsFile.max' => 'El archivo no debe ser mayor a 10MB',
        ];
    }
}
