<?php

namespace App\Http\Controllers\Client\authentication;

use App\Http\Controllers\Controller;
use App\Models\Comune;
use App\Models\Country;
use App\Models\Document;
use App\Models\Region;
use App\Services\Client\Authentication\LoginGuardianService;
use App\Services\Client\Authentication\PasswordResetService;
use App\Services\Client\Authentication\RegisterGuardianService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class GuardianAuthController extends Controller
{
    public function __construct(
        private RegisterGuardianService $registerService,
        private LoginGuardianService $loginService,
        private PasswordResetService $passwordResetService
    ) {}

    /**
     * Mostrar formulario de registro
     */
    public function showRegisterForm(Request $request)
    {
        // Obtener países (solo Chile)
        $countries = Country::where('name', 'Chile')->get();

        // Obtener regiones y comunas
        $regions = Region::with('comunes')->get();

        // Obtener tipos de documento
        $documentTypes = Document::all();

        // Si viene un token en la URL, guardarlo en sesión para recuperarlo después del login
        $token = $request->query('token');
        $programId = $request->query('program_id');

        if ($token || $programId) {
            $pendingData = $request->session()->get('pending_subscription', []);

            if ($token) {
                $pendingData['token'] = $token;
            }

            if ($programId) {
                $pendingData['program_id'] = $programId;
                $pendingData['return_url'] = "/programs/{$programId}?token={$token}";
            }

            $request->session()->put('pending_subscription', $pendingData);
        }

        return Inertia::render('Guardian/Register', [
            'countries' => $countries,
            'regions' => $regions,
            'documentTypes' => $documentTypes,
            'token' => $token
        ]);
    }

    /**
     * Procesar registro
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document_type_id' => 'required|exists:document,id',
            'document_number' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:guardian_users,email',
            'phone_code' => 'required|string|max:10',
            'phone' => 'required|string|max:20',
            'country_id' => 'required|exists:countries,id',
            'region_id' => 'required|exists:regions,id',
            'comune_id' => 'required|exists:comunes,id',
            'password' => 'required|string|min:8|confirmed',
            'terms_accepted' => 'required|accepted',
        ], [
            'document_type_id.required' => 'El tipo de documento es obligatorio',
            'document_type_id.exists' => 'El tipo de documento seleccionado no es válido',
            'document_number.required' => 'El número de documento es obligatorio',
            'name.required' => 'El nombre completo es obligatorio',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser válido',
            'email.unique' => 'Este email ya está registrado',
            'phone_code.required' => 'El código de país es obligatorio',
            'phone.required' => 'El número de celular es obligatorio',
            'country_id.required' => 'El país es obligatorio',
            'region_id.required' => 'La región es obligatoria',
            'comune_id.required' => 'La comuna es obligatoria',
            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'terms_accepted.required' => 'Debes aceptar los términos y condiciones',
            'terms_accepted.accepted' => 'Debes aceptar los términos y condiciones',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $result = $this->registerService->register($request->only([
            'document_type_id',
            'document_number',
            'name',
            'email',
            'phone_code',
            'phone',
            'country_id',
            'region_id',
            'comune_id',
            'password'
        ]));

        if (!$result['success']) {
            return back()
                ->withErrors(['error' => $result['message']])
                ->withInput();
        }

        return redirect()
            ->route('guardian.register.success')
            ->with('success', $result['message'])
            ->with('email', $result['user']->email);
    }

    /**
     * Página de éxito de registro
     */
    public function registerSuccess(Request $request)
    {
        // Recuperar token del pending_subscription si existe
        $pendingSubscription = session('pending_subscription');
        $token = $pendingSubscription['token'] ?? null;

        return Inertia::render('Guardian/RegisterSuccess', [
            'email' => session('email'),
            'token' => $token
        ]);
    }

    /**
     * Verificar email
     */
    public function verifyEmail(int $userId, string $token, Request $request)
    {
        $result = $this->registerService->verifyEmail($userId, $token);

        // Recuperar token de programa si existe en sesión
        $pendingSubscription = session('pending_subscription');
        $programToken = $pendingSubscription['token'] ?? null;

        if (!$result['success']) {
            $redirect = redirect()->route('guardian.login');

            if ($programToken) {
                $redirect->with('token', $programToken);
            }

            return $redirect->withErrors(['error' => $result['message']]);
        }

        $redirect = redirect()->route('guardian.login');

        if ($programToken) {
            // Pasar el token como parámetro de URL al login
            return redirect()->to(route('guardian.login') . '?token=' . $programToken)
                ->with('success', $result['message']);
        }

        return $redirect->with('success', $result['message']);
    }

    /**
     * Reenviar email de verificación
     */
    public function resendVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $result = $this->registerService->resendVerificationEmail($request->email);

        if (!$result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Mostrar formulario de login
     */
    public function showLoginForm(Request $request)
    {
        // Si viene un token en la URL, guardarlo en sesión para recuperarlo después del login
        $token = $request->query('token');
        $programId = $request->query('program_id');

        if ($token || $programId) {
            $pendingData = $request->session()->get('pending_subscription', []);

            if ($token) {
                $pendingData['token'] = $token;
            }

            if ($programId) {
                $pendingData['program_id'] = $programId;
                $pendingData['return_url'] = "/programs/{$programId}?token={$token}";
            }

            $request->session()->put('pending_subscription', $pendingData);
        }

        return Inertia::render('Guardian/Login', [
            'token' => $token
        ]);
    }

    /**
     * Procesar login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser válido',
            'password.required' => 'La contraseña es obligatoria',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // NUEVA VALIDACIÓN: Verificar si el email está asociado al participante del token
        $pendingSubscription = session('pending_subscription');

        \Log::info('=== INICIO VALIDACIÓN GUARDIAN ===');
        \Log::info('Pending subscription:', ['data' => $pendingSubscription]);
        \Log::info('Email del login:', ['email' => $request->email]);

        if ($pendingSubscription && isset($pendingSubscription['token'])) {
            $token = $pendingSubscription['token'];
            \Log::info('Token encontrado:', ['token' => $token]);

            $tokenData = \App\Helpers\TokenHelper::decodeParticipantToken($token);
            \Log::info('Token decodificado:', ['tokenData' => $tokenData]);

            if ($tokenData) {
                // Buscar el participante usando el mismo patrón que ProgramService
                $participant = \App\Models\Participant::join('document', 'participants.document_type', '=', 'document.id')
                    ->where('participants.document_number', $tokenData['document'])
                    ->where('document.name', $tokenData['document_type'])
                    ->select('participants.*')
                    ->first();

                \Log::info('Participante encontrado:', [
                    'participant_id' => $participant ? $participant->id : null,
                    'participant_name' => $participant ? $participant->first_name . ' ' . $participant->first_last_name : null
                ]);

                if ($participant) {
                    // Buscar directamente si el email está en emergency_contact del participante
                    $emergencyContact = \App\Models\EmergencyContact::where('participant_id', $participant->id)
                        ->where('email', $request->email)
                        ->first();

                    \Log::info('Búsqueda en emergency_contact:', [
                        'participant_id' => $participant->id,
                        'email_buscado' => $request->email,
                        'encontrado' => $emergencyContact ? 'SI' : 'NO',
                        'emergency_contact_id' => $emergencyContact ? $emergencyContact->id : null,
                        'emergency_contact_name' => $emergencyContact ? $emergencyContact->name : null
                    ]);

                    if (!$emergencyContact) {
                        \Log::warning('Acceso denegado - Email no está en emergency_contact del participante');
                        return back()
                            ->withErrors(['error' => 'Este email no está registrado como apoderado del participante. Por favor, verifica que hayas usado el email correcto o contacta al administrador.'])
                            ->withInput();
                    }

                    \Log::info('Validación exitosa - Email encontrado en emergency_contact');
                }
            }
        }

        \Log::info('=== FIN VALIDACIÓN GUARDIAN ===');

        $result = $this->loginService->login(
            $request->only(['email', 'password']),
            $request->boolean('remember')
        );

        if (!$result['success']) {
            // Si requiere verificación, mostrar opción de reenviar
            if (isset($result['requires_verification'])) {
                return back()
                    ->withErrors(['error' => $result['message']])
                    ->with('requires_verification', true)
                    ->with('email', $result['email'])
                    ->withInput();
            }

            return back()
                ->withErrors(['error' => $result['message']])
                ->withInput();
        }

        // Verificar si hay un pending_subscription en la sesión para redirigir al programa
        $pendingSubscription = session('pending_subscription');

        if ($pendingSubscription && isset($pendingSubscription['return_url'])) {
            // Limpiar la sesión
            session()->forget('pending_subscription');

            return redirect($pendingSubscription['return_url'])
                ->with('success', $result['message']);
        }

        return redirect()
            ->intended(route('guardian.dashboard'))
            ->with('success', $result['message']);
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request)
    {
        $this->loginService->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('ecommerce.index')
            ->with('success', 'Sesión cerrada exitosamente');
    }

    /**
     * Mostrar formulario de solicitud de reseteo
     */
    public function showForgotPasswordForm()
    {
        return Inertia::render('Guardian/ForgotPassword');
    }

    /**
     * Procesar solicitud de reseteo
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser válido',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $result = $this->passwordResetService->requestReset($request->email);

        return back()->with('success', $result['message']);
    }

    /**
     * Mostrar formulario de reseteo de contraseña
     */
    public function showResetPasswordForm(int $userId, string $token)
    {
        // Validar token
        $validation = $this->passwordResetService->validateToken($userId, $token);

        if (!$validation['success']) {
            return redirect()
                ->route('guardian.forgot-password')
                ->withErrors(['error' => $validation['message']]);
        }

        return Inertia::render('Guardian/ResetPassword', [
            'userId' => $userId,
            'token' => $token
        ]);
    }

    /**
     * Procesar reseteo de contraseña
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $result = $this->passwordResetService->resetPassword(
            $request->user_id,
            $request->token,
            $request->password
        );

        if (!$result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return redirect()
            ->route('guardian.login')
            ->with('success', $result['message']);
    }

    /**
     * Mostrar formulario de cambio de contraseña (usuario autenticado)
     */
    public function showChangePasswordForm()
    {
        return Inertia::render('Guardian/ChangePassword');
    }

    /**
     * Procesar cambio de contraseña (usuario autenticado)
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'La contraseña actual es obligatoria',
            'password.required' => 'La nueva contraseña es obligatoria',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $user = $this->loginService->user();

        if (!$user) {
            return redirect()
                ->route('guardian.login')
                ->withErrors(['error' => 'Debes iniciar sesión']);
        }

        $result = $this->passwordResetService->changePassword(
            $user,
            $request->current_password,
            $request->password
        );

        if (!$result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }
}
