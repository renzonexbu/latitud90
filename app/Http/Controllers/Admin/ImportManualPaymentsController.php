<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\Payments\ImportManualPaymentsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ImportManualPaymentsController extends Controller
{
    protected $importManualPaymentsService;

    public function __construct(ImportManualPaymentsService $importManualPaymentsService)
    {
        $this->importManualPaymentsService = $importManualPaymentsService;
    }

    /**
     * Show the import form
     */
    public function import()
    {
        try {
            return Inertia::render('Admin/Payments/ManualPaymentsImport');
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de importación de pagos: ' . $e->getMessage());
            return back()->with('error', 'Error al cargar el formulario de importación.');
        }
    }

    /**
     * Preview the uploaded Excel file WITHOUT inserting into database
     * Shows what would be imported for user confirmation
     */
    public function preview(Request $request)
    {
        try {
            // Validate the uploaded file
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
            ], [
                'file.required' => 'Debe seleccionar un archivo para importar.',
                'file.file' => 'El archivo seleccionado no es válido.',
                'file.mimes' => 'El archivo debe ser de tipo Excel (.xlsx o .xls).',
                'file.max' => 'El archivo no debe superar los 10MB.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => $validator->errors()->first()
                ], 422);
            }

            $file = $request->file('file');

            Log::info('Iniciando preview de pagos presenciales masivos', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'user_id' => auth()->id()
            ]);

            // Preview the Excel file (NO database inserts)
            $result = $this->importManualPaymentsService->previewExcel($file);

            if ($result['success']) {
                // Log sample detail to verify raw_row_data is included
                $sampleDetail = $result['details'][0] ?? null;
                Log::info('Preview de pagos completado exitosamente', [
                    'stats' => $result['stats'],
                    'user_id' => auth()->id(),
                    'column_indices' => $result['column_indices'] ?? null,
                    'sample_detail_has_raw_data' => isset($sampleDetail['data']['raw_row_data']),
                    'sample_detail_keys' => $sampleDetail ? array_keys($sampleDetail['data'] ?? []) : null
                ]);

                return response()->json([
                    'success' => true,
                    'stats' => $result['stats'],
                    'details' => $result['details'],
                    'total_rows' => $result['total_rows'] ?? 0,
                    'previewed_rows' => $result['previewed_rows'] ?? 0,
                    'column_indices' => $result['column_indices'] ?? null,
                    'headers' => $result['headers'] ?? null,
                ]);
            } else {
                Log::error('Error en preview de pagos', [
                    'error' => $result['error'],
                    'user_id' => auth()->id()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 422);
            }
        } catch (\Exception $e) {
            Log::error('Excepción en preview de pagos: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error inesperado al procesar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process selected rows data directly (without re-reading Excel)
     * Procesa de forma SÍNCRONA - sin jobs en segundo plano
     */
    public function importStore(Request $request)
    {
        // Aumentar límites para procesamiento síncrono
        set_time_limit(600); // 10 minutos
        ini_set('max_execution_time', 600);

        try {
            // Validate the selected rows data
            $validator = Validator::make($request->all(), [
                'rows_data' => 'required|array|min:1',
                'rows_data.*.row_number' => 'required|integer',
                'rows_data.*.data' => 'required|array',
                'column_indices' => 'required|array',
            ], [
                'rows_data.required' => 'Debe seleccionar al menos una fila para importar.',
                'rows_data.array' => 'Los datos enviados no son válidos.',
                'rows_data.min' => 'Debe seleccionar al menos una fila para importar.',
                'column_indices.required' => 'Los índices de columnas son requeridos.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => $validator->errors()->first()
                ], 422);
            }

            $rowsData = $request->input('rows_data');
            $columnIndices = $request->input('column_indices');
            $totalToImport = count($rowsData);

            Log::info('🚀 INICIANDO IMPORTACIÓN', [
                'total_a_importar' => $totalToImport,
                'user_id' => auth()->id()
            ]);

            // Procesar directamente fila por fila
            $result = $this->importManualPaymentsService->processRows($rowsData, $columnIndices);

            Log::info('✅ IMPORTACIÓN COMPLETADA', [
                'stats' => $result['stats'],
                'user_id' => auth()->id()
            ]);

            $stats = $result['stats'];
            $details = $result['details'] ?? [];

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'details' => $details, // Incluir detalles para mostrar fallos en frontend
                'message' => "Importación completada: {$stats['successful']} pagos insertados, {$stats['duplicates']} duplicados, {$stats['skipped']} omitidos, {$stats['failed']} fallidos."
            ]);

        } catch (\Exception $e) {
            Log::error('Excepción en importación síncrona: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al procesar la importación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get import progress for a specific job
     */
    public function importProgress(Request $request)
    {
        $jobId = $request->input('job_id');

        if (!$jobId) {
            return response()->json([
                'success' => false,
                'error' => 'job_id es requerido'
            ], 400);
        }

        $progress = \Illuminate\Support\Facades\Cache::get("import_progress_{$jobId}");

        if (!$progress) {
            return response()->json([
                'success' => false,
                'error' => 'Job no encontrado o expirado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'progress' => $progress
        ]);
    }

    /**
     * Export a section of the preview to Excel
     */
    public function exportSection(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:duplicates,valid,errors',
                'data' => 'required|json',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => $validator->errors()->first()
                ], 422);
            }

            $type = $request->input('type');
            $data = json_decode($request->input('data'), true);

            // Generate Excel file
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set title based on type
            $titles = [
                'duplicates' => 'Pagos Duplicados',
                'valid' => 'Pagos Válidos',
                'errors' => 'Pagos con Errores',
            ];
            $sheet->setTitle($titles[$type]);

            // Define headers based on type
            if ($type === 'errors') {
                $headers = ['Fila', 'Mensaje de Error'];
                $sheet->fromArray($headers, null, 'A1');

                $row = 2;
                foreach ($data as $item) {
                    $sheet->fromArray([
                        $item['row'] ?? '',
                        $item['message'] ?? '',
                    ], null, 'A' . $row);
                    $row++;
                }
            } else {
                // For duplicates and valid payments
                $headers = ['Fila', 'Participante', 'RUT', 'Código Programa', 'Programa', 'Monto', 'Fecha', 'Mensaje'];
                $sheet->fromArray($headers, null, 'A1');

                $row = 2;
                foreach ($data as $item) {
                    $itemData = $item['data'] ?? [];
                    $sheet->fromArray([
                        $item['row'] ?? '',
                        $itemData['participant_name'] ?? '',
                        $itemData['participant_rut'] ?? '',
                        $itemData['program_code'] ?? '',
                        $itemData['program_name'] ?? '',
                        $itemData['payment_amount'] ?? '',
                        $itemData['payment_date'] ?? '',
                        $item['message'] ?? '',
                    ], null, 'A' . $row);
                    $row++;
                }
            }

            // Style header row
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '007e93']],
            ];
            $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);

            // Auto-size columns
            foreach (range('A', $sheet->getHighestColumn()) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Generate filename
            $filename = $titles[$type] . '_' . date('Y-m-d_His') . '.xlsx';

            // Create writer and output
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            Log::error('Error al exportar sección: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al exportar: ' . $e->getMessage()
            ], 500);
        }
    }
}
