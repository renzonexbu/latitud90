<?php

namespace App\Services\Admin\Users;

use App\Models\User;
use App\Traits\AdminLogging;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CreateUserService
{
    use AdminLogging;

    public function execute(Request $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => $request->is_active ?? true,
        ]);

        // Log the user creation
        $this->logCreate(
            'users',
            'User',
            $user->id,
            "Usuario creado: {$user->name} ({$user->email})",
            [
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
            ],
            [
                'user_type' => 'admin',
                'created_by_admin' => true,
            ]
        );

        return $user;
    }
}
