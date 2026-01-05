<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProcedureDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProcedureDocumentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');

        $documents = ProcedureDocument::query()
            ->with('uploader')
            ->when($search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Admin/ProcedureDocuments/Index', [
            'documents' => $documents,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/ProcedureDocuments/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240', // Max 10MB
        ]);

        try {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $filePath = $file->store('procedure_documents', 'public');

            ProcedureDocument::create([
                'title' => $request->title,
                'description' => $request->description,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
                'active' => true,
            ]);

            return redirect()
                ->route('admin.procedure-documents.index')
                ->with('success', 'Documento agregado exitosamente');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Error al subir el documento: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProcedureDocument $procedureDocument)
    {
        return Inertia::render('Admin/ProcedureDocuments/Edit', [
            'document' => $procedureDocument->load('uploader'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProcedureDocument $procedureDocument)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:10240', // Max 10MB
            'active' => 'boolean',
        ]);

        try {
            $data = [
                'title' => $request->title,
                'description' => $request->description,
                'active' => $request->boolean('active', true),
            ];

            // Si se subió un nuevo archivo, eliminar el anterior y subir el nuevo
            if ($request->hasFile('file')) {
                // Eliminar archivo anterior
                if (Storage::disk('public')->exists($procedureDocument->file_path)) {
                    Storage::disk('public')->delete($procedureDocument->file_path);
                }

                // Subir nuevo archivo
                $file = $request->file('file');
                $fileName = $file->getClientOriginalName();
                $filePath = $file->store('procedure_documents', 'public');

                $data['file_path'] = $filePath;
                $data['file_name'] = $fileName;
                $data['file_type'] = $file->getClientOriginalExtension();
                $data['file_size'] = $file->getSize();
            }

            $procedureDocument->update($data);

            return redirect()
                ->route('admin.procedure-documents.index')
                ->with('success', 'Documento actualizado exitosamente');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Error al actualizar el documento: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProcedureDocument $procedureDocument)
    {
        try {
            // Eliminar archivo del storage
            if (Storage::disk('public')->exists($procedureDocument->file_path)) {
                Storage::disk('public')->delete($procedureDocument->file_path);
            }

            $procedureDocument->delete();

            return redirect()
                ->route('admin.procedure-documents.index')
                ->with('success', 'Documento eliminado exitosamente');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Error al eliminar el documento: ' . $e->getMessage()]);
        }
    }

    /**
     * Download a document
     */
    public function download(ProcedureDocument $procedureDocument)
    {
        if (!Storage::disk('public')->exists($procedureDocument->file_path)) {
            return back()->withErrors(['error' => 'El archivo no existe']);
        }

        return Storage::disk('public')->download(
            $procedureDocument->file_path,
            $procedureDocument->file_name
        );
    }
}
