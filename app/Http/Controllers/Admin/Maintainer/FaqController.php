<?php

namespace App\Http\Controllers\Admin\Maintainer;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FaqController extends Controller
{
    /**
     * Mostrar listado de FAQs
     */
    public function index()
    {
        $faqs = $this->getFaqsFromSiteContent();
        $titulo = SiteContent::where('section', 'faq')
            ->where('key', 'titulo')
            ->first();

        return Inertia::render('Admin/Maintainer/Faqs', [
            'faqs' => $faqs,
            'titulo' => $titulo?->value ?? 'Preguntas frecuentes',
        ]);
    }

    /**
     * Obtener las FAQs desde site_contents
     */
    private function getFaqsFromSiteContent(): array
    {
        $contents = SiteContent::where('section', 'faq')
            ->where('key', 'like', 'pregunta_%')
            ->orWhere(function ($query) {
                $query->where('section', 'faq')
                    ->where('key', 'like', 'respuesta_%');
            })
            ->orderBy('order')
            ->get();

        $faqs = [];
        $questions = $contents->filter(fn($item) => str_starts_with($item->key, 'pregunta_'));

        foreach ($questions as $question) {
            $number = str_replace('pregunta_', '', $question->key);
            $answer = $contents->firstWhere('key', 'respuesta_' . $number);

            $faqs[] = [
                'id' => (int) $number,
                'question' => $question->value,
                'question_id' => $question->id,
                'answer' => $answer?->value ?? '',
                'answer_id' => $answer?->id ?? null,
                'order' => $question->order,
            ];
        }

        // Ordenar por el número de la pregunta
        usort($faqs, fn($a, $b) => $a['id'] <=> $b['id']);

        return $faqs;
    }

    /**
     * Guardar una nueva FAQ
     */
    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string|max:2000',
        ]);

        // Encontrar el siguiente número disponible
        $maxNumber = SiteContent::where('section', 'faq')
            ->where('key', 'like', 'pregunta_%')
            ->get()
            ->map(fn($item) => (int) str_replace('pregunta_', '', $item->key))
            ->max() ?? 0;

        $newNumber = $maxNumber + 1;
        $maxOrder = SiteContent::where('section', 'faq')->max('order') ?? 0;

        // Crear pregunta
        SiteContent::create([
            'section' => 'faq',
            'key' => 'pregunta_' . $newNumber,
            'type' => 'text',
            'value' => $request->question,
            'default_value' => $request->question,
            'use_default' => false,
            'label' => 'Pregunta ' . $newNumber,
            'order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        // Crear respuesta
        SiteContent::create([
            'section' => 'faq',
            'key' => 'respuesta_' . $newNumber,
            'type' => 'textarea',
            'value' => $request->answer,
            'default_value' => $request->answer,
            'use_default' => false,
            'label' => 'Respuesta ' . $newNumber,
            'order' => $maxOrder + 2,
            'is_active' => true,
        ]);

        return redirect()->route('admin.maintainer.faqs.index')
            ->with('success', 'Pregunta frecuente creada correctamente');
    }

    /**
     * Actualizar una FAQ existente
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string|max:2000',
        ]);

        // Actualizar pregunta
        SiteContent::where('section', 'faq')
            ->where('key', 'pregunta_' . $id)
            ->update([
                'value' => $request->question,
                'use_default' => false,
            ]);

        // Actualizar respuesta
        SiteContent::where('section', 'faq')
            ->where('key', 'respuesta_' . $id)
            ->update([
                'value' => $request->answer,
                'use_default' => false,
            ]);

        return redirect()->route('admin.maintainer.faqs.index')
            ->with('success', 'Pregunta frecuente actualizada correctamente');
    }

    /**
     * Eliminar una FAQ
     */
    public function destroy(int $id)
    {
        // Eliminar pregunta y respuesta
        SiteContent::where('section', 'faq')
            ->where('key', 'pregunta_' . $id)
            ->delete();

        SiteContent::where('section', 'faq')
            ->where('key', 'respuesta_' . $id)
            ->delete();

        return redirect()->route('admin.maintainer.faqs.index')
            ->with('success', 'Pregunta frecuente eliminada correctamente');
    }

    /**
     * Actualizar el título de la sección
     */
    public function updateTitle(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:200',
        ]);

        SiteContent::updateOrCreate(
            ['section' => 'faq', 'key' => 'titulo'],
            [
                'type' => 'text',
                'value' => $request->titulo,
                'label' => 'Título de la sección',
                'order' => 1,
                'is_active' => true,
                'use_default' => false,
            ]
        );

        return redirect()->route('admin.maintainer.faqs.index')
            ->with('success', 'Título actualizado correctamente');
    }

    /**
     * Reordenar FAQs
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer',
        ]);

        $baseOrder = 2; // El título tiene order 1

        foreach ($request->order as $position => $faqId) {
            $newOrder = $baseOrder + ($position * 2);

            SiteContent::where('section', 'faq')
                ->where('key', 'pregunta_' . $faqId)
                ->update(['order' => $newOrder]);

            SiteContent::where('section', 'faq')
                ->where('key', 'respuesta_' . $faqId)
                ->update(['order' => $newOrder + 1]);
        }

        return response()->json(['success' => true]);
    }
}
