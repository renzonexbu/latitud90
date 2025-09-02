<?php

namespace App\Services\Admin\SuperAdmin\Newsletter;

use App\Models\Newsletter;
use Illuminate\Http\Request;

class GetNewsletterService
{
    public function execute(Request $request)
    {
        $query = Newsletter::query();

        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('email', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Ordenar por fecha de suscripción más reciente
        $query->orderBy('subscribed_at', 'desc');

        $perPage = 10;
        $newsletters = $query->paginate($perPage);

        return [
            'newsletters' => $newsletters->items(),
            'filters' => $request->only(['search', 'status']),
            'currentPage' => $newsletters->currentPage(),
            'totalNewsletters' => $newsletters->total(),
            'newslettersPerPage' => $perPage,
        ];
    }
}
