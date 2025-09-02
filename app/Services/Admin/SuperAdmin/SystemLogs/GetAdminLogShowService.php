<?php

namespace App\Services\Admin\SuperAdmin\SystemLogs;

use App\Models\AdminLog;
use App\Traits\AdminLogging;

class GetAdminLogShowService
{
    use AdminLogging;

    public function execute($id)
    {
        $log = AdminLog::findOrFail($id);
        
        // Log the view
        $this->logView(
            'admin_logs',
            'admin_log',
            $log->id,
            "Detalle de log consultado: {$log->description}"
        );
        
        return [
            'adminLog' => $log
        ];
    }
}
