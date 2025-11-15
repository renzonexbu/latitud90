<?php

namespace App\Services\Admin\Programs;

use App\Traits\AdminLogging;

class GetCreateDataService
{
    use AdminLogging;
    /**
     * Obtener datos necesarios para el formulario de creación de plantillas de programas
     *
     * @return array
     */
    public function execute(): array
    {
        // Log the template creation form view
        $this->logView(
            'programs',
            'ProgramTemplateCreateForm',
            0, // No specific resource ID for form views
            "Formulario de creación de plantilla de programa abierto"
        );

        // Templates don't need additional data - just return empty array
        return [];
    }
}
