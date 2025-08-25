<?php

namespace App\Services\Admin\Users;

use App\Models\User;

class DeleteUserService
{
    public function execute(User $user)
    {
        return $user->delete();
    }
}
