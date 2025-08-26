<?php

namespace App\Services\Client\PaymentProcessing;

use App\Services\Client\Programs\FrequentClientService;
use App\Traits\SystemLogging;

class StoreFrequentClientService
{
    use SystemLogging;

    /**
     * Almacenar cliente frecuente
     *
     * @param array $formData
     * @return void
     */
    public function execute(array $formData): void
    {
        try {
            $frequentClientData = [
                'full_name' => $formData['name'] ?? '',
                'document_id' => $this->resolveDocumentTypeId($formData['documentType'] ?? ''),
                'document' => $formData['documentNumber'] ?? '',
                'email' => $formData['email'] ?? '',
                'phone_code' => $formData['code_phone'] ?? '+56',
                'phone' => $formData['phone'] ?? '',
                'country_id' => $formData['countryId'] ?? '',
                'region_id' => $formData['regionId'] ?? '',
                'comune_id' => $formData['cityId'] ?? '',
                'terms_accepted' => $formData['termsAccepted'] ?? false,
                'marketing_accepted' => $formData['marketingAccepted'] ?? false,
            ];

            if (!empty($frequentClientData['full_name']) && 
                !empty($frequentClientData['document_id']) && 
                !empty($frequentClientData['document'])) {
                
                $storedClient = FrequentClientService::store($frequentClientData);
                
                $this->logInfo('Frequent client stored successfully', [
                    'client_id' => $storedClient->id,
                    'document' => $storedClient->document,
                    'full_name' => $storedClient->full_name
                ]);
            }
        } catch (\Exception $e) {
            $this->logError('Error storing frequent client, continuing with payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Resolver ID del tipo de documento
     */
    private function resolveDocumentTypeId($value): ?int
    {
        if (empty($value)) { return null; }
        if (is_numeric($value)) { return (int) $value; }
        $string = trim((string) $value);
        $id = \App\Models\Document::where('name', $string)->value('id');
        if ($id) { return (int) $id; }
        $id = \App\Models\Document::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }
}
