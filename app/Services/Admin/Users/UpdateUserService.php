<?php

namespace App\Services\Admin\Users;

use App\Models\User;
use App\Traits\AdminLogging;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UpdateUserService
{
    use AdminLogging;

    public function execute(Request $request, User $user)
    {
        // Guardar valores anteriores para el log
        $oldValues = $user->toArray();

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'is_active' => $request->is_active,
        ]);

        if ($request->filled('password')) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        // Log the user update
        $this->logUpdate(
            'users',
            'User',
            $user->id,
            "Usuario actualizado: {$user->name} ({$user->email})",
            $oldValues,
            $user->toArray(),
            [
                'password_changed' => $request->filled('password'),
                'user_type' => 'admin',
                'updated_by_admin' => true,
            ]
        );

        return $user;
    }
}
