<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SchoolController extends Controller
{
    public function index()
    {
        $schools = School::orderBy('order')->orderBy('name')->get();
        $sectionContent = SiteContent::where('section', 'schools')->get();

        return Inertia::render('Admin/Schools/Index', [
            'schools' => $schools,
            'sectionContent' => $sectionContent,
        ]);
    }

    public function updateSectionContent(Request $request)
    {
        $request->validate([
            'contents' => 'required|array',
        ]);

        foreach ($request->contents as $item) {
            if (isset($item['id'])) {
                SiteContent::where('id', $item['id'])->update([
                    'value' => $item['value'] ?? '',
                    'use_default' => $item['use_default'] ?? false,
                ]);
            }
        }

        return redirect()->route('admin.schools.index')
            ->with('success', 'Contenido actualizado correctamente');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $maxOrder = School::max('order') ?? 0;

        School::create([
            'name' => $request->name,
            'logo' => $request->logo,
            'order' => $maxOrder + 1,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->route('admin.schools.index')
            ->with('success', 'Colegio agregado correctamente');
    }

    public function update(Request $request, School $school)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $school->update([
            'name' => $request->name,
            'logo' => $request->logo,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->route('admin.schools.index')
            ->with('success', 'Colegio actualizado correctamente');
    }

    public function destroy(School $school)
    {
        // Si el logo es un archivo subido, eliminarlo
        if ($school->logo && str_starts_with($school->logo, 'schools/')) {
            Storage::disk('public')->delete($school->logo);
        }

        $school->delete();

        return redirect()->route('admin.schools.index')
            ->with('success', 'Colegio eliminado correctamente');
    }

    public function uploadLogo(Request $request)
    {
        try {
            $request->validate([
                'logo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            ]);

            $path = $request->file('logo')->store('schools', 'public');

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

    public function updateOrder(Request $request)
    {
        $request->validate([
            'schools' => 'required|array',
            'schools.*.id' => 'required|exists:schools,id',
            'schools.*.order' => 'required|integer',
        ]);

        foreach ($request->schools as $item) {
            School::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json(['success' => true]);
    }
}
