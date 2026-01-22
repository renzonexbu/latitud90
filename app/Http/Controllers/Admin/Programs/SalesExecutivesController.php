<?php

namespace App\Http\Controllers\Admin\Programs;

use App\Http\Controllers\Controller;
use App\Models\SalesExecutive;
use Illuminate\Http\Request;

class SalesExecutivesController extends Controller
{
	public function store(Request $request)
	{
		$validated = $request->validate([
			'code' => ['required','string','max:16','unique:sales_executives,code'],
			'name' => ['required','string','max:255'],
			'email' => ['nullable','email','max:255','unique:sales_executives,email'],
			'phone' => ['nullable','string','max:50'],
		], [
			'code.required' => 'El código es obligatorio',
			'code.unique' => 'Este código ya está en uso',
			'code.max' => 'El código no puede exceder 16 caracteres',
			'name.required' => 'El nombre es obligatorio',
			'email.email' => 'El email debe ser válido',
			'email.unique' => 'Este email ya está registrado',
		]);

		$executive = SalesExecutive::create([
			'code' => $validated['code'],
			'name' => $validated['name'],
			'email' => $validated['email'] ?? null,
			'phone' => $validated['phone'] ?? null,
			'active' => true,
		]);

		return response()->json(['success' => true, 'executive' => $executive]);
	}
}


