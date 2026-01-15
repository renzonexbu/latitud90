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
