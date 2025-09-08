<?php

namespace App\Services\Admin\Payments;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\PaymentOption;
use App\Models\Participant;
use App\Models\ParticipantProgram;
use App\Models\Program;
use App\Traits\AdminLogging;
use App\Helpers\RutHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class ImportRefundsService
{
    use AdminLogging;

    private CreateRefundService $createRefundService;

    public function __construct(CreateRefundService $createRefundService)
    {
        $this->createRefundService = $createRefundService;
    }

    /**
     * Procesar archivo Excel de devoluciones
     */
    public function processExcel($file): array
    {
        try {
            DB::beginTransaction();

            // Guardar el archivo temporalmente
            $filePath = $file->store('temp/refunds', 'public');
            $fullPath = storage_path('app/public/' . $filePath);

            // Leer el archivo Excel
            $spreadsheet = IOFactory::load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Buscar dinámicamente la fila que contiene los headers
            $headerRowIndex = $this->findHeaderRow($rows);
            if ($headerRowIndex === -1) {
                throw new \Exception("No se encontraron los headers requeridos en el archivo Excel");
            }

            // Extraer headers y datos
            $headers = $rows[$headerRowIndex];
            $dataRows = array_slice($rows, $headerRowIndex + 1);

            // Log de información de headers encontrados
            Log::info('=== IMPORTACIÓN DE DEVOLUCIONES ===');
            Log::info('Headers encontrados en fila: ' . ($headerRowIndex + 1));
            Log::info('Headers detectados: ' . json_encode($headers, JSON_UNESCAPED_UNICODE));
            Log::info('Número de filas de datos: ' . count($dataRows));

            // Validar headers
            $this->validateHeaders($headers);

            // Procesar cada fila
            $results = [
                'processed' => 0,
                'successful' => 0,
                'warnings' => 0,
                'errors' => 0,
                'details' => []
            ];

            foreach ($dataRows as $rowIndex => $row) {
                // Saltar filas vacías
                if (empty(array_filter($row))) {
                    continue;
                }

                // Asegurar que la fila tenga el mismo número de columnas que los headers
                while (count($row) < count($headers)) {
                    $row[] = '';
                }

                $rowData = array_combine($headers, $row);
                $results['processed']++;

                // Log de datos de la fila antes de procesar
                Log::info('--- Fila ' . ($headerRowIndex + 2 + $rowIndex) . ' ---');
                Log::info('Datos raw: ' . json_encode($row, JSON_UNESCAPED_UNICODE));
                Log::info('Datos mapeados: ' . json_encode($rowData, JSON_UNESCAPED_UNICODE));

                try {
                    Log::info("Iniciando procesamiento de fila " . ($headerRowIndex + 2 + $rowIndex));
                    // Validar y procesar la fila
                    $result = $this->processRefundRow($rowData, $headerRowIndex + 2 + $rowIndex);
                    
                    if ($result['success']) {
                        $results['successful']++;
                        if ($result['warning']) {
                            $results['warnings']++;
                        }
                    } else {
                        $results['errors']++;
                    }

                    $results['details'][] = $result;

                } catch (\Exception $e) {
                    Log::error('Error en fila ' . ($headerRowIndex + 2 + $rowIndex) . ': ' . $e->getMessage());
                    $results['errors']++;
                    $results['details'][] = [
                        'row' => $headerRowIndex + 2 + $rowIndex,
                        'success' => false,
                        'error' => $e->getMessage(),
                        'warning' => false
                    ];
                }
            }

            // Limpiar archivo temporal
            unlink($fullPath);

            DB::commit();

            // Log de la importación
            $this->logCreate(
                'payments',
                'ImportRefunds',
                0, // ID fijo para importaciones masivas
                "Importación de devoluciones completada: {$results['successful']} exitosos, {$results['errors']} errores",
                $results,
                [
                    'file_name' => $file->getClientOriginalName(),
                    'total_processed' => $results['processed'],
                    'successful' => $results['successful'],
                    'warnings' => $results['warnings'],
                    'errors' => $results['errors']
                ]
            );

            return [
                'success' => true,
                'results' => $results
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Limpiar archivo temporal si existe
            if (isset($fullPath) && file_exists($fullPath)) {
                unlink($fullPath);
            }

            Log::error('Error en importación de devoluciones', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Buscar dinámicamente la fila que contiene los headers
     */
    private function findHeaderRow(array $rows): int
    {
        $expectedHeaders = [
            'Cod. SII',
            'N. Documento',
            'Fecha',
            'RUT',
            'Nombre del Cliente',
            'Neto Afecto',
            'Neto Exento',
            'Iva',
            'Total',
            'Negocio Afiliado'
        ];

        Log::info('Buscando headers en ' . count($rows) . ' filas...');

        foreach ($rows as $rowIndex => $row) {
            // Limpiar y normalizar la fila
            $normalizedRow = array_map('trim', array_filter($row, function($cell) {
                return !empty($cell);
            }));

            Log::info("Fila {$rowIndex}: " . json_encode($normalizedRow, JSON_UNESCAPED_UNICODE));

            // Verificar si esta fila contiene todos los headers esperados
            $foundHeaders = 0;
            $foundHeaderNames = [];
            foreach ($expectedHeaders as $expectedHeader) {
                if (in_array($expectedHeader, $normalizedRow)) {
                    $foundHeaders++;
                    $foundHeaderNames[] = $expectedHeader;
                }
            }

            Log::info("Fila {$rowIndex}: Encontrados {$foundHeaders}/10 headers: " . implode(', ', $foundHeaderNames));

            // Si encontramos al menos 8 de los 10 headers, consideramos que es la fila de headers
            if ($foundHeaders >= 8) {
                Log::info("Headers encontrados en fila: " . ($rowIndex + 1));
                return $rowIndex;
            }
        }

        Log::error('No se encontró la fila de headers');
        return -1; // No se encontró la fila de headers
    }

    /**
     * Validar headers del Excel
     */
    private function validateHeaders(array $headers): void
    {
        $expectedHeaders = [
            'Cod. SII',
            'N. Documento',
            'Fecha',
            'RUT',
            'Nombre del Cliente',
            'Neto Afecto',
            'Neto Exento',
            'Iva',
            'Total',
            'Negocio Afiliado'
        ];

        $normalizedHeaders = array_map('trim', $headers);
        $normalizedExpected = array_map('trim', $expectedHeaders);

        foreach ($normalizedExpected as $expected) {
            if (!in_array($expected, $normalizedHeaders)) {
                throw new \Exception("Header requerido no encontrado: {$expected}");
            }
        }
    }

    /**
     * Procesar una fila de devolución
     */
    private function processRefundRow(array $rowData, int $rowNumber): array
    {
        $result = [
            'row' => $rowNumber,
            'success' => false,
            'error' => null,
            'warning' => false,
            'warning_message' => null
        ];

        try {
            // Validar datos de la fila
            $validation = $this->validateRowData($rowData, $rowNumber);
            if (!$validation['valid']) {
                $result['error'] = $validation['error'];
                return $result;
            }

            // Buscar la relación participant_program por enrollment_code
            $enrollmentCode = trim($rowData['Negocio Afiliado']);
            Log::info("Buscando enrollment_code: '{$enrollmentCode}'");
            
            $participantProgram = ParticipantProgram::where('enrollment_code', $enrollmentCode)->first();

            if (!$participantProgram) {
                Log::error("Código de inscripción '{$enrollmentCode}' no encontrado en la base de datos");
                $result['error'] = "Código de inscripción '{$enrollmentCode}' no encontrado";
                return $result;
            }
            
            Log::info("Enrollment code encontrado: participant_id={$participantProgram->participant_id}, program_id={$participantProgram->program_id}");

            // Obtener participante para validaciones
            $participant = Participant::find($participantProgram->participant_id);
            $cleanRut = RutHelper::clean(trim($rowData['RUT']));
            
            Log::info("RUT del comprador (Excel): '{$cleanRut}'");

            // Calcular monto pagado para validación
            $paidAmount = $this->calculatePaidAmount($participant->id, $participantProgram->program_id);
            $refundAmount = abs(floatval($rowData['Total'])); // Convertir negativo a positivo

            Log::info("Validando montos - Pagado: {$paidAmount}, Devolución: {$refundAmount}");

            // Validar que el monto pagado sea suficiente
            if ($paidAmount < $refundAmount) {
                Log::error("Monto pagado insuficiente - Pagado: {$paidAmount}, Devolución: {$refundAmount}");
                $result['error'] = "Monto pagado insuficiente. Pagado: $" . number_format($paidAmount) . ", Devolución: $" . number_format($refundAmount);
                return $result;
            }

            // Advertencia si el monto pagado es justo
            if ($paidAmount == $refundAmount) {
                $result['warning'] = true;
                $result['warning_message'] = "El participante quedará con saldo 0 después de la devolución";
            }

            // Preparar datos para el servicio de devolución individual
            $refundData = [
                'program_id' => $participantProgram->program_id,
                'participant_id' => $participantProgram->participant_id,
                'amount' => $refundAmount, // Siempre positivo para el servicio
                'transaction_date' => $this->parseDate($rowData['Fecha']),
                'payment_code' => trim($rowData['Cod. SII']),
                
                // Datos fiscales
                'sii_code' => trim($rowData['Cod. SII']),
                'document_number' => trim($rowData['N. Documento']),
                'total_amount' => $refundAmount, // Siempre positivo
                
                // Datos del cliente
                'client_rut' => $cleanRut, // Usar RUT del Excel (comprador)
                'client_name' => trim($rowData['Nombre del Cliente']),
            ];

            // Ejecutar el servicio de devolución individual (mismo que el formulario)
            Log::info("Ejecutando CreateRefundService con datos: " . json_encode($refundData, JSON_UNESCAPED_UNICODE));
            
            $refundResult = $this->createRefundService->execute($refundData);

            if ($refundResult['success']) {
                Log::info("Devolución creada exitosamente - ID: {$refundResult['refund']->id}");
                $result['success'] = true;
                $result['refund_id'] = $refundResult['refund']->id;
                $result['participant_name'] = $participant->first_name . ' ' . $participant->first_last_name;
                $result['refund_amount'] = $refundAmount;
            } else {
                Log::error("Error en CreateRefundService: " . $refundResult['error']);
                $result['error'] = $refundResult['error'];
            }

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Validar datos de una fila
     */
    private function validateRowData(array $data, int $rowNumber): array
    {
        Log::info("Validando datos de fila {$rowNumber}");
        
        // Validar campos requeridos
        $required = ['Cod. SII', 'N. Documento', 'Fecha', 'RUT', 'Nombre del Cliente', 'Total', 'Negocio Afiliado'];
        
        foreach ($required as $field) {
            if (empty(trim($data[$field] ?? ''))) {
                Log::error("Campo requerido '{$field}' está vacío en fila {$rowNumber}");
                return ['valid' => false, 'error' => "Campo requerido '{$field}' está vacío"];
            }
        }

        // Validar formato de fecha
        $date = $this->parseDate($data['Fecha']);
        if (!$date) {
            Log::error("Formato de fecha inválido en fila {$rowNumber}: " . $data['Fecha']);
            return ['valid' => false, 'error' => 'Formato de fecha inválido. Use YYYY-MM-DD'];
        }

        // Validar monto total (debe ser negativo)
        $total = floatval($data['Total']);
        if ($total >= 0) {
            Log::error("Monto Total debe ser negativo en fila {$rowNumber}: {$total}");
            return ['valid' => false, 'error' => 'El monto Total debe ser negativo (devolución)'];
        }

        // RUT ya validado en frontend, solo limpiarlo
        $cleanRut = RutHelper::clean($data['RUT']);
        Log::info("RUT limpio en fila {$rowNumber}: " . $data['RUT'] . " -> " . $cleanRut);

        Log::info("Validación de fila {$rowNumber} exitosa");
        return ['valid' => true];
    }

    /**
     * Calcular monto pagado por participante y programa
     */
    private function calculatePaidAmount($participantId, $programId): float
    {
        return \App\Models\Payment::whereHas('order', function ($query) use ($participantId, $programId) {
            $query->where('participant_id', $participantId)
                  ->where('program_id', $programId);
        })
        ->where('status', 'completed')
        ->sum('amount');
    }

    /**
     * Validar RUT chileno usando RutHelper
     */
    private function validateRut($rut): bool
    {
        Log::info("Validando RUT: '{$rut}' (longitud: " . strlen($rut) . ")");
        
        $isValid = RutHelper::validate($rut);
        Log::info("RUT válido: " . ($isValid ? 'SÍ' : 'NO'));
        
        return $isValid;
    }

    /**
     * Parsear fecha desde Excel
     */
    private function parseDate($dateValue): ?string
    {
        Log::info('Parseando fecha: ' . json_encode($dateValue) . ' (tipo: ' . gettype($dateValue) . ')');
        
        try {
            // Si es un número (fecha serial de Excel)
            if (is_numeric($dateValue)) {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue);
                $result = $date->format('Y-m-d');
                Log::info('Fecha parseada desde número: ' . $result);
                return $result;
            }

            // Limpiar la fecha (quitar espacios y caracteres extra)
            $cleanDate = trim($dateValue);
            Log::info('Fecha limpia: ' . json_encode($cleanDate));

            // Si es una cadena, intentar diferentes formatos
            $formats = [
                'd/m/Y',     // 08/01/2025 (formato del Excel)
                'd-m-Y',     // 08-01-2025
                'Y-m-d',     // 2025-01-08
                'm/d/Y',     // 01/08/2025
                'Y/m/d',     // 2025/01/08
                'd/m/y',     // 08/01/25
                'd-m-y',     // 08-01-25
            ];
            
            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $cleanDate);
                    $result = $date->format('Y-m-d');
                    Log::info("Fecha parseada desde formato '{$format}': {$result}");
                    return $result;
                } catch (\Exception $e) {
                    Log::info("Formato '{$format}' falló para: {$cleanDate}");
                    continue;
                }
            }
            
            Log::error('No se pudo parsear la fecha: ' . $cleanDate);
            return null;
        } catch (\Exception $e) {
            Log::error('Error parseando fecha: ' . $e->getMessage());
            return null;
        }
    }
}
