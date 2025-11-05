<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentTemplateController extends Controller
{
    /**
     * Listar todas las plantillas
     */
    public function index(Request $request)
    {
        $type = $request->get('type');

        $query = DocumentTemplate::with('creator')
            ->orderBy('created_at', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        $templates = $query->paginate(20);

        return Inertia::render('Admin/DocumentTemplates/Index', [
            'templates' => $templates,
            'filters' => [
                'type' => $type,
            ],
            'types' => [
                DocumentTemplate::TYPE_CONTRACT => 'Contrato de Reserva',
                DocumentTemplate::TYPE_PAYMENT_RECEIPT => 'Comprobante de Pago',
            ],
        ]);
    }

    /**
     * Mostrar formulario para crear nueva plantilla
     */
    public function create()
    {
        return Inertia::render('Admin/DocumentTemplates/Create', [
            'types' => [
                DocumentTemplate::TYPE_CONTRACT => 'Contrato de Reserva',
                DocumentTemplate::TYPE_PAYMENT_RECEIPT => 'Comprobante de Pago',
            ],
        ]);
    }

    /**
     * Guardar nueva plantilla
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:' . DocumentTemplate::TYPE_CONTRACT . ',' . DocumentTemplate::TYPE_PAYMENT_RECEIPT,
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        try {
            // Si se marca como activa, desactivar otras plantillas del mismo tipo
            if ($validated['is_active'] ?? false) {
                DocumentTemplate::where('type', $validated['type'])
                    ->update(['is_active' => false]);
            }

            $template = DocumentTemplate::create([
                'type' => $validated['type'],
                'name' => $validated['name'],
                'content' => $validated['content'],
                'variables' => $validated['variables'] ?? [],
                'is_active' => $validated['is_active'] ?? false,
                'version' => 1,
                'created_by' => Auth::id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            return redirect()->route('admin.document-templates.index')
                ->with('success', 'Plantilla creada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error creating document template', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->withErrors(['error' => 'Error al crear la plantilla: ' . $e->getMessage()]);
        }
    }

    /**
     * Mostrar detalles de una plantilla
     */
    public function show(DocumentTemplate $documentTemplate)
    {
        $documentTemplate->load(['creator', 'parentTemplate', 'childTemplates']);

        return Inertia::render('Admin/DocumentTemplates/Show', [
            'template' => $documentTemplate,
            'versions' => $documentTemplate->getAllVersions(),
        ]);
    }

    /**
     * Mostrar formulario para editar plantilla
     */
    public function edit(DocumentTemplate $documentTemplate)
    {
        // Obtener datos de ejemplo para mostrar en las variables
        $exampleData = $this->getExampleData($documentTemplate->type);

        return Inertia::render('Admin/DocumentTemplates/Edit', [
            'template' => $documentTemplate,
            'types' => [
                DocumentTemplate::TYPE_CONTRACT => 'Contrato de Reserva',
                DocumentTemplate::TYPE_PAYMENT_RECEIPT => 'Comprobante de Pago',
            ],
            'exampleData' => $exampleData,
        ]);
    }

    /**
     * Actualizar plantilla (crea nueva versión)
     */
    public function update(Request $request, DocumentTemplate $documentTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
            'create_new_version' => 'boolean',
        ]);

        try {
            $createNewVersion = $validated['create_new_version'] ?? true;

            if ($createNewVersion) {
                // Crear nueva versión
                $newTemplate = $documentTemplate->createNewVersion([
                    'name' => $validated['name'],
                    'content' => $validated['content'],
                    'variables' => $validated['variables'] ?? $documentTemplate->variables,
                    'notes' => $validated['notes'] ?? 'Actualización de plantilla',
                ], Auth::id());

                return redirect()->route('admin.document-templates.show', $newTemplate->id)
                    ->with('success', 'Nueva versión creada exitosamente');
            } else {
                // Actualizar la plantilla actual sin crear nueva versión
                if ($validated['is_active'] ?? false) {
                    DocumentTemplate::where('type', $documentTemplate->type)
                        ->where('id', '!=', $documentTemplate->id)
                        ->update(['is_active' => false]);
                }

                $documentTemplate->update([
                    'name' => $validated['name'],
                    'content' => $validated['content'],
                    'variables' => $validated['variables'] ?? $documentTemplate->variables,
                    'is_active' => $validated['is_active'] ?? $documentTemplate->is_active,
                    'notes' => $validated['notes'] ?? $documentTemplate->notes,
                ]);

                return redirect()->route('admin.document-templates.show', $documentTemplate->id)
                    ->with('success', 'Plantilla actualizada exitosamente');
            }
        } catch (\Exception $e) {
            Log::error('Error updating document template', [
                'error' => $e->getMessage(),
                'template_id' => $documentTemplate->id,
                'user_id' => Auth::id(),
            ]);

            return back()->withErrors(['error' => 'Error al actualizar la plantilla: ' . $e->getMessage()]);
        }
    }

    /**
     * Eliminar plantilla
     */
    public function destroy(DocumentTemplate $documentTemplate)
    {
        try {
            if ($documentTemplate->is_active) {
                return back()->withErrors(['error' => 'No se puede eliminar una plantilla activa. Desactívala primero.']);
            }

            $documentTemplate->delete();

            return redirect()->route('admin.document-templates.index')
                ->with('success', 'Plantilla eliminada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error deleting document template', [
                'error' => $e->getMessage(),
                'template_id' => $documentTemplate->id,
            ]);

            return back()->withErrors(['error' => 'Error al eliminar la plantilla: ' . $e->getMessage()]);
        }
    }

    /**
     * Activar/Desactivar plantilla
     */
    public function toggleActive(DocumentTemplate $documentTemplate)
    {
        try {
            $newStatus = !$documentTemplate->is_active;

            // Si se está activando, desactivar otras del mismo tipo
            if ($newStatus) {
                DocumentTemplate::where('type', $documentTemplate->type)
                    ->where('id', '!=', $documentTemplate->id)
                    ->update(['is_active' => false]);
            }

            $documentTemplate->update(['is_active' => $newStatus]);

            return back()->with('success', $newStatus ? 'Plantilla activada exitosamente' : 'Plantilla desactivada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error toggling template status', [
                'error' => $e->getMessage(),
                'template_id' => $documentTemplate->id,
            ]);

            return back()->withErrors(['error' => 'Error al cambiar el estado de la plantilla']);
        }
    }

    /**
     * Previsualizar PDF con datos de ejemplo
     */
    public function preview(Request $request, DocumentTemplate $documentTemplate)
    {
        try {
            // Datos de ejemplo para previsualizar
            $exampleData = $this->getExampleData($documentTemplate->type);

            // Renderizar contenido con datos de ejemplo
            $content = $documentTemplate->render($exampleData);

            // Generar PDF con la estructura completa (estilos + contenido)
            $fullHtml = $this->buildFullHtml($content, $documentTemplate->type);

            $pdf = Pdf::loadHTML($fullHtml);

            return $pdf->stream('preview_' . $documentTemplate->type . '.pdf');
        } catch (\Exception $e) {
            Log::error('Error previewing document template', [
                'error' => $e->getMessage(),
                'template_id' => $documentTemplate->id,
            ]);

            return back()->withErrors(['error' => 'Error al generar la vista previa: ' . $e->getMessage()]);
        }
    }

    /**
     * Previsualizar PDF con contenido temporal (sin guardar)
     */
    public function previewDraft(Request $request, DocumentTemplate $documentTemplate)
    {
        try {
            $validated = $request->validate([
                'content' => 'required|string',
            ]);

            // Datos de ejemplo para previsualizar
            $exampleData = $this->getExampleData($documentTemplate->type);

            // Renderizar contenido temporal con datos de ejemplo
            $content = $validated['content'];
            foreach ($exampleData as $key => $value) {
                $content = str_replace('{{' . $key . '}}', $value, $content);
            }

            // Generar PDF con la estructura completa (estilos + contenido)
            $fullHtml = $this->buildFullHtml($content, $documentTemplate->type);

            $pdf = Pdf::loadHTML($fullHtml);

            return $pdf->stream('preview_draft_' . $documentTemplate->type . '.pdf');
        } catch (\Exception $e) {
            Log::error('Error previewing draft template', [
                'error' => $e->getMessage(),
                'template_id' => $documentTemplate->id,
            ]);

            return back()->withErrors(['error' => 'Error al generar la vista previa: ' . $e->getMessage()]);
        }
    }

    /**
     * Duplicar plantilla
     */
    public function duplicate(DocumentTemplate $documentTemplate)
    {
        try {
            $newTemplate = DocumentTemplate::create([
                'type' => $documentTemplate->type,
                'name' => $documentTemplate->name . ' (Copia)',
                'content' => $documentTemplate->content,
                'variables' => $documentTemplate->variables,
                'is_active' => false,
                'version' => 1,
                'created_by' => Auth::id(),
                'notes' => 'Duplicado de plantilla ID: ' . $documentTemplate->id,
            ]);

            return redirect()->route('admin.document-templates.edit', $newTemplate->id)
                ->with('success', 'Plantilla duplicada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error duplicating document template', [
                'error' => $e->getMessage(),
                'template_id' => $documentTemplate->id,
            ]);

            return back()->withErrors(['error' => 'Error al duplicar la plantilla']);
        }
    }

    /**
     * Obtener datos de ejemplo según el tipo de documento
     */
    private function getExampleData(string $type): array
    {
        $commonData = [
            'logo_base64' => $this->getImageBase64(config('lat90.pdf.logo.header')),
            'divider_base64' => $this->getImageBase64(config('lat90.pdf.logo.divider')),
            'firma_base64' => $this->getImageBase64(config('lat90.pdf.logo.signature')),
            'empresa_direccion' => config('lat90.company.address.full', 'Carlos Antúnez 1941, Providencia'),
            'empresa_region' => config('lat90.company.address.region', 'Región Metropolitana'),
            'empresa_telefono' => config('lat90.company.phone', '+56 9 7909 1738'),
            'empresa_sitio' => config('lat90.company.website', 'www.latitud90.com'),
        ];

        if ($type === DocumentTemplate::TYPE_CONTRACT) {
            return array_merge($commonData, [
                'folio' => 'PROG-12345678',
                'ciudad' => 'Santiago de Chile',
                'fecha' => now()->format('d \d\e F \d\e Y'),
                'prestador_nombre' => config('lat90.company.legal_name', 'Experiencias Educativas y Capacitaciones SpA'),
                'prestador_rut' => config('lat90.company.rut', '76.203.719-K'),
                'representante_nombre' => config('lat90.company.representative.name', 'Carolina Emhart García'),
                'representante_rut' => config('lat90.company.representative.rut', '13.670.825-2'),
                'domicilio_ciudad' => config('lat90.company.address.city', 'Santiago'),
                'domicilio_comuna' => config('lat90.company.address.commune', 'Providencia'),
                'domicilio_calle' => config('lat90.company.address.street', 'Carlos Antúnez 1941'),
                'apoderado_nombre' => 'Juan Pérez García',
                'apoderado_rut' => '12.345.678-9',
                'alumno_nombre' => 'María Pérez López',
                'alumno_documento_label' => 'cédula nacional de identidad número',
                'alumno_rut' => '23.456.789-0',
                'cotizacion_fecha' => now()->subMonths(2)->format('d \d\e F \d\e Y'),
                'programa_anio' => now()->addYear()->format('Y'),
                'programa_nombre' => 'Full San Pedro de Atacama 2026',
                'prestador_nombre_firma' => config('lat90.company.legal_name', 'Experiencias Educativas y Capacitaciones SpA'),
                'firmante_nombre' => config('lat90.pdf.signature.name', 'Carmen Gutiérrez M.'),
                'firmante_cargo' => config('lat90.pdf.signature.position', 'Jefa área de recaudación'),
            ]);
        }

        // TYPE_PAYMENT_RECEIPT
        return array_merge($commonData, [
            'folio' => 'PROG-12345678',
            'apoderado_nombre' => 'Juan Pérez García',
            'monto' => '500.000',
            'fecha' => now()->format('d \d\e F, Y'),
            'alumno_nombre' => 'María Pérez López',
            'document_type' => 'cédula nacional de identidad Nro.',
            'alumno_rut' => '23.456.789-0',
            'valor_programa' => '2.500.000',
            'destino' => 'San Pedro de Atacama',
            'fecha_programa' => now()->addYear()->format('F, Y'),
            'monto_abono' => '500.000',
            'fecha_abono' => now()->format('d-m-Y'),
            'saldo_abonado' => '1.500.000',
            'prestador_nombre_firma' => config('lat90.company.legal_name', 'Experiencias Educativas y Capacitaciones SpA'),
            'firmante_nombre' => config('lat90.pdf.signature.name', 'Carmen Gutiérrez M.'),
            'firmante_cargo' => config('lat90.pdf.signature.position', 'Jefa área de recaudación'),
        ]);
    }

    /**
     * Obtener imagen en base64
     */
    private function getImageBase64(string $path): string
    {
        $fullPath = resource_path($path);
        if (file_exists($fullPath)) {
            return base64_encode(file_get_contents($fullPath));
        }
        return '';
    }

    /**
     * Construir HTML completo con estilos
     */
    private function buildFullHtml(string $content, string $type): string
    {
        // Cargar estilos desde la vista Blade original
        $styles = $this->getStyles();

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Vista Previa - {$type}</title>
    <style>{$styles}</style>
</head>
<body>
    <header></header>
    <footer>
        <div class="footer-inner">
            <img src="data:image/png;base64,{$this->getImageBase64(config('lat90.pdf.logo.footer'))}" alt="Lat90" style="width:1.02cm; height:1.34cm;" />
        </div>
    </footer>
    <main>
        {$content}
    </main>
</body>
</html>
HTML;
    }

    /**
     * Obtener estilos CSS para los PDFs
     */
    private function getStyles(): string
    {
        return <<<'CSS'
@page {
    margin: 60px 3cm 80px 3cm;
}

body {
    font-family: 'Calibri Light', Calibri, sans-serif;
    font-size: 11px;
    color: #222;
    line-height: 1.35;
}

header {
    position: fixed;
    top: -10px;
    left: 0;
    right: 0;
    height: 0;
}

footer {
    position: fixed;
    bottom: -50px;
    left: 0;
    right: 0;
    height: 50px;
    font-size: 11px;
    color: #666;
}

.footer-inner {
    width: 100%;
    text-align: left;
    padding-top: 10px;
}

h1 {
    font-family: 'Calibri Light', Calibri, sans-serif;
    font-size: 11px;
    text-align: center;
    text-transform: uppercase;
    margin: 12px 0 4px 0;
}

h2 {
    font-family: 'Calibri Light', Calibri, sans-serif;
    font-size: 11px;
    font-weight: bold;
    margin: 18px 0 12px 0;
    text-transform: uppercase;
}

p {
    font-family: 'Calibri Light', Calibri, sans-serif;
    font-size: 11px;
    margin: 6px 0;
    text-align: justify;
}

ul, ol {
    font-family: 'Calibri Light', Calibri, sans-serif;
    font-size: 11px;
    margin: 6px 0 6px 18px;
}

.section {
    margin-bottom: 8px;
}

.folio {
    text-align: right;
    font-weight: bold;
    margin-bottom: 14px;
    font-size: 11px;
}

.signature {
    width: 100%;
    margin-top: 20px;
    text-align: center;
}

.sig-role {
    font-family: 'Calibri Light', Calibri, sans-serif;
    font-size: 11px;
    margin-top: 8px;
}

.header-inner {
    width: 100%;
    display: table;
}

.header-left,
.header-right {
    display: table-cell;
    vertical-align: top;
    width: 50%;
}

.header-right {
    text-align: right;
}

.logo-lat90 {
    width: 2.34cm;
    height: 2.7cm;
}

.page-divider {
    width: 130px;
    display: inline-block;
    margin-top: 6px;
}

.company-data {
    font-size: 8pt;
    color: #0f6c7a;
    line-height: 1.3;
}

.center {
    text-align: center;
}

.mt-2 {
    margin-top: 6px;
}

.mt-4 {
    margin-top: 12px;
}

.mb-2 {
    margin-bottom: 6px;
}

.mb-4 {
    margin-bottom: 12px;
}

.bold {
    font-weight: bold;
}
CSS;
    }
}
