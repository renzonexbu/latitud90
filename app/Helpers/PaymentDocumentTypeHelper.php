<?php

namespace App\Helpers;

use App\Models\ProgramCourse;
use App\Models\OrderDetail;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PaymentDocumentTypeHelper
{
    /**
     * Tipos de documento disponibles
     */
    const TYPE_BOLETA = 'B2';           // Boleta BSale - mismo año del programa
    const TYPE_CONTRATO = 'CR';          // Contrato de Reserva - primera cuota suscripción año siguiente
    const TYPE_ANTICIPO = 'AC';          // Comprobante de Anticipo - año siguiente

    /**
     * Determina el tipo de documento según el año del programa (versión simple)
     * NOTA: Esta es la versión legacy, usar determineDocumentTypes() para la lógica completa
     *
     * @param int $programCourseId ID del program_course
     * @return string 'AC' o 'B2'
     */
    public static function determineDocumentType(int $programCourseId): string
    {
        try {
            $programCourse = ProgramCourse::find($programCourseId);

            if (!$programCourse) {
                Log::warning('ProgramCourse not found for document_type determination', [
                    'program_course_id' => $programCourseId
                ]);
                return self::TYPE_BOLETA;
            }

            $programDate = $programCourse->departure_date ?? $programCourse->start_date;

            if (!$programDate) {
                Log::warning('Program has no departure_date or start_date', [
                    'program_course_id' => $programCourseId
                ]);
                return self::TYPE_BOLETA;
            }

            $currentYear = Carbon::now()->year;
            $programYear = Carbon::parse($programDate)->year;

            if ($programYear > $currentYear) {
                return self::TYPE_ANTICIPO;
            }

            return self::TYPE_BOLETA;

        } catch (\Exception $e) {
            Log::error('Error determining document_type', [
                'program_course_id' => $programCourseId,
                'error' => $e->getMessage()
            ]);
            return self::TYPE_BOLETA;
        }
    }

    /**
     * Determina los tipos de documento a generar según la lógica de negocio completa
     *
     * Reglas:
     * - B2 (Boleta BSale): Si el pago se realiza en el MISMO año o posterior al del programa
     * - CR + AC (Contrato + Anticipo): Primera cuota de suscripción para programa del año siguiente
     * - AC (Solo Anticipo): Cuotas siguientes de suscripción para programa del año siguiente
     *
     * CASO ESPECIAL: Si una cuota de suscripción se cobra cuando YA es el año del programa,
     *                cambia de Anticipo a Boleta BSale
     *
     * @param Payment $payment El pago a evaluar
     * @param OrderDetail|null $orderDetail Detalle de la orden (opcional, se carga si no se pasa)
     * @return array Array con los tipos de documento a generar ['B2'], ['CR', 'AC'], o ['AC']
     */
    public static function determineDocumentTypes(Payment $payment, ?OrderDetail $orderDetail = null): array
    {
        try {
            // Cargar orderDetail si no se pasó
            if (!$orderDetail) {
                $orderDetail = $payment->orderDetail;
            }

            if (!$orderDetail) {
                Log::warning('PaymentDocumentTypeHelper: OrderDetail no encontrado', [
                    'payment_id' => $payment->id
                ]);
                return [self::TYPE_BOLETA];
            }

            $order = $orderDetail->order;
            if (!$order) {
                Log::warning('PaymentDocumentTypeHelper: Order no encontrada', [
                    'order_detail_id' => $orderDetail->id
                ]);
                return [self::TYPE_BOLETA];
            }

            // Obtener programa
            $programCourse = $order->programCourse;
            if (!$programCourse) {
                Log::warning('PaymentDocumentTypeHelper: ProgramCourse no encontrado', [
                    'order_id' => $order->id
                ]);
                return [self::TYPE_BOLETA];
            }

            // Obtener año del programa
            $programDate = $programCourse->departure_date ?? $programCourse->start_date;
            if (!$programDate) {
                Log::warning('PaymentDocumentTypeHelper: Programa sin fecha', [
                    'program_course_id' => $programCourse->id
                ]);
                return [self::TYPE_BOLETA];
            }

            $programYear = Carbon::parse($programDate)->year;

            // Obtener año del pago (usar transaction_date o paid_at o now)
            $paymentDate = $payment->transaction_date
                ?? $orderDetail->paid_at
                ?? now();
            $paymentYear = Carbon::parse($paymentDate)->year;

            // Información para logging
            $logContext = [
                'payment_id' => $payment->id,
                'order_detail_id' => $orderDetail->id,
                'program_course_id' => $programCourse->id,
                'program_year' => $programYear,
                'payment_year' => $paymentYear,
                'payment_type' => $order->payment_type,
                'installment_number' => $orderDetail->installment_number,
            ];

            // REGLA 1: Si el pago es en el mismo año o posterior al del programa → Boleta BSale
            if ($paymentYear >= $programYear) {
                Log::info('PaymentDocumentTypeHelper: B2 (Boleta) - Pago mismo año o posterior al programa', $logContext);
                return [self::TYPE_BOLETA];
            }

            // REGLA 2: El pago es en año ANTERIOR al del programa
            // Verificar si es suscripción (payment_type = 'monthly')
            $isSubscription = $order->payment_type === 'monthly';
            $installmentNumber = $orderDetail->installment_number ?? 1;

            if ($isSubscription && $installmentNumber === 1) {
                // Primera cuota de suscripción para programa del año siguiente
                // → Contrato de Reserva + Comprobante de Anticipo
                Log::info('PaymentDocumentTypeHelper: CR + AC - Primera cuota suscripción año anterior', $logContext);
                return [self::TYPE_CONTRATO, self::TYPE_ANTICIPO];
            }

            // Cuotas siguientes o pago único para programa del año siguiente
            // → Solo Comprobante de Anticipo
            Log::info('PaymentDocumentTypeHelper: AC (Anticipo) - Pago año anterior al programa', $logContext);
            return [self::TYPE_ANTICIPO];

        } catch (\Exception $e) {
            Log::error('PaymentDocumentTypeHelper: Error determinando tipos de documento', [
                'payment_id' => $payment->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [self::TYPE_BOLETA];
        }
    }

    /**
     * Verifica si se debe generar Boleta BSale
     */
    public static function shouldGenerateBoleta(Payment $payment, ?OrderDetail $orderDetail = null): bool
    {
        $types = self::determineDocumentTypes($payment, $orderDetail);
        return in_array(self::TYPE_BOLETA, $types);
    }

    /**
     * Verifica si se debe generar Contrato de Reserva
     */
    public static function shouldGenerateContrato(Payment $payment, ?OrderDetail $orderDetail = null): bool
    {
        $types = self::determineDocumentTypes($payment, $orderDetail);
        return in_array(self::TYPE_CONTRATO, $types);
    }

    /**
     * Verifica si se debe generar Comprobante de Anticipo
     */
    public static function shouldGenerateAnticipo(Payment $payment, ?OrderDetail $orderDetail = null): bool
    {
        $types = self::determineDocumentTypes($payment, $orderDetail);
        return in_array(self::TYPE_ANTICIPO, $types);
    }

    /**
     * Obtiene descripción legible de los tipos de documento
     */
    public static function getDocumentTypeDescriptions(array $types): array
    {
        $descriptions = [
            self::TYPE_BOLETA => 'Boleta Electrónica (BSale)',
            self::TYPE_CONTRATO => 'Contrato de Reserva',
            self::TYPE_ANTICIPO => 'Comprobante de Anticipo',
        ];

        return array_map(fn($type) => $descriptions[$type] ?? $type, $types);
    }
}
