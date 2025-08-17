<?php

namespace App\Http\Requests\Admin\Passengers;

use Illuminate\Foundation\Http\FormRequest;

class StorePassengerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rut' => 'required|string|unique:passengers,rut',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'program_id' => 'required|exists:programs,id',
            'status' => 'required|in:active,inactive,withdrawn',
        ];
    }
}


