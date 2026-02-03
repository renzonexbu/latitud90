<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\ProgramCourse;
use App\Helpers\ParticipantPriceHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecalculateCourseTotals extends Command
{
    protected $signature = 'courses:recalculate-totals
                            {--course_id= : ID específico de un curso}
                            {--program_id= : ID específico de un program_course}
                            {--dry-run : Solo mostrar lo que se haría sin guardar cambios}';

    protected $description = 'Recalcula los totales de cursos considerando descuentos de participantes';

    public function handle()
    {
        $courseId = $this->option('course_id');
        $programId = $this->option('program_id');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Modo DRY-RUN: No se guardarán cambios en la base de datos');
        }

        $this->info('Recalculando totales de cursos...');
        $this->newLine();

        // Construir query
        $query = Course::with(['programCourses', 'participants', 'institution']);

        if ($courseId) {
            $query->where('id', $courseId);
            $this->info("Procesando curso específico ID: {$courseId}");
        }

        if ($programId) {
            $query->whereHas('programCourses', function ($q) use ($programId) {
                $q->where('id', $programId);
            });
            $this->info("Procesando program_course específico ID: {$programId}");
        }

        $courses = $query->get();

        if ($courses->isEmpty()) {
            $this->warn('No se encontraron cursos para procesar');
            return Command::SUCCESS;
        }

        $this->info("Total de cursos a procesar: {$courses->count()}");
        $this->newLine();

        $results = [];

        foreach ($courses as $course) {
            $programCourse = $course->programCourses->first();

            if (!$programCourse) {
                $this->line("  Curso #{$course->id}: Sin program_course asociado - Saltando");
                continue;
            }

            $participants = $course->participants ?? collect();
            $activeParticipants = $participants->filter(fn($p) => ($p->pivot->status ?? 'active') !== 'cancelled');

            // Calcular el total sin descuentos (precio base × participantes)
            $tripPrice = (float) ($programCourse->trip_price ?? 0);
            $totalWithoutDiscounts = $tripPrice * $activeParticipants->count();

            // Calcular el total CON descuentos
            $totalWithDiscounts = 0.0;
            $totalDiscounts = 0.0;
            $participantDetails = [];

            foreach ($activeParticipants as $participant) {
                $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);

                $basePrice = $priceData['base_price'] ?? $tripPrice;
                $discounts = $priceData['discounts'] ?? 0;
                $finalPrice = $priceData['final_price'] ?? $basePrice;

                $totalWithDiscounts += $finalPrice;
                $totalDiscounts += $discounts;

                if ($discounts > 0) {
                    $participantDetails[] = [
                        'name' => $participant->full_name ?? 'N/A',
                        'discount' => $discounts,
                        'final_price' => $finalPrice,
                    ];
                }
            }

            // Calcular monto pagado
            $paidAmount = $this->calculatePaidAmount($programCourse);

            $institutionName = $course->institution->name ?? 'N/A';
            $programName = $programCourse->name ?? 'N/A';

            $this->line("───────────────────────────────────────────────");
            $this->info("Curso #{$course->id}: {$institutionName}");
            $this->line("  Programa: {$programName}");
            $this->line("  Participantes activos: {$activeParticipants->count()}");
            $this->line("  Precio base: $" . number_format($tripPrice, 0, ',', '.'));
            $this->line("  Total SIN descuentos: $" . number_format($totalWithoutDiscounts, 0, ',', '.'));
            $this->line("  Total de descuentos: $" . number_format($totalDiscounts, 0, ',', '.'));
            $this->info("  Total CON descuentos: $" . number_format($totalWithDiscounts, 0, ',', '.'));
            $this->line("  Monto pagado: $" . number_format($paidAmount, 0, ',', '.'));

            if (count($participantDetails) > 0) {
                $this->newLine();
                $this->line("  Participantes con descuento:");
                foreach ($participantDetails as $detail) {
                    $this->line("    - {$detail['name']}: -$" . number_format($detail['discount'], 0, ',', '.') .
                               " (Final: $" . number_format($detail['final_price'], 0, ',', '.') . ")");
                }
            }

            $results[] = [
                'course_id' => $course->id,
                'program_course_id' => $programCourse->id,
                'institution' => $institutionName,
                'participants' => $activeParticipants->count(),
                'total_without_discounts' => $totalWithoutDiscounts,
                'total_discounts' => $totalDiscounts,
                'total_with_discounts' => $totalWithDiscounts,
                'paid_amount' => $paidAmount,
            ];
        }

        $this->newLine();
        $this->line("═══════════════════════════════════════════════");
        $this->newLine();

        // Resumen
        $this->info('Resumen del recálculo:');
        $this->table(
            ['Curso ID', 'Institución', 'Participantes', 'Total (sin desc)', 'Descuentos', 'Total (con desc)', 'Pagado'],
            collect($results)->map(fn($r) => [
                $r['course_id'],
                substr($r['institution'], 0, 20),
                $r['participants'],
                '$' . number_format($r['total_without_discounts'], 0, ',', '.'),
                '$' . number_format($r['total_discounts'], 0, ',', '.'),
                '$' . number_format($r['total_with_discounts'], 0, ',', '.'),
                '$' . number_format($r['paid_amount'], 0, ',', '.'),
            ])
        );

        // Totales generales
        $grandTotalWithout = collect($results)->sum('total_without_discounts');
        $grandDiscounts = collect($results)->sum('total_discounts');
        $grandTotalWith = collect($results)->sum('total_with_discounts');
        $grandPaid = collect($results)->sum('paid_amount');

        $this->newLine();
        $this->info("Totales generales:");
        $this->line("  Total sin descuentos: $" . number_format($grandTotalWithout, 0, ',', '.'));
        $this->line("  Total descuentos: $" . number_format($grandDiscounts, 0, ',', '.'));
        $this->info("  Total con descuentos: $" . number_format($grandTotalWith, 0, ',', '.'));
        $this->line("  Total pagado: $" . number_format($grandPaid, 0, ',', '.'));

        if ($dryRun) {
            $this->newLine();
            $this->warn('Este fue un DRY-RUN. Los totales se calculan dinámicamente al cargar la vista.');
        }

        Log::info('RecalculateCourseTotals ejecutado', [
            'courses_processed' => count($results),
            'total_discounts' => $grandDiscounts,
            'dry_run' => $dryRun,
        ]);

        return Command::SUCCESS;
    }

    private function calculatePaidAmount(ProgramCourse $programCourse): float
    {
        // Pagos normales completados - EXCLUIR pagos de suscripción para evitar doble conteo
        $normalPayments = (float) DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->where('orders.program_id', $programCourse->id)
            ->whereIn('payments.status', ['approved', 'completed'])
            ->where(function($query) {
                $query->whereNull('payments.payment_source')
                      ->orWhere('payments.payment_source', '!=', 'subscription');
            })
            ->sum('payments.amount');

        // Cuotas de suscripciones pagadas
        $subscriptionPayments = (float) DB::table('installments')
            ->join('installment_plans', 'installments.installment_plan_id', '=', 'installment_plans.id')
            ->where('installment_plans.program_id', $programCourse->id)
            ->where('installments.status', 'paid')
            ->sum('installments.amount');

        return $normalPayments + $subscriptionPayments;
    }
}
