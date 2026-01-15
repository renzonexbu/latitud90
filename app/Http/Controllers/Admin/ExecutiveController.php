<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesExecutive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ExecutiveController extends Controller
{
    /**
     * Display a listing of executives
     */
    public function index()
    {
        try {
            $executives = SalesExecutive::withCount('programs')
                ->orderBy('name', 'asc')
                ->get()
                ->map(function ($executive) {
                    return [
                        'id' => $executive->id,
                        'name' => $executive->name,
                        'email' => $executive->email,
                        'phone' => $executive->phone,
                        'programs_count' => $executive->programs_count,
                        'created_at' => $executive->created_at ? $executive->created_at->format('d/m/Y') : '-',
                    ];
                });

            return Inertia::render('Admin/Executives/Index', [
                'executives' => $executives
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cargar ejecutivos: ' . $e->getMessage());
            return back()->with('error', 'Error al cargar los ejecutivos.');
        }
    }

    /**
     * Show the form for creating a new executive
     */
    public function create()
    {
        return Inertia::render('Admin/Executives/Create');
    }

    /**
     * Store a newly created executive
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
            ], [
                'name.required' => 'El nombre del ejecutivo es obligatorio.',
                'email.email' => 'El formato del email no es válido.',
            ]);

            if ($validator->fails()) {
                return back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('error', 'Error en la validación de datos.');
            }

            $executive = SalesExecutive::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
            ]);

            Log::info('Ejecutivo creado', [
                'executive_id' => $executive->id,
                'name' => $executive->name,
                'user_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.executives.index')
                ->with('success', 'Ejecutivo creado exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear ejecutivo: ' . $e->getMessage());
            return back()
                ->withInput()
                ->with('error', 'Error al crear el ejecutivo: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing an executive
     */
    public function edit(SalesExecutive $executive)
    {
        try {
            return Inertia::render('Admin/Executives/Edit', [
                'executive' => [
                    'id' => $executive->id,
                    'name' => $executive->name,
                    'email' => $executive->email,
                    'phone' => $executive->phone,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de edición: ' . $e->getMessage());
            return back()->with('error', 'Error al cargar el formulario de edición.');
        }
    }

    /**
     * Update the specified executive
     */
    public function update(Request $request, SalesExecutive $executive)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
            ], [
                'name.required' => 'El nombre del ejecutivo es obligatorio.',
                'email.email' => 'El formato del email no es válido.',
            ]);

            if ($validator->fails()) {
                return back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('error', 'Error en la validación de datos.');
            }

            $executive->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
            ]);

            Log::info('Ejecutivo actualizado', [
                'executive_id' => $executive->id,
                'name' => $executive->name,
                'user_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.executives.index')
                ->with('success', 'Ejecutivo actualizado exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar ejecutivo: ' . $e->getMessage());
            return back()
                ->withInput()
                ->with('error', 'Error al actualizar el ejecutivo: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified executive
     */
    public function destroy(SalesExecutive $executive)
    {
        try {
            // Check if executive has program courses
            if ($executive->programCourses()->count() > 0) {
                return back()->with('error', 'No se puede eliminar el ejecutivo porque tiene programas-cursos asociados.');
            }

            $executiveName = $executive->name;
            $executive->delete();

            Log::info('Ejecutivo eliminado', [
                'executive_id' => $executive->id,
                'name' => $executiveName,
                'user_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.executives.index')
                ->with('success', 'Ejecutivo eliminado exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar ejecutivo: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar el ejecutivo: ' . $e->getMessage());
        }
    }
}
