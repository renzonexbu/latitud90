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
use App\Models\ProgramCourse;
use App\Traits\AdminLogging;
use App\Helpers\InstallmentRoundingHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

class ImportRefundsService
{
    use AdminLogging;

    private CreateRefundService $createRefundService;

    /**
     * Mapeo de campos a variaciones de header aceptadas (case-insensitive)
     */
    private array $fieldMappings = [
        // Requeridos
        'rut' => ['rut', 'rut alumno', 'rut alumno (a)', 'rut_alumno', 'rut participante', 'nro. de documento', 'nro de documento', 'numero de documento', 'nro documento cliente', 'documento'],
        'nro_negocio' => ['nro. negocio', 'nro negocio', 'numero negocio', 'numero de negocio'],
        'monto' => ['monto', 'total', 'valor', 'precio'],
        'fecha' => ['fecha', 'fecha de pago', 'fecha pago', 'fecha_pago'],
        'tipo_reembolso' => ['tipo reembolso', 'tipo de reembolso'],

        // Opcionales
        'nro_aut' => ['nro. aut.', 'nro aut', 'nro. aut', 'numero autorizacion', 'num. aut.', 'nro. autorización', 'nro autorizacion', 'nro. autorizacion', 'codigo autorizacion', 'código autorización', 'authorization_code'],
        'aplicar_a' => ['aplicar a', 'aplicar', 'aplicar a:'],
        'cod_sii' => ['cod. sii', 'codigo sii', 'cod sii', 'código sii'],
        'n_documento' => ['n. documento', 'nro documento', 'numero documento', 'nro. documento', 'n documento'],
        'nombre_cliente' => ['nombre del cliente', 'nombre cliente', 'nombre', 'nombre del participante'],
        'tipo_documento_cliente' => ['tipo de doc.', 'tipo de doc', 'tipo doc.', 'tipo doc', 'tipo documento', 'tipo de documento', 'tipo_documento', 'tipo documento cliente', 'tipo doc cliente', 'tipo_documento_cliente', 'document type'],
        'notas' => ['notas', 'observaciones', 'nota'],
    ];

    private array $requiredFields = ['rut', 'nro_negocio', 'monto', 'fecha', 'tipo_reembolso'];

    public function __construct(CreateRefundService $createRefundService)
    {
        $this->createRefundService = $createRefundService;
    }

    /**
     * Preview archivo Excel de devoluciones WITHOUT inserting into database
     */
    public function previewExcel($file): array
    {
        try {
            $filePath = $file->store('temp/refunds', 'public');
            $fullPath = storage_path('app/public/' . $filePath);

            $spreadsheet = IOFactory::load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $this->worksheetToArrayResolved($worksheet);

            // Buscar dinámicamente la fila de headers
            $headerInfo = $this->findHeaderRow($rows);

            if ($headerInfo['row'] === -1) {
                throw new \Exception("No se encontraron los headers requeridos en el archivo Excel. Se esperan al menos: RUT, Nro. Negocio, Monto, Fecha, Tipo Reembolso");
            }

            $columnIndices = $headerInfo['indices'];
            $headerRowIndex = $headerInfo['row'];
            $dataRows = array_slice($rows, $headerRowIndex + 1);

            Log::info('=== PREVIEW DE DEVOLUCIONES (nuevo formato) ===');
            Log::info('Headers encontrados en fila: ' . ($headerRowIndex + 1));
            Log::info('Columnas mapeadas: ' . json_encode($columnIndices));
            Log::info('Número de filas de datos: ' . count($dataRows));

            $results = [
                'processed' => 0,
                'successful' => 0,
                'warnings' => 0,
                'errors' => 0,
                'details' => []
            ];

            $previewLimit = 500;
            $rowCount = 0;

            foreach ($dataRows as $rowIndex => $row) {
                if ($rowCount >= $previewLimit) {
                    break;
                }

                $rowData = $this->extractRowData($row, $columnIndices);

                // Saltar filas vacías
                if ($this->isEmptyRow($rowData)) {
                    continue;
                }

                $results['processed']++;

                try {
                    $result = $this->previewRefundRow($rowData, $headerRowIndex + 2 + $rowIndex);

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
                    Log::error('Error en preview fila ' . ($headerRowIndex + 2 + $rowIndex) . ': ' . $e->getMessage());
                    $results['errors']++;
                    $results['details'][] = [
                        'row' => $headerRowIndex + 2 + $rowIndex,
                        'success' => false,
                        'error' => $e->getMessage(),
                        'warning' => false
                    ];
                }

                $rowCount++;
            }

            unlink($fullPath);

            Log::info('Preview de devoluciones completado', [
                'stats' => $results,
                'rows_previewed' => $rowCount
            ]);

            return [
                'success' => true,
                'results' => $results,
                'total_rows' => count($dataRows),
                'previewed_rows' => $rowCount
            ];

        } catch (\Exception $e) {
            if (isset($fullPath) && file_exists($fullPath)) {
                unlink($fullPath);
            }

            Log::error('Error en preview de devoluciones', [
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
     * Procesar archivo Excel de devoluciones
     */
    public function processExcel($file): array
    {
        try {
            DB::beginTransaction();

            $filePath = $file->store('temp/refunds', 'public');
            $fullPath = storage_path('app/public/' . $filePath);

            $spreadsheet = IOFactory::load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $this->worksheetToArrayResolved($worksheet);

            $headerInfo = $this->findHeaderRow($rows);

            if ($headerInfo['row'] === -1) {
                throw new \Exception("No se encontraron los headers requeridos en el archivo Excel. Se esperan al menos: RUT, Nro. Negocio, Monto, Fecha, Tipo Reembolso");
            }

            $columnIndices = $headerInfo['indices'];
            $headerRowIndex = $headerInfo['row'];
            $dataRows = array_slice($rows, $headerRowIndex + 1);

            Log::info('=== IMPORTACIÓN DE DEVOLUCIONES (nuevo formato) ===');
            Log::info('Headers encontrados en fila: ' . ($headerRowIndex + 1));
            Log::info('Columnas mapeadas: ' . json_encode($columnIndices));
            Log::info('Número de filas de datos: ' . count($dataRows));

            $results = [
                'processed' => 0,
                'successful' => 0,
                'warnings' => 0,
                'errors' => 0,
                'details' => []
            ];

            foreach ($dataRows as $rowIndex => $row) {
                $rowData = $this->extractRowData($row, $columnIndices);

                // Saltar filas vacías
                if ($this->isEmptyRow($rowData)) {
                    continue;
                }

                $results['processed']++;
                $rowNumber = $headerRowIndex + 2 + $rowIndex;

                Log::info('--- Fila ' . $rowNumber . ' ---');
                Log::info('Datos mapeados: ' . json_encode($rowData, JSON_UNESCAPED_UNICODE));

                try {
                    $result = $this->processRefundRow($rowData, $rowNumber);

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
                    Log::error('Error en fila ' . $rowNumber . ': ' . $e->getMessage());
                    $results['errors']++;
                    $results['details'][] = [
                        'row' => $rowNumber,
                        'success' => false,
                        'error' => $e->getMessage(),
                        'warning' => false
                    ];
                }
            }

            unlink($fullPath);

            DB::commit();

            $this->logCreate(
                'payments',
                'ImportRefunds',
                0,
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
     * Buscar dinámicamente la fila de headers usando field mappings flexibles.
     * Retorna ['row' => index, 'indices' => [field => colIndex, ...]]
     */
    private function findHeaderRow(array $rows): array
    {
        foreach ($rows as $rowIndex => $row) {
            $normalizedRow = array_map(function ($cell) {
                return strtolower(trim($cell ?? ''));
            }, $row);

            Log::info("Fila {$rowIndex} headers raw: " . json_encode($normalizedRow, JSON_UNESCAPED_UNICODE));
            $indices = $this->getColumnIndices($normalizedRow);

            // Verificar que al menos 4 de 5 campos requeridos estén presentes
            $foundRequired = 0;
            foreach ($this->requiredFields as $field) {
                if (isset($indices[$field])) {
                    $foundRequired++;
                }
            }

            if ($foundRequired >= 4) {
                Log::info("Headers encontrados en fila " . ($rowIndex + 1) . ": {$foundRequired}/5 requeridos");
                return ['row' => $rowIndex, 'indices' => $indices];
            }
        }

        return ['row' => -1, 'indices' => []];
    }

    /**
     * Obtener índices de columna mapeando headers a campos internos
     */
    private function getColumnIndices(array $normalizedHeaders): array
    {
        $indices = [];

        foreach ($this->fieldMappings as $field => $possibleHeaders) {
            foreach ($normalizedHeaders as $index => $normalizedHeader) {
                if (empty($normalizedHeader)) continue;

                foreach ($possibleHeaders as $possibleHeader) {
                    // Normalizar puntos para comparación (ej: "n. documento" == "n documento")
                    $cleanHeader = str_replace('.', '', $normalizedHeader);
                    $cleanPossible = str_replace('.', '', $possibleHeader);

                    if ($normalizedHeader === $possibleHeader ||
                        $cleanHeader === $cleanPossible ||
                        // Solo match parcial si el posible header tiene 5+ chars (evita matches genéricos como 'tipo')
                        (strlen($possibleHeader) >= 5 && stripos($normalizedHeader, $possibleHeader) !== false)) {
                        $indices[$field] = $index;
                        break 2;
                    }
                }
            }
        }

        return $indices;
    }

    /**
     * Extraer datos de una fila usando los índices de columna mapeados.
     * Detecta fórmulas Excel no resueltas (ej: =VLOOKUP referenciando archivos externos)
     * y las trata como vacías para evitar almacenar texto de fórmulas.
     */
    private function extractRowData(array $row, array $columnIndices): array
    {
        $data = [];
        foreach ($columnIndices as $field => $colIndex) {
            $value = trim($row[$colIndex] ?? '');

            // Detectar fórmulas Excel no resueltas (=VLOOKUP, =IF, etc.)
            // PhpSpreadsheet no puede resolver fórmulas que referencian archivos externos
            if (str_starts_with($value, '=') || str_starts_with($value, '#REF') || str_starts_with($value, '#N/A') || str_starts_with($value, '#VALUE')) {
                Log::warning("Celda con fórmula/error no resuelta en columna '{$field}': {$value}");
                $value = '';
            }

            $data[$field] = $value;
        }
        return $data;
    }

    /**
     * Verificar si una fila está vacía
     */
    private function isEmptyRow(array $rowData): bool
    {
        foreach ($this->requiredFields as $field) {
            if (!empty($rowData[$field] ?? '')) {
                return false;
            }
        }
        return true;
    }

    /**
     * Mapear Tipo Reembolso + Aplicar A → refund_type code
     */
    private function mapRefundType(string $tipoReembolso, string $aplicarA = ''): array
    {
        $tipo = strtoupper(trim($tipoReembolso));
        $aplicar = strtolower(trim($aplicarA));

        if ($tipo === 'RA') {
            return [
                'refund_type' => 'refund_admin_reversal',
                'label' => 'Reverso Administrativo (RA)',
            ];
        }

        if ($tipo === 'NC') {
            if ($aplicar === 'aportes' || $aplicar === 'aporte') {
                return [
                    'refund_type' => 'refund_aporte_credit_note',
                    'label' => 'Nota de Crédito (NC) - Aportes',
                ];
            }

            // Default: Abonos
            return [
                'refund_type' => 'refund_credit_note',
                'label' => 'Nota de Crédito (NC) - Abonos',
            ];
        }

        return [
            'refund_type' => null,
            'label' => null,
        ];
    }

    /**
     * Preview una fila de devolución WITHOUT database insert
     */
    private function previewRefundRow(array $rowData, int $rowNumber): array
    {
        $result = [
            'row' => $rowNumber,
            'success' => false,
            'error' => null,
            'warning' => false,
            'warning_message' => null,
            'data' => null
        ];

        try {
            // Validar datos de la fila
            $validation = $this->validateRowData($rowData, $rowNumber);
            if (!$validation['valid']) {
                $result['error'] = $validation['error'];
                return $result;
            }

            // Construir enrollment_code: RUT-NroNegocio
            $cleanRut = str_replace(['.', '-', ' '], '', trim($rowData['rut']));
            $nroNegocio = trim($rowData['nro_negocio']);
            $enrollmentCode = $cleanRut . '-' . $nroNegocio;

            // Buscar participant_program
            $participantProgram = ParticipantProgram::where('enrollment_code', $enrollmentCode)->first();

            if (!$participantProgram) {
                $result['error'] = "Código de inscripción '{$enrollmentCode}' no encontrado";
                return $result;
            }

            // participant_program.program_id almacena el program_courses.id directamente
            $programCourseId = $participantProgram->program_id;
            $order = Order::where('participant_id', $participantProgram->participant_id)
                ->where('program_id', $programCourseId)
                ->first();

            if (!$order) {
                $result['error'] = "No se encontró una orden asociada al código '{$enrollmentCode}'";
                return $result;
            }

            $participant = Participant::find($participantProgram->participant_id);
            $programCourse = ProgramCourse::find($programCourseId);

            // Parsear monto y tipo reembolso
            $refundAmount = $this->parseAmount($rowData['monto']);
            $tipoReembolso = trim($rowData['tipo_reembolso']);
            $aplicarA = trim($rowData['aplicar_a'] ?? '');
            $refundTypeInfo = $this->mapRefundType($tipoReembolso, $aplicarA);

            if (!$refundTypeInfo['refund_type']) {
                $result['error'] = "Tipo de reembolso '{$tipoReembolso}' no válido. Use NC o RA";
                return $result;
            }

            // Calcular monto pagado para validación
            $paidAmount = $this->calculatePaidAmount($participant->id, $programCourseId);

            if ($paidAmount < $refundAmount) {
                $result['error'] = "Monto pagado insuficiente. Pagado: $" . number_format($paidAmount) . ", Devolución: $" . number_format($refundAmount);
                return $result;
            }

            if ($paidAmount == $refundAmount) {
                $result['warning'] = true;
                $result['warning_message'] = "El participante quedará con saldo 0 después de la devolución";
            }

            $newBalance = $paidAmount - $refundAmount;

            $result['success'] = true;
            $result['data'] = [
                'enrollment_code' => $enrollmentCode,
                'participant_name' => $participant->first_name . ' ' . $participant->first_last_name,
                'participant_rut' => $participant->rut,
                'program_name' => $programCourse->name,
                'refund_amount' => $refundAmount,
                'transaction_date' => $this->parseDate($rowData['fecha']),
                'refund_type' => $refundTypeInfo['label'],
                'refund_type_code' => strtoupper($tipoReembolso),
                'aplicar_a' => strtoupper($tipoReembolso) === 'NC' ? ($aplicarA ?: 'Abonos') : 'N/A',
                'sii_code' => trim($rowData['cod_sii'] ?? ''),
                'document_number' => trim($rowData['n_documento'] ?? ''),
                'client_name' => trim($rowData['nombre_cliente'] ?? '') ?: ($participant->first_name . ' ' . $participant->first_last_name),
                'paid_amount' => $paidAmount,
                'new_balance' => $newBalance,
            ];

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
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

            // Construir enrollment_code: RUT-NroNegocio
            $cleanRut = str_replace(['.', '-', ' '], '', trim($rowData['rut']));
            $nroNegocio = trim($rowData['nro_negocio']);
            $enrollmentCode = $cleanRut . '-' . $nroNegocio;

            Log::info("Buscando enrollment_code: '{$enrollmentCode}'");

            $participantProgram = ParticipantProgram::where('enrollment_code', $enrollmentCode)->first();

            if (!$participantProgram) {
                Log::error("Código de inscripción '{$enrollmentCode}' no encontrado");
                $result['error'] = "Código de inscripción '{$enrollmentCode}' no encontrado";
                return $result;
            }

            Log::info("Enrollment code encontrado: participant_id={$participantProgram->participant_id}, program_course_id={$participantProgram->program_id}");

            // participant_program.program_id almacena el program_courses.id directamente
            $programCourseId = $participantProgram->program_id;
            $order = Order::where('participant_id', $participantProgram->participant_id)
                ->where('program_id', $programCourseId)
                ->first();

            if (!$order) {
                Log::error("No se encontró una orden para participant_id={$participantProgram->participant_id}");
                $result['error'] = "No se encontró una orden asociada al código '{$enrollmentCode}'";
                return $result;
            }

            $participant = Participant::find($participantProgram->participant_id);

            // Parsear monto y tipo reembolso
            $refundAmount = $this->parseAmount($rowData['monto']);
            $tipoReembolso = trim($rowData['tipo_reembolso']);
            $aplicarA = trim($rowData['aplicar_a'] ?? '');
            $refundTypeInfo = $this->mapRefundType($tipoReembolso, $aplicarA);

            if (!$refundTypeInfo['refund_type']) {
                $result['error'] = "Tipo de reembolso '{$tipoReembolso}' no válido. Use NC o RA";
                return $result;
            }

            Log::info("Tipo reembolso: {$refundTypeInfo['label']}, refund_type: {$refundTypeInfo['refund_type']}");

            // Calcular monto pagado para validación
            $paidAmount = $this->calculatePaidAmount($participant->id, $programCourseId);

            Log::info("Validando montos - Pagado: {$paidAmount}, Devolución: {$refundAmount}");

            if ($paidAmount < $refundAmount) {
                Log::error("Monto pagado insuficiente - Pagado: {$paidAmount}, Devolución: {$refundAmount}");
                $result['error'] = "Monto pagado insuficiente. Pagado: $" . number_format($paidAmount) . ", Devolución: $" . number_format($refundAmount);
                return $result;
            }

            if ($paidAmount == $refundAmount) {
                $result['warning'] = true;
                $result['warning_message'] = "El participante quedará con saldo 0 después de la devolución";
            }

            // Preparar datos para el servicio
            $siiCode = trim($rowData['cod_sii'] ?? '');
            $documentNumber = trim($rowData['n_documento'] ?? '');
            $clientName = trim($rowData['nombre_cliente'] ?? '') ?: ($participant->first_name . ' ' . $participant->first_last_name);

            // Defaults para campos opcionales (RA no tiene datos fiscales SII)
            $paymentCode = $siiCode ?: ('BULK-' . now()->format('YmdHis') . '-' . $rowNumber);
            $siiCodeFinal = $siiCode ?: $paymentCode;
            $documentNumberFinal = $documentNumber ?: $paymentCode;

            // Resolver tipo de documento del cliente (RUT por defecto)
            $clientDocumentTypeId = $this->resolveClientDocumentType($rowData['tipo_documento_cliente'] ?? null);

            $refundData = [
                'program_id' => $programCourseId,
                'participant_id' => $participantProgram->participant_id,
                'amount' => $refundAmount,
                'transaction_date' => $this->parseDate($rowData['fecha']),
                'payment_code' => $paymentCode,
                'sii_code' => $siiCodeFinal,
                'document_number' => $documentNumberFinal,
                'total_amount' => $refundAmount,
                'client_document_type' => $clientDocumentTypeId,
                'client_rut' => $cleanRut,
                'client_name' => $clientName,
                'refund_type' => $refundTypeInfo['refund_type'],
                'authorization_code' => !empty($rowData['nro_aut']) ? trim((string) $rowData['nro_aut']) : null,
            ];

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
        foreach ($this->requiredFields as $field) {
            if (empty(trim($data[$field] ?? ''))) {
                $fieldLabel = $this->getFieldLabel($field);
                Log::error("Campo requerido '{$fieldLabel}' está vacío en fila {$rowNumber}");
                return ['valid' => false, 'error' => "Campo requerido '{$fieldLabel}' está vacío"];
            }
        }

        // Validar formato de fecha
        $date = $this->parseDate($data['fecha']);
        if (!$date) {
            Log::error("Formato de fecha inválido en fila {$rowNumber}: " . $data['fecha']);
            return ['valid' => false, 'error' => 'Formato de fecha inválido'];
        }

        // Validar que el monto no sea negativo en el Excel
        $rawMonto = is_numeric($data['monto']) ? (float) $data['monto'] : null;
        if ($rawMonto !== null && $rawMonto < 0) {
            Log::error("Monto negativo en fila {$rowNumber}: {$rawMonto}. Los montos deben ser positivos");
            return ['valid' => false, 'error' => 'El monto debe ser positivo (no ingrese valores negativos)'];
        }

        // Validar monto (debe ser mayor a 0)
        $monto = $this->parseAmount($data['monto']);
        if ($monto <= 0) {
            Log::error("Monto debe ser mayor a 0 en fila {$rowNumber}: {$monto}");
            return ['valid' => false, 'error' => 'El monto debe ser mayor a 0'];
        }

        // Validar tipo de reembolso
        $tipo = strtoupper(trim($data['tipo_reembolso']));
        if (!in_array($tipo, ['NC', 'RA'])) {
            Log::error("Tipo de reembolso inválido en fila {$rowNumber}: {$tipo}");
            return ['valid' => false, 'error' => "Tipo de reembolso '{$tipo}' no válido. Use NC o RA"];
        }

        // Validar RUT
        $cleanRut = str_replace(['.', '-', ' '], '', trim($data['rut']));
        Log::info("RUT limpio en fila {$rowNumber}: " . $data['rut'] . " -> " . $cleanRut);

        Log::info("Validación de fila {$rowNumber} exitosa");
        return ['valid' => true];
    }

    /**
     * Obtener etiqueta legible de un campo
     */
    private function getFieldLabel(string $field): string
    {
        $labels = [
            'rut' => 'RUT',
            'nro_negocio' => 'Nro. Negocio',
            'monto' => 'Monto',
            'fecha' => 'Fecha',
            'tipo_reembolso' => 'Tipo Reembolso',
        ];
        return $labels[$field] ?? $field;
    }

    /**
     * Calcular monto pagado por participante y programa
     */
    private function calculatePaidAmount($participantId, $programId): int
    {
        $sum = Payment::whereHas('order', function ($query) use ($participantId, $programId) {
            $query->where('participant_id', $participantId)
                  ->where('program_id', $programId);
        })
        ->whereIn('status', ['approved', 'completed'])
        ->where('amount', '>', 0)
        ->sum('amount');

        // Ley de redondeo: CLP sin decimales
        return InstallmentRoundingHelper::round((float) $sum);
    }

    /**
     * Parsear monto desde varios formatos.
     * IMPORTANTE: Verificar formato chileno (puntos como miles) ANTES de is_numeric,
     * porque "777.311" es is_numeric=true pero en CLP significa 777.311 (777 mil 311).
     */
    private function parseAmount($value): int
    {
        // Convertir a string para analizar el formato
        $stringValue = trim((string) $value);

        // Remover símbolos de moneda y espacios
        $cleaned = preg_replace('/[^0-9,.\-]/', '', $stringValue);

        // Formato chileno: 777.311 o 1.234.567 o 1.234.567,89 (punto como separador de miles)
        // DEBE evaluarse ANTES de is_numeric para no confundir 777.311 con un decimal
        if (preg_match('/^\-?\d{1,3}(\.\d{3})+(,\d+)?$/', $cleaned)) {
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
            return InstallmentRoundingHelper::round(abs((float) $cleaned));
        }

        // Número simple sin formato de miles (ej: 777311, 500, 1234567)
        if (is_numeric($value)) {
            return InstallmentRoundingHelper::round(abs((float) $value));
        }

        // Formato estándar: 1,234,567.89
        if (preg_match('/^\-?\d{1,3}(,\d{3})*(\.\d+)?$/', $cleaned)) {
            $cleaned = str_replace(',', '', $cleaned);
        }
        // Formato simple con coma como decimal
        elseif (strpos($cleaned, ',') !== false && strpos($cleaned, '.') === false) {
            $cleaned = str_replace(',', '.', $cleaned);
        }

        // Ley de redondeo: CLP no tiene decimales (.0-.59 abajo, .6-.99 arriba)
        return InstallmentRoundingHelper::round(abs((float) $cleaned));
    }

    /**
     * Parsear fecha desde Excel
     */
    private function parseDate($dateValue): ?string
    {
        try {
            // Si es un número (fecha serial de Excel)
            if (is_numeric($dateValue)) {
                $date = ExcelDate::excelToDateTimeObject($dateValue);
                return $date->format('Y-m-d');
            }

            $cleanDate = trim($dateValue);

            $formats = [
                'd/m/Y',     // 08/01/2025
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
                    return $date->format('Y-m-d');
                } catch (\Exception $e) {
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

    /**
     * Leer worksheet a array resolviendo fórmulas con cache de Excel
     */
    private function worksheetToArrayResolved($worksheet): array
    {
        $rows = [];
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();

        for ($row = 1; $row <= $highestRow; $row++) {
            $rowData = [];
            $colIterator = $worksheet->getRowIterator($row, $row)->current()->getCellIterator('A', $highestColumn);
            $colIterator->setIterateOnlyExistingCells(false);
            foreach ($colIterator as $cell) {
                $value = $cell->getValue();
                if (is_string($value) && str_starts_with($value, '=')) {
                    $cached = $cell->getOldCalculatedValue();
                    if ($cached !== null) {
                        $value = $cached;
                    } else {
                        try {
                            $value = $cell->getCalculatedValue();
                        } catch (\Exception $e) {
                            Log::warning("Fórmula no resuelta en celda {$cell->getCoordinate()}: {$value}");
                            $value = null;
                        }
                    }
                }
                $rowData[] = $value;
            }
            $rows[] = $rowData;
        }

        return $rows;
    }

    /**
     * Resuelve el tipo de documento del cliente desde el Excel.
     * Acepta: RUT, PASAPORTE, DNI. Default: RUT (id=1).
     */
    private function resolveClientDocumentType(?string $tipo): ?int
    {
        if (empty($tipo)) {
            // Default: RUT
            $doc = \App\Models\Document::where('name', 'RUT')->first();
            return $doc?->id;
        }

        $tipo = strtoupper(trim($tipo));
        $doc = \App\Models\Document::whereRaw('UPPER(name) = ?', [$tipo])->first();

        return $doc?->id;
    }
}
