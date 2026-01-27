<?php

namespace App\Services\Admin\Courses;

use App\Models\PaymentOption;
use App\Models\ProgramCourse;
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
     * Obtener programas filtrados por medio de pago
     */
    public function getProgramsByPaymentOption(array $filters): array
    {
        $paymentOptionId = $filters['paymentOptionId'] ?? null;
        $active = $filters['active'] ?? null;
        $search = $filters['search'] ?? null;

        // Obtener solo opciones de pago de pasarela online (full_ y subscription_)
        $paymentOptions = PaymentOption::where(function ($q) {
            $q->where('code', 'like', 'full_%')
              ->orWhere('code', 'like', 'subscription_%');
        })->orderBy('label')->get();

        // Query base de programas con sus opciones de pago
        $query = ProgramCourse::with(['program', 'course.institution', 'paymentOptions'])
            ->orderBy('code');

        // Filtrar por estado activo/inactivo
        if ($active === 'active') {
            $query->where('active', true);
        } elseif ($active === 'inactive') {
            $query->where('active', false);
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
            $enabledPaymentOptions = $program->paymentOptions
                ->filter(fn($opt) => $opt->pivot->enabled && (
                    str_starts_with($opt->code, 'full_') ||
                    str_starts_with($opt->code, 'subscription_')
                ))
                ->map(fn($opt) => [
                    'id' => $opt->id,
                    'code' => $opt->code,
                    'label' => $opt->label,
                    'mode' => $opt->mode,
                ])
                ->values();

            return [
                'id' => $program->id,
                'code' => $program->code,
                'name' => $program->name,
                'destination' => $program->program?->destination ?? $program->destination,
                'institution' => $program->course?->institution?->name ?? 'N/A',
                'departure_date' => $program->departure_date?->format('Y-m-d'),
                'active' => $program->active,
                'payment_options' => $enabledPaymentOptions,
                'payment_options_count' => $enabledPaymentOptions->count(),
            ];
        });

        // Calcular resumen
        $summary = [
            'total_programs' => $formattedPrograms->count(),
            'active_programs' => $formattedPrograms->where('active', true)->count(),
            'inactive_programs' => $formattedPrograms->where('active', false)->count(),
        ];

        return [
            'programs' => $formattedPrograms->values(),
            'paymentOptions' => $paymentOptions->map(fn($opt) => [
                'id' => $opt->id,
                'code' => $opt->code,
                'label' => $opt->label,
                'mode' => $opt->mode,
            ]),
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
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '1C4F4A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER]
        ]);

        // Fecha de exportación
        $sheet->setCellValue('A2', 'Fecha de exportación: ' . Carbon::now('America/Santiago')->format('d/m/Y H:i'));
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '666666']]
        ]);

        // Resumen
        $sheet->setCellValue('A3', 'Total programas: ' . $data['summary']['total_programs'] .
            ' | Activos: ' . $data['summary']['active_programs'] .
            ' | Inactivos: ' . $data['summary']['inactive_programs']);
        $sheet->mergeCells('A3:H3');

        // Headers
        $headers = ['Código', 'Nombre', 'Destino', 'Institución', 'Fecha Salida', 'Estado', 'Medios de Pago Activos', 'Cantidad'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $col++;
        }

        // Estilo de headers
        $sheet->getStyle('A5:H5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1C4F4A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]]
        ]);

        // Datos
        $row = 6;
        foreach ($programs as $program) {
            $paymentLabels = collect($program['payment_options'])->pluck('label')->join(', ');

            $sheet->setCellValue('A' . $row, $program['code'] ?? 'N/A');
            $sheet->setCellValue('B' . $row, $program['name'] ?? 'N/A');
            $sheet->setCellValue('C' . $row, $program['destination'] ?? 'N/A');
            $sheet->setCellValue('D' . $row, $program['institution'] ?? 'N/A');
            $sheet->setCellValue('E' . $row, $program['departure_date'] ? Carbon::parse($program['departure_date'])->format('d/m/Y') : 'N/A');
            $sheet->setCellValue('F' . $row, $program['active'] ? 'Activo' : 'Inactivo');
            $sheet->setCellValue('G' . $row, $paymentLabels ?: 'Ninguno');
            $sheet->setCellValue('H' . $row, $program['payment_options_count']);

            // Color de fila según estado
            if (!$program['active']) {
                $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F5F5']]
                ]);
            }

            $row++;
        }

        // Ajustar anchos de columna
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(50);
        $sheet->getColumnDimension('H')->setWidth(12);

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
