<?php

namespace App\Services\Admin\Users;

use App\Models\User;
use App\Traits\HasPermissions;
use Illuminate\Http\Request;

class GetUsersService
{
    use HasPermissions;

    public function execute(Request $request)
    {
        $query = User::with('roles');

        // Filtrar según el grupo del usuario autenticado
        $currentUser = auth()->user();
        
        // Super admin puede ver todos los usuarios
        if ($this->isSuperAdmin()) {
            // No aplicar filtros adicionales
        } 
        // Admin de contabilidad solo ve usuarios de su grupo
        elseif ($this->isAdminContabilidad() || $this->isEditorContabilidad() || $this->isVisualizadorContabilidad()) {
            $query->whereHas('roles', function ($q) {
                $q->whereIn('name', [
                    'admin_contabilidad',
                    'editor_contabilidad', 
                    'visualizador_contabilidad'
                ]);
            });
        }
        // Admin de marketing solo ve usuarios de su grupo
        elseif ($this->isAdminMarketing() || $this->isEditorMarketing() || $this->isVisualizadorMarketing()) {
            $query->whereHas('roles', function ($q) {
                $q->whereIn('name', [
                    'admin_marketing',
                    'editor_marketing',
                    'visualizador_marketing'
                ]);
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

        return $query->paginate(10)->withQueryString();
    }
}
