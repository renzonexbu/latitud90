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
                Log::info('Preview de pagos completado exitosamente', [
                    'stats' => $result['stats'],
                    'user_id' => auth()->id()
                ]);

                return response()->json([
                    'success' => true,
                    'stats' => $result['stats'],
                    'details' => $result['details'],
                    'total_rows' => $result['total_rows'] ?? 0,
                    'previewed_rows' => $result['previewed_rows'] ?? 0,
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
     * Process the uploaded Excel file
     */
    public function importStore(Request $request)
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
                return back()
                    ->withErrors($validator)
                    ->with('error', 'Error en la validación del archivo.');
            }

            $file = $request->file('file');

            Log::info('Iniciando importación de pagos presenciales masivos', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'user_id' => auth()->id()
            ]);

            // Process the Excel file
            $result = $this->importManualPaymentsService->processExcel($file);

            if ($result['success']) {
                $stats = $result['stats'];

                Log::info('Importación de pagos completada exitosamente', [
                    'stats' => $stats,
                    'user_id' => auth()->id()
                ]);

                return back()
                    ->with('importResults', [
                        'successful' => $stats['successful'],
                        'skipped' => $stats['skipped'],
                        'failed' => $stats['failed'],
                        'details' => $result['details']
                    ]);
            } else {
                Log::error('Error en importación de pagos', [
                    'error' => $result['error'],
                    'user_id' => auth()->id()
                ]);

                return back()->with('error', 'Error al procesar el archivo: ' . $result['error']);
            }
        } catch (\Exception $e) {
            Log::error('Excepción en importación de pagos: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            return back()->with('error', 'Error inesperado al procesar el archivo: ' . $e->getMessage());
        }
    }
}
