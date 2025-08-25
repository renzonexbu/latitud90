<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;
use App\Models\Payment;
use App\Helpers\ParticipantPriceHelper;
use Illuminate\Http\Request;

class GetProgramsService
{
    /**
     * Obtener programas con filtros y métricas de pagos
     *
     * @param Request $request
     * @return array
     */
    public function execute(Request $request): array
    {
        // Obtener programas paginados
        $programs = $this->getPaginatedPrograms($request);
        
        // Obtener todos los programas para filtros
        $allPrograms = $this->getAllPrograms();

        return [
            'programs' => $programs,
            'allPrograms' => $allPrograms,
            'filters' => $request->only(['search', 'status', 'active'])
        ];
    }

    /**
     * Obtener programas paginados con filtros
     *
     * @param Request $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    private function getPaginatedPrograms(Request $request)
    {
        $programs = Program::with(['paymentMode', 'course.institution', 'course.participants'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%");
            })
            ->when($request->active !== null, function ($query) use ($request) {
                $query->where('active', $request->active);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Cargar imágenes y métricas de pagos
        $programs->getCollection()->transform(function ($program) {
            return $this->addProgramMetrics($program);
        });

        return $programs;
    }

    /**
     * Obtener todos los programas para filtros
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getAllPrograms()
    {
        $allPrograms = Program::with(['paymentMode', 'course.institution', 'course.participants'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Cargar imágenes y métricas de pagos
        $allPrograms->transform(function ($program) {
            return $this->addProgramMetrics($program);
        });

        return $allPrograms;
    }

    /**
     * Agregar métricas de pagos a un programa
     *
     * @param Program $program
     * @return Program
     */
    private function addProgramMetrics(Program $program): Program
    {
        $program->images = $program->images;

        // Obtener participantes activos
        $participants = $program->course?->participants ?? collect();
        $activeParticipants = $participants->filter(function ($p) {
            return ($p->pivot->status ?? 'active') !== 'cancelled';
        });

        // Calcular monto total debido
        $courseTotalAmount = $activeParticipants->reduce(function ($carry, $p) use ($program) {
            $priceData = ParticipantPriceHelper::calculateParticipantPrice($p, $program);
            return $carry + $priceData['final_price'];
        }, 0.0);

        // Calcular monto pagado
        $coursePaidAmount = (float) Payment::whereHas('order', function ($q) use ($program) {
                $q->where('program_id', $program->id);
            })
            ->whereIn('status', ['approved', 'completed'])
            ->sum('amount');
        $coursePaidAmount = round($coursePaidAmount, 2);

        // Calcular porcentaje de pago
        $coursePaymentPercentage = $courseTotalAmount > 0
            ? round(($coursePaidAmount / $courseTotalAmount) * 100, 0)
            : 0;

        // Asignar métricas al programa
        $program->course_total_amount = $courseTotalAmount;
        $program->course_paid_amount = $coursePaidAmount;
        $program->course_payment_percentage = $coursePaymentPercentage;

        return $program;
    }
}
