<?php

namespace App\Services\Admin\Users;

use App\Models\User;
use App\Traits\HasPermissions;
use App\Traits\AdminLogging;
use Illuminate\Http\Request;

class GetUsersService
{
    use HasPermissions, AdminLogging;

    public function execute(Request $request)
    {
        $query = User::with('roles');

        // Ocultar usuario super admin de desarrollo (nunca se muestra en listado)
        $query->where('email', '!=', 'yohan@nexbu.com');

        // Filtrar según el grupo del usuario autenticado
        $currentUser = auth()->user();

        // Super admin puede ver todos los usuarios
        if ($this->isSuperAdmin()) {
            // No aplicar filtros adicionales
        }
        // Contabilidad solo ve usuarios de contabilidad
        elseif ($this->isContabilidad()) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', 'contabilidad');
            });
        }
        // Marketing solo ve usuarios de marketing
        elseif ($this->isMarketing()) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', 'marketing');
            });
        }

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Ordenamiento
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $users = $query->paginate(10);

        // Log the users list view
        $this->logView(
            'users',
            'UserList',
            0, // No specific resource ID for list views
            "Lista de usuarios consultada - Total: {$users->total()} registros",
            [
                'total_users' => $users->total(),
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'filters_applied' => $request->only(['search', 'status', 'sort', 'direction']),
                'user_role' => $currentUser->roles->first()?->name ?? 'unknown',
            ]
        );

        return $users;
    }
}
