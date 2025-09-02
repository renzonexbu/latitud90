<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;
use App\Models\Institution;
use App\Models\SalesExecutive;
use App\Models\Payment;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\DB;

class GetEditDataService
{
    use AdminLogging;
    /**
     * Obtener datos necesarios para editar un programa
     *
     * @param Program $program
     * @return array
     */
    public function execute(Program $program): array
    {
        // Cargar todas las relaciones necesarias
        $program->load([
            'course.institution',
            'course.participants',
            'paymentMode',
            'totalPaymentMethod',
            'lat90PaymentMethod'
        ]);

        // Agregar métricas de pagos
        $program = $this->addPaymentMetrics($program);

        // Pre-cargar opciones de pago habilitadas
        $program = $this->addPaymentOptions($program);

        // Obtener instituciones y ejecutivos para el dropdown
        $institutions = Institution::active()->orderBy('name')->get();
        $salesExecutives = SalesExecutive::where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        // Log the program edit view
        $this->logView(
            'programs',
            'Program',
            $program->id,
            "Programa abierto para edición: {$program->name} ({$program->code})",
            [
                'program_name' => $program->name,
                'program_code' => $program->code,
                'destination' => $program->destination,
                'has_course' => $program->course ? true : false,
                'participants_count' => $program->course?->participants?->count() ?? 0,
                'institutions_count' => $institutions->count(),
                'sales_executives_count' => $salesExecutives->count(),
            ]
        );

        return [
            'program' => $program,
            'institutions' => $institutions,
            'salesExecutives' => $salesExecutives,
        ];
    }

    /**
     * Agregar métricas de pagos al programa
     *
     * @param Program $program
     * @return Program
     */
    private function addPaymentMetrics(Program $program): Program
    {
        $participants = $program->course?->participants ?? collect();
        $activeParticipants = $participants->filter(function ($p) {
            return ($p->pivot->status ?? 'active') !== 'cancelled';
        });

        $courseTotalAmount = $activeParticipants->reduce(function ($carry, $p) use ($program) {
            $base = (float) ($p->pivot->individual_price ?? $p->individual_price ?? $program->trip_price);
            $adj = (float) ($p->pivot->price_adjustments ?? 0);
            return $carry + round($base + $adj, 2);
        }, 0.0);

        $coursePaidAmount = (float) Payment::whereHas('order', function ($q) use ($program) {
            $q->where('program_id', $program->id);
        })
            ->whereIn('status', ['approved', 'completed'])
            ->sum('amount');
        $coursePaidAmount = round($coursePaidAmount, 2);

        $coursePaymentPercentage = $courseTotalAmount > 0
            ? round(($coursePaidAmount / $courseTotalAmount) * 100, 0)
            : 0;

        $program->course_total_amount = $courseTotalAmount;
        $program->course_paid_amount = $coursePaidAmount;
        $program->course_payment_percentage = $coursePaymentPercentage;

        return $program;
    }

    /**
     * Agregar opciones de pago al programa
     *
     * @param Program $program
     * @return Program
     */
    private function addPaymentOptions(Program $program): Program
    {
        $paymentOptions = DB::table('program_payment_option')
            ->join('payment_options', 'payment_options.id', '=', 'program_payment_option.payment_option_id')
            ->where('program_payment_option.program_id', $program->id)
            ->select('payment_options.code', 'payment_options.mode')
            ->get();

        $program->full_payment_options = $paymentOptions->where('mode', 'full')->pluck('code')->values();
        $program->lat90_payment_options = $paymentOptions->where('mode', 'lat90')->pluck('code')->values();

        return $program;
    }
}
