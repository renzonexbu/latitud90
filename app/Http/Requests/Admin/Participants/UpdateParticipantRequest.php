<?php

namespace App\Http\Requests\Admin\Participants;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_last_name' => 'required|string|max:255',
            'second_last_name' => 'nullable|string|max:255',
            'first_name' => 'required|string|max:255',
            'second_name' => 'nullable|string|max:255',
            // document_number es opcional, solo super admins pueden enviarlo
            // La validación de permisos se hace en el servicio
            'document_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'code_phone' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:255',
            'allergies' => 'nullable|string',
            'intolerances' => 'nullable|string',
            'dietary_restrictions' => 'nullable|string|max:255',
            'pivot_course_id' => 'nullable|integer|exists:program_courses,id',
            'individual_price' => 'nullable|numeric|min:0',
            'discounts' => 'nullable|string', // JSON string con los descuentos
        ];
    }
}


