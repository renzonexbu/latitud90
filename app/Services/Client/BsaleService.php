<?php

namespace App\Services\Client;

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
                Log::info('BsaleService: Tipos de documento obtenidos', [
                    'document_types' => $documentTypes,
                    'document_types_type' => gettype($documentTypes),
                    'document_types_json' => json_encode($documentTypes, JSON_PRETTY_PRINT),
                ]);
                return $documentTypes;
            }

            Log::error('BsaleService: Error obteniendo tipos de documento', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('BsaleService: Error obteniendo tipos de documento', [
                'error' => $e->getMessage(),
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
                Log::info('BsaleService: Listas de precios obtenidas', [
                    'price_lists' => $priceLists,
                ]);
                return $priceLists;
            }

            Log::error('BsaleService: Error obteniendo listas de precios', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('BsaleService: Error obteniendo listas de precios', [
                'error' => $e->getMessage(),
            ]);
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
                Log::info('BsaleService: Conexión exitosa con Bsale API');
                return true;
            }

            Log::error('BsaleService: Error de conexión con Bsale API', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('BsaleService: Error de conectividad con Bsale API', [
                'error' => $e->getMessage(),
            ]);
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
            $program = $orderDetail->order->program;
            $isSameYear = $this->isSameYearDelivery($program);
            // Permitir invertir la lógica vía config para pruebas
            $shouldSkip = $this->invertSameYearLogic ? $isSameYear : !$isSameYear;
            if ($shouldSkip) {
                Log::info('BsaleService: Condición de "mismo año" no cumple generación (configurable)', [
                    'program_id' => $program->id,
                    'departure_date' => $program->departure_date,
                    'is_same_year' => $isSameYear,
                    'invert_logic' => $this->invertSameYearLogic,
                ]);
                return null;
            }

            // Crear cliente en Bsale si no existe
            $customerId = $this->createOrGetCustomer($orderDetail);
            if (!$customerId) {
                throw new \Exception('No se pudo crear/obtener el cliente en Bsale');
            }

            // Crear documento (boleta) en Bsale
            $documentData = $this->createDocument($orderDetail, $payment, $customerId);
            
            // Log completo de la respuesta de Bsale
            Log::info('BsaleService: Respuesta completa de Bsale', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'bsale_response' => $documentData,
                'bsale_response_json' => json_encode($documentData, JSON_PRETTY_PRINT),
                'bsale_response_keys' => is_array($documentData) ? array_keys($documentData) : 'No es array',
                'bsale_response_type' => gettype($documentData),
            ]);

            return $documentData;

        } catch (\Exception $e) {
            Log::error('BsaleService: Error generando boleta', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

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
            $customerData = [
                'firstName' => $this->extractFirstName($orderDetail->name),
                'lastName' => $this->extractLastName($orderDetail->name),
                'email' => $orderDetail->email,
                'phone' => $orderDetail->phone,
                'documentNumber' => $orderDetail->document_number,
                'documentTypeId' => $this->getDocumentTypeId($orderDetail->document_type),
                'company' => 'Experiencias Educativas y Capacitaciones SpA',
            ];

            // Log de datos del cliente
            Log::info('BsaleService: Datos del cliente para Bsale', [
                'order_detail_id' => $orderDetail->id,
                'customer_data' => $customerData,
                'customer_data_json' => json_encode($customerData, JSON_PRETTY_PRINT),
            ]);

            // Buscar cliente existente por email
            $existingCustomer = $this->findCustomerByEmail($orderDetail->email);
            if ($existingCustomer) {
                Log::info('BsaleService: Cliente existente encontrado', [
                    'order_detail_id' => $orderDetail->id,
                    'existing_customer' => $existingCustomer,
                ]);
                
                // Si el cliente existente no tiene company, crear uno nuevo
                if (empty($existingCustomer['company'])) {
                    Log::info('BsaleService: Cliente existente sin company, creando nuevo cliente', [
                        'order_detail_id' => $orderDetail->id,
                        'existing_customer_id' => $existingCustomer['id'],
                    ]);
                    
                    // Crear nuevo cliente con company
                    $response = Http::withHeaders([
                        'access_token' => $this->token,
                        'Content-Type' => 'application/json',
                    ])->post($this->baseUrl . '/clients.json', $customerData);

                    if ($response->successful()) {
                        $customer = $response->json();
                        Log::info('BsaleService: Nuevo cliente creado exitosamente', [
                            'order_detail_id' => $orderDetail->id,
                            'new_customer' => $customer,
                            'new_customer_json' => json_encode($customer, JSON_PRETTY_PRINT),
                        ]);
                        return $customer['id'];
                    } else {
                        Log::error('BsaleService: Error creando nuevo cliente', [
                            'response' => $response->json(),
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);
                        return null;
                    }
                }
                
                return $existingCustomer['id'];
            }

            // Crear nuevo cliente
            $response = Http::withHeaders([
                'access_token' => $this->token,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/clients.json', $customerData);

            if ($response->successful()) {
                $customer = $response->json();
                Log::info('BsaleService: Cliente creado exitosamente', [
                    'order_detail_id' => $orderDetail->id,
                    'new_customer' => $customer,
                    'new_customer_json' => json_encode($customer, JSON_PRETTY_PRINT),
                ]);
                return $customer['id'];
            }

            Log::error('BsaleService: Error creando cliente', [
                'response' => $response->json(),
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('BsaleService: Error en createOrGetCustomer', [
                'error' => $e->getMessage(),
            ]);
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
                Log::info('BsaleService: Respuesta búsqueda cliente por email', [
                    'email' => $email,
                    'items_count' => count($items),
                    'raw' => $data,
                ]);
                return !empty($items) ? $items[0] : null;
            }

            return null;

        } catch (\Exception $e) {
            Log::error('BsaleService: Error buscando cliente por email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Crear documento (boleta) en Bsale
     */
    private function createDocument(OrderDetail $orderDetail, Payment $payment, int $customerId): array
    {
        $program = $orderDetail->order->program;
        $participant = $orderDetail->order->participant;

        $documentData = [
            'clientId' => $customerId,
            'documentTypeId' => $this->documentTypeId,
            'priceListId' => $this->priceListId,
            'emissionDate' => now()->timestamp, // Unix timestamp según documentación
            'expirationDate' => now()->addDays(30)->timestamp, // Unix timestamp según documentación
            'comment' => "Pago de cuota {$orderDetail->installment_number} - Programa: {$program->name}",
            'details' => [
                [
                    'comment' => $program->name, // Nombre del programa como descripción del producto
                    'quantity' => 1,
                    'netUnitValue' => $payment->amount,
                    'taxId' => 1, // IVA
                ]
            ]
        ];

        // Log de los datos que se envían a Bsale
        Log::info('BsaleService: Datos enviados a Bsale para crear documento', [
            'order_detail_id' => $orderDetail->id,
            'payment_id' => $payment->id,
            'customer_id' => $customerId,
            'document_data' => $documentData,
            'document_data_json' => json_encode($documentData, JSON_PRETTY_PRINT),
        ]);

        $response = Http::withHeaders([
            'access_token' => $this->token,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/documents.json', $documentData);

        if (!$response->successful()) {
            Log::error('BsaleService: Error en respuesta de Bsale', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'response_status' => $response->status(),
                'response_body' => $response->body(),
                'response_json' => $response->json(),
            ]);
            throw new \Exception('Error creando documento en Bsale: ' . $response->body());
        }

        $responseData = $response->json();
        
        // Log de la respuesta completa de Bsale
        Log::info('BsaleService: Respuesta completa de Bsale al crear documento', [
            'order_detail_id' => $orderDetail->id,
            'payment_id' => $payment->id,
            'response_status' => $response->status(),
            'response_data' => $responseData,
            'response_data_json' => json_encode($responseData, JSON_PRETTY_PRINT),
            'response_headers' => $response->headers(),
        ]);

        // Guardar el token de Bsale en el pago para poder descargar el PDF
        if (isset($responseData['token'])) {
            $payment->update([
                'bsale_token' => $responseData['token']
            ]);
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
     * Obtener ID del tipo de documento en Bsale
     */
    private function getDocumentTypeId(string $documentType): int
    {
        return strtolower($documentType) === 'rut' ? 1 : 2; // 1 = RUT, 2 = Otros
    }
}
