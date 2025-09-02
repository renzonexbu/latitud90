<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingMail;
use App\Services\Commands\MarketingMailsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class MarketingMailsController extends Controller
{
    /**
     * Mostrar lista de emails de marketing
     */
    public function index(Request $request): Response
    {
        $query = MarketingMail::query();

        // Filtros
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where('email', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $status = $request->get('status');
            if ($status === 'active') {
                $query->active();
            } elseif ($status === 'inactive') {
                $query->inactive();
            }
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $marketingMails = $query->paginate(20);

        // Estadísticas
        $stats = (new MarketingMailsService())->getMarketingEmailsStats();

        return Inertia::render('Admin/MarketingMails/Index', [
            'marketingMails' => $marketingMails,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'sort_by', 'sort_direction'])
        ]);
    }

    /**
     * Cambiar estado de un email (activar/desactivar)
     */
    public function toggleStatus(MarketingMail $marketingMail): JsonResponse
    {
        try {
            if ($marketingMail->is_active) {
                $marketingMail->deactivate();
                $message = 'Email desactivado correctamente';
            } else {
                $marketingMail->activate();
                $message = 'Email activado correctamente';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'is_active' => $marketingMail->is_active
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado del email'
            ], 500);
        }
    }

    /**
     * Eliminar un email de marketing
     */
    public function destroy(MarketingMail $marketingMail): JsonResponse
    {
        try {
            $marketingMail->delete();

            return response()->json([
                'success' => true,
                'message' => 'Email eliminado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el email'
            ], 500);
        }
    }

    /**
     * Procesar emails de marketing manualmente
     */
    public function processEmails(): JsonResponse
    {
        try {
            $service = new MarketingMailsService();
            $results = $service->processMarketingEmails();

            return response()->json([
                'success' => true,
                'message' => 'Emails procesados correctamente',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar emails: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas actualizadas
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = (new MarketingMailsService())->getMarketingEmailsStats();

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas'
            ], 500);
        }
    }
}
