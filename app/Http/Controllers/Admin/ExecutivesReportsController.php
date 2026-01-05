<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ProcedureDocument;
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

    /**
     * Vista index de reportes de apoderados
     */
    public function index()
    {
        $documents = ProcedureDocument::active()
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/Reports/ExecutivesIndex', [
            'documents' => $documents
        ]);
    }

    public function consolidated(Request $request)
    {
        $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'salesExecutiveId', 'page']);

        $data = $this->consolidatedService->getConsolidated($filters);

        return Inertia::render('Admin/Reports/Executives/Consolidated', $data);
    }

    public function partialAccount(Request $request)
    {
        $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'programCode', 'page']);

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
        $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'programCode']);
        $format = $request->get('format', 'xlsx');

        return $this->exportService->exportPartialAccount($filters, $format);
    }
}


