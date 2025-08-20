<?php

namespace App\Http\Requests\Admin\Passengers;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePassengerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $passengerId = $this->route('passenger')?->id;
        return [
            'first_last_name' => 'required|string|max:255',
            'second_last_name' => 'nullable|string|max:255',
            'first_name' => 'required|string|max:255',
            'second_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:255',
            'code_phone' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'address' => 'nullable|string|max:255',
        ];
    }
}


