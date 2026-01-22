<?php

namespace App\Services\Admin\Institutions;

use App\Models\Institution;
use App\Traits\AdminLogging;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportInstitutionsService
{
    use AdminLogging;

    protected $expectedHeaders = [
        'nombre' => [
            'nombre', 'name', 'nombre de institución', 'nombre institucion',
            'institución', 'institucion', 'nombre_institucion', 'institution_name',
            'nombre completo', 'razon social', 'razón social'
        ],
    ];

    protected $optionalHeaders = [
        'codigo' => [
            'codigo', 'código', 'code', 'cod', 'rbd',
            'codigo_institucion', 'institution_code', 'cod_inst'
        ],
        'tipo' => [
            'tipo', 'type', 'tipo de institución', 'tipo institucion',
            'tipo_institucion', 'institution_type', 'categoria', 'categoría', 'category'
        ],
        'email' => [
            'email', 'correo', 'correo electrónico', 'correo electronico',
            'e-mail', 'mail', 'correo_electronico', 'email_institucion'
        ],
        'telefono' => [
            'telefono', 'teléfono', 'phone', 'fono', 'tel', 'celular',
            'movil', 'móvil', 'telephone', 'phone_number', 'numero_telefono'
        ],
        'direccion' => [
            'direccion', 'dirección', 'address', 'domicilio', 'ubicacion',
            'ubicación', 'calle', 'street', 'direccion_completa'
        ],
        'sitio_web' => [
            'sitio web', 'sitio_web', 'website', 'web', 'página web',
            'pagina web', 'pagina_web', 'url', 'sitio', 'portal'
        ],
    ];

    protected const CHUNK_SIZE = 500;
    protected const MAX_DETAILS = 1000;

    /**
     * Process the uploaded Excel file and import institutions
     */
    public function processExcel($file): array
    {
        $details = [];
        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        try {
            $filename = uniqid() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('temp', $filename);
            $fullPath = Storage::path($path);

            Log::info('Archivo Excel de instituciones guardado temporalmente', ['path' => $fullPath]);

            $reader = IOFactory::createReaderForFile($fullPath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();

            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();

            Log::info('Archivo Excel de instituciones cargado', [
                'total_rows' => $highestRow,
                'highest_column' => $highestColumn
            ]);

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
                throw new \Exception('No se encontró la fila de encabezados en el archivo Excel. Verifica que contenga al menos la columna "Nombre".');
            }

            Log::info('Encabezados de instituciones encontrados', ['headers' => $headers, 'row' => $headerRowIndex]);

            $this->validateHeaders($headers);
            $columnIndices = $this->getColumnIndices($headers);

            $dataStartRow = $headerRowIndex + 1;
            $currentChunk = [];
            $chunkCount = 0;
            $processedRows = 0;

            Log::info('Iniciando procesamiento de instituciones por chunks', [
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

                if ($this->isEmptyRow($rowData)) {
                    continue;
                }

                $currentChunk[] = [
                    'row_index' => $rowIndex,
                    'data' => $rowData
                ];

                if (count($currentChunk) >= self::CHUNK_SIZE) {
                    $chunkResult = $this->processChunk($currentChunk, $columnIndices, $details, $stats);
                    $details = $chunkResult['details'];
                    $stats = $chunkResult['stats'];

                    $chunkCount++;
                    $processedRows += count($currentChunk);

                    Log::info("Chunk {$chunkCount} de instituciones procesado", [
                        'rows_in_chunk' => count($currentChunk),
                        'total_processed' => $processedRows,
                        'stats' => $stats
                    ]);

                    $currentChunk = [];
                    gc_collect_cycles();
                }
            }

            if (!empty($currentChunk)) {
                $chunkResult = $this->processChunk($currentChunk, $columnIndices, $details, $stats);
                $details = $chunkResult['details'];
                $stats = $chunkResult['stats'];

                $chunkCount++;
                $processedRows += count($currentChunk);

                Log::info("Chunk final {$chunkCount} de instituciones procesado", [
                    'rows_in_chunk' => count($currentChunk),
                    'total_processed' => $processedRows,
                    'stats' => $stats
                ]);
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            Storage::delete($path);
            gc_collect_cycles();

            Log::info('Importación de instituciones completada', ['stats' => $stats, 'chunks_processed' => $chunkCount]);

            $this->logAction(
                'import',
                'institutions',
                "Importación masiva de instituciones completada",
                'Institution',
                null,
                null,
                null,
                [
                    'file_name' => $file->getClientOriginalName(),
                    'total_rows_processed' => $stats['created'] + $stats['updated'] + $stats['skipped'] + $stats['failed'],
                    'created' => $stats['created'],
                    'updated' => $stats['updated'],
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
            Log::error('Error al procesar archivo Excel de instituciones: ' . $e->getMessage(), [
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

                $rowData = [];
                foreach ($columnIndices as $key => $index) {
                    $rowData[$key] = isset($row[$index]) ? trim($row[$index] ?? '') : null;
                }

                $result = $this->processInstitutionRow($rowData, $rowNumber);

                if ($result['status'] !== 'success' || count($details) < self::MAX_DETAILS) {
                    $details[] = [
                        'row' => $rowNumber,
                        'status' => $result['status'],
                        'message' => $result['message'],
                        'data' => $result['data'] ?? null
                    ];
                }

                if ($result['status'] === 'created') {
                    $stats['created']++;
                } elseif ($result['status'] === 'updated') {
                    $stats['updated']++;
                } elseif ($result['status'] === 'skipped') {
                    $stats['skipped']++;
                } else {
                    $stats['failed']++;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en chunk de instituciones, rollback ejecutado: ' . $e->getMessage());

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

        foreach ($this->expectedHeaders['nombre'] as $headerVariation) {
            if (stripos($rowString, $headerVariation) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Process a single institution row
     */
    private function processInstitutionRow(array $rowData, int $rowNumber): array
    {
        try {
            $name = $rowData['nombre'] ?? null;
            $code = $rowData['codigo'] ?? null;

            if (empty($name) && empty($code)) {
                return [
                    'status' => 'error',
                    'message' => "Fila {$rowNumber}: Se requiere al menos nombre o código",
                    'data' => $rowData
                ];
            }

            $institution = null;
            $action = 'created';

            if (!empty($code)) {
                $institution = Institution::where('code', $code)->first();
            }

            if (!$institution && !empty($name)) {
                $institution = Institution::where('name', $name)->first();
            }

            if ($institution) {
                $action = 'updated';

                $updateData = [];

                if (!empty($code) && $code !== $institution->code) {
                    $updateData['code'] = $code;
                }
                if (!empty($name) && $name !== $institution->name) {
                    $updateData['name'] = $name;
                }
                if (!empty($rowData['tipo'])) {
                    $updateData['type'] = $this->normalizeType($rowData['tipo']);
                }
                if (!empty($rowData['email']) && filter_var($rowData['email'], FILTER_VALIDATE_EMAIL)) {
                    $updateData['email'] = $rowData['email'];
                }
                if (!empty($rowData['telefono'])) {
                    $updateData['phone'] = $rowData['telefono'];
                }
                if (!empty($rowData['direccion'])) {
                    $updateData['address'] = $rowData['direccion'];
                }
                if (!empty($rowData['sitio_web'])) {
                    $updateData['website'] = $this->normalizeUrl($rowData['sitio_web']);
                }

                if (!empty($updateData)) {
                    $institution->update($updateData);

                    Log::info("Institución actualizada", [
                        'id' => $institution->id,
                        'name' => $institution->name,
                        'updated_fields' => array_keys($updateData)
                    ]);

                    return [
                        'status' => 'updated',
                        'message' => "Institución '{$institution->name}' actualizada exitosamente",
                        'data' => ['id' => $institution->id, 'name' => $institution->name]
                    ];
                } else {
                    return [
                        'status' => 'skipped',
                        'message' => "Institución '{$institution->name}' sin cambios - omitida",
                        'data' => ['id' => $institution->id, 'name' => $institution->name]
                    ];
                }
            } else {
                if (empty($name)) {
                    return [
                        'status' => 'error',
                        'message' => "Fila {$rowNumber}: El nombre es requerido para crear una nueva institución",
                        'data' => $rowData
                    ];
                }

                $institution = Institution::create([
                    'code' => $code,
                    'name' => $name,
                    'type' => !empty($rowData['tipo']) ? $this->normalizeType($rowData['tipo']) : null,
                    'email' => !empty($rowData['email']) && filter_var($rowData['email'], FILTER_VALIDATE_EMAIL) ? $rowData['email'] : null,
                    'phone' => $rowData['telefono'] ?? null,
                    'address' => $rowData['direccion'] ?? null,
                    'website' => !empty($rowData['sitio_web']) ? $this->normalizeUrl($rowData['sitio_web']) : null,
                    'active' => true,
                    'created_by' => auth()->id(),
                ]);

                Log::info("Institución creada", [
                    'id' => $institution->id,
                    'code' => $institution->code,
                    'name' => $institution->name
                ]);

                return [
                    'status' => 'created',
                    'message' => "Institución '{$institution->name}' creada exitosamente",
                    'data' => ['id' => $institution->id, 'name' => $institution->name]
                ];
            }
        } catch (\Exception $e) {
            Log::error("Error procesando institución en fila {$rowNumber}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'row_data' => $rowData
            ]);

            return [
                'status' => 'error',
                'message' => "Error en fila {$rowNumber}: " . $e->getMessage(),
                'data' => $rowData
            ];
        }
    }

    /**
     * Validate that all required headers are present
     */
    private function validateHeaders(array $headers): void
    {
        $normalizedHeaders = array_map(function($header) {
            return strtolower(trim($header));
        }, $headers);

        $found = false;
        foreach ($this->expectedHeaders['nombre'] as $headerVariation) {
            foreach ($normalizedHeaders as $normalizedHeader) {
                if ($normalizedHeader === $headerVariation ||
                    stripos($normalizedHeader, $headerVariation) !== false) {
                    $found = true;
                    break 2;
                }
            }
        }

        if (!$found) {
            throw new \Exception('Falta la columna requerida: Nombre');
        }
    }

    /**
     * Get column indices for each field
     */
    private function getColumnIndices(array $headers): array
    {
        $indices = [];

        $normalizedHeaders = array_map(function($header) {
            return strtolower(trim($header));
        }, $headers);

        $allMappings = array_merge($this->expectedHeaders, $this->optionalHeaders);

        foreach ($allMappings as $field => $possibleHeaders) {
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
     * Normalize institution type
     */
    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));

        $mappings = [
            'escuela' => 'school',
            'school' => 'school',
            'colegio' => 'colegio',
            'liceo' => 'liceo',
            'universidad' => 'university',
            'university' => 'university',
            'otro' => 'other',
            'other' => 'other',
        ];

        return $mappings[$type] ?? $type;
    }

    /**
     * Normalize URL
     */
    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if (!empty($url) && !preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://' . $url;
        }

        return $url;
    }
}
