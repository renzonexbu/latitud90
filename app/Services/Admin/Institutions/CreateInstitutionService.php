<?php

namespace App\Services\Admin\Institutions;

use App\Models\Institution;

class CreateInstitutionService
{
    public function execute(array $data): Institution
    {
        return Institution::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'website' => $data['website'] ?? null,
            'active' => true,
        ]);
    }
}
