<?php

namespace App\Services\Client\Integration;

use App\Models\OrderDetail;
use App\Models\Payment;
use App\Traits\SystemLogging;
use Illuminate\Support\Facades\Http;

class BsaleService
{
    use SystemLogging;
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
                $this->logInfo('BsaleService: Tipos de documento obtenidos', [
                    'document_types' => $documentTypes,
                    'document_types_type' => gettype($documentTypes),
                    'document_types_json' => json_encode($documentTypes, JSON_PRETTY_PRINT),
                ]);
                return $documentTypes;
            }

            $this->logError('BsaleService: Error obteniendo tipos de documento', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            $this->logError('BsaleService: Error obteniendo tipos de documento', [
                'error' => $e->getMessage(),
            ], $e);
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
                $this->logInfo('BsaleService: Listas de precios obtenidas', [
                    'price_lists' => $priceLists,
                ]);
                return $priceLists;
            }

            $this->logError('BsaleService: Error obteniendo listas de precios', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            $this->logError('BsaleService: Error obteniendo listas de precios', [
                'error' => $e->getMessage(),
            ], $e);
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
                $this->logInfo('BsaleService: Conexión exitosa con Bsale API');
                return true;
            }

            $this->logError('BsaleService: Error de conexión con Bsale API', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;

        } catch (\Exception $e) {
            $this->logError('BsaleService: Error de conectividad con Bsale API', [
                'error' => $e->getMessage(),
            ], $e);
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
                $this->logInfo('BsaleService: Condición de "mismo año" no cumple generación (configurable)', [
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
            $this->logInfo('BsaleService: Respuesta completa de Bsale', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'bsale_response' => $documentData,
                'bsale_response_json' => json_encode($documentData, JSON_PRETTY_PRINT),
                'bsale_response_keys' => is_array($documentData) ? array_keys($documentData) : 'No es array',
                'bsale_response_type' => gettype($documentData),
            ]);

            return $documentData;

        } catch (\Exception $e) {
            $this->logError('BsaleService: Error generando boleta', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ], $e);

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
            $this->logInfo('BsaleService: Datos del cliente para Bsale', [
                'order_detail_id' => $orderDetail->id,
                'customer_data' => $customerData,
                'customer_data_json' => json_encode($customerData, JSON_PRETTY_PRINT),
            ]);

            // Buscar cliente existente por email
            $existingCustomer = $this->findCustomerByEmail($orderDetail->email);
            if ($existingCustomer) {
                $this->logInfo('BsaleService: Cliente existente encontrado', [
                    'order_detail_id' => $orderDetail->id,
                    'existing_customer' => $existingCustomer,
                ]);
                
                // Si el cliente existente no tiene company, crear uno nuevo
                if (empty($existingCustomer['company'])) {
                    $this->logInfo('BsaleService: Cliente existente sin company, creando nuevo cliente', [
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
                        $this->logInfo('BsaleService: Nuevo cliente creado exitosamente', [
                            'order_detail_id' => $orderDetail->id,
                            'new_customer' => $customer,
                            'new_customer_json' => json_encode($customer, JSON_PRETTY_PRINT),
                        ]);
                        return $customer['id'];
                    } else {
                        $this->logError('BsaleService: Error creando nuevo cliente', [
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
                $this->logInfo('BsaleService: Cliente creado exitosamente', [
                    'order_detail_id' => $orderDetail->id,
                    'new_customer' => $customer,
                    'new_customer_json' => json_encode($customer, JSON_PRETTY_PRINT),
                ]);
                return $customer['id'];
            }

            $this->logError('BsaleService: Error creando cliente', [
                'response' => $response->json(),
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers(),
            ]);

            return null;

        } catch (\Exception $e) {
            $this->logError('BsaleService: Error en createOrGetCustomer', [
                'error' => $e->getMessage(),
            ], $e);
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
                $this->logInfo('BsaleService: Respuesta búsqueda cliente por email', [
                    'email' => $email,
                    'items_count' => count($items),
                    'raw' => $data,
                ]);
                return !empty($items) ? $items[0] : null;
            }

            return null;

        } catch (\Exception $e) {
            $this->logError('BsaleService: Error buscando cliente por email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ], $e);
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
        $this->logInfo('BsaleService: Datos enviados a Bsale para crear documento', [
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
            $this->logError('BsaleService: Error en respuesta de Bsale', [
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
        $this->logInfo('BsaleService: Respuesta completa de Bsale al crear documento', [
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
            
            // Descargar y almacenar el PDF inmediatamente
            $this->downloadAndStoreBsalePdf($payment, $responseData);
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
                $this->logInfo('BsaleService: PDF de Bsale ya existe', [
                    'payment_id' => $payment->id,
                    'file_path' => $filePath,
                    'file_size' => filesize($filePath),
                ]);
                return;
            }
            
            // Descargar el PDF
            $response = \Illuminate\Support\Facades\Http::timeout(30)->get($bsaleUrl);
            
            if ($response->successful()) {
                file_put_contents($filePath, $response->body());
                
                $this->logInfo('BsaleService: PDF de Bsale descargado y almacenado automáticamente', [
                    'payment_id' => $payment->id,
                    'bsale_document_id' => $payment->bsale_document_id,
                    'bsale_number' => $bsaleNumber,
                    'bsale_token' => $payment->bsale_token,
                    'file_path' => $filePath,
                    'file_size' => filesize($filePath),
                    'stored_permanently' => true,
                    'auto_downloaded' => true,
                ]);
            } else {
                $this->logError('BsaleService: Error descargando PDF de Bsale automáticamente', [
                    'payment_id' => $payment->id,
                    'bsale_url' => $bsaleUrl,
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
            }
            
        } catch (\Exception $e) {
            $this->logError('BsaleService: Error descargando PDF de Bsale automáticamente', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ], $e);
        }
    }

    /**
     * Obtener ID del tipo de documento en Bsale
     */
    private function getDocumentTypeId(string $documentType): int
    {
        return strtolower($documentType) === 'rut' ? 1 : 2; // 1 = RUT, 2 = Otros
    }
}
