<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\Users\CreateUserService;
use App\Services\Admin\Users\DeleteUserService;
use App\Services\Admin\Users\GetUsersService;
use App\Services\Admin\Users\ToggleUserStatusService;
use App\Services\Admin\Users\UpdateUserService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class UsersController extends Controller
{
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
        return Inertia::render('Admin/Users/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'is_active' => 'boolean',
        ]);

        (new CreateUserService())->execute($request);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    public function edit(User $user)
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'is_active' => 'boolean',
        ]);

        (new UpdateUserService())->execute($request, $user);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    public function destroy(User $user)
    {
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
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $updatedUser = (new ToggleUserStatusService())->execute($user);
        $status = $updatedUser->is_active ? 'activado' : 'desactivado';

        return redirect()->route('admin.users.index')
            ->with('success', "Usuario {$status} exitosamente.");
    }
}
