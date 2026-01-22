<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Services\Admin\Institutions\ImportInstitutionsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class InstitutionController extends Controller
{
    /**
     * Display a listing of institutions
     */
    public function index()
    {
        try {
            $institutions = Institution::withCount('courses')
                ->orderBy('name', 'asc')
                ->get()
                ->map(function ($institution) {
                    return [
                        'id' => $institution->id,
                        'code' => $institution->code,
                        'name' => $institution->name,
                        'type' => $institution->type,
                        'address' => $institution->address,
                        'phone' => $institution->phone,
                        'email' => $institution->email,
                        'website' => $institution->website,
                        'active' => $institution->active,
                        'courses_count' => $institution->courses_count,
                        'created_at' => $institution->created_at->format('d/m/Y'),
                    ];
                });

            return Inertia::render('Admin/Institutions/Index', [
                'institutions' => $institutions
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cargar instituciones: ' . $e->getMessage());
            return back()->with('error', 'Error al cargar las instituciones.');
        }
    }

    /**
     * Show the form for creating a new institution
     */
    public function create()
    {
        return Inertia::render('Admin/Institutions/Create');
    }

    /**
     * Store a newly created institution
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'code' => 'nullable|string|max:50',
                'name' => 'required|string|max:255',
                'type' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'website' => 'nullable|url|max:255',
            ], [
                'name.required' => 'El nombre de la institución es obligatorio.',
                'email.email' => 'El formato del email no es válido.',
                'website.url' => 'El formato del sitio web no es válido.',
            ]);

            if ($validator->fails()) {
                // Si es una petición AJAX (desde modal) pero NO es Inertia, devolver JSON
                if (!$request->header('X-Inertia') && ($request->wantsJson() || $request->ajax())) {
                    return response()->json([
                        'success' => false,
                        'errors' => $validator->errors()->toArray(),
                        'message' => 'Error en la validación de datos.'
                    ], 422);
                }

                return back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('error', 'Error en la validación de datos.');
            }

            $institution = Institution::create([
                'code' => $request->code,
                'name' => $request->name,
                'type' => $request->type,
                'address' => $request->address,
                'phone' => $request->phone,
                'email' => $request->email,
                'website' => $request->website,
                'active' => true,
                'created_by' => auth()->id(),
            ]);

            Log::info('Institución creada', [
                'institution_id' => $institution->id,
                'name' => $institution->name,
                'user_id' => auth()->id()
            ]);

            // Si es una petición AJAX (desde modal) pero NO es Inertia, devolver JSON
            if (!$request->header('X-Inertia') && ($request->wantsJson() || $request->ajax())) {
                return response()->json([
                    'success' => true,
                    'institution' => [
                        'id' => $institution->id,
                        'code' => $institution->code,
                        'name' => $institution->name,
                        'type' => $institution->type,
                        'address' => $institution->address,
                        'phone' => $institution->phone,
                        'email' => $institution->email,
                        'website' => $institution->website,
                    ],
                    'message' => 'Institución creada exitosamente.'
                ]);
            }

            return redirect()
                ->route('admin.institutions.index')
                ->with('success', 'Institución creada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear institución: ' . $e->getMessage());

            // Si es una petición AJAX (desde modal) pero NO es Inertia, devolver JSON
            if (!$request->header('X-Inertia') && ($request->wantsJson() || $request->ajax())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear la institución: ' . $e->getMessage()
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Error al crear la institución: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing an institution
     */
    public function edit(Institution $institution)
    {
        try {
            return Inertia::render('Admin/Institutions/Edit', [
                'institution' => [
                    'id' => $institution->id,
                    'code' => $institution->code,
                    'name' => $institution->name,
                    'type' => $institution->type,
                    'address' => $institution->address,
                    'phone' => $institution->phone,
                    'email' => $institution->email,
                    'website' => $institution->website,
                    'active' => $institution->active,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de edición: ' . $e->getMessage());
            return back()->with('error', 'Error al cargar el formulario de edición.');
        }
    }

    /**
     * Update the specified institution
     */
    public function update(Request $request, Institution $institution)
    {
        try {
            $validator = Validator::make($request->all(), [
                'code' => 'nullable|string|max:50',
                'name' => 'required|string|max:255',
                'type' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'website' => 'nullable|url|max:255',
            ], [
                'name.required' => 'El nombre de la institución es obligatorio.',
                'email.email' => 'El formato del email no es válido.',
                'website.url' => 'El formato del sitio web no es válido.',
            ]);

            if ($validator->fails()) {
                return back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('error', 'Error en la validación de datos.');
            }

            $institution->update([
                'code' => $request->code,
                'name' => $request->name,
                'type' => $request->type,
                'address' => $request->address,
                'phone' => $request->phone,
                'email' => $request->email,
                'website' => $request->website,
            ]);

            Log::info('Institución actualizada', [
                'institution_id' => $institution->id,
                'name' => $institution->name,
                'user_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.institutions.index')
                ->with('success', 'Institución actualizada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar institución: ' . $e->getMessage());
            return back()
                ->withInput()
                ->with('error', 'Error al actualizar la institución: ' . $e->getMessage());
        }
    }

    /**
     * Import institutions from Excel file
     */
    public function import(Request $request, ImportInstitutionsService $importService)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            ], [
                'file.required' => 'Debe seleccionar un archivo.',
                'file.mimes' => 'El archivo debe ser de tipo Excel (xlsx, xls) o CSV.',
                'file.max' => 'El archivo no debe superar los 10MB.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()->toArray(),
                    'message' => 'Error en la validación del archivo.'
                ], 422);
            }

            $result = $importService->processExcel($request->file('file'));

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'stats' => $result['stats'],
                    'details' => $result['details'],
                    'message' => sprintf(
                        'Importación completada: %d creadas, %d actualizadas, %d omitidas, %d con errores.',
                        $result['stats']['created'],
                        $result['stats']['updated'],
                        $result['stats']['skipped'],
                        $result['stats']['failed']
                    )
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Error al procesar el archivo.'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error al importar instituciones: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al importar instituciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified institution
     */
    public function destroy(Institution $institution)
    {
        try {
            // Check if institution has courses
            if ($institution->courses()->count() > 0) {
                return back()->with('error', 'No se puede eliminar la institución porque tiene cursos asociados.');
            }

            $institutionName = $institution->name;
            $institution->delete();

            Log::info('Institución eliminada', [
                'institution_id' => $institution->id,
                'name' => $institutionName,
                'user_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.institutions.index')
                ->with('success', 'Institución eliminada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar institución: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar la institución: ' . $e->getMessage());
        }
    }
}
