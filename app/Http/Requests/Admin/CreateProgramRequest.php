<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateProgramRequest extends FormRequest
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
     * Programs are now templates - only validate template fields.
     */
    public function rules(): array
    {
        return [
            // Campos básicos de la plantilla
            'name' => 'required|string|max:255',
            // destination ya no es parte de la plantilla, ahora está en program_courses

            // Imágenes (obligatorias)
            'images' => 'required|array|min:1', // Al menos una imagen es obligatoria
            'images.*' => 'file|mimes:jpeg,jpg,png,gif,webp|max:15360', // 15MB max por imagen

            // Pilares educativos (fijos, opcionales porque se envían disabled)
            'pilar_1' => 'nullable|string|max:255',
            'pilar_2' => 'nullable|string|max:255',
            'pilar_3' => 'nullable|string|max:255',
            'pilar_4' => 'nullable|string|max:255',

            // Archivos PDF (opcionales)
            'itinerary_file' => 'nullable|file|mimes:pdf|max:10240', // 10MB max
            'coverage_file' => 'nullable|file|mimes:pdf|max:10240',
            'equipment_file' => 'nullable|file|mimes:pdf|max:10240',

            // Estado
            'active' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la plantilla es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',

            'images.required' => 'Debe adjuntar al menos una imagen.',
            'images.array' => 'Las imágenes deben ser enviadas como un array.',
            'images.min' => 'Debe adjuntar al menos una imagen.',
            'images.*.file' => 'Cada imagen debe ser un archivo válido.',
            'images.*.mimes' => 'Las imágenes deben ser en formato JPEG, JPG, PNG, GIF o WEBP.',
            'images.*.max' => 'Cada imagen no puede exceder 15MB.',

            'itinerary_file.file' => 'El archivo de itinerario debe ser un archivo válido.',
            'itinerary_file.mimes' => 'El archivo de itinerario debe ser un PDF.',
            'itinerary_file.max' => 'El archivo de itinerario no puede exceder 10MB.',

            'coverage_file.file' => 'El archivo de cobertura debe ser un archivo válido.',
            'coverage_file.mimes' => 'El archivo de cobertura debe ser un PDF.',
            'coverage_file.max' => 'El archivo de cobertura no puede exceder 10MB.',

            'equipment_file.file' => 'El archivo de lista de equipo debe ser un archivo válido.',
            'equipment_file.mimes' => 'El archivo de lista de equipo debe ser un PDF.',
            'equipment_file.max' => 'El archivo de lista de equipo no puede exceder 10MB.',
        ];
    }
}
