<?php

namespace App\Services\Admin\Users;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CreateUserService
{
    public function execute(Request $request)
    {
        return User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => $request->is_active ?? true,
        ]);
    }
}
