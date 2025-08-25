<?php

namespace App\Services\Admin\Users;

use App\Models\User;

class ToggleUserStatusService
{
    public function execute(User $user)
    {
        $user->update([
            'is_active' => !$user->is_active,
        ]);

        return $user;
    }
}
