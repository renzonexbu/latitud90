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

class ExportService
{
    /**
     * Verificar si el usuario actual es super_admin
     */
    private function isSuperAdmin(): bool
    {
        $user = Auth::user();
        if (!$user || !method_exists($user, 'hasRole')) {
            return false;
        }
        return $user->hasRole('super_admin');
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
            
            // Verificar si es super_admin para las columnas de contacto pagador
            $isAdmin = $this->isSuperAdmin();
            $lastColumnForMerge = $isAdmin ? 'N' : 'L';

            // Línea 1: Título
            $sheet->setCellValue('A1', 'Consolidado de Pagos');
            $sheet->mergeCells('A1:' . $lastColumnForMerge . '1');
            $this->styleTitle($sheet, 'A1');

            // Línea 2: Rango de fechas
            $dateFrom = $filters['dateFrom'] ?? '';
            $dateTo = $filters['dateTo'] ?? '';
            $dateRange = '';
            if ($dateFrom && $dateTo) {
                $dateRange = 'Período: ' . Carbon::parse($dateFrom)->format('d/m/Y') . ' - ' . Carbon::parse($dateTo)->format('d/m/Y');
            }
            $sheet->setCellValue('A2', $dateRange);
            $sheet->mergeCells('A2:' . $lastColumnForMerge . '2');
            $this->styleSubtitle($sheet, 'A2');
            
            // Línea 3: Vacía (espacio)
            $sheet->setCellValue('A3', '');
            
            // Línea 4: Cabecera de la tabla (columnas de contacto pagador solo para super_admin)
            if ($isAdmin) {
                $headers = [
                    'A4' => 'Nro. Programa',
                    'B4' => 'N° de Identificación',
                    'C4' => 'Nombres y Apellidos',
                    'D4' => 'Estado',
                    'E4' => 'Pago y/o Dev.',
                    'F4' => 'Nro. Documento',
                    'G4' => 'Tipo de Documento',
                    'H4' => 'Forma Pago',
                    'I4' => 'Fecha de Pago',
                    'J4' => 'Contacto Pagador',
                    'K4' => 'Email Contacto Pagador',
                    'L4' => 'Aporte o Beca',
                    'M4' => 'Liberado',
                    'N4' => 'Precio'
                ];
                $headerRange = 'A4:N4';
                $lastColumn = 'N';
            } else {
                // Sin columnas de contacto pagador
                $headers = [
                    'A4' => 'Nro. Programa',
                    'B4' => 'N° de Identificación',
                    'C4' => 'Nombres y Apellidos',
                    'D4' => 'Estado',
                    'E4' => 'Pago y/o Dev.',
                    'F4' => 'Nro. Documento',
                    'G4' => 'Tipo de Documento',
                    'H4' => 'Forma Pago',
                    'I4' => 'Fecha de Pago',
                    'J4' => 'Aporte o Beca',
                    'K4' => 'Liberado',
                    'L4' => 'Precio'
                ];
                $headerRange = 'A4:L4';
                $lastColumn = 'L';
            }

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

            // Datos dinámicos desde línea 5
            $row = 5;
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

                if ($isAdmin) {
                    // Incluir columnas de contacto pagador solo para super_admin
                    $sheet->setCellValue('J' . $row, $item['payer_contact'] ?? 'N/A');
                    $sheet->setCellValue('K' . $row, $item['payer_email'] ?? 'N/A');
                    $sheet->setCellValue('L' . $row, $item['scholarship_or_grant'] ?? 0);
                    $sheet->setCellValue('M' . $row, $item['liberated'] ?? 0);
                    $sheet->setCellValue('N' . $row, $item['price'] ?? 0);

                    // Aplicar formato de moneda a las columnas numéricas
                    $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle('M' . $row)->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle('N' . $row)->getNumberFormat()->setFormatCode('#,##0');
                } else {
                    // Sin columnas de contacto pagador
                    $sheet->setCellValue('J' . $row, $item['scholarship_or_grant'] ?? 0);
                    $sheet->setCellValue('K' . $row, $item['liberated'] ?? 0);
                    $sheet->setCellValue('L' . $row, $item['price'] ?? 0);

                    // Aplicar formato de moneda a las columnas numéricas
                    $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle('K' . $row)->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode('#,##0');
                }

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

            if (!$dateFrom || !$dateTo) {
                return response()->json([
                    'error' => 'Debe seleccionar rango de fechas (inicio y fin) para exportar.'
                ], 400);
            }

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
        $programLine = trim(($program->destination ? $program->destination : 'Programa') . (isset($program->year) ? ', ' . $program->year : ''));
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
            'B10' => 'Precio',
            'C10' => 'Abono',
            'D10' => "Cuotas\nPagadas",
            'E10' => "Cuotas\nVencidas",
            'F10' => "Forma de\nPago",
            'G10' => 'Aporte/Beca',
            'H10' => 'Monto Liberado',
            'I10' => 'Por pagar',
        ];
        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }
        $this->styleHeader($sheet, 'A10:I10');
        $sheet->setAutoFilter('A10:I10');
        
        // Configurar altura de fila para headers con salto de línea
        $sheet->getRowDimension(10)->setRowHeight(40);
        
        // Anchos fijos para que la tabla quepa en una página Carta
        $sheet->getColumnDimension('A')->setWidth(35); // Alumno
        $sheet->getColumnDimension('B')->setWidth(12); // Precio
        $sheet->getColumnDimension('C')->setWidth(12); // Abono
        $sheet->getColumnDimension('D')->setWidth(16); // Cuotas Pagadas
        $sheet->getColumnDimension('E')->setWidth(16); // Cuotas Vencidas
        $sheet->getColumnDimension('F')->setWidth(18); // Forma de Pago
        $sheet->getColumnDimension('G')->setWidth(14); // Aporte/Beca
        $sheet->getColumnDimension('H')->setWidth(16); // Monto Liberado
        $sheet->getColumnDimension('I')->setWidth(14); // Por pagar

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
        $participantPrograms = \App\Models\ParticipantProgram::with([
                'participant',
                'orders.installmentPlan.installments',
                'orders.payments',
                'discounts'
            ])
            ->where('program_id', $programCourse->id)
            ->get();

        foreach ($participantPrograms as $pp) {
            $participantName = $pp->participant ? $pp->participant->full_name : 'N/A';
            // Capital Case
            $participantName = ucwords(strtolower($participantName));
            $price = (float) ($pp->individual_price ?: ($programCourse->trip_price ?? 0));

            // Obtener órdenes del participante para este programa
            $orders = \App\Models\Order::with(['installmentPlan.installments'])
                ->where('participant_id', $pp->participant_id)
                ->where('program_id', $programCourse->id)
                ->get();
            $orderIds = $orders->pluck('id')->all();

            // Sumatoria de pagos (abono) ACUMULADOS - SOLO pagos completed (sin filtro de fecha)
            $abono = 0.0;
            if (!empty($orderIds)) {
                $abono = (float) \App\Models\Payment::whereIn('order_id', $orderIds)
                    ->where('amount', '>', 0)
                    ->where('status', 'completed')
                    ->sum('amount');
            }

            // Cuotas: totales y pagadas desde plan; vencidas por due_date < hoy y no pagadas
            // SOLO considerar si existen pagos completed
            $totalInstallments = 0;
            $paidInstallments = 0;
            $overdueInstallments = 0;
            
            if (!empty($orderIds)) {
                $hasCompleted = \App\Models\Payment::whereIn('order_id', $orderIds)
                    ->where('status', 'completed')
                    ->exists();
                
                foreach ($orders as $order) {
                    $plan = $order->installmentPlan;
                    if ($plan) {
                        $totalInstallments += (int) ($plan->total_installments ?? 0);
                        if ($hasCompleted) {
                            foreach ($plan->installments as $inst) {
                                if ($inst->status === 'paid') {
                                    $paidInstallments++;
                                } elseif ($inst->status !== 'paid' && $inst->due_date && $inst->due_date->lt(now('America/Santiago'))) {
                                    $overdueInstallments++;
                                }
                            }
                        }
                    } elseif ($order->total_installments) {
                        $totalInstallments += (int) $order->total_installments;
                    }
                }
            }

            // Forma de pago: último pago COMPLETED y su payment_gateway.name
            $paymentMethod = 'N/A';
            if (!empty($orderIds)) {
                $lastPayment = \App\Models\Payment::with('paymentGateway')
                    ->whereIn('order_id', $orderIds)
                    ->where('status', 'completed')
                    ->where('amount', '>', 0)
                    ->orderByRaw('COALESCE(transaction_date, created_at) DESC')
                    ->first();
                if ($lastPayment && $lastPayment->paymentGateway) {
                    $paymentMethod = $lastPayment->paymentGateway->name ?? 'N/A';
                }
            }

            // Descuentos: scholarship vs released
            $scholarship = 0.0;
            $released = 0.0;
            foreach ($pp->discounts as $disc) {
                $discAmount = 0.0;
                if (!is_null($disc->amount)) {
                    $discAmount = (float) $disc->amount;
                } elseif (!is_null($disc->percent)) {
                    $discAmount = round($price * ((float) $disc->percent) / 100.0, 2);
                }
                if ($disc->discount_type === 'released') {
                    $released += $discAmount;
                } else {
                    $scholarship += $discAmount;
                }
            }

            $porPagar = max($price - $abono - $scholarship - $released, 0);

            // Escribir fila
            $sheet->setCellValue('A' . $row, $participantName);
            $sheet->setCellValue('B' . $row, $price);
            $sheet->setCellValue('C' . $row, $abono);
            $sheet->setCellValue('D' . $row, $paidInstallments . '/' . ($totalInstallments ?: 0));
            $sheet->setCellValue('E' . $row, $overdueInstallments);
            $sheet->setCellValue('F' . $row, $paymentMethod);
            $sheet->setCellValue('G' . $row, $scholarship);
            $sheet->setCellValue('H' . $row, $released);
            $sheet->setCellValue('I' . $row, $porPagar);

            // Aplicar formato de moneda a las columnas numéricas
            foreach (['B','C','G','H','I'] as $col) {
                $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0');
            }
            
            // Centrar contenido en columnas D, E, F (Cuotas Pagadas, Cuotas Vencidas, Forma de Pago)
            $sheet->getStyle('D' . $row . ':F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $totals['price'] += $price;
            $totals['abono'] += $abono;
            $totals['scholarship'] += $scholarship;
            $totals['released'] += $released;
            $totals['por_pagar'] += $porPagar;

            $row++;
        }

        // Fila total general
        $sheet->setCellValue('A' . $row, 'Total General');
        $sheet->setCellValue('B' . $row, $totals['price']);
        $sheet->setCellValue('C' . $row, $totals['abono']);
        $sheet->setCellValue('G' . $row, $totals['scholarship']);
        $sheet->setCellValue('H' . $row, $totals['released']);
        $sheet->setCellValue('I' . $row, '(' . number_format($totals['por_pagar'], 0, ',', '.') . ')');
        foreach (['B','C','G','H'] as $col) {
            $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0');
        }
        $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
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
        $isAdmin = $this->isSuperAdmin();

        $filename = 'apoderados_consolidado_de_pagos_' . Carbon::now('America/Santiago')->format('Y-m-d_H-i-s') . '.csv';

        // Headers condicionales según rol
        if ($isAdmin) {
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
                'Email Contacto Pagador',
                'Aporte o Beca',
                'Liberado',
                'Precio'
            ];
        } else {
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
                'Aporte o Beca',
                'Liberado',
                'Precio'
            ];
        }

        $rows = [
            ['Consolidado de Pagos'],
            ['Período: ' . ($filters['dateFrom'] ?? '') . ' - ' . ($filters['dateTo'] ?? '')],
            [''],
            $headerRow
        ];

        // Usar los items del método de exportación
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                if ($isAdmin) {
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
                        $item['payer_email'] ?? 'N/A',
                        number_format($item['scholarship_or_grant'] ?? 0, 0, ',', '.'),
                        number_format($item['liberated'] ?? 0, 0, ',', '.'),
                        number_format($item['price'] ?? 0, 0, ',', '.')
                    ];
                } else {
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
                        number_format($item['scholarship_or_grant'] ?? 0, 0, ',', '.'),
                        number_format($item['liberated'] ?? 0, 0, ',', '.'),
                        number_format($item['price'] ?? 0, 0, ',', '.')
                    ];
                }
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


