<?php

namespace App\Services\Admin\Reports\Executives;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\RichText\Run;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ParticipantPriceHelper;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use App\Traits\ExcelReportHeader;

class ExportService
{
    use ExcelReportHeader;
    /**
     * Verificar si el usuario tiene permiso para ver información del contacto pagador
     */
    private function canViewPayerContact(): bool
    {
        $user = Auth::user();
        if (!$user || !method_exists($user, 'hasPermissionTo')) {
            return false;
        }
        return $user->hasPermissionTo('ver_contacto_pagador');
    }

    public function exportConsolidated(array $filters, string $format = 'xlsx')
    {
        try {
            // Obtener datos usando el servicio SIN PAGINACIÓN
            $consolidatedService = app(\App\Services\Admin\Reports\Executives\ExecutivesConsolidatedService::class);
            $data = $consolidatedService->getConsolidatedForExport($filters);
            \Illuminate\Support\Facades\Log::info('Datos recibidos del servicio de exportación', [
                'data_keys' => array_keys($data),
                'has_items' => isset($data['items']),
                'items_count' => isset($data['items']) ? count($data['items']) : 0,
                'filters' => $filters
            ]);
            // Asegurar que no haya interrupciones del flujo
            $filename = 'apoderados_consolidado_de_pagos_' . Carbon::now('America/Santiago')->format('Y-m-d_H-i-s') . '.xlsx';
            
            // Crear nuevo spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Header con logo, título y fecha de exportación
            $startRow = $this->addReportHeader($sheet, 'Consolidado de Pagos');

            // Período (si aplica)
            $dateFrom = $filters['dateFrom'] ?? '';
            $dateTo = $filters['dateTo'] ?? '';
            if ($dateFrom && $dateTo) {
                $dateRange = 'Período: ' . Carbon::parse($dateFrom)->format('d/m/Y') . ' - ' . Carbon::parse($dateTo)->format('d/m/Y');
                $sheet->setCellValue('C3', $dateRange);
                $sheet->getStyle('C3')->applyFromArray([
                    'font' => ['size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
            }

            // Cabecera de la tabla
            $headers = [
                "A{$startRow}" => 'Nro. Programa',
                "B{$startRow}" => 'N° de Identificación',
                "C{$startRow}" => 'Nombres y Apellidos',
                "D{$startRow}" => 'Estado',
                "E{$startRow}" => 'Pago y/o Dev.',
                "F{$startRow}" => 'Nro. Documento',
                "G{$startRow}" => 'Tipo de Documento',
                "H{$startRow}" => 'Forma Pago',
                "I{$startRow}" => 'Fecha de Pago',
                "J{$startRow}" => 'Contacto Pagador',
                "K{$startRow}" => 'Liberado',
                "L{$startRow}" => 'Precio'
            ];
            $headerRange = "A{$startRow}:L{$startRow}";
            $lastColumn = 'L';

            foreach ($headers as $cell => $header) {
                $sheet->setCellValue($cell, $header);
            }

            // Aplicar estilos a la cabecera
            $this->styleHeader($sheet, $headerRange);
            
            // Obtener los items del servicio asegurando estructura de arreglo
            $resolvedItems = [];
            if (isset($data['items'])) {
                if (is_array($data['items'])) {
                    $resolvedItems = $data['items'];
                } elseif ($data['items'] instanceof \Illuminate\Support\Collection) {
                    $resolvedItems = $data['items']->toArray();
                }
            }

            \Illuminate\Support\Facades\Log::info('Datos para exportación Excel', [
                'items_count' => count($resolvedItems),
                'filters' => $filters
            ]);

            // Datos dinámicos
            $row = $startRow + 1;
            foreach ($resolvedItems as $item) {
                $sheet->setCellValue('A' . $row, $item['program_number'] ?? 'N/A');
                $sheet->setCellValue('B' . $row, isset($item['identification_number']) ? $this->formatRut($item['identification_number']) : 'N/A');
                $sheet->setCellValue('C' . $row, $item['full_name'] ?? 'N/A');
                $sheet->setCellValue('D' . $row, $item['status'] ?? 'N/A');
                $sheet->setCellValue('E' . $row, $item['payment_or_refund'] ?? 0);
                $sheet->setCellValue('F' . $row, $item['document_number'] ?? 'N/A');
                $sheet->setCellValue('G' . $row, $item['document_type'] ?? 'N/A');
                $sheet->setCellValue('H' . $row, $item['payment_form'] ?? 'N/A');
                $sheet->setCellValue('I' . $row, $item['payment_date'] ?? 'N/A');
                $sheet->setCellValue('J' . $row, $item['payer_contact'] ?? 'N/A');
                $sheet->setCellValue('K' . $row, $item['liberated'] ?? 0);
                $sheet->setCellValue('L' . $row, $item['price'] ?? 0);

                // Aplicar formato de moneda a las columnas numéricas
                $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('K' . $row)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode('#,##0');

                $row++;
            }

            // Ajustar ancho de columnas automáticamente
            foreach (range('A', $lastColumn) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Escribir a archivo temporal y descargar
            $writer = new Xlsx($spreadsheet);
            $tmpPath = tempnam(sys_get_temp_dir(), 'xlsx_');
            if ($tmpPath === false) {
                \Illuminate\Support\Facades\Log::error('No se pudo crear archivo temporal para Excel');
                return $this->exportConsolidatedCsv($filters);
            }

            // Asegurar buffers limpios antes de escribir
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            $writer->save($tmpPath);
            \Illuminate\Support\Facades\Log::info('Excel generado correctamente', [
                'path' => $tmpPath,
            ]);

            return response()->download(
                $tmpPath,
                $filename,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Cache-Control' => 'no-cache, must-revalidate',
                    'Pragma' => 'public',
                ]
            )->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            // Log del error para debugging
            \Illuminate\Support\Facades\Log::error('Error en exportación Excel: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Fallback a CSV si hay error
            return $this->exportConsolidatedCsv($filters);
        }
    }

    public function exportPartialAccount(array $filters, string $format = 'xlsx')
    {
        try {
            // Validar: solo rango de fechas requerido
            $programId = $filters['programId'] ?? null;
            $programCode = $filters['programCode'] ?? null;
            $dateFrom = $filters['dateFrom'] ?? null;
            $dateTo = $filters['dateTo'] ?? null;

            // Si no hay programa => crear un ZIP con un Excel por cada programa
            if (!$programId && !$programCode) {
                $programCourses = \App\Models\ProgramCourse::with(['course.institution', 'program', 'salesExecutive'])
                    ->where('active', true)
                    ->orderBy('code')
                    ->get();

                if ($programCourses->isEmpty()) {
                    return response()->json(['error' => 'No hay programas disponibles para exportar'], 400);
                }

                $tempFiles = [];
                foreach ($programCourses as $prog) {
                    $tempFiles[] = [
                        'path' => $this->generatePartialAccountXlsx($prog, $filters),
                        'name' => 'apoderados_estado_cuenta_parcial_' . ($prog->code ?: 'programa_' . $prog->id) . '.xlsx',
                    ];
                }

                $zipPath = tempnam(sys_get_temp_dir(), 'zip_');
                $zipName = 'apoderados_estado_cuenta_parcial_' . Carbon::now('America/Santiago')->format('Y-m-d_H-i-s') . '.zip';
                $zip = new \ZipArchive();
                $zip->open($zipPath, \ZipArchive::OVERWRITE);
                foreach ($tempFiles as $file) {
                    $zip->addFile($file['path'], $file['name']);
                }
                $zip->close();

                while (ob_get_level() > 0) { ob_end_clean(); }
                foreach ($tempFiles as $file) { @unlink($file['path']); }

                return response()->download(
                    $zipPath,
                    $zipName,
                    [
                        'Content-Type' => 'application/zip',
                        'Cache-Control' => 'no-cache, must-revalidate',
                        'Pragma' => 'public',
                    ]
                )->deleteFileAfterSend(true);
            }

            // Exportar solo el programa seleccionado
            /** @var \App\Models\ProgramCourse|null $programCourse */
            $programCourse = null;
            if ($programId) {
                $programCourse = \App\Models\ProgramCourse::with(['course.institution', 'program', 'salesExecutive'])->find($programId);
            } elseif ($programCode) {
                $programCourse = \App\Models\ProgramCourse::with(['course.institution', 'program', 'salesExecutive'])->where('code', $programCode)->first();
            }

            if (!$programCourse) {
                return response()->json(['error' => 'Programa no encontrado'], 404);
            }

            $tmpPath = $this->generatePartialAccountXlsx($programCourse, $filters);
            $filename = 'apoderados_estado_cuenta_parcial_' . ($programCourse->code ?: 'programa_' . $programCourse->id) . '_' . Carbon::now('America/Santiago')->format('Y-m-d_H-i-s') . '.xlsx';
            while (ob_get_level() > 0) { ob_end_clean(); }
            return response()->download(
                $tmpPath,
                $filename,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Cache-Control' => 'no-cache, must-revalidate',
                    'Pragma' => 'public',
                ]
            )->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Export partial account error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $filename = 'apoderados_executives_partial_account_' . Carbon::now('America/Santiago')->format('Y-m-d_H-i-s') . '.csv';
            return $this->streamCsv($filename, [[
                'Error', 'No se pudo generar el archivo Excel'
            ]]);
        }
    }

    private function generatePartialAccountXlsx(\App\Models\ProgramCourse $programCourse, array $filters): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Estado de Cuenta Parcial');

        // Configurar página: Tamaño Carta y ajustar a 1 página de ancho
        $pageSetup = $sheet->getPageSetup();
        $pageSetup->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_LETTER);
        $pageSetup->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $pageSetup->setFitToWidth(1);
        $pageSetup->setFitToHeight(0);
        $pageSetup->setFitToPage(true);
        $sheet->getPageMargins()->setTop(0.5)->setRight(0.3)->setLeft(0.3)->setBottom(0.5);

        // Fondo blanco y bloque del logo
        $sheet->getStyle('A1:C6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
        $sheet->mergeCells('A1:C6');
        foreach (['A','B','C'] as $col) { $sheet->getColumnDimension($col)->setWidth(12); }
        for ($r = 1; $r <= 6; $r++) { $sheet->getRowDimension($r)->setRowHeight(20); }

        // Logo
        $logoPath = base_path('resources/images/logo-color.png');
        if (file_exists($logoPath)) {
            $drawing = new Drawing();
            $drawing->setPath($logoPath);
            $drawing->setWorksheet($sheet);
            $drawing->setCoordinates('A1');
            $drawing->setHeight(90);
            $drawing->setOffsetX(300);
            $drawing->setOffsetY(5);
        }

        // Título
        $sheet->setCellValue('D1', 'Estado de Cuenta Parcial');
        $sheet->getStyle('D1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 24],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER]
        ]);

        // Encabezado info
        $program = $programCourse->program;
        $collegeName = optional($programCourse->course->institution)->name ?: 'N/A';
        $programLine = trim(($programCourse->destination ? $programCourse->destination : 'Programa') . (isset($program->year) ? ', ' . $program->year : ''));
        $departureDateStr = '';
        if ($programCourse->departure_date) {
            $departureDateStr = Carbon::parse($programCourse->departure_date, 'America/Santiago')
                ->locale('es')
                ->translatedFormat('d \d\e F, Y');
        }
        $executive = optional($programCourse->salesExecutive)->name ?: 'N/A';

        // Etiqueta en negrita y valor normal (14px)
        $rt2 = new RichText();
        $rt2->createTextRun('Colegio:')->getFont()->setBold(true)->setSize(14);
        $rt2->createTextRun(' ' . $collegeName)->getFont()->setBold(false)->setSize(14);
        $sheet->setCellValue('D2', $rt2);

        $rt3 = new RichText();
        $rt3->createTextRun('Programa:')->getFont()->setBold(true)->setSize(14);
        $rt3->createTextRun(' ' . $programLine)->getFont()->setBold(false)->setSize(14);
        $sheet->setCellValue('D3', $rt3);

        $rt4 = new RichText();
        $rt4->createTextRun('Fecha Programada:')->getFont()->setBold(true)->setSize(14);
        $rt4->createTextRun(' ' . ($departureDateStr ?: 'N/A'))
            ->getFont()->setBold(false)->setSize(14);
        $sheet->setCellValue('D4', $rt4);

        $rt5 = new RichText();
        $rt5->createTextRun('Ejecutivo Encargado:')->getFont()->setBold(true)->setSize(14);
        $rt5->createTextRun(' ' . $executive)->getFont()->setBold(false)->setSize(14);
        $sheet->setCellValue('D5', $rt5);
        $sheet->getStyle('D2:D5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        // Nota y fecha
        $note = "Los montos reflejados a continuación son saldos parciales previos al cierre administrativo del programa.\nEste reporte se genera con fines logísticos.";
        $sheet->setCellValue('A7', $note);
        $sheet->mergeCells('A7:I7');
        $sheet->getStyle('A7')->applyFromArray([
            'font' => ['italic' => true, 'bold' => false, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER]
        ]);
        $sheet->getStyle('A7')->getAlignment()->setWrapText(true);
        // Excel no autoajusta altura en celdas fusionadas; forzamos altura adecuada para dos líneas
        $sheet->getRowDimension(7)->setRowHeight(40);
        // Fecha en la misma columna que "Por pagar" (I) y 14px
        $sheet->setCellValue('I8', Carbon::now('America/Santiago')->format('d/m/Y'));
        $sheet->getStyle('I8')->getFont()->setSize(14)->setBold(false);

        // Cabecera de tabla en A10
            $headers = [
            'A10' => 'Alumno',
            'B10' => 'Estado',
            'C10' => 'Precio',
            'D10' => 'Abono',
            'E10' => "Cuotas\nPagadas",
            'F10' => "Cuotas\nVencidas",
            'G10' => "Forma de\nPago",
            'H10' => 'Aporte/Beca',
            'I10' => 'Monto Liberado',
            'J10' => 'Saldo',
        ];
        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }
        $this->styleHeader($sheet, 'A10:J10');
        $sheet->setAutoFilter('A10:J10');
        
        // Configurar altura de fila para headers con salto de línea
        $sheet->getRowDimension(10)->setRowHeight(40);
        
        // Anchos fijos para que la tabla quepa en una página Carta
        $sheet->getColumnDimension('A')->setWidth(32); // Alumno
        $sheet->getColumnDimension('B')->setWidth(10); // Estado
        $sheet->getColumnDimension('C')->setWidth(12); // Precio
        $sheet->getColumnDimension('D')->setWidth(12); // Abono
        $sheet->getColumnDimension('E')->setWidth(14); // Cuotas Pagadas
        $sheet->getColumnDimension('F')->setWidth(14); // Cuotas Vencidas
        $sheet->getColumnDimension('G')->setWidth(16); // Forma de Pago
        $sheet->getColumnDimension('H')->setWidth(14); // Aporte/Beca
        $sheet->getColumnDimension('I')->setWidth(14); // Monto Liberado
        $sheet->getColumnDimension('J')->setWidth(12); // Saldo

        // ========================
        // Datos dinámicos por participante (A11 en adelante)
        // ========================
        $row = 11; // primera fila de datos

        $totals = [
            'price' => 0.0,
            'abono' => 0.0,
            'scholarship' => 0.0,
            'released' => 0.0,
            'por_pagar' => 0.0,
        ];

        $dateFrom = $filters['dateFrom'] ?? null;
        $dateTo = $filters['dateTo'] ?? null;
        $dateFromC = $dateFrom ? Carbon::parse($dateFrom, 'America/Santiago')->startOfDay() : Carbon::now('America/Santiago')->startOfMonth();
        $dateToC = $dateTo ? Carbon::parse($dateTo, 'America/Santiago')->endOfDay() : Carbon::now('America/Santiago')->endOfDay();

        // Cargar participantes del programa con órdenes, plan de cuotas y descuentos
        $participantProgramsQuery = \App\Models\ParticipantProgram::with([
                'participant',
                'orders.installmentPlan.installments',
                'orders.payments',
                'discounts'
            ])
            ->where('program_id', $programCourse->id);

        // Filtrar por documento si se especifica
        $documentSearch = $filters['documentSearch'] ?? null;
        if (!empty($documentSearch)) {
            // Normalizar documento (remover puntos, guiones y espacios)
            $normalizedDoc = preg_replace('/[.\-\s]/', '', $documentSearch);
            $participantProgramsQuery->whereHas('participant', function($q) use ($normalizedDoc) {
                $q->whereRaw("REPLACE(REPLACE(document_number, '.', ''), '-', '') LIKE ?", ["%{$normalizedDoc}%"]);
            });
        }

        // Filtrar por estado activo/inactivo
        if (!empty($filters['status'])) {
            $isActive = $filters['status'] === 'active';
            $participantProgramsQuery->where('participant_program.is_active', $isActive);
        }

        // Ordenar participantes alfabéticamente por apellidos y nombres
        $participantPrograms = $participantProgramsQuery
            ->join('participants as part_sort', 'participant_program.participant_id', '=', 'part_sort.id')
            ->orderBy('part_sort.first_last_name', 'asc')
            ->orderBy('part_sort.second_last_name', 'asc')
            ->orderBy('part_sort.first_name', 'asc')
            ->orderBy('part_sort.second_name', 'asc')
            ->select('participant_program.*')
            ->get();

        foreach ($participantPrograms as $pp) {
            // Construir nombre en formato: "Apellido1 Apellido2 Nombre1 Nombre2"
            $participantName = 'N/A';
            if ($pp->participant) {
                $participantName = trim(implode(' ', array_filter([
                    $pp->participant->first_last_name,
                    $pp->participant->second_last_name,
                    $pp->participant->first_name,
                    $pp->participant->second_name
                ]))) ?: 'N/A';
            }
            // Capital Case
            $participantName = ucwords(strtolower($participantName));
            // ============================================================
            // PRECIO: Usar ParticipantPriceHelper (misma lógica que vista Participantes)
            // ============================================================
            $participant = $pp->participant;
            $totalDue = 0;
            $basePrice = (float) ($pp->individual_price ?: ($programCourse->trip_price ?? 0));
            if ($participant && $programCourse) {
                $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
                $totalDue = $priceData['final_price'];
                $basePrice = $priceData['base_price'];
            } else {
                $totalDue = $basePrice;
            }

            // Obtener órdenes del participante para este programa
            $orders = \App\Models\Order::with(['installmentPlan.installments'])
                ->where('participant_id', $pp->participant_id)
                ->where('program_id', $programCourse->id)
                ->get();
            $orderIds = $orders->pluck('id')->all();

            // ============================================================
            // ABONO: Misma lógica que vista Participantes (GetParticipantsService)
            // normalPayments (excl. subscription source, excl. aportes) + subscriptionPayments
            // ============================================================
            $paidInstallments = 0;
            $overdueInstallments = 0;
            $totalInstallments = 0;
            $normalPayments = 0.0;
            $aporteAmount = 0.0;

            if (!empty($orderIds)) {
                // Obtener el plan de cuotas
                $installmentPlan = \App\Models\InstallmentPlan::whereIn('order_id', $orderIds)->first();

                if ($installmentPlan) {
                    $totalInstallments = $installmentPlan->installments()->count();
                    $paidInstallments = $installmentPlan->installments()->where('status', 'paid')->count();
                    $overdueInstallments = $installmentPlan->installments()
                        ->where(function($query) {
                            $query->where('status', 'overdue')
                                  ->orWhere(function($q) {
                                      $q->where('status', 'pending')
                                        ->where('due_date', '<', now());
                                  });
                        })->count();
                }

                // 1. Pagos normales (excluir suscripción Y excluir aportes)
                $normalPayments = (float) Payment::whereIn('order_id', $orderIds)
                    ->whereIn('status', ['approved', 'completed'])
                    ->where(function($query) {
                        $query->whereNull('payment_source')
                              ->orWhere('payment_source', '!=', 'subscription');
                    })
                    ->where(function($q) {
                        $q->whereNull('payment_option_id')
                          ->orWhereHas('paymentOption', function($sq) {
                              $sq->where('report_code', '!=', 'AP');
                          });
                    })
                    ->sum('amount');

                // Aportes (pagos con report_code 'AP', excluyendo subscription source)
                $aporteAmount = (float) Payment::whereIn('order_id', $orderIds)
                    ->whereIn('status', ['approved', 'completed'])
                    ->where(function($query) {
                        $query->whereNull('payment_source')
                              ->orWhere('payment_source', '!=', 'subscription');
                    })
                    ->whereHas('paymentOption', function($q) {
                        $q->where('report_code', 'AP');
                    })
                    ->sum('amount');
            }

            // 2. Cuotas de suscripción pagadas (installments)
            $subscriptionPayments = (float) DB::table('installments')
                ->join('installment_plans', 'installments.installment_plan_id', '=', 'installment_plans.id')
                ->where('installment_plans.participant_id', $pp->participant_id)
                ->where('installment_plans.program_id', $programCourse->id)
                ->where('installments.status', 'paid')
                ->sum('installments.amount');

            // Abono = pagos normales (sin aportes) + cuotas de suscripción
            $abono = $normalPayments + $subscriptionPayments;

            // Total pagado real (incluyendo aportes) - para calcular saldo
            $totalPaid = $abono + $aporteAmount;

            // Forma de pago: obtener del último pago con status approved/completed
            $paymentMethod = 'N/A';
            if (!empty($orderIds)) {
                $lastPayment = \App\Models\Payment::whereIn('order_id', $orderIds)
                    ->whereIn('status', ['approved', 'completed'])
                    ->where('amount', '>', 0)
                    ->with('paymentOption')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($lastPayment && $lastPayment->paymentOption) {
                    $paymentMethod = $lastPayment->paymentOption->report_code ?: 'N/A';
                } elseif ($lastPayment && $lastPayment->paymentGateway) {
                    $paymentMethod = $lastPayment->paymentGateway->name ?? 'N/A';
                }
            }

            // Calcular descuentos desglosados para las columnas de visualización
            $scholarship = 0.0;
            $released = 0.0;
            $simpleDiscounts = 0.0;
            foreach ($pp->discounts as $disc) {
                $discAmount = 0.0;
                if ($disc->percent && $disc->percent > 0) {
                    $discAmount += ($basePrice * $disc->percent) / 100;
                }
                if ($disc->amount && $disc->amount > 0) {
                    $discAmount += (float) $disc->amount;
                }
                if ($disc->discount_type === 'scholarship') {
                    $scholarship += $discAmount;
                } elseif ($disc->discount_type === 'released') {
                    $released += $discAmount;
                } else {
                    $simpleDiscounts += $discAmount;
                }
            }

            // PRECIO mostrado = base - descuentos simples (sin incluir beca ni liberado)
            $price = $basePrice - $simpleDiscounts;

            // ============================================================
            // SALDO: Igual que vista Participantes: max(0, total_due - total_paid)
            // Donde total_due ya incluye TODOS los descuentos
            // ============================================================
            $porPagar = max(0, round($totalDue - $totalPaid, 2));

            // Columna Aporte/Beca incluye aportes de pagos
            $scholarship += $aporteAmount;

            // Ajuste para participantes DE BAJA:
            // - Por Pagar siempre es $0
            // - Precio = lo que abonaron (si no pagaron todo) o $0 (si pagaron todo)
            $displayPrice = $price;
            if (!$pp->is_active) {
                $porPagar = 0;
                // Si pagaron todo el monto del programa, precio = 0
                // Si no pagaron todo, precio = lo que abonaron
                if ($abono >= $price) {
                    $displayPrice = 0;
                } else {
                    $displayPrice = $abono;
                }
            }

            // Estado del participante en el programa
            $participantStatus = $pp->is_active ? 'Activo' : 'De Baja';

            // Escribir fila
            $sheet->setCellValue('A' . $row, $participantName);
            $sheet->setCellValue('B' . $row, $participantStatus);
            $sheet->setCellValue('C' . $row, $displayPrice);
            $sheet->setCellValue('D' . $row, $abono);
            $sheet->setCellValue('E' . $row, $paidInstallments . '/' . ($totalInstallments ?: 0));
            $sheet->setCellValue('F' . $row, $overdueInstallments);
            $sheet->setCellValue('G' . $row, $paymentMethod);
            $sheet->setCellValue('H' . $row, $scholarship);
            $sheet->setCellValue('I' . $row, $released);
            $sheet->setCellValue('J' . $row, $porPagar);

            // Aplicar formato de moneda a las columnas numéricas
            foreach (['C','D','H','I','J'] as $col) {
                $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0');
            }

            // Centrar contenido en columnas B, E, F, G (Estado, Cuotas Pagadas, Cuotas Vencidas, Forma de Pago)
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row . ':G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $totals['price'] += $displayPrice;
            $totals['abono'] += $abono;
            $totals['scholarship'] += $scholarship;
            $totals['released'] += $released;
            $totals['por_pagar'] += $porPagar;

            $row++;
        }

        // Fila total general
        $sheet->setCellValue('A' . $row, 'Total General');
        $sheet->setCellValue('C' . $row, $totals['price']);
        $sheet->setCellValue('D' . $row, $totals['abono']);
        $sheet->setCellValue('H' . $row, $totals['scholarship']);
        $sheet->setCellValue('I' . $row, $totals['released']);
        $sheet->setCellValue('J' . $row, '(' . number_format($totals['por_pagar'], 0, ',', '.') . ')');
        foreach (['C','D','H','I'] as $col) {
            $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0');
        }
        $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1C4F4A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER]
        ]);

        // Guardar
        $writer = new Xlsx($spreadsheet);
        $tmpPath = tempnam(sys_get_temp_dir(), 'xlsx_');
        $writer->save($tmpPath);
        return $tmpPath;
    }

    private function exportConsolidatedCsv(array $filters)
    {
        // Obtener datos usando el servicio SIN PAGINACIÓN
        $consolidatedService = app(ExecutivesConsolidatedService::class);
        $data = $consolidatedService->getConsolidatedForExport($filters);

        $filename = 'apoderados_consolidado_de_pagos_' . Carbon::now('America/Santiago')->format('Y-m-d_H-i-s') . '.csv';

        $headerRow = [
            'Nro. Programa',
            'N° de Identificación',
            'Nombres y Apellidos',
            'Estado',
            'Pago y/o Dev.',
            'Nro. Documento',
            'Tipo de Documento',
            'Forma Pago',
            'Fecha de Pago',
            'Contacto Pagador',
            'Liberado',
            'Precio'
        ];

        $exportDate = 'Fecha de exportación: ' . Carbon::now('America/Santiago')->format('d/m/Y');
        $rows = [
            ['Consolidado de Pagos'],
            ['Período: ' . ($filters['dateFrom'] ?? '') . ' - ' . ($filters['dateTo'] ?? ''), '', '', '', '', '', '', '', $exportDate],
            [''],
            $headerRow
        ];

        // Usar los items del método de exportación
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $rows[] = [
                    $item['program_number'] ?? 'N/A',
                    $item['identification_number'] ?? 'N/A',
                    $item['full_name'] ?? 'N/A',
                    $item['status'] ?? 'N/A',
                    number_format($item['payment_or_refund'] ?? 0, 0, ',', '.'),
                    $item['document_number'] ?? 'N/A',
                    $item['document_type'] ?? 'N/A',
                    $item['payment_form'] ?? 'N/A',
                    $item['payment_date'] ?? 'N/A',
                    $item['payer_contact'] ?? 'N/A',
                    number_format($item['liberated'] ?? 0, 0, ',', '.'),
                    number_format($item['price'] ?? 0, 0, ',', '.')
                ];
            }
        }

        return $this->streamCsv($filename, $rows);
    }

    private function styleTitle($sheet, $cell)
    {
        $sheet->getStyle($cell)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '1C4F4A']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
    }

    private function styleSubtitle($sheet, $cell)
    {
        $sheet->getStyle($cell)->applyFromArray([
            'font' => [
                'bold' => false,
                'size' => 12,
                'color' => ['rgb' => '666666']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
    }

    private function styleHeader($sheet, $range)
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1C4F4A']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'FFFFFF']
                ]
            ]
        ]);
    }

    private function streamCsv(string $filename, array $rows)
    {
        $response = new StreamedResponse(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row, ';');
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
        $response->headers->set('Pragma', 'public');

        return $response;
    }

    private function formatRut($rut): string
    {
        if (!$rut) {
            return 'N/A';
        }
        $rutStr = (string)$rut;
        if (str_contains($rutStr, '.')) {
            return $rutStr;
        }
        $clean = str_replace(['.', '-'], '', $rutStr);
        if (preg_match('/^\d{7,8}[\dKk]$/', $clean) === 1) {
            $dv = strtoupper(substr($clean, -1));
            $num = substr($clean, 0, -1);
            $numFmt = number_format((int)$num, 0, '', '.');
            return $numFmt . '-' . $dv;
        }
        return $rutStr;
    }

    /**
     * Mapea códigos de forma de pago a nombres descriptivos
     */
    private function mapPaymentMethodCode(string $code): string
    {
        $mapping = [
            'KP' => 'Khipu',
            'BX' => 'Tarjeta Presencial',
            'TE' => 'Transferencia',
            'VP' => 'Pago en VirtualPos',
            'VPI' => 'Pago Internacional',
        ];

        return $mapping[$code] ?? $code;
    }
}


