<?php

namespace App\Services\Admin\SuperAdmin;

use App\Traits\HasPermissions;

class GetMaintainerIndexService
{
    use HasPermissions;

    public function execute()
    {
        return [
            'userPermissions' => $this->getPermissionsData(),
        ];
    }
}
