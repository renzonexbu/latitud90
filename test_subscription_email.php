<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProgramSubscription;
use App\Models\Participant;
use App\Models\EmergencyContact;
use App\Models\InstallmentPlan;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

echo "=== Test de Envío de Email de Suscripción ===\n\n";

// Listar suscripciones disponibles
echo "Suscripciones disponibles:\n";
echo "-------------------------\n";

$subscriptions = ProgramSubscription::with('participant', 'programCourse')->get();

if ($subscriptions->isEmpty()) {
    echo "No hay suscripciones en la base de datos.\n";
    exit;
}

foreach ($subscriptions as $subscription) {
    echo "ID: {$subscription->id}\n";
    echo "Participante: {$subscription->participant->first_name} {$subscription->participant->first_last_name}\n";
    echo "Programa: " . ($subscription->programCourse->name ?? 'N/A') . "\n";
    echo "Estado: {$subscription->status}\n";
    echo "Email enviado: " . ($subscription->email_sent ? 'Sí' : 'No') . "\n";
    echo "-------------------------\n";
}

// Tomar la primera suscripción para prueba
$subscription = $subscriptions->first();

echo "\n=== Enviando email de prueba ===\n";
echo "Suscripción ID: {$subscription->id}\n";

// Buscar la orden asociada (sin filtrar por payment_method que no existe en orders)
$order = Order::where('participant_id', $subscription->participant_id)
    ->where('program_id', $subscription->program_id)
    ->orderBy('created_at', 'desc')
    ->first();

if ($order) {
    echo "Orden ID: {$order->id}\n";
} else {
    echo "ADVERTENCIA: No se encontró orden asociada (no es requerida para el email).\n";
}

// Buscar el plan de cuotas
$installmentPlan = InstallmentPlan::where('participant_id', $subscription->participant_id)
    ->where('program_id', $subscription->program_id)
    ->first();

if (!$installmentPlan) {
    echo "ERROR: No se encontró el plan de cuotas asociado.\n";
    exit;
}

echo "Plan de cuotas ID: {$installmentPlan->id}\n";

// Obtener datos necesarios
$participant = $subscription->participant;
$programCourse = $subscription->programCourse;

// Buscar contacto de emergencia (apoderado)
$emergencyContact = EmergencyContact::where('participant_id', $participant->id)->first();

if (!$emergencyContact) {
    echo "ERROR: No se encontró contacto de emergencia para el participante.\n";
    exit;
}

echo "Email destino: {$emergencyContact->email}\n";

// Extraer fecha de primer cobro desde charge_program
$chargeProgram = $subscription->charge_program ?? [];
$firstCharge = !empty($chargeProgram) ? $chargeProgram[0] : null;
$firstChargeDate = $firstCharge ? Carbon::parse($firstCharge['charge_date']) : null;
$firstChargeAmount = $firstCharge['amount'] ?? $subscription->amount;

echo "Fecha primer cobro: " . ($firstChargeDate ? $firstChargeDate->format('d/m/Y') : 'No disponible') . "\n";
echo "Monto: {$firstChargeAmount}\n";

// Preparar datos del email
$companyName = config('app.name', 'Latitud 90');
$companyEmail = config('mail.from.address', 'info@latitud90.com');
$companyPhone = '+56 2 1234 5678'; // TODO: Obtener de configuración

$emailData = [
    'customer_name' => ucwords(strtolower($emergencyContact->name)),
    'customer_email' => $emergencyContact->email,
    'participant_name' => ucwords(strtolower($participant->first_name . ' ' . $participant->first_last_name)),
    'program_name' => $programCourse->name ?? 'Programa',
    'subscription_amount' => number_format($firstChargeAmount, 0, ',', '.'),
    'first_charge_date' => $firstChargeDate ? $firstChargeDate->format('d/m/Y') : 'Próximamente',
    'total_installments' => $installmentPlan->total_installments,
    'payment_method' => $subscription->payment_method['brand'] ?? 'Tarjeta de Crédito/Débito',
    'subscription_id' => $subscription->virtualpos_subscription_id ?? $subscription->id,
    'company_name' => $companyName,
    'company_email' => $companyEmail,
    'company_phone' => $companyPhone,
    'subject' => '¡Suscripción Exitosa! - ' . ($programCourse->name ?? 'Programa'),
];

echo "\n=== Enviando email ===\n";

try {
    Mail::send('Mails.subscription_success', $emailData, function ($message) use ($emailData) {
        $message->to($emailData['customer_email'], $emailData['customer_name'])
                ->subject($emailData['subject']);
    });

    echo "✓ Email enviado exitosamente a: {$emailData['customer_email']}\n";

    // Actualizar el registro para marcar que se envió el email
    $subscription->update([
        'email_sent' => true,
        'email_sent_at' => now()
    ]);

    echo "✓ Registro actualizado: email_sent = true\n";

} catch (\Exception $e) {
    echo "✗ ERROR al enviar email: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Proceso completado ===\n";
