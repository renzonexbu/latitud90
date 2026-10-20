<?php

namespace App\Http\Controllers\Api;

use App\Helpers\RutHelper;
use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\ParticipantProgram;
use App\Models\ProgramCourse;
use App\Services\Admin\ParticipantFinancialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Estado financiero de un programa, en tiempo real.
 *
 * Solo lectura. Reutiliza ParticipantFinancialService, que es la fuente única
 * de verdad del sistema para precios, pagos y saldos: las cifras de esta API
 * coinciden con las de los reportes internos porque salen del mismo cálculo.
 *
 * Criterio acordado con la clienta: el saldo por cobrar nunca es negativo y el
 * excedente se informa aparte, para que los totales de distintos programas se
 * puedan sumar y comparar sin que un sobrepago los distorsione.
 */
class ProgramFinancialController extends Controller
{
    public function show(string $codigo): JsonResponse
    {
        $programCourse = ProgramCourse::where('code', $codigo)->first();

        if (!$programCourse) {
            return response()->json([
                'error' => 'Programa no encontrado',
                'codigo' => $codigo,
            ], 404);
        }

        $participantIds = ParticipantProgram::where('program_id', $programCourse->id)
            ->pluck('participant_id')
            ->all();

        $participants = Participant::with('documentType:id,name')
            ->whereIn('id', $participantIds)
            ->get(['id', 'first_name', 'second_name', 'first_last_name', 'second_last_name', 'document_number', 'document_type'])
            ->keyBy('id');

        $mora = $this->moraPorParticipante($programCourse->id);

        $detalle = [];
        $valorTotal = 0.0;
        $recaudado = 0.0;
        $saldo = 0.0;
        $excedente = 0.0;
        $deBaja = 0;
        $enMora = 0;

        foreach ($participantIds as $participantId) {
            $participant = $participants->get($participantId);
            if (!$participant) {
                continue;
            }

            $f = ParticipantFinancialService::calculate($participantId, $programCourse->id);

            $valorTotal += $f['net_amount'];
            $recaudado  += $f['total_paid'];
            $saldo      += $f['pending_amount'];
            $excedente  += max($f['total_paid'] - $f['net_amount'], 0);

            if ($f['is_de_baja']) {
                $deBaja++;
            }

            $atraso = $mora[$participantId] ?? null;
            if ($atraso) {
                $enMora++;
            }

            $detalle[$this->clave($participant)] = [
                'nombre' => $this->nombre($participant),
                'valor' => $this->peso($f['net_amount']),
                'recaudado' => $this->peso($f['total_paid']),
                'saldo_por_cobrar' => $this->peso($f['pending_amount']),
                'avance' => (int) round($f['progress_percentage']),
                'mora' => [
                    'en_mora' => (bool) $atraso,
                    'dias' => (int) ($atraso->dias ?? 0),
                    'cuotas_vencidas' => (int) ($atraso->cuotas ?? 0),
                ],
                'estado' => $this->estado($f['status']),
                'de_baja' => (bool) $f['is_de_baja'],
            ];
        }

        return response()->json([
            'programa' => [
                'codigo' => $programCourse->code,
                'nombre' => $programCourse->name,
                'fecha_salida' => optional($programCourse->departure_date)->toDateString(),
                'actualizado_al' => now()->toIso8601String(),

                'resumen' => [
                    'participantes' => count($detalle),
                    'valor_total' => $this->peso($valorTotal),
                    'recaudado' => $this->peso($recaudado),
                    // Suma de los saldos individuales: si un participante pagó de
                    // más, su excedente no tapa la deuda de otro.
                    'saldo_por_cobrar' => $this->peso($saldo),
                    'avance' => $valorTotal > 0 ? (int) round(($recaudado / $valorTotal) * 100) : 0,
                    'excedente' => [
                        'tiene' => $excedente > 0,
                        'monto' => $this->peso($excedente),
                    ],
                    'participantes_de_baja' => $deBaja,
                    'participantes_en_mora' => $enMora,
                ],

                'participantes' => $detalle,
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /**
     * Cuotas vencidas e impagas por participante, en una sola consulta.
     *
     * Se excluyen los planes cancelados: tras dar de baja un PAT las cuotas que
     * quedan vivas no son deuda exigible.
     */
    private function moraPorParticipante(int $programCourseId)
    {
        return DB::table('installments as i')
            ->join('installment_plans as ip', 'ip.id', '=', 'i.installment_plan_id')
            ->where('ip.program_id', $programCourseId)
            ->where('ip.status', '!=', 'cancelled')
            ->whereNotIn('i.status', ['paid', 'cancelled'])
            ->whereNotNull('i.due_date')
            ->whereDate('i.due_date', '<', now()->toDateString())
            ->groupBy('ip.participant_id')
            ->select([
                'ip.participant_id',
                DB::raw('COUNT(*) as cuotas'),
                DB::raw('DATEDIFF(CURDATE(), MIN(i.due_date)) as dias'),
            ])
            ->get()
            ->keyBy('participant_id');
    }

    /**
     * Clave del participante: el RUT con formato, o el pasaporte/DNI tal cual.
     *
     * Se decide por el tipo de documento declarado y no por el digito
     * verificador: hay pasaportes que por casualidad pasan la validacion de RUT
     * y quedarian presentados como si lo fueran.
     */
    private function clave(Participant $p): string
    {
        $documento = trim((string) $p->document_number);

        if ($documento === '') {
            return 'sin-documento';
        }

        $esRut = strtoupper((string) $p->documentType?->name) === 'RUT';

        return $esRut && RutHelper::validate($documento)
            ? RutHelper::format($documento)
            : $documento;
    }

    private function nombre(Participant $p): string
    {
        $nombre = trim(implode(' ', array_filter([
            $p->first_name,
            $p->second_name,
            $p->first_last_name,
            $p->second_last_name,
        ])));

        return ucwords(mb_strtolower($nombre));
    }

    /** CLP sin centavos: los montos se informan en pesos enteros. */
    private function peso(float $monto): int
    {
        return (int) round($monto);
    }

    private function estado(string $status): string
    {
        return match ($status) {
            'paid' => 'pagado',
            'partial' => 'parcial',
            default => 'pendiente',
        };
    }
}
