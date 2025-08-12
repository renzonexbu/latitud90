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
            // Datos del participante
            'course_id' => 'required|exists:courses,id',
            'education_level' => 'required|string|in:preescolar,basica,media,universitaria',
            'year' => 'required|integer',
            'grade' => 'required|integer',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:participants,email',
            'code_phone' => 'required|string|max:10',
            'phone' => 'nullable|string|max:20',
            'document_type' => 'required|string|max:50',
            'document_number' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'birth_date' => 'required|date|before:today',
            'address' => 'nullable|string',
            'dietary_restrictions' => 'nullable|string',
            'medical_conditions' => 'nullable|string',
            'individual_price' => 'required|numeric|min:0',
            'price_adjustments' => 'nullable|numeric',
            'adjustment_reason' => 'nullable|string',

            // Contactos de emergencia (mínimo 1, máximo 3)
            'emergency_contacts' => 'required|array|min:1|max:3',
            'emergency_contacts.*.first_name' => 'required|string|max:255',
            'emergency_contacts.*.last_name' => 'required|string|max:255',
            'emergency_contacts.*.email' => 'required|email|max:255',
            'emergency_contacts.*.code_phone' => 'required|string|max:10',
            'emergency_contacts.*.phone' => 'required|string|max:20',
            'emergency_contacts.*.country' => 'required|string|max:100',
            'emergency_contacts.*.birth_date' => 'nullable|date|before:today',
            'emergency_contacts.*.address' => 'nullable|string',
            'emergency_contacts.*.relationship' => 'required|string|max:100',

            // Condiciones médicas (como string, no array)
            'medical_conditions' => 'nullable|string|max:1000',
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
            'course_id.required' => 'Debe seleccionar un curso.',
            'course_id.exists' => 'El curso seleccionado no existe.',
            'education_level.required' => 'El nivel de educación es obligatorio.',
            'education_level.in' => 'El nivel de educación seleccionado no es válido.',
            'year.required' => 'El año es obligatorio.',
            'year.integer' => 'El año debe ser un número válido.',
            'grade.required' => 'El grado es obligatorio.',
            'grade.integer' => 'El grado debe ser un número válido.',
            'first_name.required' => 'El nombre es obligatorio.',
            'last_name.required' => 'El apellido es obligatorio.',
            'email.email' => 'El email debe tener un formato válido.',
            'email.unique' => 'Este email ya está registrado.',
            'code_phone.required' => 'El código de país es obligatorio.',
            'document_type.required' => 'El tipo de documento es obligatorio.',
            'document_number.required' => 'El RUT es obligatorio.',
            'country.required' => 'El país es obligatorio.',
            'birth_date.required' => 'La fecha de nacimiento es obligatoria.',
            'birth_date.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'individual_price.required' => 'El precio individual es obligatorio.',
            'individual_price.numeric' => 'El precio debe ser un número.',
            'individual_price.min' => 'El precio debe ser mayor a 0.',

            // Mensajes para contactos de emergencia
            'emergency_contacts.required' => 'Debe agregar al menos un contacto de emergencia.',
            'emergency_contacts.min' => 'Debe agregar al menos un contacto de emergencia.',
            'emergency_contacts.max' => 'Puede agregar máximo 3 contactos de emergencia.',
            'emergency_contacts.*.first_name.required' => 'El nombre del contacto de emergencia es obligatorio.',
            'emergency_contacts.*.last_name.required' => 'El apellido del contacto de emergencia es obligatorio.',
            'emergency_contacts.*.email.required' => 'El email del contacto de emergencia es obligatorio.',
            'emergency_contacts.*.email.email' => 'El email del contacto de emergencia debe tener un formato válido.',
            'emergency_contacts.*.code_phone.required' => 'El código de país del contacto de emergencia es obligatorio.',
            'emergency_contacts.*.phone.required' => 'El teléfono del contacto de emergencia es obligatorio.',
            'emergency_contacts.*.country.required' => 'El país del contacto de emergencia es obligatorio.',
            'emergency_contacts.*.birth_date.date' => 'La fecha de nacimiento del contacto de emergencia debe tener un formato válido.',
            'emergency_contacts.*.birth_date.before' => 'La fecha de nacimiento del contacto de emergencia debe ser anterior a hoy.',
            'emergency_contacts.*.address.string' => 'La dirección del contacto de emergencia debe ser texto.',
            'emergency_contacts.*.relationship.required' => 'La relación con el alumno es obligatoria.',

            // Mensajes para condiciones médicas
            'medical_conditions.string' => 'Las condiciones médicas deben ser texto.',
            'medical_conditions.max' => 'Las condiciones médicas no pueden exceder 1000 caracteres.',
        ];
    }
} 