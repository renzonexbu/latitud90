<?php

namespace App\Http\Controllers\Admin\Maintainer;

use App\Http\Controllers\Controller;
use App\Models\TermCondition;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TermsConditionsController extends Controller
{
    /**
     * Mostrar lista de términos y condiciones
     */
    public function index()
    {
        $terms = TermCondition::ordered()->get();

        return Inertia::render('Admin/Maintainer/TermsConditions', [
            'terms' => $terms
        ]);
    }

    /**
     * Crear nuevo término
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_active' => 'boolean',
        ]);

        // Obtener la última posición
        $lastPosition = TermCondition::max('position') ?? 0;
        $validated['position'] = $lastPosition + 1;

        $term = TermCondition::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Término creado exitosamente',
            'term' => $term
        ]);
    }

    /**
     * Actualizar término existente
     */
    public function update(Request $request, int $id)
    {
        $term = TermCondition::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $term->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Término actualizado exitosamente',
            'term' => $term
        ]);
    }

    /**
     * Eliminar término
     */
    public function destroy(int $id)
    {
        $term = TermCondition::findOrFail($id);
        $term->delete();

        // Reordenar posiciones
        $this->reorderPositions();

        return response()->json([
            'success' => true,
            'message' => 'Término eliminado exitosamente'
        ]);
    }

    /**
     * Cambiar estado activo/inactivo
     */
    public function toggleStatus(int $id)
    {
        $term = TermCondition::findOrFail($id);
        $term->is_active = !$term->is_active;
        $term->save();

        return response()->json([
            'success' => true,
            'message' => $term->is_active ? 'Término activado' : 'Término desactivado',
            'term' => $term
        ]);
    }

    /**
     * Reordenar términos
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:terms_conditions,id'
        ]);

        foreach ($validated['order'] as $position => $id) {
            TermCondition::where('id', $id)->update(['position' => $position + 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Orden actualizado exitosamente'
        ]);
    }

    /**
     * Reordenar posiciones después de eliminar
     */
    private function reorderPositions()
    {
        $terms = TermCondition::orderBy('position')->get();
        foreach ($terms as $index => $term) {
            $term->position = $index + 1;
            $term->save();
        }
    }
}
