<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SiteContentController extends Controller
{
    /**
     * Mostrar listado de secciones
     */
    public function index()
    {
        $allSections = SiteContent::getSections();

        // Filtrar solo las secciones activas que se muestran en el home
        $activeSectionKeys = ['hero', 'schools', 'experiences', 'courses', 'faq', 'payment_form', 'contact', 'footer'];
        $sections = array_filter($allSections, function($key) use ($activeSectionKeys) {
            return in_array($key, $activeSectionKeys);
        }, ARRAY_FILTER_USE_KEY);

        $contentBySection = [];

        foreach ($sections as $key => $label) {
            $contentBySection[$key] = [
                'label' => $label,
                'items' => SiteContent::where('section', $key)
                    ->orderBy('order')
                    ->get(),
            ];
        }

        return Inertia::render('Admin/SiteContent/Index', [
            'sections' => $sections,
            'contentBySection' => $contentBySection,
        ]);
    }

    /**
     * Mostrar formulario de edición de una sección
     */
    public function edit(string $section)
    {
        $sections = SiteContent::getSections();

        if (!array_key_exists($section, $sections)) {
            return redirect()->route('admin.site-content.index')
                ->with('error', 'Sección no encontrada');
        }

        // Redirigir FAQs al nuevo mantenedor especializado
        if ($section === 'faq') {
            return redirect()->route('admin.maintainer.faqs.index');
        }

        // Redirigir formulario de pago al mantenedor especializado
        if ($section === 'payment_form') {
            return redirect()->route('admin.maintainer.payment-form.index');
        }

        $contents = SiteContent::where('section', $section)
            ->orderBy('order')
            ->get();

        return Inertia::render('Admin/SiteContent/Edit', [
            'section' => $section,
            'sectionLabel' => $sections[$section],
            'contents' => $contents,
            'types' => SiteContent::getTypes(),
        ]);
    }

    /**
     * Actualizar contenido de una sección
     */
    public function update(Request $request, string $section)
    {
        $sections = SiteContent::getSections();

        if (!array_key_exists($section, $sections)) {
            return redirect()->route('admin.site-content.index')
                ->with('error', 'Sección no encontrada');
        }

        $contents = $request->input('contents', []);

        foreach ($contents as $item) {
            if (empty($item['key'])) {
                continue;
            }

            $data = [
                'section' => $section,
                'key' => $item['key'],
                'type' => $item['type'] ?? 'text',
                'value' => $item['value'] ?? '',
                'label' => $item['label'] ?? '',
                'order' => $item['order'] ?? 0,
                'is_active' => $item['is_active'] ?? true,
                'use_default' => $item['use_default'] ?? false,
                'focal_point_mobile_x' => $item['focal_point_mobile_x'] ?? 50,
                'focal_point_mobile_y' => $item['focal_point_mobile_y'] ?? 50,
                'focal_point_desktop_x' => $item['focal_point_desktop_x'] ?? 50,
                'focal_point_desktop_y' => $item['focal_point_desktop_y'] ?? 50,
            ];

            if (isset($item['id'])) {
                SiteContent::where('id', $item['id'])->update($data);
            } else {
                $data['default_value'] = $data['value'];
                SiteContent::create($data);
            }
        }

        return redirect()->route('admin.site-content.edit', $section)
            ->with('success', 'Contenido actualizado correctamente');
    }

    /**
     * Subir imagen (optimizada y convertida a WebP)
     */
    public function uploadImage(Request $request, ImageOptimizationService $imageService)
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:20480',
                'section' => 'required|string',
            ]);

            $directory = 'site-content/' . $request->section;
            $path = $imageService->optimizeAndStore(
                $request->file('image'),
                $directory
            );

            return response()->json([
                'success' => true,
                'path' => $path,
                'url' => asset('storage/' . $path),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar contenido específico
     */
    public function destroy(SiteContent $siteContent)
    {
        // Si es imagen, eliminar el archivo
        if ($siteContent->type === 'image' && $siteContent->value) {
            Storage::disk('public')->delete($siteContent->value);
        }

        $section = $siteContent->section;
        $siteContent->delete();

        return redirect()->route('admin.site-content.edit', $section)
            ->with('success', 'Contenido eliminado correctamente');
    }

    /**
     * Agregar nuevo contenido a una sección
     */
    public function store(Request $request)
    {
        $request->validate([
            'section' => 'required|string',
            'key' => 'required|string',
            'type' => 'required|in:text,textarea,html,image',
            'label' => 'nullable|string',
            'value' => 'nullable|string',
        ]);

        $sections = SiteContent::getSections();

        if (!array_key_exists($request->section, $sections)) {
            return back()->with('error', 'Sección no válida');
        }

        // Verificar que no exista ya
        $exists = SiteContent::where('section', $request->section)
            ->where('key', $request->key)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Ya existe un contenido con esa clave en esta sección');
        }

        $maxOrder = SiteContent::where('section', $request->section)->max('order') ?? 0;

        SiteContent::create([
            'section' => $request->section,
            'key' => $request->key,
            'type' => $request->type,
            'label' => $request->label ?? $request->key,
            'value' => $request->value ?? '',
            'order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        return redirect()->route('admin.site-content.edit', $request->section)
            ->with('success', 'Contenido agregado correctamente');
    }
}
