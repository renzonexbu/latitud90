<?php

namespace App\Services\Admin\Courses;

use App\Models\PaymentOption;
use App\Models\ProgramCourse;
use App\Services\Admin\ParticipantFinancialService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PaymentOptionsProgramService
{
    /**
     * Mapeo de labels para mostrar nombres amigables
     * Esto permite renombrar opciones sin modificar la base de datos
     */
    protected array $labelOverrides = [
        'subscription_virtualpos' => 'Pago automático con tarjeta (PAT)',
        'full_debit_credit_0' => 'Débito o Crédito (cuotas con interés según banco emisor)',
        'full_debit_credit_3' => 'Crédito hasta 3 cuotas sin interés',
        'full_debit_credit_6' => 'Crédito hasta 6 cuotas sin interés',
        'full_debit_credit_9' => 'Crédito hasta 9 cuotas sin interés',
        'full_debit_credit_12' => 'Crédito hasta 12 cuotas sin interés',
    ];

    /**
     * Códigos de opciones de pago que deben ocultarse en la vista
     */
    protected array $hiddenCodes = [
        'full_webpay_link', // Ocultar "Pago webpay con link de pago"
    ];

    /**
     * Transforma el label de una opción de pago según el programa
     * Para suscripciones, calcula las cuotas disponibles en tiempo real usando final_payment_date
     */
    protected function transformLabel(string $code, string $originalLabel, ?ProgramCourse $program = null): string
    {
        $baseLabel = $this->labelOverrides[$code] ?? $originalLabel;

        // Si es una suscripción y tenemos el programa, calcular cuotas en tiempo real
        if ($code === 'subscription_virtualpos' && $program) {
            $availableMonths = $this->calculateAvailableInstallments(
                $program->final_payment_date,
                $program->subscription_max_months ?? 12
            );

            if ($availableMonths > 0) {
                $baseLabel .= " - hasta {$availableMonths} cuotas";
            } else {
                $baseLabel .= " - no disponible";
            }
        }

        return $baseLabel;
    }

    /**
     * Calcular cuotas disponibles hasta una fecha límite (misma fórmula que el ecommerce)
     */
    protected function calculateAvailableInstallments(?string $endDate, int $maxInstallments = 12): int
    {
        if (!$endDate) {
            return $maxInstallments;
        }

        $today = Carbon::today()->setTimezone('America/Santiago');
        $finalDate = Carbon::parse($endDate)->setTimezone('America/Santiago');
        $diffDays = $today->diffInDays($finalDate, false);

        if ($diffDays < 0) {
            return 0;
        }

        return min($maxInstallments, (int) floor($diffDays / 30) + 1);
    }

    /**
     * Verifica si una opción de pago debe ocultarse
     */
    protected function shouldHide(string $code): bool
    {
        return in_array($code, $this->hiddenCodes);
    }

    /**
     * Limpia el label removiendo sufijos como (Webpay), (Khipu), (VirtualPos)
     */
    protected function cleanLabel(string $label): string
    {
        return trim(preg_replace('/\s*\((Webpay|Khipu|VirtualPos)\)\s*$/i', '', $label));
    }

    /**
     * Obtener programas filtrados por medio de pago
     */
    public function getProgramsByPaymentOption(array $filters): array
    {
        $paymentOptionId = $filters['paymentOptionId'] ?? null;
        $salesExecutiveId = $filters['salesExecutiveId'] ?? null;
        $search = $filters['search'] ?? null;

        // Obtener solo opciones de pago de pasarela online (full_ y subscription_)
        $paymentOptions = PaymentOption::where(function ($q) {
            $q->where('code', 'like', 'full_%')
              ->orWhere('code', 'like', 'subscription_%');
        })->orderBy('label')->get();

        // Obtener ejecutivos comerciales para el filtro
        $salesExecutives = \App\Models\SalesExecutive::select('id', 'name')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        // Query base de programas con sus opciones de pago y participantes
        $query = ProgramCourse::with([
                'program',
                'course.institution',
                'paymentOptions',
                'participantPrograms',
                'salesExecutive'
            ])
            ->where('active', true) // Siempre mostrar solo programas activos
            ->orderBy('code');

        // Filtrar por ejecutivo comercial
        if ($salesExecutiveId) {
            $query->where('sales_executive_id', $salesExecutiveId);
        }

        // Filtrar por búsqueda de código o nombre
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Filtrar por medio de pago específico
        if ($paymentOptionId) {
            $query->whereHas('paymentOptions', function ($q) use ($paymentOptionId) {
                $q->where('payment_options.id', $paymentOptionId)
                  ->where('program_course_payment_option.enabled', true);
            });
        }

        $programs = $query->get();

        // Formatear datos para el frontend
        $formattedPrograms = $programs->map(function ($program) {
            // Solo incluir opciones de pasarela online (full_ y subscription_)
            // Excluir las opciones ocultas y transformar labels
            $enabledPaymentOptions = $program->paymentOptions
                ->filter(fn($opt) => $opt->pivot->enabled && (
                    str_starts_with($opt->code, 'full_') ||
                    str_starts_with($opt->code, 'subscription_')
                ) && !$this->shouldHide($opt->code))
                ->map(fn($opt) => [
                    'id' => $opt->id,
                    'code' => $opt->code,
                    'label' => $this->transformLabel($opt->code, $opt->label, $program),
                    'mode' => $opt->mode,
                ])
                ->values();

            // Cálculo centralizado: precios, abonos, descuentos, NC/RA/CT, etc
            $totals = ParticipantFinancialService::calculateProgramTotals($program->id);
            $participantsCount = $totals['participants_count'];
            $totalAmount = $totals['total_amount'];
            $paidAmount = $totals['total_paid'];
            // El porcentaje ya viene floor-eado desde ParticipantFinancialService
            $paymentPercentage = (int) $totals['payment_percentage'];
            $hasExcess = (bool) ($totals['has_excess'] ?? false);
            $excessAmount = (float) ($totals['excess_amount'] ?? 0);

            return [
                'id' => $program->id,
                'code' => $program->code,
                'name' => $program->name,
                'destination' => $program->program?->destination ?? $program->destination,
                'trip_price' => (float) ($program->trip_price ?? 0),
                'departure_date' => $program->departure_date?->format('Y-m-d'),
                'sales_executive_name' => $program->salesExecutive?->name ?? 'Sin asignar',
                'active' => $program->active,
                'payment_options' => $enabledPaymentOptions,
                'payment_options_count' => $enabledPaymentOptions->count(),
                'participants_count' => $participantsCount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'payment_percentage' => $paymentPercentage,
                'has_excess' => $hasExcess,
                'excess_amount' => $excessAmount,
            ];
        });

        // Calcular resumen
        $summary = [
            'total_programs' => $formattedPrograms->count(),
        ];

        return [
            'programs' => $formattedPrograms->values(),
            'paymentOptions' => $paymentOptions
                ->filter(fn($opt) => !$this->shouldHide($opt->code))
                ->map(fn($opt) => [
                    'id' => $opt->id,
                    'code' => $opt->code,
                    'label' => $this->transformLabel($opt->code, $opt->label, null),
                    'mode' => $opt->mode,
                ])
                ->values(),
            'salesExecutives' => $salesExecutives,
            'summary' => $summary,
        ];
    }

    /**
     * Exportar a Excel los programas con sus medios de pago
     */
    public function exportToExcel(array $filters)
    {
        $data = $this->getProgramsByPaymentOption($filters);
        $programs = $data['programs'];
        $allPaymentOptions = $data['paymentOptions'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Programas por Medio de Pago');

        // Título
        $sheet->setCellValue('A1', 'Programas por Medio de Pago');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '1C4F4A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER]
        ]);

        // Fecha de exportación
        $sheet->setCellValue('A2', 'Fecha de exportación: ' . Carbon::now('America/Santiago')->format('d/m/Y H:i'));
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '666666']]
        ]);

        // Resumen
        $sheet->setCellValue('A3', 'Total programas activos: ' . $data['summary']['total_programs']);
        $sheet->mergeCells('A3:J3');

        // Headers
        $headers = ['Código', 'Nombre del Programa', 'Fecha Inicio', 'Valor Unitario', 'Ejecutivo', 'Participantes', '% Pago', 'Recaudado', 'Total', 'Medios de Pago Activos'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $col++;
        }

        // Estilo de headers
        $sheet->getStyle('A5:J5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1C4F4A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]]
        ]);

        // Datos
        $row = 6;
        foreach ($programs as $program) {
            $paymentLabels = collect($program['payment_options'])
                ->pluck('label')
                ->map(fn($label) => $this->cleanLabel($label))
                ->join(', ');

            $sheet->setCellValue('A' . $row, $program['code'] ?? 'N/A');
            $sheet->setCellValue('B' . $row, $program['name'] ?? 'N/A');
            $sheet->setCellValue('C' . $row, $program['departure_date'] ? Carbon::parse($program['departure_date'])->format('d/m/Y') : 'N/A');
            $sheet->setCellValue('D' . $row, $program['trip_price'] ?? 0);
            $sheet->setCellValue('E' . $row, $program['sales_executive_name'] ?? 'Sin asignar');
            $sheet->setCellValue('F' . $row, $program['participants_count'] ?? 0);
            $sheet->setCellValue('G' . $row, ($program['payment_percentage'] ?? 0) . '%');
            $sheet->setCellValue('H' . $row, $program['paid_amount'] ?? 0);
            $sheet->setCellValue('I' . $row, $program['total_amount'] ?? 0);
            $sheet->setCellValue('J' . $row, $paymentLabels ?: 'Ninguno');

            // Formato de moneda para columnas Valor Unitario, Recaudado y Total
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('$#,##0');
            $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('$#,##0');
            $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('$#,##0');

            // Centrar columnas
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F' . $row . ':G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        // Ajustar anchos de columna
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(10);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(15);
        $sheet->getColumnDimension('J')->setWidth(50);

        // Segunda hoja: Resumen por medio de pago
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Resumen por Medio de Pago');

        $sheet2->setCellValue('A1', 'Resumen de Programas por Medio de Pago');
        $sheet2->mergeCells('A1:D1');
        $sheet2->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '1C4F4A']]
        ]);

        // Headers de resumen
        $sheet2->setCellValue('A3', 'Código');
        $sheet2->setCellValue('B3', 'Medio de Pago');
        $sheet2->setCellValue('C3', 'Programas Activos');
        $sheet2->setCellValue('D3', 'Total Programas');

        $sheet2->getStyle('A3:D3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1C4F4A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Calcular conteo por medio de pago
        $row = 4;
        foreach ($allPaymentOptions as $option) {
            $activeCount = 0;
            $totalCount = 0;

            foreach ($programs as $program) {
                $hasOption = collect($program['payment_options'])->contains('id', $option['id']);
                if ($hasOption) {
                    $totalCount++;
                    if ($program['active']) {
                        $activeCount++;
                    }
                }
            }

            $sheet2->setCellValue('A' . $row, $option['code']);
            $sheet2->setCellValue('B' . $row, $option['label']);
            $sheet2->setCellValue('C' . $row, $activeCount);
            $sheet2->setCellValue('D' . $row, $totalCount);
            $row++;
        }

        $sheet2->getColumnDimension('A')->setWidth(20);
        $sheet2->getColumnDimension('B')->setWidth(35);
        $sheet2->getColumnDimension('C')->setWidth(20);
        $sheet2->getColumnDimension('D')->setWidth(20);

        // Volver a la primera hoja
        $spreadsheet->setActiveSheetIndex(0);

        // Guardar y descargar
        $writer = new Xlsx($spreadsheet);
        $filename = 'programas_medios_pago_' . Carbon::now('America/Santiago')->format('Y-m-d_H-i-s') . '.xlsx';
        $tmpPath = tempnam(sys_get_temp_dir(), 'xlsx_');

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $writer->save($tmpPath);

        return response()->download(
            $tmpPath,
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-cache, must-revalidate',
                'Pragma' => 'public',
            ]
        )->deleteFileAfterSend(true);
    }
}
