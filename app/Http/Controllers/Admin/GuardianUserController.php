<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\GuardianPasswordReset;
use App\Models\GuardianUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class GuardianUserController extends Controller
{
    /**
     * Listar guardian users con filtros y paginación
     */
    public function index(Request $request)
    {
        $query = GuardianUser::query();

        // Filtro de búsqueda por nombre o email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('document', 'like', "%{$search}%");
            });
        }

        // Filtro por estado
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtro por verificación de email
        if ($request->filled('email_verified')) {
            if ($request->email_verified === 'verified') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->email_verified === 'unverified') {
                $query->whereNull('email_verified_at');
            }
        }

        // Filtro por rango de fechas
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $guardianUsers = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('Admin/GuardianUsers/Index', [
            'guardianUsers' => $guardianUsers,
            'filters' => $request->only(['search', 'status', 'email_verified', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Cambiar estado de un guardian user (active <-> suspended)
     */
    public function toggleStatus(GuardianUser $guardianUser)
    {
        $newStatus = $guardianUser->status === 'active' ? 'suspended' : 'active';
        $guardianUser->update(['status' => $newStatus]);

        $label = $newStatus === 'active' ? 'activado' : 'suspendido';

        return back()->with('success', "Usuario {$guardianUser->name} {$label} correctamente.");
    }

    /**
     * Reenviar email de recuperación de contraseña
     */
    public function resendPasswordReset(GuardianUser $guardianUser)
    {
        $resetToken = Str::random(64);

        cache()->put(
            'password_reset_' . $guardianUser->id,
            $resetToken,
            now()->addHour()
        );

        try {
            Mail::to($guardianUser->email)->sendNow(
                new GuardianPasswordReset($guardianUser, $resetToken)
            );

            \Log::info('Email de reseteo de contraseña enviado desde admin a: ' . $guardianUser->email);

            return back()->with('success', "Email de recuperación enviado a {$guardianUser->email}.");
        } catch (\Exception $e) {
            \Log::error('Error enviando email de reseteo desde admin', [
                'guardian_user_id' => $guardianUser->id,
                'email' => $guardianUser->email,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'No se pudo enviar el email. Error: ' . $e->getMessage());
        }
    }

    /**
     * Verificar email manualmente desde el panel admin
     */
    public function verifyEmail(GuardianUser $guardianUser)
    {
        if ($guardianUser->email_verified_at) {
            return back()->with('info', "El email de {$guardianUser->name} ya está verificado.");
        }

        $guardianUser->update(['email_verified_at' => now()]);

        // Limpiar token de verificación pendiente si existe
        cache()->forget('email_verification_' . $guardianUser->id);

        return back()->with('success', "Email de {$guardianUser->name} verificado correctamente.");
    }
}
