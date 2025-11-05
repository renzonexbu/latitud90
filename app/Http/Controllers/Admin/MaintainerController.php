<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SuperAdmin\Marketing\MarketingMailService;
use App\Services\Admin\SuperAdmin\GetMaintainerIndexService;
use App\Services\Admin\SuperAdmin\Newsletter\GetNewsletterService;
use App\Services\Admin\SuperAdmin\Newsletter\GetNewsletterEditService;
use App\Services\Admin\SuperAdmin\Newsletter\UpdateNewsletterService;
use App\Services\Admin\SuperAdmin\Newsletter\DeleteNewsletterService;
use App\Services\Admin\SuperAdmin\Newsletter\ExportNewsletterService;
use App\Services\Admin\SuperAdmin\SystemLogs\GetAdminLogsService;
use App\Services\Admin\SuperAdmin\SystemLogs\GetAdminLogShowService;
use App\Services\Admin\SuperAdmin\SystemLogs\ExportAdminLogsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MaintainerController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super_admin');
    }

    public function index()
    {
        $service = new GetMaintainerIndexService();
        $data = $service->execute();

        return Inertia::render('Admin/Maintainer/Index', $data);
    }

    public function newsletter(Request $request)
    {
        $service = new GetNewsletterService();
        $data = $service->execute($request);

        return Inertia::render('Admin/Maintainer/Newsletter', $data);
    }

    public function exportNewsletter(Request $request)
    {
        try {
            $service = new ExportNewsletterService();
            return $service->execute($request);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al exportar newsletters: ' . $e->getMessage()]);
        }
    }

    public function editNewsletter($id)
    {
        $service = new GetNewsletterEditService();
        $data = $service->execute($id);

        return Inertia::render('Admin/Maintainer/NewsletterEdit', $data);
    }

    public function updateNewsletter(Request $request, $id)
    {
        $service = new UpdateNewsletterService();
        $service->execute($request, $id);

        return redirect()->route('admin.maintainer.newsletter.index')
            ->with('success', 'Newsletter actualizado exitosamente.');
    }

    public function destroyNewsletter($id)
    {
        $service = new DeleteNewsletterService();
        $service->execute($id);

        return redirect()->route('admin.maintainer.newsletter.index')
            ->with('success', 'Newsletter eliminado exitosamente.');
    }

    // Admin Logs Methods
    public function adminLogs(Request $request)
    {
        $service = new GetAdminLogsService();
        $data = $service->execute($request);

        return Inertia::render('Admin/Maintainer/AdminLogs', $data);
    }

    public function showAdminLog($id)
    {
        $service = new GetAdminLogShowService();
        $data = $service->execute($id);

        return Inertia::render('Admin/Maintainer/AdminLogShow', $data);
    }

    public function exportAdminLogs(Request $request)
    {
        try {
            $service = new ExportAdminLogsService();
            return $service->execute($request);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al exportar logs: ' . $e->getMessage()]);
        }
    }

    public function marketingMails(Request $request)
    {
        $service = new MarketingMailService();
        $data = $service->execute($request);

        return Inertia::render('Admin/Maintainer/MarketingMail', $data);
    }

    public function toggleMarketingMailStatus($id)
    {
        try {
            $service = new MarketingMailService();
            $result = $service->toggleStatus($id);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyMarketingMail($id)
    {
        try {
            $service = new MarketingMailService();
            $result = $service->destroy($id);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportMarketingMails(Request $request)
    {
        try {
            $filters = $request->only(['search', 'status', 'sort_by', 'sort_order']);
            $service = new \App\Services\Admin\Reports\MarketingMailExportService();
            return $service->export($filters);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al exportar marketing mails: ' . $e->getMessage()]);
        }
    }

    public function syncMarketingMails()
    {
        try {
            $service = new \App\Services\Commands\MarketingMailsService();
            $results = $service->processMarketingEmails();

            return response()->json([
                'success' => true,
                'message' => 'Sincronización completada exitosamente.',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar: ' . $e->getMessage()
            ], 500);
        }
    }
}
