<?php

namespace App\Console\Commands;

use App\Helpers\PaymentDocumentTypeHelper;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ParticipantProgram;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\PaymentOption;
use App\Services\Shared\OrderNumberGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportManualPaymentsCommand extends Command
{
    protected $signature = 'payments:import-manual {file : Ruta al archivo Excel}';
    protected $description = 'Importa pagos presenciales masivos desde un archivo Excel';

    private $paymentGateway;
    private $paymentOptions;

    // Mapeo de columnas esperadas
    private $columnMapping = [
        'rut' => ['rut alumno (a)', 'rut alumno', 'rut participante', 'rut_participante', 'rut'],
        'nro_negocio' => ['nro. negocio', 'nro negocio', 'nro_negocio', 'numero negocio', 'n negocio', 'negocio'],
        'monto' => ['pago y/o dev.', 'pago y/o dev', 'monto', 'monto pago', 'valor', 'amount'],
        'fecha_pago' => ['fecha de pago', 'fecha pago', 'fecha_pago', 'fecha', 'date'],
        'tipo_pago' => ['forma pago', 'tipo pago', 'tipo_pago', 'tipo', 'payment_type'],
        'nro_aut' => ['nro. aut.', 'nro. aut', 'nro aut', 'nro_aut', 'autorizacion', 'authorization'],
        'referencia' => ['nro. boleta', 'nro boleta', 'referencia', 'ref', 'reference'],
        'contacto_pagador' => ['contacto pagador', 'contacto_pagador', 'pagador', 'buyer'],
    ];

    public function handle()
    {
        $filePath = $this->argument('file');

        // Verificar si es ruta relativa
        if (!str_starts_with($filePath, '/')) {
            $filePath = base_path($filePath);
        }

        if (!file_exists($filePath)) {
            $this->error("Archivo no encontrado: {$filePath}");
            return 1;
        }

        $this->info("📂 Cargando archivo: {$filePath}");

        // Pre-cargar datos
        $this->paymentGateway = PaymentGateway::where('code', 'presencial')->first();
        $this->paymentOptions = PaymentOption::whereIn('code', [
            'presential_bank_transfer',
            'presential_deposit',
            'presential_webpay',
            'presential_aporte',
            'presential_pos_office',
            'presential_khipu_link',
            'presential_debit_credit',
            'presential_international',
        ])->get()->keyBy('code');

        if (!$this->paymentGateway) {
            $this->error("No se encontró el PaymentGateway 'presencial'");
            return 1;
        }

        // Leer Excel
        $this->info("📊 Leyendo archivo Excel...");
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        if (count($rows) < 2) {
            $this->error("El archivo no tiene datos suficientes");
            return 1;
        }

        // Detectar columnas
        $headers = array_map(fn($h) => strtolower(trim($h ?? '')), $rows[0]);
        $columnIndices = $this->detectColumnIndices($headers);

        $this->info("📋 Columnas detectadas:");
        foreach ($columnIndices as $key => $index) {
            if ($index !== null) {
                $this->line("   - {$key}: columna " . ($index + 1) . " ({$headers[$index]})");
            }
        }

        // Procesar filas
        $dataRows = array_slice($rows, 1); // Quitar header
        $totalRows = count($dataRows);

        $this->info("🚀 Procesando {$totalRows} filas...");
        $this->newLine();

        $stats = ['successful' => 0, 'duplicates' => 0, 'skipped' => 0, 'failed' => 0];
        $bar = $this->output->createProgressBar($totalRows);
        $bar->start();

        $this->newLine();
        $this->info("🔍 DEBUG: Iniciando loop de procesamiento...");

        foreach ($dataRows as $index => $row) {
            $this->line("  → Procesando fila " . ($index + 2) . "...");
            $rowNumber = $index + 2; // +2 porque empezamos en fila 2 (después del header)

            // Extraer datos de la fila
            $rowData = $this->extractRowData($row, $columnIndices);

            if (empty($rowData['rut']) || empty($rowData['nro_negocio'])) {
                $stats['skipped']++;
                $bar->advance();
                continue;
            }

            $result = $this->processRow($rowData, $rowNumber);

            if ($result['status'] === 'success') {
                $stats['successful']++;
            } elseif ($result['status'] === 'duplicate') {
                $stats['duplicates']++;
            } elseif ($result['status'] === 'skipped') {
                $stats['skipped']++;
            } else {
                $stats['failed']++;
                // Mostrar error sin interrumpir la barra de progreso
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Mostrar resumen
        $this->info("✅ IMPORTACIÓN COMPLETADA");
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Exitosos', $stats['successful']],
                ['Duplicados', $stats['duplicates']],
                ['Omitidos', $stats['skipped']],
                ['Fallidos', $stats['failed']],
                ['Total', $totalRows],
            ]
        );

        return 0;
    }

    private function detectColumnIndices(array $headers): array
    {
        $indices = [];

        foreach ($this->columnMapping as $key => $possibleNames) {
            $indices[$key] = null;
            foreach ($possibleNames as $name) {
                $index = array_search($name, $headers);
                if ($index !== false) {
                    $indices[$key] = $index;
                    break;
                }
            }
        }

        return $indices;
    }

    private function extractRowData(array $row, array $columnIndices): array
    {
        $data = [];
        foreach ($columnIndices as $key => $index) {
            $data[$key] = ($index !== null && isset($row[$index])) ? trim($row[$index] ?? '') : null;
        }
        return $data;
    }

    private function processRow(array $rowData, int $rowNumber): array
    {
        echo "    [1] Construyendo enrollment_code...\n";
        // 1. Construir enrollment_code
        // El RUT viene como "230,590,680" que es 23059068-0 (sin guión, con comas como separador de miles)
        $rutRaw = $rowData['rut'] ?? '';
        $rut = preg_replace('/[^0-9kK]/', '', $rutRaw); // Quitar todo excepto números y K

        $nroNegocioRaw = $rowData['nro_negocio'] ?? '';
        $nroNegocio = trim($nroNegocioRaw);

        $enrollmentCode = $rut . '-' . $nroNegocio;
        echo "    [2] enrollment_code: {$enrollmentCode}\n";

        // 2. Buscar participante
        echo "    [3] Buscando participante...\n";
        $participantProgram = ParticipantProgram::with(['participant', 'programCourse'])
            ->where('enrollment_code', $enrollmentCode)
            ->first();
        echo "    [4] Participante encontrado: " . ($participantProgram ? 'SÍ' : 'NO') . "\n";

        if (!$participantProgram || !$participantProgram->participant || !$participantProgram->programCourse) {
            return [
                'status' => 'error',
                'message' => "No se encontró participante: {$enrollmentCode}",
            ];
        }

        $participant = $participantProgram->participant;
        $programCourse = $participantProgram->programCourse;

        // 3. Parsear monto
        $paymentAmount = $this->parseAmount($rowData['monto'] ?? '0');
        if ($paymentAmount <= 0) {
            return ['status' => 'skipped', 'message' => 'Monto inválido'];
        }

        // 4. Parsear fecha
        $paymentDate = $this->parseDate($rowData['fecha_pago'] ?? null);

        // 5. Verificar duplicado
        $authorizationCode = $rowData['nro_aut'] ?? null;
        if ($this->isDuplicate($participant->id, $programCourse->id, $paymentAmount, $paymentDate, $authorizationCode)) {
            return ['status' => 'duplicate', 'message' => 'Pago duplicado'];
        }

        // 6. Obtener payment option
        $tipoPago = strtoupper(trim($rowData['tipo_pago'] ?? 'TE'));
        $paymentOptionCode = $this->mapPaymentType($tipoPago);
        $paymentOption = $this->paymentOptions->get($paymentOptionCode);

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
            'notes' => 'Importación masiva (comando)',
        ]);

        // 8. Crear OrderDetail
        $orderDetail = OrderDetail::create([
            'order_id' => $order->id,
            'payment_option_id' => $paymentOption?->id,
            'payment_gateway_id' => $this->paymentGateway->id,
            'name' => $rowData['contacto_pagador'] ?? $participant->full_name,
            'email' => $participant->email,
            'base_amount' => $paymentAmount,
            'discount_amount' => 0,
            'amount' => $paymentAmount,
            'due_date' => $paymentDate,
            'is_paid' => true,
            'status' => 'paid',
            'paid_at' => $paymentDate,
            'gateway_response' => ['created_manually' => true, 'import_row' => $rowNumber],
        ]);

        // 9. Crear Payment
        $referencia = $rowData['referencia'] ?? null;
        $documentType = PaymentDocumentTypeHelper::determineDocumentType($programCourse->id);

        $payment = Payment::create([
            'order_id' => $order->id,
            'order_detail_id' => $orderDetail->id,
            'payment_gateway_id' => $this->paymentGateway->id,
            'payment_option_id' => $paymentOption?->id,
            'buy_order' => $order->order_number,
            'amount' => $paymentAmount,
            'status' => 'approved',
            'transaction_date' => $paymentDate,
            'authorization_code' => $authorizationCode,
            // payment_code SIEMPRE guarda la referencia manual
            // bsale_number se llena SOLO cuando BSale genera la boleta
            'payment_code' => $referencia,
            'bsale_number' => null,
            'gateway_response' => ['created_manually' => true, 'import_row' => $rowNumber],
            'currency' => 'CLP',
            'document_type' => $documentType,
        ]);

        // 10. Actualizar estado
        $order->refreshStatus();

        // 11. Manejar APORTE
        if ($tipoPago === 'AP') {
            $this->handleAporte($participantProgram, $paymentAmount);
        }

        return ['status' => 'success', 'message' => 'OK', 'payment_id' => $payment->id];
    }

    private function parseAmount($value): float
    {
        if (is_numeric($value)) {
            return round((float) $value);
        }
        $clean = preg_replace('/[^0-9,.]/', '', $value);
        $clean = str_replace(['.', ','], ['', '.'], $clean);
        return round((float) $clean);
    }

    private function parseDate($value): Carbon
    {
        if (empty($value)) {
            return Carbon::now();
        }

        // Si es número (Excel serial date)
        if (is_numeric($value)) {
            return Carbon::createFromTimestamp(($value - 25569) * 86400);
        }

        try {
            // Intentar varios formatos
            foreach (['d-m-Y', 'd/m/Y', 'Y-m-d', 'd-m-y', 'd/m/y'] as $format) {
                try {
                    return Carbon::createFromFormat($format, $value);
                } catch (\Exception $e) {
                    continue;
                }
            }
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return Carbon::now();
        }
    }

    private function isDuplicate($participantId, $programId, $amount, $date, $authCode): bool
    {
        $query = Payment::whereHas('order', function ($q) use ($participantId, $programId) {
            $q->where('participant_id', $participantId)->where('program_id', $programId);
        })->where('status', 'approved');

        if ($authCode) {
            return $query->where('authorization_code', $authCode)->exists();
        }

        return $query->where('amount', $amount)
            ->whereDate('transaction_date', $date->toDateString())
            ->exists();
    }

    private function mapPaymentType(string $tipo): string
    {
        return match ($tipo) {
            'TE' => 'presential_bank_transfer',
            'DE' => 'presential_deposit',
            'WP', 'VP' => 'presential_webpay', // VP = VirtualPOS/Webpay
            'AP' => 'presential_aporte',
            'POS' => 'presential_pos_office',
            'KH' => 'presential_khipu_link',
            'TD', 'TC', 'TB' => 'presential_debit_credit', // TC/TB = Tarjeta Crédito/Débito
            'INT' => 'presential_international',
            default => 'presential_bank_transfer',
        };
    }

    private function handleAporte($participantProgram, $amount): void
    {
        $currentAporte = (float) ($participantProgram->aporte_amount ?? 0);
        $participantProgram->update([
            'aporte_amount' => $currentAporte + $amount,
        ]);
    }
}
