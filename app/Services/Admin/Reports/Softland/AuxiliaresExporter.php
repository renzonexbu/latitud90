<?php

namespace App\Services\Admin\Reports\Softland;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AuxiliaresExporter
{
    private SoftlandAuxiliaresService $auxiliaresService;

    public function __construct(SoftlandAuxiliaresService $auxiliaresService)
    {
        $this->auxiliaresService = $auxiliaresService;
    }

    /**
     * Exporta los auxiliares en formato Excel
     */
    public function exportToExcel(): StreamedResponse
    {
        try {
            Log::info('Iniciando exportación de auxiliares Excel');
            
            $data = $this->auxiliaresService->generateAuxiliaresData();
            $headers = $this->auxiliaresService->getHeaders();
            Log::info('Headers obtenidos: ' . count($headers) . ' columnas');
            Log::info('Datos obtenidos: ' . $data->count() . ' auxiliares');
            
            $spreadsheet = new Spreadsheet();
            Log::info('Spreadsheet creado exitosamente');
            
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Auxiliares Softland');
            Log::info('Hoja activa obtenida');
            
            // Limpiar buffers de salida para evitar corrupción del archivo
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Escribir encabezados (fila 1)
            $colIndex = 1; // 1 = columna A
            foreach ($headers as $header) {
                $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue($columnLetter . '1', $header);
                Log::info('Header agregado en columna ' . $columnLetter . ': ' . $header);
                $colIndex++;
            }
            Log::info('Headers agregados exitosamente: ' . count($headers) . ' columnas');
            
            // Escribir datos de auxiliares
            $rowIndex = 2;
            foreach ($data as $auxiliar) {
                $colIndex = 1;
                foreach ($auxiliar as $value) {
                    $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->setCellValue($columnLetter . $rowIndex, $value);
                    $colIndex++;
                }
                $rowIndex++;
            }
            Log::info('Datos agregados exitosamente: ' . ($rowIndex - 2) . ' filas');
            
            // Estilos de encabezado
            $lastColumnIndex = count($headers);
            $lastColumnLetter = Coordinate::stringFromColumnIndex($lastColumnIndex);
            $sheet->getStyle('A1:' . $lastColumnLetter . '1')->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    'outline' => ['borderStyle' => Border::BORDER_THIN],
                ],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(28);
            
            // Ajuste de ancho de columnas
            for ($i = 1; $i <= $lastColumnIndex; $i++) {
                $columnLetter = Coordinate::stringFromColumnIndex($i);
                $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
            }
            
            // Congelar la fila de encabezado
            $sheet->freezePane('A2');
            
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            
            $response = new StreamedResponse(function () use ($writer) {
                $writer->save('php://output');
            });
            
            $filename = 'auxiliares_softland_' . Carbon::now()->format('Y-m-d_H-i-s');
            
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.xlsx"');
            $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
            $response->headers->set('Expires', '0');
            $response->headers->set('Pragma', 'public');
            
            Log::info('StreamedResponse creado exitosamente');
            return $response;
            
        } catch (\Exception $e) {
            Log::error('Error en exportToExcel: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Exporta los auxiliares en formato CSV
     */
    public function exportToCsv(): string
    {
        try {
            Log::info('Iniciando exportación de auxiliares CSV');
            
            $data = $this->auxiliaresService->generateAuxiliaresData();
            $headers = $this->auxiliaresService->getHeaders();
            Log::info('Headers obtenidos: ' . count($headers) . ' columnas');
            Log::info('Datos obtenidos: ' . $data->count() . ' auxiliares');
            
            $filename = 'auxiliares_softland_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';
            $filepath = storage_path('app/temp/' . $filename);
            
            // Crear directorio si no existe
            if (!file_exists(dirname($filepath))) {
                mkdir(dirname($filepath), 0755, true);
            }
            
            $file = fopen($filepath, 'w');
            
            // Escribir BOM para UTF-8
            fwrite($file, "\xEF\xBB\xBF");
            
            // Escribir encabezados
            fputcsv($file, $headers, ';');
            Log::info('Headers escritos en CSV');
            
            // Escribir datos
            foreach ($data as $auxiliar) {
                fputcsv($file, $auxiliar, ';');
            }
            Log::info('Datos escritos en CSV: ' . $data->count() . ' filas');
            
            fclose($file);
            
            Log::info('Archivo CSV creado exitosamente en: ' . $filepath);
            return $filepath;
            
        } catch (\Exception $e) {
            Log::error('Error en exportToCsv: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Determina si un campo debe ser tratado como texto o fecha
     */
    private function isTextOrDateField(int $columnIndex, $value): bool
    {
        // Campos que siempre son texto o fecha según la especificación de Softland
        $textFields = [
            0,  // Código auxiliar
            1,  // Nombre Auxiliar
            2,  // Nombre de Fantasía
            3,  // RUT Auxiliar
            4,  // Activo
            5,  // Código Giro Comercial
            6,  // Código País Auxiliar
            8,  // Código Ciudad Auxiliar
            9,  // Código Comuna Auxiliar
            10, // Dirección Auxiliar
            11, // Número Dir. Auxiliar
            12, // Teléfono 1 Auxiliar
            13, // Teléfono 2 Auxiliar
            14, // Teléfono 3 Auxiliar
            15, // Fax 1 Auxiliar
            16, // Fax 2 Auxiliar
            17, // Clasificación Cliente
            18, // Clasificación Proveedor
            19, // Clasificación Empleado
            20, // Clasificación Socio
            21, // Clasificación Distribuidor
            22, // Clasificación Otro
            23, // Casilla Auxiliar
            24, // E-Mail Auxiliar
            25, // Sitio Web Auxiliar
            26, // Notas Auxiliar
            27, // Nombre Contacto
            28, // Código Cargo Contacto
            29, // Teléfono Contacto
            30, // Fax Contacto
            31, // E-Mail Contacto
            32, // Código Vendedor
            33, // Condición de Venta
            35, // Código Categoría Cliente
            36, // Código Zona Vendedor
            37, // Código Canal de Venta
            38, // Lugar Despacho
            39, // Dirección Despacho
            40, // Código Comuna Despacho
            41, // Código Ciudad Despacho
            42, // Código País Despacho
            43, // Teléfono 1 Despacho
            44, // Teléfono 2 Despacho
            45, // Teléfono 3 Despacho
            46, // Fax Despacho
            47, // Atención Despacho
            48, // Código Cobrador
            49, // Dirección Cobranza
            50, // Código Comuna Cobranza
            51, // Código Ciudad Cobranza
            52, // Código País Cobranza
            53, // Teléfono
            55, // Código Lista Precio
            56, // eMail DTE
            57, // Es Emisor o Receptor de Documentos Electrónicos
            58, // Código Clasificación de Negocio
            59, // Cuenta clientes doctos. Moneda Base
            60, // Cuenta clientes doctos. Moneda Extranjera y/o Exportación
            61, // Código Banco
            62, // Cuenta Corriente
            63, // Código de condición de pago Proveedor
        ];
        
        return in_array($columnIndex, $textFields);
    }

    /**
     * Limpia archivos temporales antiguos
     */
    public function cleanTempFiles(): void
    {
        $tempDir = storage_path('app/temp');
        
        if (!is_dir($tempDir)) {
            return;
        }
        
        $files = glob($tempDir . '/auxiliares_softland_*.{xlsx,csv}', GLOB_BRACE);
        $cutoff = Carbon::now()->subHours(2);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff->timestamp) {
                unlink($file);
            }
        }
    }
}