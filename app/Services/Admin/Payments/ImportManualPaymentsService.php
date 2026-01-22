<?php

namespace App\Services\Admin\Payments;

use App\Helpers\RutHelper;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ParticipantProgram;
use App\Models\Payment;
use App\Models\ProgramSubscription;
use App\Services\Admin\Installments\RegisterManualPaymentService;
use App\Services\Shared\OrderNumberGenerator;
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
    ];

    public function __construct(RegisterManualPaymentService $registerManualPaymentService)
    {
        $this->registerManualPaymentService = $registerManualPaymentService;
    }

    // Chunk size for processing large files
    protected const CHUNK_SIZE = 500;

    // Maximum details to return (to avoid memory issues)
    protected const MAX_DETAILS = 1000;

    /**
     * Process the uploaded Excel file and import manual payments
     * Uses chunked processing for large files (10k-100k rows)
     */
    public function processExcel($file): array
    {
        $details = [];
        $stats = [
            'successful' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        try {
            // Save the file temporarily
            $filename = uniqid() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('temp', $filename);
            $fullPath = Storage::path($path);

            Log::info('Archivo Excel guardado temporalmente', ['path' => $fullPath]);

            // Load the spreadsheet with memory optimization
            $reader = IOFactory::createReaderForFile($fullPath);
            $reader->setReadDataOnly(true); // Only read data, skip styles/formatting
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

            Log::info('Iniciando procesamiento por chunks', [
                'chunk_size' => self::CHUNK_SIZE,
                'data_start_row' => $dataStartRow,
                'total_data_rows' => $highestRow - $headerRowIndex
            ]);

            foreach ($worksheet->getRowIterator($dataStartRow) as $rowIndex => $row) {
                $cellIterator = $row->getCellIterator('A', $highestColumn);
                $cellIterator->setIterateOnlyExistingCells(false);

                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();
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
                    $chunkResult = $this->processChunk($currentChunk, $columnIndices, $details, $stats);
                    $details = $chunkResult['details'];
                    $stats = $chunkResult['stats'];

                    $chunkCount++;
                    $processedRows += count($currentChunk);

                    Log::info("Chunk {$chunkCount} procesado", [
                        'rows_in_chunk' => count($currentChunk),
                        'total_processed' => $processedRows,
                        'stats' => $stats
                    ]);

                    // Clear chunk and free memory
                    $currentChunk = [];
                    gc_collect_cycles();
                }
            }

            // Process remaining rows
            if (!empty($currentChunk)) {
                $chunkResult = $this->processChunk($currentChunk, $columnIndices, $details, $stats);
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

            Log::info('Importación de pagos completada', ['stats' => $stats, 'chunks_processed' => $chunkCount]);

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
     */
    private function processChunk(array $chunk, array $columnIndices, array $details, array $stats): array
    {
        DB::beginTransaction();

        try {
            foreach ($chunk as $item) {
                $rowNumber = $item['row_index'];
                $row = $item['data'];

                // Build row data array
                $rowData = [];
                foreach ($columnIndices as $key => $index) {
                    $rowData[$key] = isset($row[$index]) ? trim($row[$index]) : null;
                }

                // Process this payment row
                $result = $this->processPaymentRow($rowData, $rowNumber, false);

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
                } elseif ($result['status'] === 'skipped') {
                    $stats['skipped']++;
                } else {
                    $stats['failed']++;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en chunk, rollback ejecutado: ' . $e->getMessage());

            // Mark all remaining rows in chunk as failed
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
     * Process a single payment row
     * @param bool $debugMode Si es true, solo prepara y loguea los datos sin guardar en BD
     */
    private function processPaymentRow(array $rowData, int $rowNumber, bool $debugMode = false): array
    {
        try {
            // Validate row data
            $validation = $this->validateRowData($rowData, $rowNumber);
            if (!$validation['valid']) {
                return [
                    'status' => 'error',
                    'message' => $validation['error'],
                    'data' => [
                        'row_data' => $rowData,
                    ]
                ];
            }

            // 1. Build enrollment code from RUT and Nro. Negocio
            // Format: [RUT_WITHOUT_DOTS_OR_HYPHENS]-[NRO_NEGOCIO]
            // Example: 122088901-1001
            $cleanRut = str_replace(['.', '-', ' '], '', trim($rowData['rut']));
            $nroNegocio = trim($rowData['nro_negocio']);
            $enrollmentCode = $cleanRut . '-' . $nroNegocio;

            Log::info("Enrollment code construido", [
                'rut_original' => $rowData['rut'],
                'rut_limpio' => $cleanRut,
                'nro_negocio' => $nroNegocio,
                'enrollment_code' => $enrollmentCode
            ]);

            // 2. Find participant by enrollment code
            $participantProgram = ParticipantProgram::where('enrollment_code', $enrollmentCode)->first();

            if (!$participantProgram) {
                return [
                    'status' => 'error',
                    'message' => "No se encontró participante con código de inscripción: {$enrollmentCode}",
                    'data' => [
                        'row_data' => $rowData,
                    ]
                ];
            }

            $participant = $participantProgram->participant;
            $programCourse = $participantProgram->programCourse;

            Log::info("Participante encontrado", [
                'participant_id' => $participant->id,
                'name' => $participant->full_name,
                'enrollment_code' => $enrollmentCode
            ]);

            // 2. Check if participant has an active subscription
            $hasSubscription = ProgramSubscription::where('participant_id', $participant->id)
                ->where('program_id', $participantProgram->program_id)
                ->where('status', 'ACTIVA')
                ->exists();

            if ($hasSubscription) {
                Log::info("Participante tiene suscripción activa, omitiendo", [
                    'participant_id' => $participant->id,
                    'enrollment_code' => $enrollmentCode
                ]);

                return [
                    'status' => 'skipped',
                    'message' => "Participante {$participant->full_name} ya tiene suscripción activa - omitido",
                    'data' => [
                        'row_data' => $rowData,
                        'participant' => [
                            'id' => $participant->id,
                            'name' => $participant->full_name,
                            'email' => $participant->email,
                            'rut' => $participant->rut,
                        ],
                        'program' => [
                            'id' => $programCourse->id,
                            'name' => $programCourse->name,
                            'code' => $programCourse->code,
                        ],
                    ]
                ];
            }

            // 3. Find or create the order for this participant
            $order = Order::where('participant_program_id', $participantProgram->id)->first();

            if (!$order) {
                Log::info("No se encontró orden, creando automáticamente", [
                    'participant_id' => $participant->id,
                    'participant_program_id' => $participantProgram->id
                ]);

                // Get the total amount from the participant's program cost
                $totalAmount = $programCourse->cost ?? 0;

                // Create order WITHOUT installment system
                $order = Order::create([
                    'participant_id' => $participant->id,
                    'program_id' => $programCourse->id,
                    'participant_program_id' => $participantProgram->id,
                    'total_amount' => $totalAmount,
                    'discount' => 0,
                    'final_amount' => $totalAmount,
                    'total_installments' => 0, // No installments for presential payments
                    'payment_type' => 'total', // Mark as total payment
                    'status' => 'pending',
                    'notes' => 'Orden creada para pagos presenciales (sin sistema de cuotas)',
                    'order_number' => app(OrderNumberGenerator::class)->generate(),
                    'session_id' => null,
                ]);

                Log::info("Orden creada exitosamente (sin sistema de cuotas)", [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number
                ]);
            }

            // 4. Parse payment data
            $paymentAmount = floatval(str_replace(['.', ','], ['', '.'], $rowData['monto']));
            $paymentDate = $this->parseDate($rowData['fecha_pago']);
            $paymentType = $this->mapPaymentType($rowData['tipo_pago']);

            // 5. Get payment_option_id for the payment type
            $paymentOptionId = $this->getPaymentOptionIdBySource($paymentType);

            // 6. Buyer data
            $buyerName = $rowData['contacto_pagador'] ?? ($participant->first_name . ' ' . $participant->last_name);
            $buyerEmailFromData = $rowData['email_contacto_pagador'] ?? null;
            $buyerEmail = ($buyerEmailFromData && filter_var($buyerEmailFromData, FILTER_VALIDATE_EMAIL))
                ? $buyerEmailFromData
                : $participant->email;

            // 7. Count existing payments to generate unique identifiers
            $existingPaymentsCount = Payment::where('order_id', $order->id)->count();
            $paymentNumber = $existingPaymentsCount + 1;

            Log::info("Registrando pago presencial directo (sin cuotas)", [
                'order_id' => $order->id,
                'payment_number' => $paymentNumber,
                'amount' => $paymentAmount
            ]);

            // 8. Create OrderDetail
            $orderDetail = OrderDetail::create([
                'order_id' => $order->id,
                'installment_number' => null, // No hay cuotas
                'amount' => $paymentAmount,
                'due_date' => $paymentDate,
                'status' => 'paid',
                'is_paid' => true,
                'paid_at' => $paymentDate,
                'name' => $buyerName,
                'email' => $buyerEmail,
                'phone' => null,
                'code_phone' => null,
                'document_type' => null,
                'document_number' => null,
                'country' => null,
                'region' => null,
                'city' => null,
            ]);

            // 9. Create Payment
            $payment = Payment::create([
                'order_id' => $order->id,
                'order_detail_id' => $orderDetail->id,
                'buy_order' => $order->order_number . '-P' . $paymentNumber,
                'amount' => $paymentAmount,
                'status' => 'approved',
                'payment_gateway_id' => null,
                'payment_option_id' => $paymentOptionId,
                'transaction_date' => $paymentDate,
                'installments_number' => 1,
                'document_type' => 'B2',
                'authorization_code' => $rowData['nro_aut'] ?? null,
            ]);

            // 10. Update order status
            $order->refreshStatus();

            Log::info("✅ Pago presencial registrado exitosamente", [
                'payment_id' => $payment->id,
                'order_detail_id' => $orderDetail->id,
                'amount' => $paymentAmount,
                'order_id' => $order->id,
                'participant' => $participant->full_name
            ]);

            return [
                'status' => 'success',
                'message' => "Pago de \${$paymentAmount} registrado exitosamente para {$participant->full_name} (RUT: {$participant->rut})"
            ];
        } catch (\Exception $e) {
            Log::error("Error procesando fila {$rowNumber}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'row_data' => $rowData
            ]);

            return [
                'status' => 'error',
                'message' => "Error en fila {$rowNumber}: " . $e->getMessage(),
                'data' => [
                    'row_data' => $rowData,
                ]
            ];
        }
    }

    /**
     * Validate that all required headers are present
     */
    private function validateHeaders(array $headers): void
    {
        $missingHeaders = [];

        // Normalize headers for comparison
        $normalizedHeaders = array_map(function($header) {
            return strtolower(trim($header));
        }, $headers);

        foreach ($this->expectedHeaders as $field => $headerVariations) {
            $found = false;

            // Check if any variation of this header exists
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
                // Use the first variation as the display name
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
    private function getColumnIndices(array $headers): array
    {
        $indices = [];

        // Normalize headers for comparison
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
        ];

        foreach ($fieldMappings as $field => $possibleHeaders) {
            foreach ($normalizedHeaders as $index => $normalizedHeader) {
                // Check if the normalized header matches any of the possible headers
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
        // Check RUT (required to build enrollment code)
        if (empty($data['rut'])) {
            return ['valid' => false, 'error' => "RUT vacío en fila {$rowNumber}"];
        }

        // Check Nro. Negocio (required to build enrollment code)
        if (empty($data['nro_negocio'])) {
            return ['valid' => false, 'error' => "Número de negocio vacío en fila {$rowNumber}"];
        }

        // Check amount
        if (empty($data['monto']) || !is_numeric(str_replace(['.', ','], ['', '.'], $data['monto']))) {
            return ['valid' => false, 'error' => "Monto inválido en fila {$rowNumber}"];
        }

        $amount = floatval(str_replace(['.', ','], ['', '.'], $data['monto']));
        if ($amount <= 0) {
            return ['valid' => false, 'error' => "El monto debe ser mayor a cero en fila {$rowNumber}"];
        }

        // Check payment date
        if (empty($data['fecha_pago'])) {
            return ['valid' => false, 'error' => "Fecha de pago vacía en fila {$rowNumber}"];
        }

        // Check payment type
        if (empty($data['tipo_pago'])) {
            return ['valid' => false, 'error' => "Tipo de pago vacío en fila {$rowNumber}"];
        }

        return ['valid' => true];
    }

    /**
     * Parse date from Excel
     */
    private function parseDate($dateValue): Carbon
    {
        // If it's a numeric value (Excel date format)
        if (is_numeric($dateValue)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject($dateValue));
        }

        // Try to parse as string
        try {
            // Try DD/MM/YYYY format
            if (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/', $dateValue, $matches)) {
                return Carbon::createFromFormat('d/m/Y', $dateValue);
            }

            // Try YYYY-MM-DD format
            if (preg_match('/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})$/', $dateValue)) {
                return Carbon::parse($dateValue);
            }

            // Try general parsing
            return Carbon::parse($dateValue);
        } catch (\Exception $e) {
            Log::warning("No se pudo parsear fecha: {$dateValue}, usando fecha actual");
            return Carbon::now();
        }
    }

    /**
     * Map payment type from report codes to manual payment sources
     * Todos los códigos de forma de pago del sistema
     */
    private function mapPaymentType(string $type): string
    {
        $type = strtoupper(trim($type));

        // Map report codes to manual payment sources
        $mappings = [
            'TC' => 'manual_card_office',    // POS Oficina
            'KP' => 'manual_transfer',       // Link KP (Khipu)
            'PAT' => 'manual_subscription',  // Suscripción Cuotas
            'TE' => 'manual_transfer',       // Transferencia Banco
            'VP' => 'manual_card',           // Link TD/TC (Tarjeta Débito/Crédito)
            'VPI' => 'manual_international', // Link Internacional
            'DP' => 'manual_deposit',        // Depósito
            'WP' => 'manual_webpay',         // Webpay
            // Códigos legacy
            'BX' => 'manual_card_office',    // Tarjeta presencial (legacy)
        ];

        return $mappings[$type] ?? 'manual_card';
    }

    /**
     * Check if a row is empty
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (!empty(trim($cell))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get payment_option_id based on payment_source
     * Maps internal codes (manual_*) to presential payment options in DB
     */
    private function getPaymentOptionIdBySource(string $paymentSource): ?int
    {
        // Map payment_source to payment_option code
        $mapping = [
            'manual_card_office' => 'presential_pos_office',         // TC
            'manual_transfer' => 'presential_bank_transfer',         // TE/KP
            'manual_subscription' => 'presential_subscription',      // PAT
            'manual_card' => 'presential_debit_credit',              // VP
            'manual_international' => 'presential_international',    // VPI
            'manual_deposit' => 'presential_deposit',                // DP
            'manual_webpay' => 'presential_webpay',                  // WP
        ];

        $paymentOptionCode = $mapping[$paymentSource] ?? null;

        if (!$paymentOptionCode) {
            return null;
        }

        // Find payment_option_id in database
        $paymentOption = \App\Models\PaymentOption::where('code', $paymentOptionCode)->first();

        return $paymentOption?->id;
    }
}
