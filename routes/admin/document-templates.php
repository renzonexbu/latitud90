<?php

use App\Http\Controllers\Admin\DocumentTemplateController;
use Illuminate\Support\Facades\Route;

// Rutas de gestión de plantillas de documentos
Route::prefix('document-templates')->name('document-templates.')->group(function () {
    // Listar plantillas
    Route::get('/', [DocumentTemplateController::class, 'index'])->name('index');

    // Crear nueva plantilla
    Route::get('/create', [DocumentTemplateController::class, 'create'])->name('create');
    Route::post('/', [DocumentTemplateController::class, 'store'])->name('store');

    // Ver detalles de plantilla
    Route::get('/{documentTemplate}', [DocumentTemplateController::class, 'show'])->name('show');

    // Editar plantilla
    Route::get('/{documentTemplate}/edit', [DocumentTemplateController::class, 'edit'])->name('edit');
    Route::put('/{documentTemplate}', [DocumentTemplateController::class, 'update'])->name('update');
    Route::patch('/{documentTemplate}', [DocumentTemplateController::class, 'update']);

    // Eliminar plantilla
    Route::delete('/{documentTemplate}', [DocumentTemplateController::class, 'destroy'])->name('destroy');

    // Activar/Desactivar plantilla
    Route::post('/{documentTemplate}/toggle-active', [DocumentTemplateController::class, 'toggleActive'])->name('toggle-active');

    // Previsualizar PDF
    Route::get('/{documentTemplate}/preview', [DocumentTemplateController::class, 'preview'])->name('preview');
    Route::post('/{documentTemplate}/preview-draft', [DocumentTemplateController::class, 'previewDraft'])->name('preview-draft');

    // Duplicar plantilla
    Route::post('/{documentTemplate}/duplicate', [DocumentTemplateController::class, 'duplicate'])->name('duplicate');
});
