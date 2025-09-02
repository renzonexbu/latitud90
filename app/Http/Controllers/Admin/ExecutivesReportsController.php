<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\Admin\Reports\Executives\ExecutivesConsolidatedService;
use App\Services\Admin\Reports\Executives\ExecutivesPartialAccountService;
use App\Services\Admin\Reports\Executives\ExportService as ExecutivesExportService;

class ExecutivesReportsController extends Controller
{
    public function __construct(
        private ExecutivesConsolidatedService $consolidatedService,
        private ExecutivesPartialAccountService $partialAccountService,
        private ExecutivesExportService $exportService
    ) {}

    public function consolidated(Request $request)
    {
        $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'salesExecutiveId', 'page']);

        $data = $this->consolidatedService->getConsolidated($filters);

        return Inertia::render('Admin/Reports/Executives/Consolidated', $data);
    }

    public function partialAccount(Request $request)
    {
        $filters = $request->only(['dateFrom', 'dateTo', 'programCode', 'page']);

        $data = $this->partialAccountService->getPartialAccounts($filters);

        return Inertia::render('Admin/Reports/Executives/PartialAccount', $data);
    }

    public function exportConsolidated(Request $request)
    {
        $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'salesExecutiveId', 'page']);
        $format = $request->get('format', 'xlsx');

        return $this->exportService->exportConsolidated($filters, $format);
    }

    public function exportPartialAccount(Request $request)
    {
        $filters = $request->only(['dateFrom', 'dateTo', 'programCode']);
        $format = $request->get('format', 'xlsx');

        return $this->exportService->exportPartialAccount($filters, $format);
    }
}


