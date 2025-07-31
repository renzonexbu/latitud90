<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Passenger;
use App\Models\Payment;
use App\Models\Contract;
use App\Models\PaymentLink;
use App\Services\TransbankService;
use App\Services\KhipuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Carbon\Carbon;

class EcommerceController extends Controller
{
    protected $transbankService;
    protected $khipuService;

    public function __construct(TransbankService $transbankService, KhipuService $khipuService)
    {
        $this->transbankService = $transbankService;
        $this->khipuService = $khipuService;
    }

    /**
     * Mostrar página principal del ecommerce con programas disponibles
     */
    public function index(Request $request)
    {
        // Temporalmente deshabilitado para desarrollo frontend
        // TODO: Restaurar cuando la base de datos esté lista

        return Inertia::render('Ecommerce/Index', [
            'programs' => [],
            'filters' => [],
            'serviceTypes' => []
        ]);
    }

    public function termsAndConditions()
    {
        return Inertia::render('Ecommerce/TermsAndConditions');
    }

    /**
     * Mostrar detalles de un programa específico
     */
    public function show(Program $program)
    {
        $program->load(['passengers' => function($query) {
            $query->where('status', '!=', 'cancelled');
        }]);

        $program->available_spots = $program->capacity - $program->passengers->count();
        $program->is_available = $program->available_spots > 0;

        // Programas relacionados (mismo tipo de servicio)
        $relatedPrograms = Program::where('service_type', $program->service_type)
                                 ->where('id', '!=', $program->id)
                                 ->where('active', true)
                                 ->where('departure_date', '>', now())
                                 ->limit(4)
                                 ->get();

        return Inertia::render('Ecommerce/ProgramDetail', [
            'program' => $program,
            'relatedPrograms' => $relatedPrograms
        ]);
    }

    /**
     * Mostrar formulario de reserva
     */
    public function reservation(Program $program)
    {
        if (!$program->active || $program->available_spots <= 0) {
            return redirect()->route('ecommerce.show', $program)
                           ->with('error', 'Este programa no está disponible para reservas.');
        }

        return Inertia::render('Ecommerce/Reservation', [
            'program' => $program
        ]);
    }

    /**
     * Procesar reserva
     */
    public function storeReservation(Request $request, Program $program)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'document_type' => 'required|in:cedula,pasaporte,rut',
            'document_number' => 'required|string|max:50',
            'birth_date' => 'required|date|before:today',
            'address' => 'required|string|max:500',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_phone' => 'required|string|max:20',
            'dietary_restrictions' => 'nullable|string|max:500',
            'medical_conditions' => 'nullable|string|max:500',
            'payment_method' => 'required|in:full,installments',
            'payment_gateway' => 'required|in:transbank,khipu'
        ]);

        if (!$program->active || $program->available_spots <= 0) {
            return response()->json(['error' => 'Programa no disponible'], 422);
        }

        DB::beginTransaction();
        try {
            // Crear pasajero
            $passenger = Passenger::create([
                'program_id' => $program->id,
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'document_type' => $request->document_type,
                'document_number' => $request->document_number,
                'birth_date' => $request->birth_date,
                'address' => $request->address,
                'emergency_contact_name' => $request->emergency_contact_name,
                'emergency_contact_phone' => $request->emergency_contact_phone,
                'dietary_restrictions' => $request->dietary_restrictions,
                'medical_conditions' => $request->medical_conditions,
                'status' => 'pending_payment',
                'registration_date' => now(),
                'individual_price' => $program->base_price
            ]);

            // Crear contrato/voucher si es necesario
            if (in_array($program->service_type, ['tours', 'excursiones'])) {
                Contract::create([
                    'passenger_id' => $passenger->id,
                    'contract_number' => 'VOUCHER-' . str_pad($passenger->id, 6, '0', STR_PAD_LEFT),
                    'contract_type' => 'voucher',
                    'total_amount' => $passenger->individual_price,
                    'status' => 'pending',
                    'issue_date' => now()
                ]);
            } else {
                Contract::create([
                    'passenger_id' => $passenger->id,
                    'contract_number' => 'CONT-' . str_pad($passenger->id, 6, '0', STR_PAD_LEFT),
                    'contract_type' => 'contract',
                    'total_amount' => $passenger->individual_price,
                    'status' => 'pending',
                    'issue_date' => now()
                ]);
            }

            // Configurar pagos según el método seleccionado
            if ($request->payment_method === 'full') {
                // Pago completo
                $this->createPayment($passenger, $passenger->individual_price, 1, 1, $request->payment_gateway, $program->id);
            } else {
                // Pago en cuotas (3 cuotas por defecto)
                $installmentAmount = round($passenger->individual_price / 3, 0);
                for ($i = 1; $i <= 3; $i++) {
                    $amount = ($i === 3) ? $passenger->individual_price - (2 * $installmentAmount) : $installmentAmount;
                    $this->createPayment($passenger, $amount, $i, 3, $request->payment_gateway, $program->id);
                }
            }

            DB::commit();

            // Redirigir al proceso de pago
            return response()->json([
                'success' => true,
                'passenger_id' => $passenger->id,
                'redirect_url' => route('ecommerce.payment', $passenger)
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Error al procesar la reserva: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mostrar página de pago
     */
    public function payment(Passenger $passenger)
    {
        $passenger->load(['program', 'payments' => function($query) {
            $query->where('status', 'pending')->orderBy('installment_number');
        }]);

        if ($passenger->status !== 'pending_payment') {
            return redirect()->route('ecommerce.index')
                           ->with('error', 'Esta reserva no requiere pago o ya ha sido procesada.');
        }

        return Inertia::render('Ecommerce/Payment', [
            'passenger' => $passenger,
            'pendingPayments' => $passenger->payments
        ]);
    }

    /**
     * Procesar pago específico
     */
    public function processPayment(Request $request, Payment $payment)
    {
        if ($payment->status !== 'pending') {
            return response()->json(['error' => 'Este pago ya fue procesado'], 422);
        }

        // Actualizar el gateway si se proporciona
        if ($request->has('gateway')) {
            $request->validate([
                'gateway' => 'required|in:transbank,khipu'
            ]);

            $payment->update(['gateway' => $request->gateway]);
        }

        try {
            if ($payment->gateway === 'transbank') {
                $result = $this->transbankService->createTransaction(
                    $payment->amount,
                    route('payment.return', ['payment' => $payment->id]),
                    $payment->id
                );

                $payment->update([
                    'gateway_transaction_id' => $result['token'],
                    'gateway_response' => json_encode($result)
                ]);

                return response()->json([
                    'success' => true,
                    'payment_url' => $result['url'] . '?token_ws=' . $result['token']
                ]);

            } elseif ($payment->gateway === 'khipu') {
                // Obtener información del programa para la descripción
                $program = null;
                if ($payment->passenger->program_id) {
                    $program = $payment->passenger->program;
                } else {
                    $program = $payment->passenger->programs->first();
                }

                $description = 'Pago programa ' . ($program ? $program->name : 'Viaje');
                if ($payment->total_installments > 1) {
                    $description .= ' - Cuota ' . $payment->installment_number . ' de ' . $payment->total_installments;
                }

                $result = $this->khipuService->createPayment(
                    $payment->amount,
                    $description,
                    $payment->passenger->email,
                    route('payment.return', ['payment' => $payment->id]),
                    $payment->id
                );

                $payment->update([
                    'gateway_transaction_id' => $result['payment_id'],
                    'gateway_response' => json_encode($result)
                ]);

                return response()->json([
                    'success' => true,
                    'payment_url' => $result['payment_url']
                ]);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al procesar el pago: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Página de confirmación de reserva
     */
    public function confirmation(Passenger $passenger)
    {
        $passenger->load(['program', 'payments', 'contracts']);

        return Inertia::render('Ecommerce/Confirmation', [
            'passenger' => $passenger
        ]);
    }

    /**
     * Buscar reserva por email y número de documento
     */
    public function searchReservation(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'document_number' => 'required|string'
        ]);

        $passenger = Passenger::with(['program', 'payments', 'contracts'])
                             ->where('email', $request->email)
                             ->where('document_number', $request->document_number)
                             ->first();

        if (!$passenger) {
            return back()->with('error', 'No se encontró ninguna reserva con esos datos.');
        }

        return Inertia::render('Ecommerce/ReservationDetail', [
            'passenger' => $passenger
        ]);
    }

    /**
     * Página de búsqueda de reservas
     */
    public function findReservation()
    {
        return Inertia::render('Ecommerce/FindReservation');
    }

    /**
     * Buscar viajes por número de documento del pasajero (RUT, cédula, pasaporte, etc.)
     */
    public function searchByRut(Request $request)
    {
        $request->validate([
            'rut' => 'required|string'
        ]);

        // Buscar pasajeros por número de documento (cualquier tipo: RUT, cédula, pasaporte, etc.)
        $passengers = Passenger::with(['programs', 'payments', 'contracts'])
                               ->where('document_number', $request->rut)
                               ->where('status', '!=', 'cancelled')
                               ->get();

        if ($passengers->isEmpty()) {
            return redirect()->back()->with('error', 'No se encontraron viajes asociados a este número de documento.');
        }

        $user = null;
        if ($passengers->first()->email) {
            $user = $passengers->first()->full_name;
        }

        // Preparar los viajes para la vista - ahora cada pasajero puede tener múltiples programas
        $trips = collect();

        foreach ($passengers as $passenger) {
            // Si el pasajero tiene programas en la nueva relación many-to-many
            if ($passenger->programs->isNotEmpty()) {
                foreach ($passenger->programs as $program) {
                    // Obtener el precio individual del programa desde la tabla pivot
                    $programPrice = $program->pivot->individual_price ?? 0;

                    // Calcular pagos específicos para este programa
                    // Filtrar pagos que correspondan a este programa específico
                    $programPayments = $passenger->payments
                        ->where('program_id', $program->id)
                        ->whereIn('status', ['completed', 'approved']);

                    $programPaidAmount = $programPayments->sum('amount');

                    $programStatus = $program->pivot->status ?? $passenger->status;
                    $remainingAmount = $programPrice - $programPaidAmount;

                    // Solo hay pagos pendientes si el estado no es 'confirmed' y hay monto restante
                    $hasPendingPayments = $programStatus !== 'confirmed' && $remainingAmount > 0;

                    $trips->push([
                        // Información básica del viaje
                        'id' => $passenger->id . '_' . $program->id, // ID único para el viaje
                        'passenger_id' => $passenger->id,
                        'program_id' => $program->id,

                        // Todos los campos del programa
                        'program' => [
                            'id' => $program->id,
                            'name' => $program->name,
                            'description' => $program->description,
                            'service_type' => $program->service_type,
                            'destination' => $program->destination,
                            'departure_date' => $program->departure_date,
                            'return_date' => $program->return_date,
                            'duration_days' => $program->duration_days,
                            'capacity' => $program->capacity,
                            'base_price' => $program->base_price,
                            'includes' => $program->includes,
                            'excludes' => $program->excludes,
                            'requirements' => $program->requirements,
                            'itinerary' => $program->itinerary,
                            'image_url' => $program->image_url ? asset('storage/' . $program->image_url) : asset('storage/programs/default.jpg'),
                            'active' => $program->active,
                            'created_at' => $program->created_at,
                            'updated_at' => $program->updated_at,
                        ],

                        // Todos los campos del pasajero
                        'passenger' => [
                            'id' => $passenger->id,
                            'rut' => $passenger->rut,
                            'first_name' => $passenger->first_name,
                            'last_name' => $passenger->last_name,
                            'full_name' => $passenger->full_name,
                            'email' => $passenger->email,
                            'phone' => $passenger->phone,
                            'document_type' => $passenger->document_type,
                            'document_number' => $passenger->document_number,
                            'birth_date' => $passenger->birth_date,
                            'address' => $passenger->address,
                            'emergency_contact_name' => $passenger->emergency_contact_name,
                            'emergency_contact_phone' => $passenger->emergency_contact_phone,
                            'dietary_restrictions' => $passenger->dietary_restrictions,
                            'medical_conditions' => $passenger->medical_conditions,
                            'registration_date' => $passenger->registration_date,
                            'individual_price' => $programPrice,
                            'price_adjustments' => $program->pivot->price_adjustments ?? 0,
                            'adjustment_reason' => $program->pivot->adjustment_reason,
                            'created_at' => $passenger->created_at,
                            'updated_at' => $passenger->updated_at,
                        ],

                        // Campos de compatibilidad hacia atrás (para componentes existentes)
                        'name' => $program->name,
                        'description' => $program->description ?? 'Sin descripción',
                        'destination' => $program->destination,
                        'departure_date' => $program->departure_date,
                        'return_date' => $program->return_date,
                        'image_url' => $program->image_url ? asset('storage/' . $program->image_url) : asset('storage/programs/default.jpg'),
                        'total_price' => $programPrice,
                        'paid_amount' => $programPaidAmount,
                        'remaining_amount' => $remainingAmount,
                        'payment_percentage' => $programPrice > 0 ? round(($programPaidAmount / $programPrice) * 100, 1) : 0,
                        'status' => $programStatus,
                        'has_pending_payments' => $hasPendingPayments,
                        'last_payment_date' => $programPayments->sortByDesc('payment_date')->first()?->payment_date,
                        'next_payment_date' => $passenger->payments
                            ->where('program_id', $program->id)
                            ->where('status', 'pending')
                            ->sortBy('due_date')
                            ->first()?->due_date,
                    ]);
                }
            }
            // Fallback para pasajeros con la relación antigua (program_id)
            else if ($passenger->program_id && $passenger->program) {
                $paidAmount = $passenger->payments->whereIn('status', ['completed', 'approved'])->sum('amount');
                $remainingAmount = $passenger->individual_price - $paidAmount;

                // Solo hay pagos pendientes si el estado no es 'confirmed' y hay monto restante
                $hasPendingPayments = $passenger->status !== 'confirmed' && $remainingAmount > 0;

                $trips->push([
                    // Información básica del viaje
                    'id' => $passenger->id,
                    'passenger_id' => $passenger->id,
                    'program_id' => $passenger->program_id,

                    // Todos los campos del programa
                    'program' => [
                        'id' => $passenger->program->id,
                        'name' => $passenger->program->name,
                        'description' => $passenger->program->description,
                        'service_type' => $passenger->program->service_type,
                        'destination' => $passenger->program->destination,
                        'departure_date' => $passenger->program->departure_date,
                        'return_date' => $passenger->program->return_date,
                        'duration_days' => $passenger->program->duration_days,
                        'capacity' => $passenger->program->capacity,
                        'base_price' => $passenger->program->base_price,
                        'includes' => $passenger->program->includes,
                        'excludes' => $passenger->program->excludes,
                        'requirements' => $passenger->program->requirements,
                        'itinerary' => $passenger->program->itinerary,
                        'image_url' => $passenger->program->image_url ? asset('storage/' . $passenger->program->image_url) : asset('storage/programs/default.jpg'),
                        'active' => $passenger->program->active,
                        'created_at' => $passenger->program->created_at,
                        'updated_at' => $passenger->program->updated_at,
                    ],

                    // Todos los campos del pasajero
                    'passenger' => [
                        'id' => $passenger->id,
                        'rut' => $passenger->rut,
                        'first_name' => $passenger->first_name,
                        'last_name' => $passenger->last_name,
                        'full_name' => $passenger->full_name,
                        'email' => $passenger->email,
                        'phone' => $passenger->phone,
                        'document_type' => $passenger->document_type,
                        'document_number' => $passenger->document_number,
                        'birth_date' => $passenger->birth_date,
                        'address' => $passenger->address,
                        'emergency_contact_name' => $passenger->emergency_contact_name,
                        'emergency_contact_phone' => $passenger->emergency_contact_phone,
                        'dietary_restrictions' => $passenger->dietary_restrictions,
                        'medical_conditions' => $passenger->medical_conditions,
                        'registration_date' => $passenger->registration_date,
                        'individual_price' => $passenger->individual_price,
                        'price_adjustments' => $passenger->price_adjustments,
                        'adjustment_reason' => $passenger->adjustment_reason,
                        'created_at' => $passenger->created_at,
                        'updated_at' => $passenger->updated_at,
                    ],

                    // Campos de compatibilidad hacia atrás (para componentes existentes)
                    'name' => $passenger->program->name,
                    'description' => $passenger->program->description ?? 'Sin descripción',
                    'destination' => $passenger->program->destination,
                    'departure_date' => $passenger->program->departure_date,
                    'return_date' => $passenger->program->return_date,
                    'image_url' => $passenger->program->image_url ? asset('storage/' . $passenger->program->image_url) : asset('storage/programs/default.jpg'),
                    'total_price' => $passenger->individual_price,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'payment_percentage' => $passenger->individual_price > 0 ? round(($paidAmount / $passenger->individual_price) * 100, 1) : 0,
                    'status' => $passenger->status,
                    'has_pending_payments' => $hasPendingPayments,
                    'last_payment_date' => $passenger->payments->whereIn('status', ['completed', 'approved'])->sortByDesc('payment_date')->first()?->payment_date,
                    'next_payment_date' => $passenger->payments->where('status', 'pending')->sortBy('due_date')->first()?->due_date,
                ]);
            }
        }

        return Inertia::render('Payment/TripSelection', [
            'trips' => $trips,
            'user' => $user,
            'rut' => $request->rut
        ]);
    }

    /**
     * Mostrar página de selección de método de pago (Paso 2)
     */
    public function paymentMethod(Request $request)
    {
        $request->validate([
            'trip' => 'required|array',
            'rut' => 'required|string'
        ]);

        $trip = $request->trip;
        $rut = $request->rut;

        return Inertia::render('Payment/PaymentMethod', [
            'trip' => $trip,
            'rut' => $rut
        ]);
    }

    /**
     * Configurar el pago según el método seleccionado
     */
    public function setupPayment(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:full,installments',
            'installments' => 'required|integer|min:1|max:12',
            'trip_id' => 'required|string',
            'passenger_id' => 'required|integer',
            'program_id' => 'required|integer'
        ]);

        DB::beginTransaction();
        try {
            // Buscar el pasajero y programa
            $passenger = Passenger::with(['programs', 'payments'])->findOrFail($request->passenger_id);
            $program = Program::findOrFail($request->program_id);

            // Calcular el monto a pagar específico del programa
            $programPrice = 0;

            // Si usa la nueva relación many-to-many
            $programRelation = $passenger->programs->where('id', $request->program_id)->first();
            if ($programRelation) {
                $programPrice = $programRelation->pivot->individual_price ?? 0;
            } else {
                // Fallback para la relación antigua
                $programPrice = $passenger->individual_price ?? 0;
            }

            // Calcular pagos ya realizados específicos para este programa
            $paidAmount = $passenger->payments
                ->where('program_id', $request->program_id)
                ->whereIn('status', ['completed', 'approved'])
                ->sum('amount');

            $remainingAmount = $programPrice - $paidAmount;

            if ($remainingAmount <= 0) {
                DB::rollback();
                return response()->json(['error' => 'Este programa ya está completamente pagado'], 422);
            }

            // Si es pago en cuotas, verificar si ya existen cuotas pendientes para este programa
            if ($request->payment_method === 'installments') {
                // Eliminar pagos pendientes existentes para este programa específico
                $passenger->payments()
                    ->where('program_id', $request->program_id)
                    ->where('status', 'pending')
                    ->delete();

                // Crear las nuevas cuotas
                $installmentAmount = round($remainingAmount / $request->installments, 0);

                for ($i = 1; $i <= $request->installments; $i++) {
                    // Ajustar la última cuota para que no haya diferencias por redondeo
                    $amount = ($i === $request->installments)
                        ? $remainingAmount - (($request->installments - 1) * $installmentAmount)
                        : $installmentAmount;

                    Payment::create([
                        'passenger_id' => $passenger->id,
                        'program_id' => $request->program_id,
                        'amount' => $amount,
                        'payment_date' => null,
                        'payment_method' => 'online',
                        'gateway' => 'transbank', // Por defecto, se puede cambiar después
                        'status' => 'pending',
                        'installment_number' => $i,
                        'total_installments' => $request->installments,
                        'due_date' => now()->addMonths($i - 1)->addDays(7) // Primera cuota en 7 días, luego mensual
                    ]);
                }
            } else {
                // Pago total - eliminar pagos pendientes para este programa y crear uno solo
                $passenger->payments()
                    ->where('program_id', $request->program_id)
                    ->where('status', 'pending')
                    ->delete();

                Payment::create([
                    'passenger_id' => $passenger->id,
                    'program_id' => $request->program_id,
                    'amount' => $remainingAmount,
                    'payment_date' => null,
                    'payment_method' => 'online',
                    'gateway' => 'transbank',
                    'status' => 'pending',
                    'installment_number' => 1,
                    'total_installments' => 1,
                    'due_date' => now()->addDays(1)
                ]);
            }

            DB::commit();

            // Redirigir al paso 3 (gateway de pago)
            return redirect()->route('payment.gateway', [
                'passenger' => $passenger->id,
                'rut' => $request->input('rut') ?? $passenger->document_number
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Error al configurar el pago: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mostrar gateway de pago (Paso 3)
     */
    public function paymentGateway(Request $request, $passengerId)
    {
        $passenger = Passenger::with(['payments' => function($query) {
            $query->where('status', 'pending')
                  ->orderBy('installment_number');
        }])->findOrFail($passengerId);

        // Obtener el siguiente pago pendiente
        $nextPayment = $passenger->payments->where('status', 'pending')
                                          ->sortBy('installment_number')
                                          ->first();

        if (!$nextPayment) {
            return redirect()->route('ecommerce.index')
                           ->with('error', 'No hay pagos pendientes para este pasajero.');
        }

        // Obtener información del programa
        $program = null;
        if ($passenger->program_id) {
            $program = $passenger->program;
        } else {
            $program = $passenger->programs->first();
        }

        return Inertia::render('Payment/Gateway', [
            'passenger' => $passenger,
            'payment' => $nextPayment,
            'program' => $program,
            'rut' => $request->rut
        ]);
    }

    /**
     * Crear un pago
     */
    private function createPayment($passenger, $amount, $installmentNumber, $totalInstallments, $gateway, $programId = null)
    {
        return Payment::create([
            'passenger_id' => $passenger->id,
            'program_id' => $programId ?? $passenger->program_id,
            'amount' => $amount,
            'payment_date' => null,
            'payment_method' => 'online',
            'gateway' => $gateway,
            'status' => 'pending',
            'installment_number' => $installmentNumber,
            'total_installments' => $totalInstallments,
            'due_date' => $installmentNumber === 1 ? now()->addDays(1) : now()->addDays(30 * $installmentNumber)
        ]);
    }
}
