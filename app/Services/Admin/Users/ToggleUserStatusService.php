<?php

namespace App\Services\Admin\Users;

use App\Models\User;
use App\Traits\AdminLogging;

class ToggleUserStatusService
{
    use AdminLogging;

    public function execute(User $user)
    {
        $oldStatus = $user->is_active;
        $newStatus = !$user->is_active;

        $user->update([
            'is_active' => $newStatus,
        ]);

        // Log the status change
        $this->logStatusChange(
            'users',
            'User',
            $user->id,
            $oldStatus ? 'active' : 'inactive',
            $newStatus ? 'active' : 'inactive',
            "Cambio de estado de usuario: {$user->name} ({$user->email})",
            [
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_type' => 'admin',
                'changed_by_admin' => true,
            ]
        );

        return $user;
    }
}
