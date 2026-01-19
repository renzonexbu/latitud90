<?php

namespace App\Services\Client\Integration;

use App\Models\OrderDetail;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BsaleService
{
    private $baseUrl = 'https://api.bsale.io/v1';
    private $token;
    private $documentTypeId;
    private $priceListId;
    private $invertSameYearLogic;

    public function __construct()
    {
        $this->token = config('services.bsale.token') ?? env('BSALE_TOKEN');
        $this->baseUrl = config('services.bsale.base_url', $this->baseUrl);
        $this->documentTypeId = (int) config('services.bsale.document_type_id', 3);
        $this->priceListId = (int) config('services.bsale.price_list_id', 2);
        $this->invertSameYearLogic = filter_var(config('services.bsale.invert_same_year_logic', false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Obtener tipos de documento válidos de Bsale
     */
    public function getDocumentTypes(): ?array
    {
        try {
            $response = Http::withHeaders([
                'access_token' => $this->token,
            ])->get($this->baseUrl . '/document_types.json');

            if ($response->successful()) {
                $documentTypes = $response->json();
                return $documentTypes;
            }


            return null;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obtener detalles de un tipo de documento específico
     */
    public function getDocumentTypeDetails(int $documentTypeId): ?array
    {
        try {
            $response = Http::withHeaders([
                'access_token' => $this->token,
            ])->get($this->baseUrl . "/document_types/{$documentTypeId}.json");

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Bsale getDocumentTypeDetails error', [
                'document_type_id' => $documentTypeId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Obtener folios disponibles para un tipo de documento específico
     */
    public function getAvailableFolios(int $documentTypeId): ?array
    {
        try {
            $response = Http::withHeaders([
                'access_token' => $this->token,
            ])->get($this->baseUrl . '/document_types/number_availables.json', [
                'documenttypeid' => $documentTypeId,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Bsale getAvailableFolios response', [
                'document_type_id' => $documentTypeId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Bsale getAvailableFolios error', [
                'document_type_id' => $documentTypeId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Obtener información del CAF (Código de Autorización de Folio) vigente
     */
    public function getCafDetails(int $documentTypeId): ?array
    {
        try {
            $response = Http::withHeaders([
                'access_token' => $this->token,
            ])->get($this->baseUrl . '/document_types/caf.json', [
                'documenttypeid' => $documentTypeId,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Bsale getCafDetails response', [
                'document_type_id' => $documentTypeId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Bsale getCafDetails error', [
                'document_type_id' => $documentTypeId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Obtener el ID del tipo de documento por nombre
     */
    public function getDocumentTypeIdByName(string $name): ?int
    {
        $documentTypes = $this->getDocumentTypes();
        
        if (!$documentTypes || !isset($documentTypes['items'])) {
            return null;
        }

        foreach ($documentTypes['items'] as $type) {
            if (strcasecmp($type['name'], $name) === 0) {
                return $type['id'];
            }
        }

        return null;
    }

    /**
     * Obtener listas de precios disponibles
     */
    public function getPriceLists(): ?array
    {
        try {
            $response = Http::withHeaders([
                'access_token' => $this->token,
            ])->get($this->baseUrl . '/price_lists.json');

            if ($response->successful()) {
                $priceLists = $response->json();
                return $priceLists;
            }


            return null;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Verificar conectividad con Bsale
     */
    public function testConnection(): bool
    {
        try {
            $response = Http::withHeaders([
                'access_token' => $this->token,
            ])->get($this->baseUrl . '/clients.json', [
                'limit' => 1,
            ]);

            if ($response->successful()) {
                return true;
            }


            return false;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generar boleta automáticamente al confirmar pago
     */
    public function generateInvoice(OrderDetail $orderDetail, Payment $payment): ?array
    {
        try {
            // Verificar si el programa es de entrega el mismo año
            $program = $orderDetail->order->programCourse;
            $isSameYear = $this->isSameYearDelivery($program);
            
            // Log para debugging
            Log::info('BsaleService: Verificando generación de boleta', [
                'order_detail_id' => $orderDetail->id,
                'program_id' => $program->id ?? null,
                'program_name' => $program->name ?? null,
                'departure_date' => $program->departure_date ?? null,
                'current_year' => now()->year,
                'departure_year' => $program->departure_date ? $program->departure_date->year : null,
                'is_same_year' => $isSameYear,
                'invert_same_year_logic' => $this->invertSameYearLogic,
            ]);
            
            // BSale documents should be generated for same-year programs (not reservations)
            // Different year programs get contracts instead of BSale documents
            $shouldSkip = $this->invertSameYearLogic ? $isSameYear : !$isSameYear;
            
            Log::info('BsaleService: Decisión de generación', [
                'should_skip' => $shouldSkip,
                'will_generate' => !$shouldSkip,
            ]);
            
            if ($shouldSkip) {
                return null;
            }

            // Crear cliente en Bsale si no existe
            $customerId = $this->createOrGetCustomer($orderDetail);
            if (!$customerId) {
                throw new \Exception('No se pudo crear/obtener el cliente en Bsale');
            }


            // Crear documento (boleta) en Bsale
            $documentData = $this->createDocument($orderDetail, $payment, $customerId);
            

            return $documentData;

        } catch (\Exception $e) {

            // No lanzar excepción para no interrumpir el flujo de pago
            return null;
        }
    }

    /**
     * Verificar si el programa es de entrega el mismo año
     */
    private function isSameYearDelivery($program): bool
    {
        if (!$program->departure_date) {
            return false;
        }

        $currentYear = now()->year;
        $departureYear = $program->departure_date->year;

        return $departureYear === $currentYear;
    }

    /**
     * Crear o obtener cliente en Bsale
     */
    private function createOrGetCustomer(OrderDetail $orderDetail): ?int
    {
        try {
            // Ensure relationships are loaded including document type and convert to array
            $data = $orderDetail->load(['country', 'region', 'city', 'documentType'])->toArray();
            
            // Construir dirección
            $address = '';
            if (isset($data['country']) && !empty($data['country']['name'])) {
                $address .= $data['country']['name'];
            }
            if (isset($data['region']) && !empty($data['region']['name'])) {
                $address .= ($address ? ', ' : '') . $data['region']['name'];
            }
            
            // Comuna
            $comuna = isset($data['city']) && !empty($data['city']['name']) ? $data['city']['name'] : '';
            
            // Handle passport document type - use fixed RUT 55.555.555-5
            $documentNumber = $data['document_number'];
            $documentTypeName = '';
            
            // Check document type from loaded relationship
            if (isset($data['document_type']) && !empty($data['document_type']['name'])) {
                $documentTypeName = $data['document_type']['name'];
            } else {
                // Fallback: query the document type directly
                $documentType = \App\Models\Document::find($data['document_type']);
                $documentTypeName = $documentType ? $documentType->name : '';
            }

            
            if (strtoupper($documentTypeName) === 'PASAPORTE') {
                $documentNumber = '55.555.555-5';
            } else {
                // For RUT, keep original formatting with dots and hyphens
                // BSale expects RUT in format XX.XXX.XXX-X
                $documentNumber = $documentNumber; // Keep as is: "25.808.242-7"
            }

            // For BSale document type 3, company field is required
            // Use customer's full name as company to satisfy BSale requirement
            $fullName = $this->extractFirstName($orderDetail->name) . ' ' . $this->extractLastName($orderDetail->name);
            
            $customerData = [
                'firstName' => $this->extractFirstName($orderDetail->name),
                'lastName' => $this->extractLastName($orderDetail->name),
                'email' => $orderDetail->email,
                'code' => $documentNumber, // BSale uses 'code' field for RUT
                'documentNumber' => $documentNumber,
                'documentTypeId' => $this->getDocumentTypeId($orderDetail->document_type),
                'company' => trim($fullName), // Use customer's name as company (required by document type 3)
                'address' => $address ?: null, // Ensure it's not empty string
                'city' => $comuna ?: null, // Ensure it's not empty string
                'activity' => null, // Explicitly null to avoid "Sin Giro"
                'municipality' => $comuna ?: null, // Comuna field
                // Remove unwanted fields: phone, contacto, oc_referencia, total_abonado
            ];



            // Buscar cliente existente por RUT/código primero
            $existingCustomerByCode = $this->findCustomerByCode($documentNumber);
            if ($existingCustomerByCode) {
                Log::info('BsaleService: Cliente encontrado por código', [
                    'customer_id' => $existingCustomerByCode['id'],
                    'code' => $documentNumber,
                ]);
                return $existingCustomerByCode['id'];
            }

            // Buscar cliente existente por email como fallback
            $existingCustomer = $this->findCustomerByEmail($orderDetail->email);
            if ($existingCustomer) {
                Log::info('BsaleService: Cliente encontrado por email', [
                    'customer_id' => $existingCustomer['id'],
                    'email' => $orderDetail->email,
                ]);
                return $existingCustomer['id'];
            }

            // Crear nuevo cliente
            $response = Http::withHeaders([
                'access_token' => $this->token,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/clients.json', $customerData);


            if ($response->successful()) {
                $customer = $response->json();
                return $customer['id'];
            } else {
                Log::error('BSale New Customer Creation Failed:', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }

            return null;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Buscar cliente por código/RUT
     */
    private function findCustomerByCode(string $code): ?array
    {
        try {
            $response = Http::withHeaders([
                'access_token' => $this->token,
            ])->get($this->baseUrl . '/clients.json', [
                'code' => $code,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $items = is_array($data) && isset($data['items']) && is_array($data['items']) ? $data['items'] : [];
                return !empty($items) ? $items[0] : null;
            }

            return null;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Buscar cliente por email
     */
    private function findCustomerByEmail(string $email): ?array
    {
        try {
            $response = Http::withHeaders([
                'access_token' => $this->token,
            ])->get($this->baseUrl . '/clients.json', [
                'email' => $email,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $items = is_array($data) && isset($data['items']) && is_array($data['items']) ? $data['items'] : [];
                return !empty($items) ? $items[0] : null;
            }

            return null;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Crear documento (boleta) en Bsale
     */
    private function createDocument(OrderDetail $orderDetail, Payment $payment, int $customerId): array
    {
        $program = $orderDetail->order->programCourse;
        $participant = $orderDetail->order->participant;
        
        // Build full participant name from individual fields in uppercase
        $participantFullName = '';
        if ($participant) {
            $nameParts = array_filter([
                $participant->first_name,
                $participant->second_name,
                $participant->first_last_name,
                $participant->second_last_name
            ]);
            $participantFullName = ucwords(strtolower(implode(' ', $nameParts)));
        }
        
        // Item description: "Programa de Estudio" + participant full name in uppercase
        $itemDetail = "Programa de Estudio\n    " . ($participantFullName ?: 'PARTICIPANTE');

        $documentData = [
            'clientId' => $customerId,
            'documentTypeId' => $this->documentTypeId,
            'priceListId' => $this->priceListId,
            'emissionDate' => now()->timestamp, // Unix timestamp según documentación
            'expirationDate' => now()->addDays(30)->timestamp, // Unix timestamp según documentación
            'comment' => "Pago de cuota {$orderDetail->installment_number} - Programa: {$program->name}",
            'details' => [
                [
                    'comment' => $itemDetail,
                    'quantity' => 1,
                    'netUnitValue' => $payment->amount,
                    'taxId' => 1, // IVA
                ]
            ]
        ];


        $response = Http::withHeaders([
            'access_token' => $this->token,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/documents.json', $documentData);


        if (!$response->successful()) {
            Log::error('BSale Document Creation Failed:', [
                'status' => $response->status(),
                'body' => $response->body(),
                'documentData' => $documentData
            ]);
            throw new \Exception('Error creando documento en Bsale: ' . $response->body());
        }

        $responseData = $response->json();
        

        // Guardar datos de Bsale en el pago para poder descargar el PDF y adjuntarlo en emails
        $updateData = [];
        if (isset($responseData['token'])) {
            $updateData['bsale_token'] = $responseData['token'];
        }
        if (isset($responseData['id'])) {
            $updateData['bsale_document_id'] = $responseData['id'];
        }
        if (isset($responseData['number'])) {
            $updateData['bsale_number'] = $responseData['number'];
        }
        
        if (!empty($updateData)) {
            $payment->update($updateData);
            
            // Descargar y almacenar el PDF inmediatamente
            if (isset($responseData['token'])) {
                $this->downloadAndStoreBsalePdf($payment, $responseData);
            }
        }

        return $responseData;
    }

    /**
     * Extraer primer nombre
     */
    private function extractFirstName(string $fullName): string
    {
        $names = explode(' ', trim($fullName));
        return $names[0] ?? '';
    }

    /**
     * Extraer apellido
     */
    private function extractLastName(string $fullName): string
    {
        $names = explode(' ', trim($fullName));
        return count($names) > 1 ? implode(' ', array_slice($names, 1)) : '';
    }

    /**
     * Descargar y almacenar PDF de Bsale inmediatamente después de crear el documento
     */
    private function downloadAndStoreBsalePdf(Payment $payment, array $bsaleResponse): void
    {
        try {

            // Construir la URL del PDF de Bsale
            $bsaleUrl = "https://app2.bsale.cl/view/90370/" . $payment->bsale_token . ".pdf?sfd=99";
            
            // Crear directorio de almacenamiento permanente si no existe
            $bsaleDir = storage_path('app/bsale_documents');
            if (!file_exists($bsaleDir)) {
                mkdir($bsaleDir, 0755, true);
            }
            
            // Crear subdirectorio por año para mejor organización
            $yearDir = $bsaleDir . '/' . date('Y');
            if (!file_exists($yearDir)) {
                mkdir($yearDir, 0755, true);
            }
            
            // Generar nombre de archivo permanente
            $bsaleNumber = $bsaleResponse['number'] ?? $payment->id;
            $filename = 'bsale_' . $bsaleNumber . '_payment_' . $payment->id . '.pdf';
            $filePath = $yearDir . '/' . $filename;
            
            // Verificar si el archivo ya existe
            if (file_exists($filePath)) {
                return;
            }
            
            // Descargar el PDF
            $response = \Illuminate\Support\Facades\Http::timeout(30)->get($bsaleUrl);
            
            
            if ($response->successful()) {
                file_put_contents($filePath, $response->body());
            } else {
                Log::error('BSale PDF Download Failed:', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('BSale PDF Download Exception:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Obtener ID del tipo de documento en Bsale
     */
    private function getDocumentTypeId($documentType): int
    {
        // Siempre retornar 1 (RUT) para BSale
        // Cuando sea pasaporte, ya se maneja el número 55.555.555-5 en el documentNumber
        return 1;
    }
}
