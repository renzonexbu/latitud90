<?php

namespace App\Services\Admin\Users;

use App\Models\User;
use App\Traits\AdminLogging;

class DeleteUserService
{
    use AdminLogging;

    public function execute(User $user)
    {
        // Guardar datos del usuario antes de eliminarlo para el log
        $userData = $user->toArray();

        $result = $user->delete();

        // Log the user deletion
        $this->logDelete(
            'users',
            'User',
            $userData['id'],
            "Usuario eliminado: {$userData['name']} ({$userData['email']})",
            $userData,
            [
                'user_type' => 'admin',
                'deleted_by_admin' => true,
            ]
        );

        return $result;
    }
}
