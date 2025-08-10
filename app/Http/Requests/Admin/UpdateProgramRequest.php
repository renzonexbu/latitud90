<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

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
     */
    public function rules(): array
    {
        return [
            // Campos del programa (todos opcionales en edición, solo validar tipo si se envían)
            'name' => 'nullable|string|max:255',
            'destination' => 'nullable|string|max:255',
            'departure_date' => 'nullable|date',
            'trip_description' => 'nullable|string|max:2000',
            'description' => 'nullable|string|max:2000', // Campo del frontend
            'images_folder' => 'nullable|string|max:255',
            'images' => 'nullable|array', // Las imágenes son opcionales en edición
            'images.*' => 'file|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max por imagen
            'pillars' => 'nullable|string|max:500',
            'pilar_1' => 'nullable|string|max:255',
            'pilar_2' => 'nullable|string|max:255',
            'pilar_3' => 'nullable|string|max:255',
            'pilar_4' => 'nullable|string|max:255',
            'itinerary' => 'nullable|string|max:1000', // Campo del frontend
            'itinerary_file' => 'nullable|file|mimes:pdf|max:10240', // 10MB max
            'coverage_file' => 'nullable|file|mimes:pdf|max:10240', // Campo del frontend
            'equipment_file' => 'nullable|file|mimes:pdf|max:10240', // Campo del frontend
            'travel_assistance_coverage' => 'nullable|file|mimes:pdf|max:10240',
            'equipment_list' => 'nullable|file|mimes:pdf|max:10240',
            'trip_price' => 'nullable|numeric|min:0',
            'total_price' => 'nullable|numeric|min:0', // Campo del frontend
            'final_payment_date' => 'nullable|date',
            'seller_name' => 'nullable|string|max:255',
            'sales_person' => 'nullable|string|max:255', // Campo del frontend
            
            // Campos del detalle administrativo (todos opcionales)
            'institution_id' => 'nullable|exists:institutions,id',
            'institution_name' => 'nullable|string|max:255',
            'education_level' => 'nullable|string|in:inicial,primario,secundario,universitario',
            'shift' => 'nullable|string|in:mañana,tarde,noche',
            'grade' => 'nullable|string|max:10',
            'students_file' => 'nullable|file|mimes:xlsx,xls,csv|max:10240',
            'group_benefit' => 'nullable|string|in:descuento_10,descuento_15,descuento_20',
            'discount_type' => 'nullable|string|in:porcentaje_10,porcentaje_15,porcentaje_20,monto_fijo',
            'discount_amount' => 'nullable|numeric|min:0',
            // Aceptar múltiples opciones en edición, manteniendo compatibilidad con el campo singular
            'payment_options' => 'nullable|array',
            'payment_options.*' => 'string|in:full_payment,installments',
            'payment_option' => 'nullable|string|in:full_payment,installments',
            'full_payment_method' => 'nullable|string|in:todos_medios,solo_tarjeta,solo_transferencia,solo_contado',
            'installments_payment_method' => 'nullable|string|in:khipu,webpay_1,webpay_3,webpay_6,webpay_12',
            'max_installments' => 'nullable|string|in:3,6,9,12',
            'payment_mode_id' => 'nullable|exists:payment_modes,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'active' => 'boolean',
        ];
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
            // Mensajes para campos obligatorios
            'name.required' => 'El nombre del programa es obligatorio.',
            'name.string' => 'El nombre del programa debe ser texto.',
            'name.max' => 'El nombre del programa no puede exceder 255 caracteres.',
            
            'destination.required' => 'El destino es obligatorio.',
            'destination.string' => 'El destino debe ser texto.',
            'destination.max' => 'El destino no puede exceder 255 caracteres.',
            
            'departure_date.required' => 'La fecha de salida es obligatoria.',
            'departure_date.date' => 'La fecha de salida debe tener un formato válido.',
            'departure_date.after' => 'La fecha de salida debe ser posterior a hoy.',
            
            'trip_description.required_without' => 'La descripción del viaje es obligatoria.',
            'description.required_without' => 'La descripción del viaje es obligatoria.',
            'trip_description.string' => 'La descripción del viaje debe ser texto.',
            'description.string' => 'La descripción del viaje debe ser texto.',
            'trip_description.max' => 'La descripción del viaje no puede exceder 2000 caracteres.',
            'description.max' => 'La descripción del viaje no puede exceder 2000 caracteres.',
            
            'itinerary.string' => 'La descripción del itinerario debe ser texto.',
            'itinerary.max' => 'La descripción del itinerario no puede exceder 1000 caracteres.',
            
            'trip_price.required_without' => 'El precio del viaje es obligatorio.',
            'total_price.required_without' => 'El precio del viaje es obligatorio.',
            'trip_price.numeric' => 'El precio del viaje debe ser un número.',
            'total_price.numeric' => 'El precio del viaje debe ser un número.',
            'trip_price.min' => 'El precio del viaje debe ser mayor o igual a 0.',
            'total_price.min' => 'El precio del viaje debe ser mayor o igual a 0.',
            
            'final_payment_date.required' => 'La fecha final de pago es obligatoria.',
            'final_payment_date.date' => 'La fecha final de pago debe tener un formato válido.',
            'final_payment_date.after' => 'La fecha final de pago debe ser posterior a hoy.',
            
            'seller_name.required_without' => 'El nombre del vendedor es obligatorio.',
            'sales_person.required_without' => 'El nombre del vendedor es obligatorio.',
            'seller_name.string' => 'El nombre del vendedor debe ser texto.',
            'sales_person.string' => 'El nombre del vendedor debe ser texto.',
            'seller_name.max' => 'El nombre del vendedor no puede exceder 255 caracteres.',
            'sales_person.max' => 'El nombre del vendedor no puede exceder 255 caracteres.',
            
            // Mensajes para imágenes (opcionales en edición)
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
            
            'students_file.file' => 'El archivo de estudiantes debe ser un archivo válido.',
            'students_file.mimes' => 'El archivo de estudiantes debe ser Excel (.xlsx, .xls) o CSV.',
            'students_file.max' => 'El archivo de estudiantes no puede exceder 10MB.',
            
            // Mensajes para campos opcionales
            'education_level.in' => 'El nivel de educación seleccionado no es válido.',
            'shift.in' => 'El turno seleccionado no es válido.',
            'group_benefit.in' => 'El beneficio grupal seleccionado no es válido.',
            'discount_type.in' => 'El tipo de descuento seleccionado no es válido.',
            'discount_amount.numeric' => 'El monto de descuento debe ser un número.',
            'discount_amount.min' => 'El monto de descuento debe ser mayor o igual a 0.',
            'payment_option.in' => 'La opción de pago seleccionada no es válida.',
            'full_payment_method.in' => 'El método de pago total seleccionado no es válido.',
            'installments_payment_method.in' => 'El método de pago en cuotas seleccionado no es válido.',
            'max_installments.in' => 'El número máximo de cuotas seleccionado no es válido.',
            
            // Mensajes para validaciones básicas
            'institution_id.exists' => 'La institución seleccionada no existe.',
        ];
    }
} 