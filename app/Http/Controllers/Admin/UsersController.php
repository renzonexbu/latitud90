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

        // Validar combinaciones de roles
        if ($request->has('roles') && is_array($request->roles)) {
            $this->validateRolesCombination($request->roles);
        }

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

        // Validar combinaciones de roles
        if ($request->has('roles') && is_array($request->roles)) {
            $this->validateRolesCombination($request->roles);
        }

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
                    'description' => 'Administrador de Contabilidad',
                    'roles' => $allRoles->whereIn('name', ['contabilidad'])->values()
                ],
                'marketing' => [
                    'name' => 'Marketing',
                    'description' => 'Administrador de Marketing',
                    'roles' => $allRoles->whereIn('name', ['marketing'])->values()
                ],
                'comercial' => [
                    'name' => 'Comercial',
                    'description' => 'Roles comerciales',
                    'roles' => $allRoles->whereIn('name', ['ejecutivo_comercial'])->values()
                ]
            ];
        }

        return $availableRoles;
    }

    /**
     * Validar que la combinación de roles sea válida
     */
    private function validateRolesCombination(array $roles)
    {
        // Super admin no puede tener otros roles
        if (in_array('super_admin', $roles) && count($roles) > 1) {
            abort(422, 'El rol Super Admin no puede combinarse con otros roles.');
        }

        // Contabilidad no puede tener marketing ni ejecutivo_comercial
        if (in_array('contabilidad', $roles)) {
            if (in_array('marketing', $roles)) {
                abort(422, 'Los roles Contabilidad y Marketing no pueden combinarse.');
            }
            if (in_array('ejecutivo_comercial', $roles)) {
                abort(422, 'Los roles Contabilidad y Ejecutivo Comercial no pueden combinarse.');
            }
        }

        // Marketing solo puede combinarse con ejecutivo_comercial
        if (in_array('marketing', $roles)) {
            $otherRoles = array_diff($roles, ['marketing', 'ejecutivo_comercial']);
            if (!empty($otherRoles)) {
                abort(422, 'El rol Marketing solo puede combinarse con Ejecutivo Comercial.');
            }
        }

        // Ejecutivo comercial solo puede combinarse con marketing
        if (in_array('ejecutivo_comercial', $roles)) {
            $otherRoles = array_diff($roles, ['ejecutivo_comercial', 'marketing']);
            if (!empty($otherRoles)) {
                abort(422, 'El rol Ejecutivo Comercial solo puede combinarse con Marketing.');
            }
        }
    }
}
