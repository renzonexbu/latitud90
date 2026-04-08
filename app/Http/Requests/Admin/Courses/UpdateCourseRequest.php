<?php

namespace App\Http\Requests\Admin\Courses;

class UpdateCourseRequest extends CourseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Transform payment_options arrays to boolean flags
        $paymentOptions = $this->input('payment_options', []);
        $fullPaymentOptions = $this->input('full_payment_options', []);
        $subscriptionPaymentOptions = $this->input('subscription_payment_options', []);

        // Determine if each payment type is enabled
        $enableTotalPayment = in_array('full_payment', $paymentOptions) && !empty($fullPaymentOptions);
        $enableSubscriptionPayment = in_array('subscription', $paymentOptions) && !empty($subscriptionPaymentOptions);

        // Allow empty grade (set to null if empty or '---')
        $grade = $this->input('grade');
        if (empty($grade) || $grade === '---') {
            $grade = null;
        }

        // Allow empty courseNumber (set to null if empty or '---')
        $courseNumber = $this->input('courseNumber');
        if (empty($courseNumber) || $courseNumber === '---') {
            $courseNumber = null;
        }

        $this->merge([
            'enable_total_payment' => $enableTotalPayment,
            'enable_subscription_payment' => $enableSubscriptionPayment,
            'grade' => $grade,
            'courseNumber' => $courseNumber,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Obtener el ID del curso actual (puede ser modelo o ID por route model binding)
        $courseParam = $this->route('course');
        $courseId = $courseParam instanceof \App\Models\Course ? $courseParam->id : $courseParam;

        // Buscar el program_course_id asociado al curso
        $course = \App\Models\Course::with('programCourses')->find($courseId);
        $programCourse = $course?->programCourses?->first();
        $programCourseId = $programCourse?->id;

        return array_merge($this->commonRules(), [
            // Campos del curso
            'institutionId' => ['required', 'exists:institutions,id'],
            'educationLevel' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'studentsFile' => ['nullable', 'file', 'mimes:csv,xls,xlsx', 'max:10240'],

            // Campos del plan de programa (ProgramCourse)
            'program_id' => ['required', 'exists:programs,id'],
            'code' => ['required', 'string', 'max:50', 'unique:program_courses,code,' . $programCourseId],
            'destination' => ['required', 'string', 'max:255'],
            'departure_date' => array_values(array_filter(['required', 'date', auth()->user()?->hasRole('super_admin') ? null : 'after_or_equal:today'])),
            'trip_price' => ['required', 'numeric', 'min:0'],
            'final_payment_date' => ['required', 'date'],

            // Opciones de pago
            'enable_total_payment' => ['nullable', 'boolean'],
            'enable_subscription_payment' => ['nullable', 'boolean'],
            'payment_options' => ['nullable', 'array'],
            'payment_options.*' => ['nullable', 'string'],
            'full_payment_options' => ['nullable', 'array'],
            'full_payment_options.*' => ['nullable', 'string'],
            'subscription_payment_options' => ['nullable', 'array'],
            'subscription_payment_options.*' => ['nullable', 'string'],
            'subscription_max_months' => ['nullable', 'integer', 'min:1', 'max:36', 'required_if:enable_subscription_payment,true'],
            'immediate_first_charge' => ['nullable', 'boolean'],

            // Descuentos
            'discount_type' => ['nullable', 'in:percentage,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],

            // Ejecutivo de ventas
            'sales_executive_id' => ['nullable', 'exists:sales_executives,id'],

            // Archivos del programa
            'itinerary_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'coverage_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'equipment_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'remove_itinerary_file' => ['nullable', 'boolean'],
            'remove_coverage_file' => ['nullable', 'boolean'],
            'remove_equipment_file' => ['nullable', 'boolean'],

            // Estado activo
            'active' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Archivo de estudiantes
            'studentsFile.mimes' => 'El archivo debe ser de tipo: csv, xls, xlsx',
            'studentsFile.max' => 'El archivo no debe ser mayor a 10MB',

            // Programa
            'program_id.required' => 'Debe seleccionar una plantilla de programa',
            'program_id.exists' => 'La plantilla de programa seleccionada no existe',

            // Código
            'code.required' => 'El código del programa es obligatorio',
            'code.max' => 'El código no puede exceder 50 caracteres',
            'code.unique' => 'El código del programa ya está en uso. Por favor, usa un código diferente',

            // Destino
            'destination.required' => 'El destino es obligatorio',
            'destination.max' => 'El destino no puede exceder 255 caracteres',

            // Fechas
            'departure_date.required' => 'La fecha de salida es obligatoria',
            'departure_date.date' => 'La fecha de salida debe ser una fecha válida',
            'departure_date.after_or_equal' => 'La fecha de inicio no puede ser anterior a hoy',
                        'final_payment_date.required' => 'La fecha límite de pago es obligatoria',
            'final_payment_date.date' => 'La fecha límite de pago debe ser una fecha válida',

            // Precio
            'trip_price.required' => 'El precio del viaje es obligatorio',
            'trip_price.numeric' => 'El precio debe ser un número válido',
            'trip_price.min' => 'El precio debe ser mayor o igual a 0',

            // Suscripción
            'subscription_max_months.required_if' => 'Debe especificar el máximo de meses cuando el pago por suscripción está habilitado',
            'subscription_max_months.min' => 'El máximo de meses debe ser al menos 1',
            'subscription_max_months.max' => 'El máximo de meses no puede exceder 36',

            // Descuentos
            'discount_type.in' => 'El tipo de descuento debe ser porcentaje o monto fijo',
            'discount_value.numeric' => 'El valor del descuento debe ser un número válido',
            'discount_value.min' => 'El valor del descuento debe ser mayor o igual a 0',

            // Ejecutivo de ventas
            'sales_executive_id.exists' => 'El ejecutivo de ventas seleccionado no existe',

            // Archivos del programa
            'itinerary_file.file' => 'El archivo de itinerario debe ser un archivo válido',
            'itinerary_file.mimes' => 'El archivo de itinerario debe ser un PDF',
            'itinerary_file.max' => 'El archivo de itinerario no debe ser mayor a 10MB',
            'coverage_file.file' => 'El archivo de cobertura debe ser un archivo válido',
            'coverage_file.mimes' => 'El archivo de cobertura debe ser un PDF',
            'coverage_file.max' => 'El archivo de cobertura no debe ser mayor a 10MB',
            'equipment_file.file' => 'El archivo de lista de equipo debe ser un archivo válido',
            'equipment_file.mimes' => 'El archivo de lista de equipo debe ser un PDF',
            'equipment_file.max' => 'El archivo de lista de equipo no debe ser mayor a 10MB',
        ];
    }
}
