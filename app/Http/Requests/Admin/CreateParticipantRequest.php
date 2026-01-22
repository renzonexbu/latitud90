<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateParticipantRequest extends FormRequest
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
            'program_id' => 'required|exists:program_courses,id',
            'first_last_name' => 'required|string|max:255',
            'second_last_name' => 'nullable|string|max:255',
            'first_name' => 'required|string|max:255',
            'second_name' => 'nullable|string|max:255',
            'document_number' => 'required|string|max:255',
            // document_type es un ID a la tabla document
            'document_type' => 'required|integer|exists:document,id',
            'birth_date' => 'nullable|date',
            'nationality' => 'nullable|string|max:255',
            'gender' => 'nullable|string|max:255',
            'dietary_restrictions' => 'nullable|string|max:255',
            'intolerances' => 'nullable|string|max:255',
            'allergies' => 'nullable|string|max:255',
            'country' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'medical_conditions' => 'nullable|string|max:255',
            // Solo se permite un contacto de emergencia
            'emergency_contacts' => 'nullable|array|size:1',
            'emergency_contacts.*.name' => 'nullable|string|max:255',
            'emergency_contacts.*.email' => 'nullable|email|max:255',
            'emergency_contacts.*.document_number' => 'nullable|string|max:255',
            'emergency_contacts.*.document_type' => 'nullable|integer|exists:document,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'program_id.required' => 'Debe seleccionar un programa.',
            'program_id.exists' => 'El programa seleccionado no existe.',
            'first_last_name.required' => 'El primer apellido es obligatorio.',
            'second_last_name.string' => 'El segundo apellido debe ser texto.',
            'second_last_name.max' => 'El segundo apellido no puede exceder 255 caracteres.',
            'first_name.required' => 'El nombre es obligatorio.',
            'second_name.string' => 'El segundo nombre debe ser texto.',
            'second_name.max' => 'El segundo nombre no puede exceder 255 caracteres.',
            'document_number.required' => 'El RUT es obligatorio.',
            'document_type.required' => 'El tipo de documento es obligatorio.',
            'document_type.integer' => 'El tipo de documento seleccionado es inválido.',
            'document_type.exists' => 'El tipo de documento seleccionado no existe.',
            'birth_date.required' => 'La fecha de nacimiento es obligatoria.',
            'birth_date.date' => 'La fecha de nacimiento debe tener un formato válido.',
            'nationality.required' => 'La nacionalidad es obligatoria.',
            'gender.required' => 'El sexo es obligatorio.',
            'country.required' => 'El país es obligatorio.',
            'address.string' => 'La dirección debe ser texto.',
            'address.max' => 'La dirección no puede exceder 255 caracteres.',
            'dietary_restrictions.string' => 'Las restricciones dietarias deben ser texto.',
            'dietary_restrictions.max' => 'Las restricciones dietarias no pueden exceder 255 caracteres.',
            'medical_conditions.string' => 'Las condiciones médicas deben ser texto.',
            'medical_conditions.max' => 'Las condiciones médicas no pueden exceder 255 caracteres.',

            // Mensajes para contactos de emergencia
            'emergency_contacts.required' => 'Debe agregar un contacto de emergencia.',
            'emergency_contacts.size' => 'Solo se permite un contacto de emergencia.',
            'emergency_contacts.*.name.required' => 'El nombre del contacto de emergencia es obligatorio.',
            'emergency_contacts.*.email.required' => 'El email del contacto de emergencia es obligatorio.',
            'emergency_contacts.*.email.email' => 'El email del contacto de emergencia debe tener un formato válido.',
            'emergency_contacts.*.document_number.required' => 'El RUT del contacto de emergencia es obligatorio.',

        ];
    }
} 