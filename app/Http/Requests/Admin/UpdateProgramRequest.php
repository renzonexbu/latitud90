<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Program;

class UpdateProgramRequest extends FormRequest
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
            // Campos básicos de la plantilla (todos opcionales en edición)
            'name' => 'nullable|string|max:255',
            'destination' => 'nullable|string|max:255',
            'trip_description' => 'nullable|string|max:2000',
            'description' => 'nullable|string|max:2000', // Campo del frontend

            // Imágenes (opcionales en edición, solo validar tipo si se envían)
            'images' => 'nullable|array',
            'images.*' => 'file|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max por imagen

            // Eliminación de imágenes/archivos existentes
            'imagesToDelete' => 'nullable|array',
            'imagesToDelete.*' => 'integer|min:0',
            'filesToDelete' => 'nullable|array',
            'filesToDelete.*' => 'in:itinerary,coverage,equipment',

            // Pilares educativos
            'pilar_1' => 'nullable|string|max:255',
            'pilar_2' => 'nullable|string|max:255',
            'pilar_3' => 'nullable|string|max:255',
            'pilar_4' => 'nullable|string|max:255',

            // Itinerario
            'itinerary' => 'nullable|string|max:1000',
            'itinerary_description' => 'nullable|string|max:1000',

            // Archivos PDF (opcionales)
            'itinerary_file' => 'nullable|file|mimes:pdf|max:10240', // 10MB max
            'coverage_file' => 'nullable|file|mimes:pdf|max:10240',
            'equipment_file' => 'nullable|file|mimes:pdf|max:10240',
            'travel_assistance_coverage' => 'nullable|file|mimes:pdf|max:10240',
            'equipment_list' => 'nullable|file|mimes:pdf|max:10240',

            // Estado
            'active' => 'boolean',
        ];
    }

	/**
	 * Validaciones adicionales: exigir al menos una imagen final (entre existentes que quedan y nuevas subidas).
	 */
	public function withValidator($validator): void
	{
		$validator->after(function ($validator) {
			/** @var Program|null $program */
			$program = $this->route('program');
			if (!$program instanceof Program) {
				return;
			}

			// Imágenes actuales en disco (ya expuestas por el accesor getImagesAttribute)
			$existingCount = is_array($program->images ?? null) ? count($program->images) : 0;
			$toDelete = $this->input('imagesToDelete');
			$deleteCount = is_array($toDelete) ? count($toDelete) : 0;
			$newImages = $this->file('images');
			$newCount = is_array($newImages) ? count($newImages) : 0;

			$finalCount = max(0, $existingCount - $deleteCount) + $newCount;
			if ($finalCount < 1) {
				$validator->errors()->add('images', 'Debe tener al menos una imagen en la plantilla.');
			}
		});
	}

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalizar 'active' para que siempre sea un booleano real
        if ($this->has('active')) {
            $active = $this->input('active');
            $normalized = filter_var($active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($normalized !== null) {
                $this->merge(['active' => $normalized]);
            }
        }
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',

            'destination.string' => 'El destino debe ser texto.',
            'destination.max' => 'El destino no puede exceder 255 caracteres.',

            'description.string' => 'La descripción debe ser texto.',
            'description.max' => 'La descripción no puede exceder 2000 caracteres.',
            'trip_description.string' => 'La descripción debe ser texto.',
            'trip_description.max' => 'La descripción no puede exceder 2000 caracteres.',

            // Mensajes para imágenes
            'images.array' => 'Las imágenes deben ser enviadas como un array.',
            'images.*.file' => 'Cada imagen debe ser un archivo válido.',
            'images.*.mimes' => 'Las imágenes deben ser en formato JPEG, JPG, PNG, GIF o WEBP.',
            'images.*.max' => 'Cada imagen no puede exceder 5MB.',

            // Mensajes para archivos
            'itinerary_file.file' => 'El archivo de itinerario debe ser un archivo válido.',
            'itinerary_file.mimes' => 'El archivo de itinerario debe ser un PDF.',
            'itinerary_file.max' => 'El archivo de itinerario no puede exceder 10MB.',

            'travel_assistance_coverage.file' => 'El archivo de cobertura debe ser un archivo válido.',
            'travel_assistance_coverage.mimes' => 'El archivo de cobertura debe ser un PDF.',
            'travel_assistance_coverage.max' => 'El archivo de cobertura no puede exceder 10MB.',

            'equipment_list.file' => 'El archivo de lista de equipo debe ser un archivo válido.',
            'equipment_list.mimes' => 'El archivo de lista de equipo debe ser un PDF.',
            'equipment_list.max' => 'El archivo de lista de equipo no puede exceder 10MB.',
        ];
    }
}
