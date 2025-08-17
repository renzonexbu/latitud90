<?php


namespace App\Services\Client;

use App\Models\FrequentClient;
use Illuminate\Support\Facades\DB;

class FrequentClientService
{
    public static function findByDocument(array $data)
    {
        $client = FrequentClient::with([
            'documentType',
            'country',
            'region',
            'comune'
        ])->where('document_id', $data['document_id'])
          ->where('document', $data['document'])
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
        return FrequentClient::updateOrCreate(
            [
                'document_id' => $data['document_id'],
                'document' => $data['document'],
            ],
            [
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
                'usage_count' => DB::raw('usage_count + 1'),
            ]
        );
    }
}
