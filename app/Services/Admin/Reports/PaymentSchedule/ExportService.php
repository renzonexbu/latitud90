<?php

namespace App\Services\Admin\Reports\PaymentSchedule;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
    public function export(array $data, string $filename, string $format = 'xlsx'): StreamedResponse
    {
        try {
            // Limpiar cualquier output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            $spreadsheet = new Spreadsheet();
            
            // Crear hoja de resumen general
            $this->createSummarySheet($spreadsheet, $data);
            
            // Crear hoja de detalles
            $this->createDetailsSheet($spreadsheet, $data);
            
            // Crear hoja de participantes sin pagos iniciados
            $this->createNoPaymentSheet($spreadsheet, $data);
            
            // Configurar writer XLSX
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->setIncludeCharts(false);
            
            // Crear respuesta streaming
            $response = new StreamedResponse(function () use ($writer, $filename) {
                $writer->save('php://output');
            });
            
            // Headers para Excel
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.xlsx"');
            $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
            $response->headers->set('Expires', '0');
            $response->headers->set('Pragma', 'public');
            
            return $response;
            
        } catch (\Exception $e) {
            throw new \RuntimeException('Error al exportar cronograma de pagos: ' . $e->getMessage());
        }
    }
    
    private function createSummarySheet(Spreadsheet $spreadsheet, array $data): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen General');
        
        // Convertir Collection a array si es necesario
        $executiveSummary = $data['executiveSummary'] ?? [];
        if ($executiveSummary instanceof \Illuminate\Support\Collection) {
            $executiveSummary = $executiveSummary->toArray();
        }
        
        $this->buildSummarySheet($sheet, $executiveSummary);
    }
    
    private function buildSummarySheet($sheet, array $summary): void
    {
        $rowIndex = 1;

        foreach ($summary as $group) {
            try {
                // Agrupar meses correctamente ordenados
                $months = collect($group['months'] ?? [])->sortBy(fn($m) => ($m['year'] * 100) + $m['month'])->values();

                // Encabezado Ejecutivo/Programa
                $sheet->setCellValue("A{$rowIndex}", "Ejecutivo: " . ($group['sales_executive_name'] ?? 'N/A'));
                $sheet->setCellValue("B{$rowIndex}", "Programa: " . ($group['program_code'] ?? '') . " - " . ($group['program_name'] ?? ''));
                $sheet->getStyle("A{$rowIndex}:B{$rowIndex}")->getFont()->setBold(true);
                $sheet->getStyle("A{$rowIndex}:B{$rowIndex}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('1c4f4a');
                $sheet->getStyle("A{$rowIndex}:B{$rowIndex}")->getFont()->getColor()->setRGB('FFFFFF');
                $rowIndex += 2;

                foreach ($months as $m) {
                    // Header del mes
                    $sheet->setCellValue("A{$rowIndex}", mb_strtoupper($m['month_name'] . " " . $m['year']));
                    $sheet->getStyle("A{$rowIndex}")->getFont()->setBold(true);
                    $sheet->getStyle("A{$rowIndex}")->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('4472C4');
                    $sheet->getStyle("A{$rowIndex}")->getFont()->getColor()->setRGB('FFFFFF');
                    $rowIndex++;

                    // Datos del mes
                    $rows = [
                        ['Cuotas No Pagadas N°', (int)($m['cuotas_no_pagadas_count'] ?? 0)],
                        ['Cuotas No Pagadas $', (int)round($m['cuotas_no_pagadas_amount'] ?? 0)],
                        ['Cuotas Pagadas TC N°', (int)($m['cuotas_pagadas_tc_count'] ?? 0)],
                        ['Cuotas Pagadas TC $', (int)round($m['cuotas_pagadas_tc_amount'] ?? 0)],
                        ['Cuotas Pagadas PAT N°', (int)($m['cuotas_pagadas_pat_count'] ?? 0)],
                        ['Cuotas Pagadas PAT $', (int)round($m['cuotas_pagadas_pat_amount'] ?? 0)],
                    ];

                    foreach ($rows as $r) {
                        $sheet->setCellValue("A{$rowIndex}", $r[0]);
                        $sheet->setCellValue("B{$rowIndex}", $r[1]);
                        $rowIndex++;
                    }

                    // Fila vacía de separación
                    $rowIndex++;
                }

                // Fila vacía de separación entre grupos
                $rowIndex++;

            } catch (\Throwable $e) {
                throw $e;
            }
        }
        
        // Auto-ajustar columnas
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }
    
    private function createDetailsSheet(Spreadsheet $spreadsheet, array $data): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Detalles de Cuotas');
        
        // Headers de los detalles
        $headers = [
            'Ejecutivo Comercial',
            'Programa',
            'Participante',
            'Documento',
            'N° Cuota',
            'Fecha Vencimiento',
            'Monto Cuota',
            'Estado',
            'Método de Pago',
            'Fecha Pago',
            'Monto Pagado'
        ];
        
        // Escribir headers
        $colIndex = 0;
        foreach ($headers as $header) {
            $col = chr(65 + $colIndex);
            $sheet->setCellValue($col . '1', $header);
            $colIndex++;
        }
        
        // Escribir datos de los detalles
        $rowIndex = 2;
        $paymentSchedules = $data['paymentSchedules'] ?? collect([]);
        
        // Convertir Collection a array si es necesario
        if ($paymentSchedules instanceof \Illuminate\Support\Collection) {
            $paymentSchedules = $paymentSchedules->toArray();
        }
        
        if (is_array($paymentSchedules)) {
            foreach ($paymentSchedules as $schedule) {
                $scheduleArray = (array) $schedule;
                $sheet->setCellValue('A' . $rowIndex, $scheduleArray['sales_executive_name'] ?? 'N/A');
                $sheet->setCellValue('B' . $rowIndex, $scheduleArray['program_name'] ?? 'N/A');
                $sheet->setCellValue('C' . $rowIndex, $scheduleArray['participant_name'] ?? 'N/A');
                $sheet->setCellValue('D' . $rowIndex, $scheduleArray['participant_document'] ?? 'N/A');
                $sheet->setCellValue('E' . $rowIndex, $scheduleArray['installment_number'] ?? 'N/A');
                $sheet->setCellValue('F' . $rowIndex, $scheduleArray['due_date'] ?? 'N/A');
                $sheet->setCellValue('G' . $rowIndex, $scheduleArray['installment_amount'] ?? 0);
                $sheet->setCellValue('H' . $rowIndex, $scheduleArray['installment_status'] ?? 'N/A');
                $sheet->setCellValue('I' . $rowIndex, $scheduleArray['gateway_code'] ?? 'N/A');
                $sheet->setCellValue('J' . $rowIndex, $scheduleArray['transaction_date'] ?? 'N/A');
                $sheet->setCellValue('K' . $rowIndex, $scheduleArray['payment_amount'] ?? 0);
                $rowIndex++;
            }
        }
        
        // Aplicar formato
        $this->applyDetailsFormatting($sheet, $headers);
    }
    
    
    private function applyDetailsFormatting($sheet, array $headers): void
    {
        // Formato para headers
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1c4f4a'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        
        $sheet->getStyle('A1:' . chr(65 + count($headers) - 1) . '1')->applyFromArray($headerStyle);
        
        // Auto-ajustar columnas
        foreach (range('A', chr(65 + count($headers) - 1)) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Bordes para toda la tabla
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:' . chr(65 + count($headers) - 1) . $lastRow)->applyFromArray($borderStyle);
    }

    private function createNoPaymentSheet(Spreadsheet $spreadsheet, array $data): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Pago no iniciado');
        $isAdmin = $this->isSuperAdmin();

        // Headers para participantes sin pagos (columnas de contacto pagador solo para super_admin)
        if ($isAdmin) {
            $headers = [
                'Participante',
                'Documento',
                'Email Participante',
                'Contacto Pagador',
                'Email Contacto',
                'Teléfono Contacto',
                'Relación',
                'Fecha Inscripción',
                'Precio Individual',
                'Descuento',
                'Monto Final',
                'Programa',
                'Código Programa',
                'Ejecutivo Comercial'
            ];
        } else {
            $headers = [
                'Participante',
                'Documento',
                'Email Participante',
                'Fecha Inscripción',
                'Precio Individual',
                'Descuento',
                'Monto Final',
                'Programa',
                'Código Programa',
                'Ejecutivo Comercial'
            ];
        }

        // Escribir headers
        $colIndex = 0;
        foreach ($headers as $header) {
            $col = chr(65 + $colIndex);
            $sheet->setCellValue($col . '1', $header);
            $colIndex++;
        }

        // Escribir datos de participantes sin pagos
        $rowIndex = 2;
        $participantsWithoutPayments = $data['participantsWithoutPayments'] ?? [];

        // Convertir Collection a array si es necesario
        if ($participantsWithoutPayments instanceof \Illuminate\Support\Collection) {
            $participantsWithoutPayments = $participantsWithoutPayments->toArray();
        }

        if (is_array($participantsWithoutPayments)) {
            foreach ($participantsWithoutPayments as $participant) {
                $participantArray = (array) $participant;

                $fullName = trim(
                    ($participantArray['first_name'] ?? '') . ' ' .
                    ($participantArray['first_last_name'] ?? '') . ' ' .
                    ($participantArray['second_last_name'] ?? '')
                );

                $incorporationDate = isset($participantArray['incorporation_date'])
                    ? date('d/m/Y', strtotime($participantArray['incorporation_date']))
                    : 'N/A';

                if ($isAdmin) {
                    $sheet->setCellValue('A' . $rowIndex, $fullName);
                    $sheet->setCellValue('B' . $rowIndex, $this->formatRut($participantArray['document_number'] ?? ''));
                    $sheet->setCellValue('C' . $rowIndex, $participantArray['participant_email'] ?? 'N/A');
                    $sheet->setCellValue('D' . $rowIndex, $participantArray['emergency_contact_name'] ?? 'Sin contacto');
                    $sheet->setCellValue('E' . $rowIndex, $participantArray['emergency_contact_email'] ?? 'Sin email');
                    $sheet->setCellValue('F' . $rowIndex, $participantArray['emergency_contact_phone'] ?? 'N/A');
                    $sheet->setCellValue('G' . $rowIndex, $participantArray['emergency_contact_relationship'] ?? 'N/A');
                    $sheet->setCellValue('H' . $rowIndex, $incorporationDate);
                    $sheet->setCellValue('I' . $rowIndex, (int)($participantArray['individual_price'] ?? 0));
                    $sheet->setCellValue('J' . $rowIndex, (int)($participantArray['discount_amount'] ?? 0));
                    $sheet->setCellValue('K' . $rowIndex, (int)($participantArray['final_amount'] ?? 0));
                    $sheet->setCellValue('L' . $rowIndex, $participantArray['program_name'] ?? 'N/A');
                    $sheet->setCellValue('M' . $rowIndex, $participantArray['program_code'] ?? 'N/A');
                    $sheet->setCellValue('N' . $rowIndex, $participantArray['sales_executive_name'] ?? 'Sin asignar');
                } else {
                    // Sin columnas de contacto pagador
                    $sheet->setCellValue('A' . $rowIndex, $fullName);
                    $sheet->setCellValue('B' . $rowIndex, $this->formatRut($participantArray['document_number'] ?? ''));
                    $sheet->setCellValue('C' . $rowIndex, $participantArray['participant_email'] ?? 'N/A');
                    $sheet->setCellValue('D' . $rowIndex, $incorporationDate);
                    $sheet->setCellValue('E' . $rowIndex, (int)($participantArray['individual_price'] ?? 0));
                    $sheet->setCellValue('F' . $rowIndex, (int)($participantArray['discount_amount'] ?? 0));
                    $sheet->setCellValue('G' . $rowIndex, (int)($participantArray['final_amount'] ?? 0));
                    $sheet->setCellValue('H' . $rowIndex, $participantArray['program_name'] ?? 'N/A');
                    $sheet->setCellValue('I' . $rowIndex, $participantArray['program_code'] ?? 'N/A');
                    $sheet->setCellValue('J' . $rowIndex, $participantArray['sales_executive_name'] ?? 'Sin asignar');
                }

                $rowIndex++;
            }
        }

        // Aplicar formato
        $this->applyNoPaymentFormatting($sheet, $headers);
    }

    private function applyNoPaymentFormatting($sheet, array $headers): void
    {
        // Formato para headers
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DC3545'], // Color rojo para indicar "sin pago"
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        
        $sheet->getStyle('A1:' . chr(65 + count($headers) - 1) . '1')->applyFromArray($headerStyle);
        
        // Auto-ajustar columnas
        foreach (range('A', chr(65 + count($headers) - 1)) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Bordes para toda la tabla
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        
        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
            $sheet->getStyle('A1:' . chr(65 + count($headers) - 1) . $lastRow)->applyFromArray($borderStyle);
        }
    }

    private function formatRut($rut): string
    {
        if (!$rut) return 'N/A';
        
        // Limpiar el RUT de puntos y guiones
        $rutLimpio = preg_replace('/[^0-9kK]/', '', (string)$rut);
        
        if (strlen($rutLimpio) < 2) return (string)$rut;
        
        // Separar número y dígito verificador
        $dv = substr($rutLimpio, -1);
        $numero = substr($rutLimpio, 0, -1);
        
        // Formatear número con puntos
        $numeroFormateado = number_format((int)$numero, 0, '', '.');
        
        // Retornar RUT formateado
        return $numeroFormateado . '-' . strtoupper($dv);
    }
}