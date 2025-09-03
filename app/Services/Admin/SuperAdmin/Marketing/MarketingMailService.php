<?php

namespace App\Services\Admin\SuperAdmin\Marketing;

use App\Models\MarketingMail;
use Illuminate\Http\Request;

class MarketingMailService
{
    public function execute(Request $request)
    {
        $query = MarketingMail::query();

        // Filtro por estado
        if ($request->has('status') && $request->status !== '') {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->inactive();
            }
        }

        // Filtro por búsqueda de email
        if ($request->has('search') && $request->search !== '') {
            $query->where('email', 'like', '%' . $request->search . '%');
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $marketingMails = $query->paginate($perPage);

        return [
            'marketingMails' => $marketingMails,
            'filters' => [
                'status' => $request->get('status', ''),
                'search' => $request->get('search', ''),
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
                'per_page' => $perPage,
            ],
            'stats' => [
                'total' => MarketingMail::count(),
                'active' => MarketingMail::active()->count(),
                'inactive' => MarketingMail::inactive()->count(),
            ]
        ];
    }

    public function toggleStatus($id)
    {
        $marketingMail = MarketingMail::findOrFail($id);
        
        if ($marketingMail->isActive()) {
            $marketingMail->deactivate();
            $message = 'Email desactivado exitosamente.';
        } else {
            $marketingMail->activate();
            $message = 'Email activado exitosamente.';
        }

        return [
            'success' => true,
            'message' => $message,
            'is_active' => $marketingMail->isActive()
        ];
    }

    public function destroy($id)
    {
        $marketingMail = MarketingMail::findOrFail($id);
        $marketingMail->delete();

        return [
            'success' => true,
            'message' => 'Email eliminado exitosamente.'
        ];
    }
}