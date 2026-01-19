<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\Users\CreateUserService;
use App\Services\Admin\Users\DeleteUserService;
use App\Services\Admin\Users\GetUsersService;
use App\Services\Admin\Users\ToggleUserStatusService;
use App\Services\Admin\Users\UpdateUserService;
use App\Traits\HasPermissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    use HasPermissions;

    public function index(Request $request)
    {
        $users = (new GetUsersService())->execute($request);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'status', 'sort', 'direction']),
        ]);
    }

    public function create()
    {
        $roles = $this->getAvailableRoles();

        return Inertia::render('Admin/Users/Create', [
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'is_active' => 'boolean',
            'roles' => 'array',
        ]);

        $user = (new CreateUserService())->execute($request);

        // Asignar roles si se proporcionaron
        if ($request->has('roles') && is_array($request->roles)) {
            $user->syncRoles($request->roles);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    public function edit(User $user)
    {
        // Proteger usuario super admin oculto
        if ($user->email === 'yohan@nexbu.com') {
            abort(404);
        }

        $roles = $this->getAvailableRoles();
        $user->load('roles');

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, User $user)
    {
        // Proteger usuario super admin oculto
        if ($user->email === 'yohan@nexbu.com') {
            abort(404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'is_active' => 'boolean',
            'roles' => 'array',
        ]);

        (new UpdateUserService())->execute($request, $user);

        // Actualizar roles si se proporcionaron
        if ($request->has('roles') && is_array($request->roles)) {
            $user->syncRoles($request->roles);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    public function destroy(User $user)
    {
        // Proteger usuario super admin oculto
        if ($user->email === 'yohan@nexbu.com') {
            abort(404);
        }

        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        (new DeleteUserService())->execute($user);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario eliminado exitosamente.');
    }

    public function toggleStatus(User $user)
    {
        // Proteger usuario super admin oculto
        if ($user->email === 'yohan@nexbu.com') {
            abort(404);
        }

        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $updatedUser = (new ToggleUserStatusService())->execute($user);
        $status = $updatedUser->is_active ? 'activado' : 'desactivado';

        return redirect()->route('admin.users.index')
            ->with('success', "Usuario {$status} exitosamente.");
    }

    /**
     * Obtener roles disponibles según el usuario autenticado
     */
    private function getAvailableRoles()
    {
        $allRoles = Role::all();
        $availableRoles = [];

        // Super admin puede asignar todos los roles
        if ($this->isSuperAdmin()) {
            $availableRoles = [
                'super_admin' => [
                    'name' => 'Super Admin',
                    'description' => 'Acceso total al sistema',
                    'roles' => $allRoles->whereIn('name', ['super_admin'])->values()
                ],
                'contabilidad' => [
                    'name' => 'Contabilidad',
                    'description' => 'Grupo de contabilidad',
                    'roles' => $allRoles->whereIn('name', [
                        'admin_contabilidad',
                        'editor_contabilidad',
                        'visualizador_contabilidad'
                    ])->values()
                ],
                'marketing' => [
                    'name' => 'Marketing',
                    'description' => 'Grupo de marketing',
                    'roles' => $allRoles->whereIn('name', [
                        'admin_marketing',
                        'editor_marketing',
                        'visualizador_marketing'
                    ])->values()
                ],
                'comercial' => [
                    'name' => 'Comercial',
                    'description' => 'Roles comerciales',
                    'roles' => $allRoles->whereIn('name', [
                        'ejecutivo_comercial'
                    ])->values()
                ]
            ];
        }
        // Admin de contabilidad solo puede asignar roles de su grupo
        elseif ($this->isAdminContabilidad()) {
            $availableRoles = [
                'contabilidad' => [
                    'name' => 'Contabilidad',
                    'description' => 'Grupo de contabilidad',
                    'roles' => $allRoles->whereIn('name', [
                        'admin_contabilidad',
                        'editor_contabilidad',
                        'visualizador_contabilidad'
                    ])->values()
                ]
            ];
        }
        // Admin de marketing solo puede asignar roles de su grupo
        elseif ($this->isAdminMarketing()) {
            $availableRoles = [
                'marketing' => [
                    'name' => 'Marketing',
                    'description' => 'Grupo de marketing',
                    'roles' => $allRoles->whereIn('name', [
                        'admin_marketing',
                        'editor_marketing',
                        'visualizador_marketing'
                    ])->values()
                ]
            ];
        }

        return $availableRoles;
    }
}
