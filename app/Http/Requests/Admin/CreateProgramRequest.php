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
     */
    public function rules(): array
    {
        return [
            // Campos obligatorios del programa (aceptar ambos nombres)
            'code' => ['required','string','max:8','regex:/^\d{1,8}$/','unique:programs,code'],
            'name' => 'nullable|string|max:255',
            'destination' => 'required|string|max:255',
            'departure_date' => 'required|date|after:today',
            'trip_description' => 'required_without:description|string|max:2000',
            'description' => 'required_without:trip_description|string|max:2000', // Campo del frontend
            'images_folder' => 'nullable|string|max:255',
            'images' => 'required|array|min:1', // Al menos una imagen es obligatoria
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
            'trip_price' => 'required_without:total_price|numeric|min:0',
            'total_price' => 'required_without:trip_price|numeric|min:0', // Campo del frontend
            'final_payment_date' => [
                'required',
                'date',
                'after:today',
                'before_or_equal:departure_date',
                function ($attribute, $value, $fail) {
                    $departureDate = $this->input('departure_date');
                    if ($departureDate) {
                        $departure = \Carbon\Carbon::parse($departureDate);
                        $paymentDate = \Carbon\Carbon::parse($value);

                        // Calcular la diferencia correctamente: fecha_mayor - fecha_menor
                        // Si paymentDate es antes que departure (correcto), la diferencia será positiva
                        $daysDifference = $paymentDate->diffInDays($departure, false);

                        // 🔍 LOG: Validación de fechas en el backend
                        \Log::info('📅 BACKEND - Validación de fechas:', [
                            'departure_date_input' => $departureDate,
                            'final_payment_date_input' => $value,
                            'departure_parsed' => $departure->toDateTimeString(),
                            'payment_parsed' => $paymentDate->toDateTimeString(),
                            'days_difference' => $daysDifference,
                            'payment_is_before_departure' => $paymentDate->lt($departure),
                            'is_valid' => $daysDifference >= 60
                        ]);

                        // Si la fecha de pago es después de la fecha de salida, fallar
                        if ($paymentDate->gte($departure)) {
                            $fail('La fecha final de pago debe ser anterior a la fecha de salida.');
                            return;
                        }

                        // Verificar que haya al menos 60 días de diferencia
                        if ($daysDifference < 60) {
                            $fail("La fecha final de pago debe ser al menos 60 días antes de la fecha de salida. (Actualmente hay {$daysDifference} días)");
                        }
                    }
                }
            ],
            'sales_executive_id' => ['required','integer','exists:sales_executives,id'],
            'seller_name' => 'nullable|string|max:255',
            'sales_person' => 'nullable|string|max:255', // Campo del frontend
            
            // Campos del detalle administrativo (todos opcionales)
            'institution_id' => 'required|exists:institutions,id',
            'institution_name' => 'nullable|string|max:255',
            'education_level' => 'nullable|string|in:preescolar,basica,media',
            'grade' => 'required_if:education_level,basica,media|nullable|in:A,B,C,D,E',
            'course_number' => 'nullable|integer|min:1|max:12',
            'students_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'group_benefit' => 'nullable|string',
            'discount_type' => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'payment_options' => 'required|array|min:1',
            'payment_options.*' => 'string|in:full_payment,subscription',
            // Nuevas selecciones por checkbox
            'full_payment_options' => 'required_if:payment_options,full_payment|array|min:1',
            'full_payment_options.*' => 'string|in:full_transfer_khipu,full_debit_credit_0,full_debit_credit_3,full_debit_credit_6,full_debit_credit_9,full_debit_credit_12,full_international',
            'subscription_payment_options' => 'required_if:payment_options,subscription|array|min:1',
            'subscription_payment_options.*' => 'string|in:subscription_virtualpos',
            'created_by' => 'nullable|exists:users,id',
            'active' => 'boolean',
            'max_installments' => [
                'required_if:payment_options,subscription',
                'integer',
                'min:1',
                'max:12',
                function ($attribute, $value, $fail) {
                    if ($value && $this->input('final_payment_date')) {
                        $finalPaymentDate = \Carbon\Carbon::parse($this->input('final_payment_date'));
                        $now = \Carbon\Carbon::now();

                        // Calcular meses disponibles hasta la fecha de pago
                        $monthsAvailable = $now->diffInMonths($finalPaymentDate);
                        if ($now->day > $finalPaymentDate->day) {
                            $monthsAvailable -= 1;
                        }
                        $monthsAvailable = max(0, $monthsAvailable);

                        if ($value > $monthsAvailable) {
                            $fail("El número máximo de meses de suscripción ({$value}) no puede exceder los meses disponibles hasta la fecha de pago ({$monthsAvailable} meses).");
                        }
                    }
                }
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Mensajes para campos obligatorios
            'code.required' => 'El código del programa es obligatorio.',
            'code.string' => 'El código del programa debe ser texto.',
            'code.max' => 'El código del programa no puede exceder 8 caracteres.',
            'code.regex' => 'El código del programa debe contener solo dígitos (1-8 caracteres).',
            'code.unique' => 'El código del programa ya existe. Por favor, utiliza un código diferente.',
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
            'final_payment_date.before_or_equal' => 'La fecha final de pago no puede ser posterior a la fecha de salida.',
            
            'sales_executive_id.required' => 'El ejecutivo de ventas es obligatorio.',
            'sales_executive_id.integer' => 'El ejecutivo de ventas debe ser un número válido.',
            'sales_executive_id.exists' => 'El ejecutivo de ventas seleccionado no existe.',
            
            'seller_name.required_without' => 'El nombre del vendedor es obligatorio.',
            'sales_person.required_without' => 'El nombre del vendedor es obligatorio.',
            'seller_name.string' => 'El nombre del vendedor debe ser texto.',
            'sales_person.string' => 'El nombre del vendedor debe ser texto.',
            'seller_name.max' => 'El nombre del vendedor no puede exceder 255 caracteres.',
            'sales_person.max' => 'El nombre del vendedor no puede exceder 255 caracteres.',
            
            // Mensajes para imágenes
            'images.required' => 'Debe seleccionar al menos una imagen para el programa.',
            'images.array' => 'Las imágenes deben ser enviadas como un array.',
            'images.min' => 'Debe seleccionar al menos una imagen para el programa.',
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
            
            'students_file.required' => 'El archivo de estudiantes es obligatorio.',
            'students_file.file' => 'El archivo de estudiantes debe ser un archivo válido.',
            'students_file.mimes' => 'El archivo de estudiantes debe ser Excel (.xlsx, .xls) o CSV.',
            'students_file.max' => 'El archivo de estudiantes no puede exceder 10MB.',
            
            // Mensajes para opciones de pago
            'full_payment_options.required_if' => 'Debe seleccionar al menos una opción de pago total.',
            'full_payment_options.min' => 'Debe seleccionar al menos una opción de pago total.',
            'full_payment_options.*.in' => 'La opción de pago total seleccionada no es válida.',
            'subscription_payment_options.required_if' => 'Debe seleccionar al menos una opción de suscripción.',
            'subscription_payment_options.min' => 'Debe seleccionar al menos una opción de suscripción.',
            'subscription_payment_options.*.in' => 'La opción de suscripción seleccionada no es válida.',
            
            // Mensajes para campos opcionales
            'education_level.in' => 'El nivel de educación seleccionado no es válido.',
            'grade.required_if' => 'El grado es obligatorio cuando se selecciona educación básica o media.',
            'grade.in' => 'El grado seleccionado no es válido. Debe ser A, B, C, D o E.',
            'course_number.integer' => 'El curso debe ser un número válido.',
            'course_number.min' => 'El curso debe ser al menos 1.',
            'course_number.max' => 'El curso no puede ser mayor a 12.',
            'group_benefit.in' => 'El beneficio grupal seleccionado no es válido.',
            'discount_type.in' => 'El tipo de descuento seleccionado no es válido.',
            'discount_amount.numeric' => 'El monto de descuento debe ser un número.',
            'discount_amount.min' => 'El monto de descuento debe ser mayor o igual a 0.',
            'payment_options.required' => 'Debe seleccionar al menos una opción de pago.',
            'payment_options.min' => 'Debe seleccionar al menos una opción de pago.',
            'payment_options.array' => 'Las opciones de pago deben ser enviadas como un array.',
            'payment_options.*.in' => 'La opción de pago seleccionada no es válida.',
            'full_payment_method.in' => 'El método de pago total seleccionado no es válido.',
            'subscription_payment_method.in' => 'El método de suscripción seleccionado no es válido.',
            'max_installments.required_if' => 'El número máximo de meses de suscripción es obligatorio cuando se selecciona suscripción.',
            'max_installments.integer' => 'El número máximo de meses de suscripción debe ser un número entero.',
            'max_installments.min' => 'El número máximo de meses de suscripción debe ser al menos 1.',
            'max_installments.max' => 'El número máximo de meses de suscripción no puede ser mayor a 12.',
            'created_by.exists' => 'El usuario creador no existe.',
            
            // Mensajes para validaciones básicas
            'institution_id.exists' => 'La institución seleccionada no existe.',
            
            // Mensajes para validaciones personalizadas
            'final_payment_date.60_days_before_departure' => 'hola mundo',
            'max_installments.months_available' => 'El número máximo de meses de suscripción no puede exceder los meses disponibles hasta la fecha de pago.',
        ];
    }
} 