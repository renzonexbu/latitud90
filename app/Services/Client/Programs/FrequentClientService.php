<?php


namespace App\Services\Client\Programs;

use App\Models\FrequentClient;
use Illuminate\Support\Facades\DB;
use App\Models\Document;
use App\Traits\SystemLogging;

class FrequentClientService
{
    use SystemLogging;
    public static function findByDocument(array $data)
    {
        // Obtener el tipo de documento para determinar el formato
        $documentType = Document::find($data['document_id']);
        $documentNumber = $data['document'];
        
        // Formatear el número de documento según el tipo
        if ($documentType && strtolower($documentType->name) === 'rut') {
            // Limpiar RUT: quitar puntos y guiones
            $documentNumber = preg_replace('/[.-]/', '', $documentNumber);
        } elseif ($documentType && strtolower($documentType->name) === 'pasaporte') {
            // Pasaporte en mayúsculas
            $documentNumber = strtoupper($documentNumber);
        }

        $client = FrequentClient::with([
            'documentType',
            'country',
            'region',
            'comune'
        ])->where('document_id', $data['document_id'])
          ->where('document', $documentNumber)
          ->first();

        if (!$client) {
            return null;
        }

        // Incrementar contador de uso
        $client->incrementUsage();

        return $client;
    }


    public static function store(array $data)
    {
        $documentType = Document::find($data['document_id']);
        $documentNumber = $data['document'];
        
        if ($documentType && strtolower($documentType->name) === 'rut') {
            $documentNumber = preg_replace('/[.-]/', '', $documentNumber);

        } elseif ($documentType && strtolower($documentType->name) === 'pasaporte') {
            $documentNumber = strtoupper($documentNumber);
        }

        // Buscar si ya existe el cliente
        $existingClient = FrequentClient::where('document_id', $data['document_id'])
            ->where('document', $documentNumber)
            ->first();

        if ($existingClient) {
            // Si existe, incrementar usage_count y actualizar otros campos
            $existingClient->increment('usage_count');
            $existingClient->update([
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone_code' => $data['phone_code'],
                'phone' => $data['phone'],
                'country_id' => $data['country_id'],
                'region_id' => $data['region_id'],
                'comune_id' => $data['comune_id'],
                'terms_accepted' => $data['terms_accepted'],
                'marketing_accepted' => $data['marketing_accepted'],
                'last_used_at' => now(),
            ]);
            
            return $existingClient;
        } else {
            // Si no existe, crear nuevo con usage_count = 1
            $newClient = FrequentClient::create([
                'full_name' => $data['full_name'],
                'document_id' => $data['document_id'],
                'document' => $documentNumber,
                'email' => $data['email'],
                'phone_code' => $data['phone_code'],
                'phone' => $data['phone'],
                'country_id' => $data['country_id'],
                'region_id' => $data['region_id'],
                'comune_id' => $data['comune_id'],
                'terms_accepted' => $data['terms_accepted'],
                'marketing_accepted' => $data['marketing_accepted'],
                'last_used_at' => now(),
                'usage_count' => 1,
            ]);
            return $newClient;
        }
    }
}
