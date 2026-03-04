<?php

namespace App\Services\Admin\Payments;

use App\Helpers\ParticipantPriceHelper;
use App\Helpers\RutHelper;
use App\Helpers\PaymentDocumentTypeHelper;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Participant;
use App\Models\ParticipantProgram;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\PaymentOption;
use App\Models\ProgramCourse;
use App\Models\ProgramSubscription;
use App\Services\Admin\Installments\RegisterManualPaymentService;
use App\Services\Shared\OrderNumberGenerator;
use App\Services\Subscription\SubscriptionRecalculationService;
use App\Traits\AdminLogging;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportManualPaymentsService
{
    use AdminLogging;
    protected $registerManualPaymentService;

    // Expected column headers in the Excel file (at least one variation of each must be present)
    protected $expectedHeaders = [
        'rut' => ['rut', 'rut alumno', 'rut alumno (a)'],
        'nro_negocio' => ['nro. negocio', 'nro negocio', 'numero negocio'],
        'monto' => ['monto', 'valor', 'pago y/o dev.', 'pago', 'precio'],
        'fecha_pago' => ['fecha de pago', 'fecha pago', 'fecha'],
        'tipo_pago' => ['tipo de pago', 'tipo pago', 'forma pago', 'forma de pago'],
    ];

    // Optional headers
    protected $optionalHeaders = [
        'Referencia/Comprobante',
        'Notas',
        'Nombre del Participante',
        'Tipo de Documento',
    ];

    public function __construct(RegisterManualPaymentService $registerManualPaymentService)
    {
        $this->registerManualPaymentService = $registerManualPaymentService;
    }

    /**
     * Procesar filas directamente desde el frontend (sin chunks, sin Excel)
     * SIMPLE: Una fila = una inserción
     *
     * @param array $rowsData Array de filas con datos ya mapeados del frontend
     * @param array $columnIndices Índices de columnas (del preview)
     * @return array
     */
    public function processRows(array $rowsData, array $columnIndices): array
    {
        set_time_limit(600); // 10 minutos
        ini_set('memory_limit', '512M');

        $stats = ['successful' => 0, 'duplicates' => 0, 'skipped' => 0, 'failed' => 0];
        $details = [];

        // Pre-cargar gateway y options UNA sola vez
        $paymentGateway = PaymentGateway::where('code', 'presencial')->first();
        $paymentOptions = PaymentOption::whereIn('code', [
            'presential_bank_transfer',
            'presential_deposit',
            'presential_webpay',
            'presential_aporte',
            'presential_pos_office',
            'presential_khipu_link',
            'presential_debit_credit',
            'presential_international',
            'presential_credit_temp',
        ])->get()->keyBy('code');

        $totalRows = count($rowsData);
        Log::info("🚀 PROCESANDO {$totalRows} FILAS DIRECTAMENTE", [
            'total' => $totalRows,
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);

        foreach ($rowsData as $index => $rowItem) {
            $rowNumber = $rowItem['row_number'] ?? ($index + 1);
            $rowData = $rowItem['data'] ?? [];

            if (empty($rowData)) {
                $stats['skipped']++;
                continue;
            }

            try {
                $result = $this->insertPayment($rowData, $rowNumber, $paymentGateway, $paymentOptions);

                if ($result['status'] === 'success') {
                    $stats['successful']++;
                    $rawData = $rowData['raw_row_data'] ?? [];
                    Log::info("💰 PAGO #{$stats['successful']} - Fila {$rowNumber}", [
                        'participante' => $result['data']['participant_name'] ?? 'N/A',
                        'monto' => $rowData['payment_amount'] ?? $rawData['monto'] ?? $rowData['monto'] ?? 'N/A',
                    ]);
                } elseif ($result['status'] === 'updated') {
                    $stats['updated'] = ($stats['updated'] ?? 0) + 1;
                    Log::info("🔄 PAGO ACTUALIZADO - Fila {$rowNumber}", [
                        'payment_id' => $result['data']['existing_payment_id'] ?? 'N/A',
                        'campos' => $result['data']['updated_fields'] ?? [],
                    ]);
                } elseif ($result['status'] === 'duplicate') {
                    $stats['duplicates']++;
                } elseif ($result['status'] === 'skipped') {
                    $stats['skipped']++;
                } else {
                    $stats['failed']++;
                    Log::warning("❌ FALLO Fila {$rowNumber}: " . ($result['message'] ?? 'Error desconocido'));
                }

                $details[] = [
                    'row' => $rowNumber,
                    'status' => $result['status'],
                    'message' => $result['message'] ?? '',
                    'data' => $result['data'] ?? null
                ];

            } catch (\Exception $e) {
                $stats['failed']++;
                Log::error("❌ EXCEPCIÓN Fila {$rowNumber}: " . $e->getMessage());

                $details[] = [
                    'row' => $rowNumber,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'data' => null
                ];
            }
        }

        Log::info("✅ PROCESAMIENTO COMPLETADO", [
            'successful' => $stats['successful'],
            'duplicates' => $stats['duplicates'],
            'skipped' => $stats['skipped'],
            'failed' => $stats['failed'],
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);

        return [
            'stats' => $stats,
            'details' => $details
        ];
    }

    /**
     * Insertar un pago individual - SIMPLE como StorePaymentService
     * Order -> OrderDetail -> Payment -> refreshStatus()
     */
    private function insertPayment(array $rowData, int $rowNumber, ?PaymentGateway $paymentGateway, $paymentOptions): array
    {
        // Los datos pueden venir:
        // 1. Directamente procesados del preview (enrollment_code, payment_amount, etc.)
        // 2. O dentro de raw_row_data (rut, nro_negocio, monto, etc.)
        $rawData = $rowData['raw_row_data'] ?? [];

        // 1. Extraer enrollment_code (datos procesados del preview)
        $enrollmentCode = $rowData['enrollment_code'] ?? null;

        // Si no viene, construirlo desde raw_row_data
        if (empty($enrollmentCode)) {
            $rut = $rawData['rut'] ?? $rowData['rut'] ?? null;
            $nroNegocio = $rawData['nro_negocio'] ?? $rowData['nro_negocio'] ?? null;

            if (!empty($rut) && !empty($nroNegocio)) {
                $cleanRut = str_replace(['.', '-', ' '], '', trim($rut));
                $enrollmentCode = $cleanRut . '-' . trim($nroNegocio);
            }
        }

        if (empty($enrollmentCode)) {
            return [
                'status' => 'error',
                'message' => "Fila {$rowNumber}: Falta código de inscripción",
                'data' => $rowData // Incluir datos para reintento
            ];
        }

        // 2. Extraer monto (preferir payment_amount procesado, sino parsear desde raw)
        $paymentAmount = $rowData['payment_amount'] ?? null;
        if (empty($paymentAmount)) {
            $montoRaw = $rawData['monto'] ?? $rowData['monto'] ?? null;
            if (!empty($montoRaw)) {
                $paymentAmount = $this->parseAmount($montoRaw);
            }
        }
        $paymentAmount = (float) $paymentAmount;

        if ($paymentAmount <= 0) {
            return [
                'status' => 'error',
                'message' => "Fila {$rowNumber}: Monto inválido",
                'data' => $rowData // Incluir datos para reintento
            ];
        }

        // 3. Extraer fecha (preferir payment_date procesado, sino parsear desde raw)
        $paymentDateStr = $rowData['payment_date'] ?? null;
        if (!empty($paymentDateStr)) {
            $paymentDate = Carbon::parse($paymentDateStr);
        } else {
            $fechaRaw = $rawData['fecha_pago'] ?? $rowData['fecha_pago'] ?? null;
            if (!empty($fechaRaw)) {
                $paymentDate = $this->parseDate($fechaRaw);
            } else {
                $paymentDate = Carbon::now();
            }
        }

        // 4. Buscar participante
        $participantProgram = ParticipantProgram::with(['participant', 'programCourse'])
            ->where('enrollment_code', $enrollmentCode)
            ->first();

        if (!$participantProgram || !$participantProgram->participant || !$participantProgram->programCourse) {
            return [
                'status' => 'error',
                'message' => "No se encontró participante con código: {$enrollmentCode}",
                'data' => array_merge($rowData, [
                    'enrollment_code' => $enrollmentCode,
                    'payment_amount' => $paymentAmount,
                    'payment_date' => $paymentDate->format('Y-m-d'),
                ]) // Incluir datos procesados para reintento
            ];
        }

        $participant = $participantProgram->participant;
        $programCourse = $participantProgram->programCourse;

        // 5. Verificar duplicado (usar authorization_code si viene)
        $authorizationCode = $rawData['nro_aut'] ?? $rowData['nro_aut'] ?? $rowData['authorization_code'] ?? null;
        $existingPayment = $this->checkIfPaymentExists(
            $participant->id,
            $programCourse->id,
            $paymentAmount,
            $paymentDate,
            $authorizationCode
        );

        if ($existingPayment) {
            return $this->updateExistingPaymentFields($existingPayment, $rowData, $rawData);
        }

        // 6. Obtener payment option
        $tipoPago = $rawData['tipo_pago'] ?? $rowData['tipo_pago'] ?? $rowData['payment_type'] ?? 'TE';
        $paymentOptionCode = $this->mapPresentialPaymentTypeToOption($tipoPago);
        $paymentOption = $paymentOptions->get($paymentOptionCode);

        // 7. Crear Order
        $order = Order::create([
            'participant_id' => $participant->id,
            'program_id' => $programCourse->id,
            'participant_program_id' => $participantProgram->id,
            'total_amount' => $paymentAmount,
            'discount' => 0,
            'final_amount' => $paymentAmount,
            'total_installments' => 1,
            'payment_type' => 'total',
            'status' => 'pending',
            'order_number' => app(OrderNumberGenerator::class)->generate(),
            'notes' => 'Importación masiva de pagos'
        ]);

        // 8. Crear OrderDetail
        $buyerName = $rowData['buyer_name'] ?? $rawData['contacto_pagador'] ?? $rowData['contacto_pagador'] ?? $participant->full_name;
        $buyerEmail = $rowData['buyer_email'] ?? $participant->email;

        $orderDetail = OrderDetail::create([
            'order_id' => $order->id,
            'payment_option_id' => $paymentOption?->id,
            'payment_gateway_id' => $paymentGateway?->id,
            'name' => $buyerName,
            'email' => $buyerEmail,
            'base_amount' => $paymentAmount,
            'discount_amount' => 0,
            'amount' => $paymentAmount,
            'due_date' => $paymentDate,
            'is_paid' => true,
            'status' => 'paid',
            'paid_at' => $paymentDate,
            'gateway_response' => [
                'created_manually' => true,
                'payment_type' => 'presential',
                'import_row' => $rowNumber
            ]
        ]);

        // 9. Crear Payment
        $referencia = $rawData['referencia'] ?? $rowData['referencia'] ?? $rowData['reference'] ?? null;
        $tipoDocumento = $rawData['tipo_documento'] ?? $rowData['tipo_documento'] ?? null;
        $documentType = $this->resolveDocumentType($tipoDocumento, $paymentOption, $programCourse->id);

        $payment = Payment::create([
            'order_id' => $order->id,
            'order_detail_id' => $orderDetail->id,
            'payment_gateway_id' => $paymentGateway?->id,
            'payment_option_id' => $paymentOption?->id,
            'buy_order' => $order->order_number,
            'amount' => $paymentAmount,
            'status' => 'approved',
            'transaction_date' => $paymentDate,
            'authorization_code' => $authorizationCode,
            // payment_code SIEMPRE guarda la referencia manual ingresada
            // bsale_number se llena SOLO cuando BSale genera la boleta automáticamente
            'payment_code' => $referencia,
            'bsale_number' => null,
            'gateway_response' => [
                'created_manually' => true,
                'payment_type' => 'presential',
                'import_row' => $rowNumber
            ],
            'currency' => 'CLP',
            'document_type' => $documentType,
        ]);

        // 10. Actualizar estado de la orden
        $order->refreshStatus();

        // 11. Manejar APORTE si aplica
        $isAporte = strtoupper(trim($tipoPago)) === 'AP' || ($rowData['is_aporte'] ?? false);
        if ($isAporte) {
            $this->handleAportePayment($participantProgram, $paymentAmount);
        }

        return [
            'status' => 'success',
            'message' => "Pago de $" . number_format($paymentAmount, 0, ',', '.') . " registrado para {$participant->full_name}",
            'data' => [
                'payment_id' => $payment->id,
                'participant_name' => $participant->full_name,
                'program_code' => $programCourse->code ?? $participantProgram->enrollment_code
            ]
        ];
    }

    // Chunk size for processing large files
    protected const CHUNK_SIZE = 100; // Reducido a 100 para commits más frecuentes

    // Maximum details to return (to avoid memory issues)
    protected const MAX_DETAILS = 1000;

    /**
     * Preview the uploaded Excel file WITHOUT inserting into database
     * Shows what would be imported for user confirmation
     *
     * IMPORTANTE: Procesa TODAS las filas del archivo (sin límite) por chunks
     * para que el usuario pueda validar EXACTAMENTE lo que se va a insertar
     */
    public function previewExcel($file): array
    {
        // Aumentar límites de ejecución
        ini_set('max_execution_time', 600); // 10 minutos
        ini_set('memory_limit', '512M');

        $details = [];
        $stats = [
            'successful' => 0,
            'duplicates' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        try {
            // Save the file temporarily
            $filename = uniqid() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('temp', $filename);
            $fullPath = Storage::path($path);

            Log::info('Archivo Excel guardado temporalmente para preview', ['path' => $fullPath]);

            // Load the spreadsheet (sin ReadDataOnly para preservar cache de fórmulas)
            $reader = IOFactory::createReaderForFile($fullPath);
            $spreadsheet = $reader->load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();

            // Get total rows for logging
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();

            Log::info('Archivo Excel cargado para preview', [
                'total_rows' => $highestRow,
                'highest_column' => $highestColumn
            ]);

            // Find header row
            $headerRowIndex = null;
            $headers = [];

            foreach ($worksheet->getRowIterator(1, min(20, $highestRow)) as $rowIndex => $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();
                }

                if ($this->isHeaderRow($rowData)) {
                    $headerRowIndex = $rowIndex;
                    $headers = array_map('trim', $rowData);
                    break;
                }
            }

            if ($headerRowIndex === null) {
                throw new \Exception('No se encontró la fila de encabezados en el archivo Excel. Verifica que contenga las columnas requeridas.');
            }

            // Validate required headers
            $this->validateHeaders($headers);

            // Get column indices
            $columnIndices = $this->getColumnIndices($headers);

            // Process data rows for preview por CHUNKS (NO database inserts)
            $dataStartRow = $headerRowIndex + 1;
            $totalDataRows = $highestRow - $headerRowIndex;
            $currentChunk = [];
            $chunkCount = 0;
            $rowCount = 0;

            Log::info('⏳ Iniciando preview de TODAS las filas por chunks', [
                'data_start_row' => $dataStartRow,
                'total_data_rows' => $totalDataRows,
                'chunk_size' => self::CHUNK_SIZE
            ]);

            foreach ($worksheet->getRowIterator($dataStartRow) as $rowIndex => $row) {
                $cellIterator = $row->getCellIterator('A', $highestColumn);
                $cellIterator->setIterateOnlyExistingCells(false);

                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $this->getCellResolvedValue($cell);
                }

                // Skip empty rows
                if ($this->isEmptyRow($rowData)) {
                    continue;
                }

                $currentChunk[] = [
                    'row_index' => $rowIndex,
                    'data' => $rowData
                ];

                // Process chunk when full
                if (count($currentChunk) >= self::CHUNK_SIZE) {
                    $chunkResult = $this->previewChunk($currentChunk, $columnIndices, $details, $stats);
                    $details = $chunkResult['details'];
                    $stats = $chunkResult['stats'];

                    $chunkCount++;
                    $rowCount += count($currentChunk);

                    Log::info("Preview chunk {$chunkCount} procesado", [
                        'rows_in_chunk' => count($currentChunk),
                        'total_previewed' => $rowCount,
                        'stats' => $stats
                    ]);

                    // Clear chunk and free memory
                    $currentChunk = [];
                    gc_collect_cycles();
                }
            }

            // Process remaining rows
            if (!empty($currentChunk)) {
                $chunkResult = $this->previewChunk($currentChunk, $columnIndices, $details, $stats);
                $details = $chunkResult['details'];
                $stats = $chunkResult['stats'];

                $chunkCount++;
                $rowCount += count($currentChunk);

                Log::info("Preview chunk final {$chunkCount} procesado", [
                    'rows_in_chunk' => count($currentChunk),
                    'total_previewed' => $rowCount,
                    'stats' => $stats
                ]);
            }

            // Clean up
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            Storage::delete($path);
            gc_collect_cycles();

            Log::info('✅ Preview de pagos completado', [
                'stats' => $stats,
                'total_rows_previewed' => $rowCount,
                'chunks_processed' => $chunkCount
            ]);

            return [
                'success' => true,
                'stats' => $stats,
                'details' => $details,
                'total_rows' => $totalDataRows,
                'previewed_rows' => $rowCount,
                'chunks_processed' => $chunkCount,
                'column_indices' => $columnIndices, // Enviar columnIndices al frontend para reutilizar
                'headers' => $headers, // Enviar headers también para referencia
            ];
        } catch (\Exception $e) {
            Log::error('Error al previsualizar archivo Excel: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Preview a chunk of rows WITHOUT database inserts
     * Mismo flujo que processChunk pero SIN transacciones ni inserts
     */
    private function previewChunk(array $chunk, array $columnIndices, array $details, array $stats): array
    {
        try {
            foreach ($chunk as $item) {
                $rowNumber = $item['row_index'];
                $row = $item['data'];

                // Build row data array
                $rowData = [];
                foreach ($columnIndices as $key => $index) {
                    $rowData[$key] = isset($row[$index]) ? trim($row[$index]) : null;
                }

                // Validate and preview this row (NO database insert)
                $result = $this->previewPaymentRow($rowData, $rowNumber);

                // Include raw row data for import later
                $detailData = $result['data'] ?? null;
                if ($detailData && is_array($detailData)) {
                    $detailData['raw_row_data'] = $rowData; // Add raw data
                }

                $details[] = [
                    'row' => $rowNumber,
                    'status' => $result['status'],
                    'message' => $result['message'],
                    'data' => $detailData
                ];

                if ($result['status'] === 'success') {
                    $stats['successful']++;
                } elseif ($result['status'] === 'updated') {
                    $stats['updated'] = ($stats['updated'] ?? 0) + 1;
                } elseif ($result['status'] === 'duplicate') {
                    $stats['duplicates']++;
                } elseif ($result['status'] === 'skipped') {
                    $stats['skipped']++;
                } else {
                    $stats['failed']++;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error en preview chunk: ' . $e->getMessage());

            // Mark all rows in chunk as failed
            $stats['failed'] += count($chunk);
            $details[] = [
                'row' => 'chunk',
                'status' => 'error',
                'message' => 'Error en bloque de filas: ' . $e->getMessage(),
                'data' => null
            ];
        }

        return [
            'details' => $details,
            'stats' => $stats
        ];
    }

    /**
     * Process the uploaded Excel file and import manual payments
     * Uses chunked processing for large files (10k-100k rows)
     *
     * @param $file The uploaded Excel file
     * @param array|null $selectedRows Array of row numbers to import, or null to import all
     */
    public function processExcel($file, ?array $selectedRows = null): array
    {
        // Aumentar límites de ejecución para imports grandes
        set_time_limit(1200); // 20 minutos
        ini_set('max_execution_time', 1200); // 20 minutos
        ini_set('memory_limit', '512M');

        $details = [];
        $stats = [
            'successful' => 0,
            'duplicates' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        // Convert selectedRows array to a Set for O(1) lookup
        $selectedRowsSet = $selectedRows ? array_flip($selectedRows) : null;

        try {
            // Save the file temporarily
            $filename = uniqid() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('temp', $filename);
            $fullPath = Storage::path($path);

            Log::info('Archivo Excel guardado temporalmente', ['path' => $fullPath]);

            // Load the spreadsheet (sin ReadDataOnly para preservar cache de fórmulas)
            $reader = IOFactory::createReaderForFile($fullPath);
            $spreadsheet = $reader->load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();

            // Get total rows for logging
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();

            Log::info('Archivo Excel cargado', [
                'total_rows' => $highestRow,
                'highest_column' => $highestColumn
            ]);

            // Find header row using iterator (only first 20 rows)
            $headerRowIndex = null;
            $headers = [];

            foreach ($worksheet->getRowIterator(1, min(20, $highestRow)) as $rowIndex => $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();
                }

                if ($this->isHeaderRow($rowData)) {
                    $headerRowIndex = $rowIndex;
                    $headers = array_map('trim', $rowData);
                    break;
                }
            }

            if ($headerRowIndex === null) {
                throw new \Exception('No se encontró la fila de encabezados en el archivo Excel. Verifica que contenga las columnas requeridas.');
            }

            Log::info('Encabezados encontrados', ['headers' => $headers, 'row' => $headerRowIndex]);

            // Validate required headers
            $this->validateHeaders($headers);

            // Get column indices
            $columnIndices = $this->getColumnIndices($headers);

            // Process data rows in chunks
            $dataStartRow = $headerRowIndex + 1;
            $currentChunk = [];
            $chunkCount = 0;
            $processedRows = 0;
            $totalDataRows = $highestRow - $headerRowIndex;
            $importStartTime = microtime(true);

            Log::info('⏳ Iniciando importación masiva de pagos', [
                'chunk_size' => self::CHUNK_SIZE,
                'data_start_row' => $dataStartRow,
                'total_data_rows' => $totalDataRows,
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);

            foreach ($worksheet->getRowIterator($dataStartRow) as $rowIndex => $row) {
                $cellIterator = $row->getCellIterator('A', $highestColumn);
                $cellIterator->setIterateOnlyExistingCells(false);

                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $this->getCellResolvedValue($cell);
                }

                // Skip empty rows
                if ($this->isEmptyRow($rowData)) {
                    continue;
                }

                $currentChunk[] = [
                    'row_index' => $rowIndex,
                    'data' => $rowData
                ];

                // Process chunk when full
                if (count($currentChunk) >= self::CHUNK_SIZE) {
                    $chunkResult = $this->processChunk($currentChunk, $columnIndices, $details, $stats, $selectedRowsSet);
                    $details = $chunkResult['details'];
                    $stats = $chunkResult['stats'];

                    $chunkCount++;
                    $processedRows += count($currentChunk);

                    // Calculate progress metrics
                    $progressPercent = round(($processedRows / $totalDataRows) * 100, 1);
                    $elapsedTime = microtime(true) - $importStartTime;
                    $avgTimePerRow = $elapsedTime / $processedRows;
                    $remainingRows = $totalDataRows - $processedRows;
                    $estimatedRemainingTime = $remainingRows * $avgTimePerRow;

                    $totalSelected = $selectedRowsSet ? count($selectedRowsSet) : $totalDataRows;
                    Log::info("✅ PROGRESO: {$stats['successful']} de {$totalSelected} pagos guardados [{$progressPercent}%]", [
                        'chunk' => $chunkCount,
                        'rows_in_chunk' => count($currentChunk),
                        'total_processed' => $processedRows,
                        'progress_percent' => $progressPercent . '%',
                        'pagos_guardados' => $stats['successful'],
                        'pagos_fallidos' => $stats['failed'],
                        'pagos_omitidos' => $stats['skipped'],
                        'tiempo_transcurrido_seg' => round($elapsedTime, 1),
                        'tiempo_estimado_restante_seg' => round($estimatedRemainingTime, 1)
                    ]);

                    // Clear chunk and free memory
                    $currentChunk = [];
                    gc_collect_cycles();
                }
            }

            // Process remaining rows
            if (!empty($currentChunk)) {
                $chunkResult = $this->processChunk($currentChunk, $columnIndices, $details, $stats, $selectedRowsSet);
                $details = $chunkResult['details'];
                $stats = $chunkResult['stats'];

                $chunkCount++;
                $processedRows += count($currentChunk);

                Log::info("Chunk final {$chunkCount} procesado", [
                    'rows_in_chunk' => count($currentChunk),
                    'total_processed' => $processedRows,
                    'stats' => $stats
                ]);
            }

            // Clean up
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            Storage::delete($path);
            gc_collect_cycles();

            // Calculate final metrics
            $totalElapsedTime = microtime(true) - $importStartTime;
            $avgTimePerRow = $processedRows > 0 ? $totalElapsedTime / $processedRows : 0;

            Log::info('🎉 IMPORTACIÓN COMPLETADA', [
                '✅ PAGOS_GUARDADOS' => $stats['successful'],
                '❌ PAGOS_FALLIDOS' => $stats['failed'],
                '⏭️ PAGOS_OMITIDOS' => $stats['skipped'],
                '📊 DUPLICADOS_DETECTADOS' => $stats['duplicates'] ?? 0,
                'chunks_procesados' => $chunkCount,
                'filas_procesadas' => $processedRows,
                'tiempo_total_segundos' => round($totalElapsedTime, 1),
                'tiempo_total_minutos' => round($totalElapsedTime / 60, 1),
                'velocidad_pagos_por_minuto' => round($processedRows / ($totalElapsedTime / 60), 1),
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);

            // Admin logging
            $this->logAction(
                'import',
                'payments',
                "Importación masiva de pagos manuales completada",
                'Payment',
                null,
                null,
                null,
                [
                    'file_name' => $file->getClientOriginalName(),
                    'total_rows_processed' => $stats['successful'] + $stats['skipped'] + $stats['failed'],
                    'successful' => $stats['successful'],
                    'skipped' => $stats['skipped'],
                    'failed' => $stats['failed'],
                    'chunks_processed' => $chunkCount,
                ]
            );

            return [
                'success' => true,
                'stats' => $stats,
                'details' => $details
            ];
        } catch (\Exception $e) {
            Log::error('Error al procesar archivo Excel: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process a chunk of rows with its own transaction
     * OPTIMIZADO: Pre-carga payment gateways/options y cachea montos pagados
     */
    public function processChunk(array $chunk, array $columnIndices, array $details, array $stats, ?array $selectedRowsSet = null): array
    {
        $startTime = microtime(true);

        // Pre-cargar payment gateway y options UNA VEZ
        $paymentGateway = PaymentGateway::where('code', 'presencial')->first();
        $paymentOptions = PaymentOption::whereIn('code', [
            'presential_pos_office',
            'presential_khipu_link',
            'presential_subscription',
            'presential_bank_transfer',
            'presential_debit_credit',
            'presential_international',
            'presential_deposit',
            'presential_webpay',
            'presential_aporte',
            'presential_credit_temp',
        ])->get()->keyBy('code');

        $paidAmountCache = [];
        $rowsProcessed = 0;

        foreach ($chunk as $item) {
                $rowNumber = $item['row_index'];
                $row = $item['data'];

                // Skip row if it's not in the selected rows set
                if ($selectedRowsSet !== null && !isset($selectedRowsSet[$rowNumber])) {
                    continue;
                }

                // Build row data array
                // If data is already mapped (associative array), use it directly
                // Otherwise, map using columnIndices
                if ($this->isAssociativeArray($row)) {
                    $rowData = $row; // Already mapped
                } else {
                    $rowData = [];
                    foreach ($columnIndices as $key => $index) {
                        $rowData[$key] = isset($row[$index]) ? trim($row[$index]) : null;
                    }
                }

                // Process this payment row
                $result = $this->processPaymentRowOptimized(
                    $rowData,
                    $rowNumber,
                    $paymentGateway,
                    $paymentOptions,
                    $paidAmountCache
                );

                // Only store details for non-success results or if under limit
                if ($result['status'] !== 'success' || count($details) < self::MAX_DETAILS) {
                    $details[] = [
                        'row' => $rowNumber,
                        'status' => $result['status'],
                        'message' => $result['message'],
                        'data' => $result['data'] ?? null
                    ];
                }

                if ($result['status'] === 'success') {
                    $stats['successful']++;
                    // Log cada pago exitoso
                    Log::info("💰 PAGO #{$stats['successful']} GUARDADO - Fila {$rowNumber}", [
                        'participante' => $result['data']['participant_name'] ?? 'N/A',
                        'rut' => $rowData['rut'] ?? 'N/A',
                        'monto' => $rowData['monto'] ?? 'N/A',
                        'programa' => $result['data']['program_code'] ?? 'N/A',
                    ]);
                } elseif ($result['status'] === 'updated') {
                    $stats['updated'] = ($stats['updated'] ?? 0) + 1;
                    Log::info("🔄 PAGO ACTUALIZADO - Fila {$rowNumber}", [
                        'payment_id' => $result['data']['existing_payment_id'] ?? 'N/A',
                        'campos' => $result['data']['updated_fields'] ?? [],
                    ]);
                } elseif ($result['status'] === 'duplicate') {
                    $stats['duplicates']++;
                } elseif ($result['status'] === 'skipped') {
                    $stats['skipped']++;
                } else {
                    $stats['failed']++;
                    // Log cada pago fallido
                    Log::warning("❌ PAGO FALLIDO - Fila {$rowNumber}", [
                        'rut' => $rowData['rut'] ?? 'N/A',
                        'error' => $result['message'] ?? 'Error desconocido',
                    ]);
                }

                $rowsProcessed++;
            }

        $elapsedTime = round(microtime(true) - $startTime, 2);
        $avgTimePerRow = $rowsProcessed > 0 ? round($elapsedTime / $rowsProcessed, 2) : 0;

        Log::info("✅ Chunk procesado", [
            'rows_processed' => $rowsProcessed,
            'elapsed_seconds' => $elapsedTime,
            'avg_seconds_per_row' => $avgTimePerRow,
            'successful' => $stats['successful'],
            'failed' => $stats['failed'],
            'skipped' => $stats['skipped']
        ]);

        return [
            'details' => $details,
            'stats' => $stats
        ];
    }

    /**
     * Check if a row is the header row
     */
    private function isHeaderRow(array $row): bool
    {
        $normalizedRow = array_map(function($cell) {
            return strtolower(trim($cell ?? ''));
        }, $row);

        $rowString = implode('|', $normalizedRow);

        $foundHeaders = 0;
        foreach ($this->expectedHeaders as $headerVariations) {
            foreach ($headerVariations as $headerVariation) {
                if (stripos($rowString, $headerVariation) !== false) {
                    $foundHeaders++;
                    break;
                }
            }
        }

        return $foundHeaders >= 4;
    }

    /**
     * Preview a single payment row WITHOUT database insert
     * Returns what would be inserted for user confirmation
     */
    private function previewPaymentRow(array $rowData, int $rowNumber): array
    {
        try {
            // Validate row data
            $validation = $this->validateRowData($rowData, $rowNumber);
            if (!$validation['valid']) {
                return [
                    'status' => 'error',
                    'message' => $validation['error'],
                    'data' => ['row_data' => $rowData]
                ];
            }

            // 1. Build enrollment code from RUT and Nro. Negocio
            $cleanRut = str_replace(['.', '-', ' '], '', trim($rowData['rut']));
            $nroNegocio = trim($rowData['nro_negocio']);
            $enrollmentCode = $cleanRut . '-' . $nroNegocio;

            // 2. Find participant by enrollment code
            $participantProgram = ParticipantProgram::with(['participant', 'programCourse.program'])
                ->where('enrollment_code', $enrollmentCode)
                ->first();

            if (!$participantProgram) {
                return [
                    'status' => 'error',
                    'message' => "No se encontró participante con código de inscripción: {$enrollmentCode}",
                    'data' => ['row_data' => $rowData]
                ];
            }

            $participant = $participantProgram->participant;
            $programCourse = $participantProgram->programCourse;

            if (!$participant || !$programCourse) {
                return [
                    'status' => 'error',
                    'message' => "Datos incompletos para código de inscripción: {$enrollmentCode}",
                    'data' => ['row_data' => $rowData]
                ];
            }

            // 3. Validate no active subscription
            $subscriptionValidation = $this->validateNoActiveSubscription($participant->id, $programCourse->id);
            if (!$subscriptionValidation['valid']) {
                return [
                    'status' => 'skipped',
                    'message' => $subscriptionValidation['message'],
                    'data' => [
                        'row_data' => $rowData,
                        'participant' => $participant->full_name,
                        'program' => $programCourse->name
                    ]
                ];
            }

            // 3.5. CHECK IF PAYMENT ALREADY EXISTS (to avoid duplicates)
            $paymentAmount = $this->parseAmount($rowData['monto']);
            $paymentDate = $this->parseDate($rowData['fecha_pago']);
            $authorizationCode = $rowData['nro_aut'] ?? null;

            $existingPayment = $this->checkIfPaymentExists(
                $participant->id,
                $programCourse->id,
                $paymentAmount,
                $paymentDate,
                $authorizationCode
            );

            if ($existingPayment) {
                // En preview: detectar si hay campos actualizables, sin guardar
                $referencia = $rowData['referencia'] ?? null;
                $hasUpdatableFields = ($referencia && empty($existingPayment->payment_code) && empty($existingPayment->bsale_number));
                return [
                    'status' => $hasUpdatableFields ? 'updated' : 'duplicate',
                    'message' => $hasUpdatableFields
                        ? "Pago existente se actualizará con datos faltantes para {$participant->full_name}"
                        : "Pago ya existe para {$participant->full_name} (${paymentAmount} el {$paymentDate->format('d/m/Y')})",
                    'data' => [
                        'row_data' => $rowData,
                        'enrollment_code' => $enrollmentCode,
                        'participant_name' => $participant->full_name,
                        'participant_rut' => $participant->rut,
                        'program_name' => $programCourse->name,
                        'program_code' => $programCourse->code,
                        'payment_amount' => $paymentAmount,
                        'payment_date' => $paymentDate->format('Y-m-d'),
                        'existing_payment_id' => $existingPayment->id,
                        'reason' => $hasUpdatableFields ? 'update' : 'duplicate'
                    ]
                ];
            }

            // 4. Calculate participant price
            $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
            $totalAmount = (float) $priceData['final_price'];

            // 5. Calculate paid amount
            $paidAmount = $this->calculatePaidAmount($participant->id, $programCourse->id);
            $previousBalance = max($totalAmount - $paidAmount, 0);

            // 6. Parse payment data
            $paymentAmount = $this->parseAmount($rowData['monto']);
            $paymentDate = $this->parseDate($rowData['fecha_pago']);
            $paymentType = $this->mapPaymentType($rowData['tipo_pago']);
            $isAporte = strtoupper(trim($rowData['tipo_pago'])) === 'AP';

            // 7. Calculate new balance
            $newPaidAmount = $paidAmount + $paymentAmount;
            $newBalance = max($totalAmount - $newPaidAmount, 0);

            $paymentTypeLabel = $isAporte ? 'APORTE' : 'Pago';

            // Return preview data WITHOUT inserting
            return [
                'status' => 'success',
                'message' => "{$paymentTypeLabel} de $" . number_format($paymentAmount, 0, ',', '.') .
                    " para {$participant->full_name}. " .
                    "Saldo anterior: $" . number_format($previousBalance, 0, ',', '.') .
                    " → Nuevo saldo: $" . number_format($newBalance, 0, ',', '.'),
                'data' => [
                    'enrollment_code' => $enrollmentCode,
                    'participant_name' => $participant->full_name,
                    'participant_rut' => $participant->rut,
                    'program_name' => $programCourse->name,
                    'program_code' => $programCourse->code,
                    'payment_amount' => $paymentAmount,
                    'payment_date' => $paymentDate->format('Y-m-d'),
                    'payment_type' => $paymentType,
                    'payment_type_label' => $paymentTypeLabel,
                    'is_aporte' => $isAporte,
                    'previous_balance' => $previousBalance,
                    'new_balance' => $newBalance,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'buyer_name' => $rowData['contacto_pagador'] ?? ($participant->first_name . ' ' . $participant->first_last_name),
                    'buyer_email' => $this->validateEmail($rowData['email_contacto_pagador'] ?? null)
                        ? $rowData['email_contacto_pagador']
                        : $participant->email,
                    'reference' => $rowData['referencia'] ?? null,
                    'notes' => $rowData['notas'] ?? null,
                    'document_type' => $this->resolveDocumentType($rowData['tipo_documento'] ?? null, null, $programCourse->id),
                    'document_type_label' => $this->getDocumentTypeLabel($this->resolveDocumentType($rowData['tipo_documento'] ?? null, null, $programCourse->id)),
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Error previsualizando fila {$rowNumber}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'row_data' => $rowData
            ]);

            return [
                'status' => 'error',
                'message' => "Error en fila {$rowNumber}: " . $e->getMessage(),
                'data' => ['row_data' => $rowData]
            ];
        }
    }

    /**
     * Process a single payment row - Similar to CreateParticularPaymentService
     */
    private function processPaymentRow(array $rowData, int $rowNumber): array
    {
        try {
            // Validate row data
            $validation = $this->validateRowData($rowData, $rowNumber);
            if (!$validation['valid']) {
                return [
                    'status' => 'error',
                    'message' => $validation['error'],
                    'data' => ['row_data' => $rowData]
                ];
            }

            // 1. Build enrollment code from RUT and Nro. Negocio
            $cleanRut = str_replace(['.', '-', ' '], '', trim($rowData['rut']));
            $nroNegocio = trim($rowData['nro_negocio']);
            $enrollmentCode = $cleanRut . '-' . $nroNegocio;

            Log::info("Procesando fila {$rowNumber}", [
                'enrollment_code' => $enrollmentCode,
                'monto' => $rowData['monto']
            ]);

            // 2. Find participant by enrollment code
            $participantProgram = ParticipantProgram::with(['participant', 'programCourse.program'])
                ->where('enrollment_code', $enrollmentCode)
                ->first();

            if (!$participantProgram) {
                return [
                    'status' => 'error',
                    'message' => "No se encontró participante con código de inscripción: {$enrollmentCode}",
                    'data' => ['row_data' => $rowData]
                ];
            }

            $participant = $participantProgram->participant;
            $programCourse = $participantProgram->programCourse;

            if (!$participant || !$programCourse) {
                return [
                    'status' => 'error',
                    'message' => "Datos incompletos para código de inscripción: {$enrollmentCode}",
                    'data' => ['row_data' => $rowData]
                ];
            }

            // 3. Validate no active subscription (same as CreateParticularPaymentService)
            $subscriptionValidation = $this->validateNoActiveSubscription($participant->id, $programCourse->id);
            if (!$subscriptionValidation['valid']) {
                return [
                    'status' => 'skipped',
                    'message' => $subscriptionValidation['message'],
                    'data' => [
                        'row_data' => $rowData,
                        'participant' => $participant->full_name,
                        'program' => $programCourse->name
                    ]
                ];
            }

            // 4. Calculate participant price using ParticipantPriceHelper (includes discounts, contributions)
            $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
            $totalAmount = (float) $priceData['final_price'];
            $discounts = (float) $priceData['discounts'];
            $basePrice = (float) $priceData['base_price'];

            // 5. Calculate paid amount
            $paidAmount = $this->calculatePaidAmount($participant->id, $programCourse->id);
            $previousBalance = max($totalAmount - $paidAmount, 0);

            // 6. Parse payment data
            $paymentAmount = $this->parseAmount($rowData['monto']);
            $paymentDate = $this->parseDate($rowData['fecha_pago']);
            $paymentType = $this->mapPaymentType($rowData['tipo_pago']);

            Log::info("Montos calculados para {$participant->full_name}", [
                'base_price' => $basePrice,
                'discounts' => $discounts,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'previous_balance' => $previousBalance,
                'payment_amount' => $paymentAmount
            ]);

            // 7. Validate payment amount against balance
            if ($paymentAmount > $previousBalance && $previousBalance > 0) {
                // Solo advertir si el monto excede significativamente (más de $100 de diferencia)
                if (($paymentAmount - $previousBalance) > 100) {
                    Log::warning("Monto de pago excede saldo pendiente", [
                        'payment_amount' => $paymentAmount,
                        'previous_balance' => $previousBalance,
                        'difference' => $paymentAmount - $previousBalance,
                        'participant' => $participant->full_name
                    ]);
                }
            }

            // 8. Get payment gateway and option
            $paymentGateway = PaymentGateway::where('code', 'presencial')->first();
            $paymentOptionCode = $this->mapPresentialPaymentTypeToOption($rowData['tipo_pago']);
            $paymentOption = PaymentOption::where('code', $paymentOptionCode)->first();

            // 9. Find or create the order
            $order = $this->findOrCreateOrder($participant, $programCourse, $participantProgram, $totalAmount, $discounts);

            // 10. Create order detail with buyer data
            $buyerName = $rowData['contacto_pagador'] ?? ($participant->first_name . ' ' . $participant->first_last_name);
            $buyerEmail = $this->validateEmail($rowData['email_contacto_pagador'] ?? null)
                ? $rowData['email_contacto_pagador']
                : $participant->email;

            $orderDetail = OrderDetail::create([
                'order_id' => $order->id,
                'payment_option_id' => $paymentOption?->id,
                'payment_gateway_id' => $paymentGateway?->id,
                'name' => $buyerName,
                'email' => $buyerEmail,
                'country' => null,
                'region' => null,
                'city' => null,
                'code_phone' => null,
                'phone' => null,
                'document_type' => null,
                'document_number' => null,
                'installment_number' => null,
                'installments_number' => $rowData['cuotas'] ?? null,
                'base_amount' => $paymentAmount,
                'discount_amount' => 0,
                'amount' => $paymentAmount,
                'due_date' => $paymentDate,
                'is_paid' => true,
                'status' => 'paid',
                'paid_at' => $paymentDate,
                'gateway_response' => [
                    'notes' => $rowData['notas'] ?? null,
                    'created_manually' => true,
                    'payment_type' => 'presential',
                    'import_row' => $rowNumber
                ]
            ]);

            // 11. Create payment
            $existingPaymentsCount = Payment::where('order_id', $order->id)->count();
            $paymentNumber = $existingPaymentsCount + 1;
            $referencia = $rowData['referencia'] ?? null;
            $tipoDocumento = $rowData['tipo_documento'] ?? null;
            $documentType = $this->resolveDocumentType($tipoDocumento, $paymentOption, $programCourse->id);

            $payment = Payment::create([
                'order_id' => $order->id,
                'order_detail_id' => $orderDetail->id,
                'payment_gateway_id' => $paymentGateway?->id,
                'payment_option_id' => $paymentOption?->id,
                'buy_order' => $order->order_number . '-P' . $paymentNumber,
                'amount' => $paymentAmount,
                'status' => 'approved',
                'transaction_date' => $paymentDate,
                'authorization_code' => $rowData['nro_aut'] ?? null,
                // Si es B2 (boleta), guardar en bsale_number; sino en payment_code
                'payment_code' => $documentType !== 'B2' ? $referencia : null,
                'bsale_number' => $documentType === 'B2' ? $referencia : null,
                'installments_number' => $rowData['cuotas'] ?? null,
                'installment_amount' => isset($rowData['cuotas']) && $rowData['cuotas'] > 0 ? round($paymentAmount / $rowData['cuotas'], 2) : null,
                'gateway_response' => [
                    'notes' => $rowData['notas'] ?? null,
                    'created_manually' => true,
                    'payment_type' => 'presential',
                    'import_row' => $rowNumber,
                    'buyer_data' => [
                        'full_name' => $buyerName,
                        'email' => $buyerEmail,
                    ]
                ],
                'currency' => 'CLP',
                'document_type' => $documentType,
            ]);

            // 12. Handle APORTE (AP) - Update contribution field
            $isAporte = strtoupper(trim($rowData['tipo_pago'])) === 'AP';
            if ($isAporte) {
                $this->handleAportePayment($participantProgram, $paymentAmount);
            }

            // 13. Handle installment restructure (same as CreateParticularPaymentService)
            $this->handleInstallmentRestructure($participant, $programCourse, $paymentAmount);

            // 14. Handle subscription adjustment if needed
            $this->handleSubscriptionAdjustment($participant->id, $programCourse->id, $paymentAmount);

            // 15. Update order status
            $order->refreshStatus();

            $newPaidAmount = $paidAmount + $paymentAmount;
            $newBalance = max($totalAmount - $newPaidAmount, 0);

            $paymentTypeLabel = $isAporte ? 'APORTE' : 'Pago';

            Log::info("✅ {$paymentTypeLabel} importado exitosamente", [
                'payment_id' => $payment->id,
                'participant' => $participant->full_name,
                'amount' => $paymentAmount,
                'is_aporte' => $isAporte,
                'new_paid_amount' => $newPaidAmount,
                'new_balance' => $newBalance
            ]);

            return [
                'status' => 'success',
                'message' => "{$paymentTypeLabel} de $" . number_format($paymentAmount, 0, ',', '.') .
                    " registrado para {$participant->full_name}. " .
                    "Saldo anterior: $" . number_format($previousBalance, 0, ',', '.') .
                    " → Nuevo saldo: $" . number_format($newBalance, 0, ',', '.'),
                'data' => [
                    'payment_id' => $payment->id,
                    'participant' => $participant->full_name,
                    'amount' => $paymentAmount,
                    'is_aporte' => $isAporte,
                    'previous_balance' => $previousBalance,
                    'new_balance' => $newBalance
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Error procesando fila {$rowNumber}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'row_data' => $rowData
            ]);

            return [
                'status' => 'error',
                'message' => "Error en fila {$rowNumber}: " . $e->getMessage(),
                'data' => ['row_data' => $rowData]
            ];
        }
    }

    /**
     * Process a single payment row - VERSIÓN OPTIMIZADA
     * Usa datos pre-cargados (payment gateway/options) y caché de montos pagados
     *
     * @param array $rowData Datos de la fila del Excel
     * @param int $rowNumber Número de fila
     * @param PaymentGateway|null $paymentGateway Gateway pre-cargado
     * @param \Illuminate\Support\Collection $paymentOptions Opciones de pago pre-cargadas (keyBy code)
     * @param array $paidAmountCache Caché de montos pagados (referencia)
     */
    /**
     * Procesar una fila de pago - SIMPLIFICADO igual que StorePaymentService
     * Solo crea: Order -> OrderDetail -> Payment (sin calcular precios ni reestructurar cuotas)
     */
    private function processPaymentRowOptimized(
        array $rowData,
        int $rowNumber,
        ?PaymentGateway $paymentGateway,
        $paymentOptions,
        array &$paidAmountCache
    ): array {
        try {
            // Timeout corto para evitar bloqueos infinitos
            DB::statement("SET SESSION innodb_lock_wait_timeout = 5");

            // 1. Validar datos mínimos
            if (empty($rowData['rut']) || empty($rowData['nro_negocio']) || empty($rowData['monto'])) {
                return [
                    'status' => 'error',
                    'message' => "Fila {$rowNumber}: Faltan datos obligatorios (RUT, Nro Negocio o Monto)",
                    'data' => ['row_data' => $rowData]
                ];
            }

            // 2. Construir enrollment code
            $cleanRut = str_replace(['.', '-', ' '], '', trim($rowData['rut']));
            $nroNegocio = trim($rowData['nro_negocio']);
            $enrollmentCode = $cleanRut . '-' . $nroNegocio;

            // 3. Buscar participante
            $participantProgram = ParticipantProgram::with(['participant', 'programCourse'])
                ->where('enrollment_code', $enrollmentCode)
                ->first();

            if (!$participantProgram || !$participantProgram->participant || !$participantProgram->programCourse) {
                return [
                    'status' => 'error',
                    'message' => "No se encontró participante con código: {$enrollmentCode}",
                    'data' => ['row_data' => $rowData]
                ];
            }

            $participant = $participantProgram->participant;
            $programCourse = $participantProgram->programCourse;

            // 4. Parsear datos del pago
            $paymentAmount = $this->parseAmount($rowData['monto']);
            $paymentDate = $this->parseDate($rowData['fecha_pago']);
            $authorizationCode = $rowData['nro_aut'] ?? null;

            // 5. Verificar duplicado
            $existingPayment = $this->checkIfPaymentExists(
                $participant->id,
                $programCourse->id,
                $paymentAmount,
                $paymentDate,
                $authorizationCode
            );

            if ($existingPayment) {
                return $this->updateExistingPaymentFields($existingPayment, $rowData);
            }

            // 6. Obtener payment option
            $paymentOptionCode = $this->mapPresentialPaymentTypeToOption($rowData['tipo_pago'] ?? 'TE');
            $paymentOption = $paymentOptions->get($paymentOptionCode);

            // 7. Crear Order (igual que StorePaymentService - siempre nueva)
            $order = Order::create([
                'participant_id' => $participant->id,
                'program_id' => $programCourse->id,
                'participant_program_id' => $participantProgram->id,
                'total_amount' => $paymentAmount,
                'discount' => 0,
                'final_amount' => $paymentAmount,
                'total_installments' => 1,
                'payment_type' => 'total',
                'status' => 'pending',
                'order_number' => app(\App\Services\Shared\OrderNumberGenerator::class)->generate(),
                'notes' => 'Orden creada desde importación masiva de pagos'
            ]);

            // 8. Crear OrderDetail
            $buyerName = $rowData['contacto_pagador'] ?? $participant->full_name;

            $orderDetail = OrderDetail::create([
                'order_id' => $order->id,
                'payment_option_id' => $paymentOption?->id,
                'payment_gateway_id' => $paymentGateway?->id,
                'name' => $buyerName,
                'email' => $participant->email,
                'base_amount' => $paymentAmount,
                'discount_amount' => 0,
                'amount' => $paymentAmount,
                'due_date' => $paymentDate,
                'is_paid' => true,
                'status' => 'paid',
                'paid_at' => $paymentDate,
                'gateway_response' => [
                    'created_manually' => true,
                    'payment_type' => 'presential',
                    'import_row' => $rowNumber
                ]
            ]);

            // 9. Crear Payment
            $referencia = $rowData['referencia'] ?? null;
            $tipoDocumento = $rowData['tipo_documento'] ?? null;
            $documentType = $this->resolveDocumentType($tipoDocumento, $paymentOption, $programCourse->id);

            $payment = Payment::create([
                'order_id' => $order->id,
                'order_detail_id' => $orderDetail->id,
                'payment_gateway_id' => $paymentGateway?->id,
                'payment_option_id' => $paymentOption?->id,
                'buy_order' => $order->order_number,
                'amount' => $paymentAmount,
                'status' => 'approved',
                'transaction_date' => $paymentDate,
                'authorization_code' => $authorizationCode,
                // Si es B2 (boleta), guardar en bsale_number; sino en payment_code
                'payment_code' => $documentType !== 'B2' ? $referencia : null,
                'bsale_number' => $documentType === 'B2' ? $referencia : null,
                'gateway_response' => [
                    'created_manually' => true,
                    'payment_type' => 'presential',
                    'import_row' => $rowNumber
                ],
                'currency' => 'CLP',
                'document_type' => $documentType,
            ]);

            // 10. Actualizar estado de la orden
            $order->refreshStatus();

            // 11. Manejar APORTE si aplica
            if (strtoupper(trim($rowData['tipo_pago'] ?? '')) === 'AP') {
                $this->handleAportePayment($participantProgram, $paymentAmount);
            }

            return [
                'status' => 'success',
                'message' => "Pago registrado para {$participant->full_name}",
                'data' => [
                    'payment_id' => $payment->id,
                    'participant_name' => $participant->full_name,
                    'program_code' => $nroNegocio
                ]
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => "Error fila {$rowNumber}: " . $e->getMessage(),
                'data' => ['row_data' => $rowData]
            ];
        }
    }

    /**
     * Validate no active subscription (similar to CreateParticularPaymentService)
     */
    private function validateNoActiveSubscription(int $participantId, int $programId): array
    {
        $activeSubscription = ProgramSubscription::where('participant_id', $participantId)
            ->where('program_id', $programId)
            ->whereIn('status', ['ACTIVA', 'SUSCRIBIENDO'])
            ->first();

        if (!$activeSubscription) {
            return ['valid' => true];
        }

        // Check for failed charges (can allow payment in this case)
        $failedCharges = \App\Models\ChargeAttempt::where('program_subscription_id', $activeSubscription->id)
            ->where('status', 'failed')
            ->whereHas('installment', function ($query) {
                $query->whereIn('status', ['pending', 'overdue']);
            })
            ->count();

        if ($failedCharges === 0) {
            return [
                'valid' => false,
                'message' => "Participante tiene suscripción activa sin cobros rechazados - omitido"
            ];
        }

        // Has failed charges, allow presential payment
        return ['valid' => true];
    }

    /**
     * Calculate paid amount for participant
     * OPTIMIZADO: Usa JOIN en lugar de whereHas para mejor rendimiento con índices
     */
    private function calculatePaidAmount(int $participantId, int $programId): float
    {
        return (float) Payment::join('orders', 'payments.order_id', '=', 'orders.id')
            ->where('orders.participant_id', $participantId)
            ->where('orders.program_id', $programId)
            ->whereIn('payments.status', ['completed', 'approved'])
            ->sum('payments.amount');
    }

    /**
     * Calculate paid amount for participant (versión con caché)
     * Usa el índice compuesto idx_orders_participant_program_date
     */
    private function calculatePaidAmountCached(int $participantId, int $programId, array &$cache): float
    {
        $cacheKey = "{$participantId}_{$programId}";

        if (!isset($cache[$cacheKey])) {
            $cache[$cacheKey] = $this->calculatePaidAmount($participantId, $programId);
        }

        return $cache[$cacheKey];
    }

    /**
     * Find or create order for participant
     */
    private function findOrCreateOrder(Participant $participant, ProgramCourse $programCourse, ParticipantProgram $participantProgram, float $totalAmount, float $discounts): Order
    {
        // Try to find existing order
        $order = Order::where('participant_id', $participant->id)
            ->where('program_id', $programCourse->id)
            ->first();

        if ($order) {
            return $order;
        }

        // Create new order
        return Order::create([
            'participant_id' => $participant->id,
            'program_id' => $programCourse->id,
            'participant_program_id' => $participantProgram->id,
            'total_amount' => $totalAmount + $discounts, // Base price
            'discount' => $discounts,
            'final_amount' => $totalAmount,
            'total_installments' => 1,
            'payment_type' => 'total',
            'status' => 'pending',
            'order_number' => app(OrderNumberGenerator::class)->generate(),
            'notes' => 'Orden creada desde importación masiva de pagos'
        ]);
    }

    /**
     * Handle installment restructure after payment (same as CreateParticularPaymentService)
     */
    private function handleInstallmentRestructure(Participant $participant, ProgramCourse $programCourse, float $paymentAmount): void
    {
        $installmentPlan = InstallmentPlan::where('participant_id', $participant->id)
            ->where('program_id', $programCourse->id)
            ->where('status', 'active')
            ->first();

        if (!$installmentPlan) {
            return;
        }

        // Calculate current amounts using ParticipantPriceHelper
        $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
        $totalAmount = (float) $priceData['final_price'];
        $paidAmount = $this->calculatePaidAmount($participant->id, $programCourse->id);
        $remainingBalance = max($totalAmount - $paidAmount, 0);

        if ($remainingBalance <= 0) {
            // Fully paid - mark all pending installments as paid
            $installmentPlan->installments()
                ->where('status', 'pending')
                ->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'notes' => 'Marcada como pagada por importación masiva'
                ]);

            $installmentPlan->update([
                'status' => 'completed',
                'end_date' => now()
            ]);

            Log::info('Plan de cuotas completado por importación masiva', [
                'installment_plan_id' => $installmentPlan->id,
                'participant_id' => $participant->id
            ]);
            return;
        }

        // Get pending installments
        $pendingInstallments = $installmentPlan->installments()
            ->where('status', 'pending')
            ->orderBy('installment_number')
            ->get();

        if ($pendingInstallments->isEmpty()) {
            return;
        }

        // Redistribute remaining balance: solo .6+ hacia arriba, última cuota absorbe residuo
        $installmentCount = $pendingInstallments->count();
        $remainingBalance = (int) round($remainingBalance);
        $baseAmount = (int) floor(($remainingBalance / $installmentCount) + 0.4);

        // Última cuota absorbe el residuo
        $allocated = $baseAmount * ($installmentCount - 1);
        $lastAmount = $remainingBalance - $allocated;

        foreach ($pendingInstallments as $index => $installment) {
            // Última cuota absorbe el residuo
            $newAmount = ($index === $installmentCount - 1) ? $lastAmount : $baseAmount;

            $installment->update([
                'amount' => $newAmount,
                'adjusted_at' => now(),
                'adjustment_reason' => 'Reestructuración por importación masiva de pagos'
            ]);
        }

        Log::info('Cuotas reestructuradas por importación masiva', [
            'installment_plan_id' => $installmentPlan->id,
            'remaining_balance' => $remainingBalance,
            'pending_installments' => $installmentCount
        ]);
    }

    /**
     * Handle installment restructure after payment - VERSIÓN OPTIMIZADA
     * NO duplica las llamadas a calculateParticipantPrice y calculatePaidAmount
     *
     * @param Participant $participant
     * @param ProgramCourse $programCourse
     * @param float $paymentAmount Monto del pago actual
     * @param float $totalAmount Total adeudado (ya calculado)
     * @param float $previousPaidAmount Monto ya pagado ANTES de este pago
     * @param array $paidAmountCache Caché de montos pagados (referencia)
     */
    private function handleInstallmentRestructureOptimized(
        Participant $participant,
        ProgramCourse $programCourse,
        float $paymentAmount,
        float $totalAmount,
        float $previousPaidAmount,
        array &$paidAmountCache
    ): void {
        $installmentPlan = InstallmentPlan::where('participant_id', $participant->id)
            ->where('program_id', $programCourse->id)
            ->where('status', 'active')
            ->first();

        if (!$installmentPlan) {
            return;
        }

        // OPTIMIZADO: Usar valores ya calculados en lugar de recalcular
        $newPaidAmount = $previousPaidAmount + $paymentAmount;
        $remainingBalance = max($totalAmount - $newPaidAmount, 0);

        if ($remainingBalance <= 0) {
            // Fully paid - OPTIMIZADO: Batch update en lugar de loop
            $installmentPlan->installments()
                ->where('status', 'pending')
                ->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'notes' => 'Marcada como pagada por importación masiva'
                ]);

            $installmentPlan->update([
                'status' => 'completed',
                'end_date' => now()
            ]);

            Log::info('Plan de cuotas completado por importación masiva', [
                'installment_plan_id' => $installmentPlan->id,
                'participant_id' => $participant->id
            ]);
            return;
        }

        // Get pending installments
        $pendingInstallments = $installmentPlan->installments()
            ->where('status', 'pending')
            ->orderBy('installment_number')
            ->get();

        if ($pendingInstallments->isEmpty()) {
            return;
        }

        // Redistribute remaining balance: solo .6+ hacia arriba, última cuota absorbe residuo
        $installmentCount = $pendingInstallments->count();
        $remainingBalance = (int) round($remainingBalance);
        $baseAmount = (int) floor(($remainingBalance / $installmentCount) + 0.4);

        // Última cuota absorbe el residuo
        $allocated = $baseAmount * ($installmentCount - 1);
        $lastAmount = $remainingBalance - $allocated;

        // OPTIMIZADO: Preparar datos para batch update
        $installmentUpdates = [];
        foreach ($pendingInstallments as $index => $installment) {
            // Última cuota absorbe el residuo
            $newAmount = ($index === $installmentCount - 1) ? $lastAmount : $baseAmount;
            $installmentUpdates[$installment->id] = $newAmount;
        }

        // OPTIMIZADO: Batch update usando query builder en lugar de loop individual
        foreach ($installmentUpdates as $installmentId => $newAmount) {
            Installment::where('id', $installmentId)->update([
                'amount' => $newAmount,
                'adjusted_at' => now(),
                'adjustment_reason' => 'Reestructuración por importación masiva de pagos'
            ]);
        }

        Log::info('Cuotas reestructuradas por importación masiva', [
            'installment_plan_id' => $installmentPlan->id,
            'remaining_balance' => $remainingBalance,
            'pending_installments' => $installmentCount
        ]);
    }

    /**
     * Handle APORTE payment - Update contribution field in participant_program
     */
    private function handleAportePayment(ParticipantProgram $participantProgram, float $paymentAmount): void
    {
        // Get current contribution and add the new aporte
        $currentContribution = (float) ($participantProgram->contribution ?? 0);
        $newContribution = $currentContribution + $paymentAmount;

        $participantProgram->update([
            'contribution' => $newContribution
        ]);

        Log::info('Aporte registrado y actualizado en participant_program', [
            'participant_program_id' => $participantProgram->id,
            'previous_contribution' => $currentContribution,
            'aporte_amount' => $paymentAmount,
            'new_contribution' => $newContribution
        ]);
    }

    /**
     * Handle subscription adjustment (same as CreateParticularPaymentService)
     */
    private function handleSubscriptionAdjustment(int $participantId, int $programId, float $paymentAmount): void
    {
        try {
            $recalculationService = app(SubscriptionRecalculationService::class);

            $result = $recalculationService->processPaymentWithSubscriptionAdjustment(
                $participantId,
                $programId,
                $paymentAmount,
                'payment'
            );

            if ($result['has_subscription'] && $result['subscription_cancelled']) {
                Log::info('Suscripción ajustada por importación masiva', [
                    'participant_id' => $participantId,
                    'program_id' => $programId,
                    'old_subscription_id' => $result['old_subscription_id'] ?? null,
                    'new_subscription_id' => $result['new_subscription_id'] ?? null
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Error manejando ajuste de suscripción en importación masiva', [
                'participant_id' => $participantId,
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);
            // Don't throw - payment was already processed
        }
    }

    /**
     * Validate that all required headers are present
     */
    private function validateHeaders(array $headers): void
    {
        $missingHeaders = [];

        $normalizedHeaders = array_map(function($header) {
            return strtolower(trim($header));
        }, $headers);

        foreach ($this->expectedHeaders as $field => $headerVariations) {
            $found = false;

            foreach ($headerVariations as $headerVariation) {
                foreach ($normalizedHeaders as $normalizedHeader) {
                    if ($normalizedHeader === $headerVariation ||
                        stripos($normalizedHeader, $headerVariation) !== false ||
                        stripos($headerVariation, $normalizedHeader) !== false) {
                        $found = true;
                        break 2;
                    }
                }
            }

            if (!$found) {
                $missingHeaders[] = ucfirst($headerVariations[0]);
            }
        }

        if (!empty($missingHeaders)) {
            throw new \Exception('Faltan columnas requeridas en el archivo: ' . implode(', ', $missingHeaders));
        }
    }

    /**
     * Get column indices for each field
     */
    public function getColumnIndices(array $headers): array
    {
        $indices = [];

        $normalizedHeaders = array_map(function($header) {
            return strtolower(trim($header));
        }, $headers);

        $fieldMappings = [
            'rut' => ['rut', 'rut alumno', 'rut alumno (a)', 'rut_alumno', 'rut participante'],
            'nro_negocio' => ['nro. negocio', 'nro negocio', 'numero negocio', 'numero de negocio'],
            'nro_aut' => ['nro. aut.', 'nro aut', 'nro. aut', 'numero autorizacion', 'num. aut.', 'nro. autorización', 'nro autorizacion', 'nro. autorizacion'],
            'monto' => ['monto', 'valor', 'pago y/o dev.', 'pago y/o dev', 'pago', 'precio'],
            'fecha_pago' => ['fecha de pago', 'fecha pago', 'fecha_pago', 'fecha', 'fecha de pag'],
            'tipo_pago' => ['tipo de pago', 'tipo pago', 'tipo_pago', 'tipo', 'forma pago', 'forma de pago'],
            'referencia' => ['referencia/comprobante', 'referencia', 'comprobante', 'nro. boleta', 'nro boleta', 'numero boleta', 'num. boleta'],
            'notas' => ['notas', 'observaciones'],
            'nombre' => ['nombre del participante', 'nombre participante', 'nombre', 'alumno (a)', 'alumno'],
            'contacto_pagador' => ['contacto pagador', 'nombre pagador', 'pagador'],
            'email_contacto_pagador' => ['email contacto pagador', 'email pagador', 'correo pagador', 'correo contacto pagador'],
            'cuotas' => ['# cuotas', 'cuotas', 'numero cuotas', 'nro cuotas', 'nro. cuotas'],
            'tipo_documento' => ['tipo de documento', 'tipo documento', 'tipo doc', 'documento fiscal', 'document type'],
        ];

        foreach ($fieldMappings as $field => $possibleHeaders) {
            foreach ($normalizedHeaders as $index => $normalizedHeader) {
                foreach ($possibleHeaders as $possibleHeader) {
                    if ($normalizedHeader === $possibleHeader ||
                        stripos($normalizedHeader, $possibleHeader) !== false ||
                        stripos($possibleHeader, $normalizedHeader) !== false) {
                        $indices[$field] = $index;
                        break 2;
                    }
                }
            }
        }

        return $indices;
    }

    /**
     * Validate row data
     */
    private function validateRowData(array $data, int $rowNumber): array
    {
        if (empty($data['rut'])) {
            return ['valid' => false, 'error' => "RUT vacío en fila {$rowNumber}"];
        }

        if (empty($data['nro_negocio'])) {
            return ['valid' => false, 'error' => "Número de negocio vacío en fila {$rowNumber}"];
        }

        if (empty($data['monto'])) {
            return ['valid' => false, 'error' => "Monto vacío en fila {$rowNumber}"];
        }

        $amount = $this->parseAmount($data['monto']);
        if ($amount <= 0) {
            return ['valid' => false, 'error' => "El monto debe ser mayor a cero en fila {$rowNumber}"];
        }

        if (empty($data['fecha_pago'])) {
            return ['valid' => false, 'error' => "Fecha de pago vacía en fila {$rowNumber}"];
        }

        if (empty($data['tipo_pago'])) {
            return ['valid' => false, 'error' => "Tipo de pago vacío en fila {$rowNumber}"];
        }

        return ['valid' => true];
    }

    /**
     * Parse amount from various formats.
     * IMPORTANTE: Verificar formato chileno (puntos como miles) ANTES de is_numeric,
     * porque "777.311" es is_numeric=true pero en CLP significa 777.311 (777 mil 311).
     */
    private function parseAmount($value): float
    {
        // Convertir a string para analizar el formato
        $stringValue = trim((string) $value);

        // Remove currency symbols and spaces
        $cleaned = preg_replace('/[^0-9,.\-]/', '', $stringValue);

        // Handle Chilean format: 777.311 or 1.234.567 or 1.234.567,89 (dot as thousands separator)
        // MUST be checked BEFORE is_numeric to avoid treating 777.311 as a decimal
        if (preg_match('/^\-?\d{1,3}(\.\d{3})+(,\d+)?$/', $cleaned)) {
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
            return (float) $cleaned;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        // Handle standard format: 1,234,567.89
        if (preg_match('/^\-?\d{1,3}(,\d{3})*(\.\d+)?$/', $cleaned)) {
            $cleaned = str_replace(',', '', $cleaned);
        }
        // Simple format with comma as decimal
        elseif (strpos($cleaned, ',') !== false && strpos($cleaned, '.') === false) {
            $cleaned = str_replace(',', '.', $cleaned);
        }

        return (float) $cleaned;
    }

    /**
     * Parse date from Excel
     */
    private function parseDate($dateValue): Carbon
    {
        if (is_numeric($dateValue)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject($dateValue));
        }

        try {
            if (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/', $dateValue, $matches)) {
                return Carbon::createFromFormat('d/m/Y', str_replace('-', '/', $dateValue));
            }

            if (preg_match('/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})$/', $dateValue)) {
                return Carbon::parse($dateValue);
            }

            return Carbon::parse($dateValue);
        } catch (\Exception $e) {
            Log::warning("No se pudo parsear fecha: {$dateValue}, usando fecha actual");
            return Carbon::now();
        }
    }

    /**
     * Map payment type from report codes to manual payment sources
     */
    private function mapPaymentType(string $type): string
    {
        $type = strtoupper(trim($type));

        $mappings = [
            'TC' => 'manual_card_office',
            'KP' => 'manual_transfer',
            'PAT' => 'manual_subscription',
            'TE' => 'manual_transfer',
            'VP' => 'manual_card',
            'VPI' => 'manual_international',
            'DP' => 'manual_deposit',
            'WP' => 'manual_webpay',
            'BX' => 'manual_card_office',
            'AP' => 'manual_aporte',
            'CT' => 'manual_credit_temp',
        ];

        return $mappings[$type] ?? 'manual_card';
    }

    /**
     * Map presential payment type to payment option code
     */
    private function mapPresentialPaymentTypeToOption(string $type): string
    {
        $type = strtoupper(trim($type));

        $mapping = [
            'TC' => 'presential_pos_office',
            'KP' => 'presential_khipu_link',
            'PAT' => 'presential_subscription',
            'TE' => 'presential_bank_transfer',
            'VP' => 'presential_debit_credit',
            'VPI' => 'presential_international',
            'DP' => 'presential_deposit',
            'WP' => 'presential_webpay',
            'BX' => 'presential_pos_office',
            'AP' => 'presential_aporte',
            'CT' => 'presential_credit_temp',
        ];

        return $mapping[$type] ?? 'presential_pos_office';
    }

    /**
     * Resuelve el tipo de documento fiscal para un pago.
     * CT siempre gana, luego override del usuario, luego auto-determinación.
     */
    private function resolveDocumentType(?string $userDocType, ?PaymentOption $paymentOption, int $programCourseId): string
    {
        // CT (Crédito Temporal) siempre fuerza CT
        if ($paymentOption?->code === 'presential_credit_temp') {
            return 'CT';
        }

        // Si el usuario especificó un tipo de documento, usarlo
        if (!empty($userDocType)) {
            $mapped = PaymentDocumentTypeHelper::mapUserDocumentType($userDocType);
            if ($mapped) {
                return $mapped;
            }
        }

        // Fallback: auto-determinación por año del programa
        return PaymentDocumentTypeHelper::determineDocumentType($programCourseId);
    }

    private function getDocumentTypeLabel(string $code): string
    {
        $labels = ['B2' => 'Boleta', 'FF' => 'Factura', 'AC' => 'Anticipo', 'CR' => 'Contrato', 'CT' => 'Crédito Temporal'];
        return $labels[$code] ?? $code;
    }

    /**
     * Check if a row is empty
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (!empty(trim($cell ?? ''))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if array is associative (has string keys)
     */
    private function isAssociativeArray(array $arr): bool
    {
        if (empty($arr)) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Validate email format
     */
    private function validateEmail(?string $email): bool
    {
        if (empty($email)) {
            return false;
        }
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check if a payment already exists in database
     * Criteria: RUT alumno (participant) + Programa + Nro. Aut. (authorization_code)
     *
     * @param int $participantId
     * @param int $programCourseId
     * @param float $amount
     * @param \Carbon\Carbon $transactionDate
     * @param string|null $authorizationCode Nro. Aut. from Excel
     * @return \App\Models\Payment|null
     */

    /**
     * Obtener el valor resuelto de una celda, usando el cache de Excel para fórmulas externas
     */
    private function getCellResolvedValue($cell)
    {
        $value = $cell->getValue();
        if (is_string($value) && str_starts_with($value, '=')) {
            $cached = $cell->getOldCalculatedValue();
            if ($cached !== null) {
                return $cached;
            }
            try {
                return $cell->getCalculatedValue();
            } catch (\Exception $e) {
                Log::warning("Fórmula no resuelta: {$value}");
                return null;
            }
        }
        return $value;
    }

    private function checkIfPaymentExists(
        int $participantId,
        int $programCourseId,
        float $amount,
        \Carbon\Carbon $transactionDate,
        ?string $authorizationCode = null
    ): ?\App\Models\Payment {
        // Build query base: participant + program + status
        $query = \App\Models\Payment::whereHas('order', function($query) use ($participantId, $programCourseId) {
                $query->where('participant_id', $participantId)
                      ->where('program_id', $programCourseId);
            })
            ->whereIn('status', ['approved', 'completed']);

        // If we have authorization_code (Nro. Aut.), use it for exact match
        if (!empty($authorizationCode)) {
            $query->where('authorization_code', trim($authorizationCode));
        } else {
            // Fallback: if no authorization_code, use amount + date ranges (old behavior)
            $amountMin = $amount * 0.95;
            $amountMax = $amount * 1.05;
            $dateStart = $transactionDate->copy()->subDay();
            $dateEnd = $transactionDate->copy()->addDay();

            $query->whereBetween('amount', [$amountMin, $amountMax])
                  ->whereBetween('transaction_date', [$dateStart, $dateEnd]);
        }

        return $query->first();
    }

    /**
     * Actualiza campos faltantes de un pago existente (duplicado).
     * Útil cuando se re-importa un Excel para completar datos como Nro. Boleta.
     */
    private function updateExistingPaymentFields(
        \App\Models\Payment $existingPayment,
        array $rowData,
        array $rawData = []
    ): array {
        $updated = [];

        // Referencia / Nro. Boleta
        $referencia = $rawData['referencia'] ?? $rowData['referencia'] ?? $rowData['reference'] ?? null;
        if ($referencia) {
            $referencia = trim((string)$referencia);
            if ($referencia !== '' && empty($existingPayment->payment_code) && empty($existingPayment->bsale_number)) {
                if ($existingPayment->document_type === 'B2') {
                    $existingPayment->bsale_number = $referencia;
                    $updated[] = 'bsale_number';
                } else {
                    $existingPayment->payment_code = $referencia;
                    $updated[] = 'payment_code';
                }
            }
        }

        // Nro. Autorización
        $authCode = $rawData['nro_aut'] ?? $rowData['nro_aut'] ?? null;
        if ($authCode && empty($existingPayment->authorization_code)) {
            $existingPayment->authorization_code = trim((string)$authCode);
            $updated[] = 'authorization_code';
        }

        // Contacto pagador (en order_details)
        $buyerName = $rawData['contacto_pagador'] ?? $rowData['contacto_pagador'] ?? null;
        if ($buyerName) {
            $orderDetail = $existingPayment->orderDetail;
            if ($orderDetail && (empty($orderDetail->name) || $orderDetail->name === ($existingPayment->order?->participant?->full_name ?? ''))) {
                $orderDetail->name = trim((string)$buyerName);
                $orderDetail->save();
                $updated[] = 'contacto_pagador';
            }
        }

        if (!empty($updated)) {
            $existingPayment->save();
            return [
                'status' => 'updated',
                'message' => "Pago existente actualizado (" . implode(', ', $updated) . ")",
                'data' => ['existing_payment_id' => $existingPayment->id, 'updated_fields' => $updated]
            ];
        }

        return [
            'status' => 'duplicate',
            'message' => "Pago duplicado, sin campos nuevos para actualizar",
            'data' => ['existing_payment_id' => $existingPayment->id]
        ];
    }
}
