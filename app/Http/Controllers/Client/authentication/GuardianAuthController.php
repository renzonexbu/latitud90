<?php

namespace App\Http\Controllers\Client\authentication;

use App\Http\Controllers\Controller;
use App\Models\Comune;
use App\Models\Country;
use App\Models\Document;
use App\Models\Participant;
use App\Models\Region;
use App\Services\Client\Authentication\LoginGuardianService;
use App\Services\Client\Authentication\PasswordResetService;
use App\Services\Client\Authentication\RegisterGuardianService;
use App\Services\EcommerceAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class GuardianAuthController extends Controller
{
    public function __construct(
        private RegisterGuardianService $registerService,
        private LoginGuardianService $loginService,
        private PasswordResetService $passwordResetService,
        private EcommerceAnalyticsService $analyticsService
    ) {}

    /**
     * Mostrar formulario de registro
     */
    public function showRegisterForm(Request $request)
    {
        $countries = Country::forSelect();

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
            'document_number' => [
                'required', 'string', 'max:50',
                Rule::unique('guardian_users', 'document')
                    ->where('document_id', $request->document_type_id)
                    ->whereNull('deleted_at'),
            ],
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:guardian_users,email',
            'phone_code' => 'required|string|max:10',
            'phone' => 'required|string|max:20',
            'country_id' => 'required|exists:countries,id',
            'region_id' => [
                'nullable',
                Rule::requiredIf(fn () => (int) $request->country_id === (int) Country::chileId()),
                'exists:regions,id',
            ],
            'comune_id' => [
                'nullable',
                Rule::requiredIf(fn () => (int) $request->country_id === (int) Country::chileId()),
                'exists:comunes,id',
            ],
            'password' => 'required|string|min:8|confirmed',
            'terms_accepted' => 'required|accepted',
        ], [
            'document_type_id.required' => 'El tipo de documento es obligatorio',
            'document_type_id.exists' => 'El tipo de documento seleccionado no es válido',
            'document_number.required' => 'El número de documento es obligatorio',
            'document_number.unique' => 'Este RUT/documento ya tiene una cuenta registrada. Si olvidaste tu contraseña, usa la opción de recuperación.',
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

        // Buscar participante desde el token en sesion
        $participantId = null;
        $participantRut = null;
        $pendingSubscription = session('pending_subscription');

        if ($pendingSubscription && isset($pendingSubscription['token'])) {
            $tokenData = \App\Helpers\TokenHelper::decodeParticipantToken($pendingSubscription['token']);
            if ($tokenData && isset($tokenData['document'])) {
                $participantRut = $tokenData['document'];

                // Buscar el participante por documento
                $participant = Participant::where('document_number', $tokenData['document'])->first();
                if ($participant) {
                    $participantId = $participant->id;
                }
            }
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
        ]), $participantId);

        if (!$result['success']) {
            return back()
                ->withErrors(['error' => $result['message']])
                ->withInput();
        }

        // Registrar tracking de registro de guardian
        $this->analyticsService->recordGuardianRegister($request, $request->email, $participantRut);

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

        // ✅ LOGEAR AUTOMÁTICAMENTE después de verificar email exitosamente
        Auth::guard('guardian')->login($result['user']);

        // Enviar email de bienvenida con cuenta verificada
        try {
            Mail::to($result['user']->email)
                ->send(new \App\Mail\GuardianAccountVerified($result['user']));
        } catch (\Exception $e) {
            Log::error('Error enviando email de cuenta verificada', [
                'user_id' => $result['user']->id,
                'error' => $e->getMessage()
            ]);
        }

        // Si hay un token de programa pendiente, redirigir al programa
        if ($programToken) {
            // Limpiar la sesión
            session()->forget('pending_subscription');

            return redirect("/programs/{$pendingSubscription['program_id']}?token={$programToken}")
                ->with('success', '¡Email verificado! Ya puedes continuar con tu suscripción.');
        }

        // Si no hay programa pendiente, redirigir al dashboard
        return redirect()->route('guardian.dashboard')
            ->with('success', '¡Email verificado exitosamente!');
    }

    /**
     * Reenviar email de verificación
     */
    public function resendVerification(Request $request)
    {
        Log::info('=== INICIO REENVÍO VERIFICACIÓN (Controlador) ===');
        Log::info('Request data:', $request->all());

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            Log::error('Validación falló:', $validator->errors()->toArray());
            return back()->withErrors($validator);
        }

        Log::info('Llamando a registerService->resendVerificationEmail con email: ' . $request->email);
        $result = $this->registerService->resendVerificationEmail($request->email);

        Log::info('Resultado del servicio:', $result);

        if (!$result['success']) {
            Log::error('Error al reenviar email: ' . $result['message']);
            return back()->withErrors(['error' => $result['message']]);
        }

        Log::info('Email reenviado exitosamente');
        Log::info('=== FIN REENVÍO VERIFICACIÓN (Controlador) ===');
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
        $participantId = $request->query('participant_id');
        $redirectReason = $request->query('redirect_reason');

        if ($token || $programId || $redirectReason) {
            $pendingData = $request->session()->get('pending_subscription', []);

            if ($token) {
                $pendingData['token'] = $token;
            }

            if ($programId) {
                $pendingData['program_id'] = $programId;

                if ($token) {
                    $pendingData['return_url'] = "/programs/{$programId}?token={$token}";
                }
            }

            if ($participantId) {
                $pendingData['participant_id'] = $participantId;
            }

            if ($redirectReason) {
                $pendingData['redirect_reason'] = $redirectReason;
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

        // Registrar tracking de login de guardian
        $participantRut = null;
        $pendingSubscription = session('pending_subscription');
        if ($pendingSubscription && isset($pendingSubscription['token'])) {
            $tokenData = \App\Helpers\TokenHelper::decodeParticipantToken($pendingSubscription['token']);
            if ($tokenData && isset($tokenData['document'])) {
                $participantRut = $tokenData['document'];
            }
        }
        $this->analyticsService->recordGuardianLogin($request, $request->email, $participantRut);

        // Verificar si hay un pending_subscription en la sesión para redirigir al programa

        if ($pendingSubscription && isset($pendingSubscription['return_url'])) {
            // Limpiar la sesión
            session()->forget('pending_subscription');

            return redirect($pendingSubscription['return_url'])
                ->with('success', $result['message']);
        }

        // NUEVA LÓGICA: Verificar si viene de un programa con suscripción activa
        $pendingData = session('pending_subscription');
        $redirectReason = $pendingData['redirect_reason'] ?? null;
        $programId = $pendingData['program_id'] ?? null;
        $participantId = $pendingData['participant_id'] ?? null;

        if ($redirectReason === 'subscription_access' && $programId && $participantId) {
            // Limpiar la sesión
            session()->forget('pending_subscription');

            // Verificar que el guardian logeado tenga permiso para este participante
            $guardian = auth('guardian')->user();

            if ($guardian && $guardian->canPayFor($participantId)) {
                // Verificar si hay suscripción activa
                $subscription = \App\Models\ProgramSubscription::where('participant_id', $participantId)
                    ->where('program_id', $programId)
                    ->whereIn('status', ['ACTIVA', 'SUSCRIBIENDO'])
                    ->first();

                $paidOrder = \App\Models\Order::where('participant_id', $participantId)
                    ->where('program_id', $programId)
                    ->whereIn('status', ['paid', 'completed'])
                    ->first();

                // Si tiene suscripción o pago completado, redirigir al dashboard
                if ($subscription || $paidOrder) {
                    return redirect()
                        ->route('guardian.dashboard')
                        ->with('success', 'Bienvenido! Aquí puedes gestionar tus suscripciones.')
                        ->with('highlight_participant', $participantId);
                }
            }

            // Guardian no está vinculado a este participante → cerrar sesión y volver al home
            Auth::guard('guardian')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('ecommerce.index')
                ->with('error', 'La cuenta con la que iniciaste sesión no está asociada a este participante. Por favor inicia sesión con la cuenta correcta.');
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

        // Solo regenerar el token CSRF por seguridad
        // NO invalidar toda la sesión para no afectar otros guards (ej: admin)
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
     * Mostrar página de verificación de email pendiente (usuario autenticado)
     */
    public function showVerificationNotice()
    {
        $user = auth('guardian')->user();

        // Si ya está verificado, redirigir al dashboard
        if ($user->email_verified_at) {
            return redirect()->route('guardian.dashboard')
                ->with('success', 'Tu email ya está verificado.');
        }

        return Inertia::render('Guardian/VerificationNotice', [
            'email' => $user->email
        ]);
    }

    /**
     * Reenviar email de verificación (usuario autenticado)
     */
    public function resendVerificationAuthenticated(Request $request)
    {
        $user = auth('guardian')->user();

        if ($user->email_verified_at) {
            return back()->with('info', 'Tu email ya está verificado.');
        }

        $result = $this->registerService->resendVerificationEmail($user->email);

        if (!$result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', 'Email de verificación reenviado. Revisa tu bandeja de entrada.');
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
