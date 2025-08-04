<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateCourseRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'institutionId' => 'required|exists:institutions,id',
            'educationLevel' => 'required|in:preescolar,primaria,secundaria,universitaria',
            'year' => 'required|string|max:4',
            'grade' => 'required|string|max:10',
            'shift' => 'required|in:mañana,tarde,noche',
            'contactEmail' => 'required|email|max:255',
            'contactPhone' => 'required|string|max:20',
            'associatedProgram' => 'nullable|string|max:255',
            'endDate' => 'nullable|date|after:today',
            'studentsFile' => 'nullable|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'institutionId.required' => 'La institución es obligatoria.',
            'institutionId.exists' => 'La institución seleccionada no existe.',
            
            'educationLevel.required' => 'El nivel de educación es obligatorio.',
            'educationLevel.in' => 'El nivel de educación seleccionado no es válido.',
            
            'year.required' => 'El año es obligatorio.',
            'year.string' => 'El año debe ser texto.',
            'year.max' => 'El año no puede tener más de 4 caracteres.',
            
            'grade.required' => 'El grado es obligatorio.',
            'grade.string' => 'El grado debe ser texto.',
            'grade.max' => 'El grado no puede tener más de 10 caracteres.',
            
            'shift.required' => 'El turno es obligatorio.',
            'shift.in' => 'El turno seleccionado no es válido.',
            
            'contactEmail.required' => 'El correo de contacto es obligatorio.',
            'contactEmail.email' => 'El correo de contacto debe tener un formato válido.',
            'contactEmail.max' => 'El correo de contacto no puede tener más de 255 caracteres.',
            
            'contactPhone.required' => 'El número de contacto es obligatorio.',
            'contactPhone.string' => 'El número de contacto debe ser texto.',
            'contactPhone.max' => 'El número de contacto no puede tener más de 20 caracteres.',
            
            // 'associatedProgram.required' => 'El programa asociado es obligatorio.', // Removed since it's now nullable
            'associatedProgram.string' => 'El programa asociado debe ser texto.',
            'associatedProgram.max' => 'El programa asociado no puede tener más de 255 caracteres.',
            
            // 'endDate.required' => 'La fecha de finalización es obligatoria.', // Removed since it's now nullable
            'endDate.date' => 'La fecha de finalización debe tener un formato válido.',
            'endDate.after' => 'La fecha de finalización debe ser posterior a hoy.',
            
            'studentsFile.file' => 'El archivo de estudiantes debe ser un archivo válido.',
            'studentsFile.mimes' => 'El archivo de estudiantes debe ser un archivo Excel (.xlsx, .xls) o CSV.',
            'studentsFile.max' => 'El archivo de estudiantes no puede ser mayor a 10MB.',
        ];
    }
} 