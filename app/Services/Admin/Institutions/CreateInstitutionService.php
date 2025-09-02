<?php

namespace App\Services\Admin\Institutions;

use App\Models\Institution;
use App\Traits\AdminLogging;

class CreateInstitutionService
{
    use AdminLogging;

    public function execute(array $data): Institution
    {
        $institution = Institution::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'website' => $data['website'] ?? null,
            'active' => true,
        ]);

        // Log the institution creation
        $this->logCreate(
            'institutions',
            'Institution',
            $institution->id,
            "Institución creada: {$institution->name}",
            $institution->toArray(),
            [
                'institution_type' => $data['type'],
                'has_contact_info' => !empty($data['email']) || !empty($data['phone']),
            ]
        );

        return $institution;
    }
}
