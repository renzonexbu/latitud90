<?php

namespace App\Services\Client\Integration;

use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\PaymentConfirmationLog;
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
            Log::channel('bsale')->error('Bsale getDocumentTypeDetails error', [
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

            Log::channel('bsale')->warning('Bsale getAvailableFolios response', [
                'document_type_id' => $documentTypeId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::channel('bsale')->error('Bsale getAvailableFolios error', [
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

            Log::channel('bsale')->warning('Bsale getCafDetails response', [
                'document_type_id' => $documentTypeId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::channel('bsale')->error('Bsale getCafDetails error', [
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
     * Solo genera boleta si el document_type del pago es "B2" (boleta)
     * Si es "AC" (anticipo/contrato) no genera documento en Bsale
     */
    public function generateInvoice(OrderDetail $orderDetail, Payment $payment): ?array
    {
        try {
            // Kill switch: verificar si BSale está habilitado globalmente
            // Configurar BSALE_ENABLED=false en .env para desactivar generación de boletas
            if (!config('services.bsale.enabled', true)) {
                Log::channel('bsale')->info('BsaleService: Generación de boletas DESACTIVADA por configuración (BSALE_ENABLED=false)', [
                    'payment_id' => $payment->id,
                    'order_detail_id' => $orderDetail->id,
                ]);
                return null;
            }

            // IMPORTANTE: Verificar si el Payment ya tiene una boleta generada para evitar duplicados
            if (!empty($payment->bsale_document_id) || !empty($payment->bsale_number)) {
                Log::channel('bsale')->info('BsaleService: Payment ya tiene boleta generada, omitiendo generación duplicada', [
                    'payment_id' => $payment->id,
                    'bsale_document_id' => $payment->bsale_document_id,
                    'bsale_number' => $payment->bsale_number,
                ]);
                return null;
            }

            // IMPORTANTE: Verificar si el Payment ya tiene un error PERMANENTE de BSale
            // Errores permanentes: client_blocked, cliente bloqueado, etc.
            $permanentErrors = ['client_blocked', 'cli_005'];
            if (!empty($payment->bsale_error_code) && in_array($payment->bsale_error_code, $permanentErrors)) {
                Log::channel('bsale')->info('BsaleService: Payment tiene error permanente de BSale, no reintentando', [
                    'payment_id' => $payment->id,
                    'bsale_error' => $payment->bsale_error,
                    'bsale_error_code' => $payment->bsale_error_code,
                ]);
                return null;
            }

            // CRÍTICO: Solo generar boleta si el pago está CONFIRMADO (completed o approved)
            // Estados como 'pending', 'processing', 'procesando' NO deben generar boleta
            $confirmedStatuses = ['completed', 'approved'];
            if (!in_array($payment->status, $confirmedStatuses)) {
                Log::channel('bsale')->warning('BsaleService: NO se genera boleta - pago NO está confirmado', [
                    'payment_id' => $payment->id,
                    'payment_status' => $payment->status,
                    'required_statuses' => $confirmedStatuses,
                ]);
                return null;
            }

            // Verificar flags según tipo de pago (suscripción vs total)
            $order = $orderDetail->order;
            $isSubscription = $this->isSubscriptionPayment($payment, $order);

            if ($isSubscription) {
                if (!config('services.bsale.subscription_enabled', true)) {
                    Log::channel('bsale')->info('BsaleService: Generación DESACTIVADA para suscripciones (BSALE_SUBSCRIPTION_ENABLED=false)', [
                        'payment_id' => $payment->id,
                        'order_payment_type' => $order->payment_type ?? 'N/A',
                    ]);
                    return null;
                }
            } else {
                if (!config('services.bsale.total_enabled', true)) {
                    Log::channel('bsale')->info('BsaleService: Generación DESACTIVADA para pagos totales (BSALE_TOTAL_ENABLED=false)', [
                        'payment_id' => $payment->id,
                        'order_payment_type' => $order->payment_type ?? 'N/A',
                    ]);
                    return null;
                }
            }

            $program = $orderDetail->order->programCourse;

            // Usar el document_type del Payment para decidir si generar boleta
            // B2 = Boleta (programa mismo año) → generar en Bsale
            // AC = Anticipo/Contrato (programa otro año) → NO generar en Bsale
            $documentType = $payment->document_type;
            $shouldGenerateBoleta = $documentType === 'B2';

            Log::channel('bsale')->info('BsaleService: Verificando generación de boleta', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'payment_document_type' => $documentType,
                'should_generate_boleta' => $shouldGenerateBoleta,
                'program_name' => $program->name ?? null,
                'departure_date' => $program->departure_date ?? null,
            ]);

            if (!$shouldGenerateBoleta) {
                Log::channel('bsale')->info('BsaleService: No se genera boleta - document_type es AC (anticipo/contrato)', [
                    'payment_id' => $payment->id,
                    'document_type' => $documentType,
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

            Log::channel('bsale')->info('BsaleService: Boleta generada exitosamente', [
                'payment_id' => $payment->id,
                'bsale_number' => $documentData['number'] ?? null,
                'bsale_token' => $documentData['token'] ?? null,
            ]);

            return $documentData;

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            Log::channel('bsale')->error('BsaleService: Error generando boleta', [
                'payment_id' => $payment->id,
                'error' => $errorMessage,
            ]);

            // Detectar errores PERMANENTES de BSale y guardarlos para no reintentar
            $permanentErrorPatterns = [
                'client blocked' => 'cli_005',
                'cliente bloqueado' => 'cli_005',
                'cli_005' => 'cli_005',
            ];

            foreach ($permanentErrorPatterns as $pattern => $errorCode) {
                if (stripos($errorMessage, $pattern) !== false) {
                    $payment->update([
                        'bsale_error' => 'Cliente bloqueado en BSale',
                        'bsale_error_code' => $errorCode,
                    ]);

                    Log::channel('bsale')->warning('BsaleService: Error PERMANENTE detectado - cliente bloqueado', [
                        'payment_id' => $payment->id,
                        'error_code' => $errorCode,
                        'error_message' => $errorMessage,
                    ]);
                    break;
                }
            }

            // No lanzar excepción para no interrumpir el flujo de pago
            return null;
        }
    }

    /**
     * Crear o obtener cliente en Bsale
     */
    private function createOrGetCustomer(OrderDetail $orderDetail): ?int
    {
        try {
            // Ensure relationships are loaded including document type and convert to array
            $data = $orderDetail->load(['country', 'region', 'city', 'documentType'])->toArray();

            // Obtener datos del participante como fallback
            $participant = $orderDetail->order->participant ?? null;

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

            // Usar document_number del OrderDetail, o del participante como fallback
            $documentNumber = $data['document_number'];
            if (empty($documentNumber) && $participant) {
                $documentNumber = $participant->document_number;
            }

            // Si aún no hay document_number, no podemos crear cliente en Bsale
            if (empty($documentNumber)) {
                Log::channel('bsale')->warning('BsaleService: No se puede crear cliente - document_number vacío', [
                    'order_detail_id' => $orderDetail->id,
                ]);
                return null;
            }

            // Determinar el tipo de documento (RUT, PASAPORTE, DNI, etc.)
            $documentTypeName = '';
            if (isset($data['document_type']) && !empty($data['document_type']['name'])) {
                $documentTypeName = $data['document_type']['name'];
            } else {
                // Fallback: query the document type directly
                $documentType = \App\Models\Document::find($data['document_type']);
                $documentTypeName = $documentType ? $documentType->name : '';
            }

            $documentTypeUpper = strtoupper($documentTypeName);

            // Manejar según tipo de documento
            if ($documentTypeUpper === 'PASAPORTE') {
                // Pasaporte: usar RUT genérico para Bsale
                $documentNumber = '55.555.555-5';
                Log::channel('bsale')->info('BsaleService: Pasaporte detectado, usando RUT genérico', [
                    'order_detail_id' => $orderDetail->id,
                    'original_document' => $data['document_number'],
                ]);
            } elseif ($documentTypeUpper === 'DNI' || $documentTypeUpper === 'DOCUMENTO DE IDENTIDAD') {
                // DNI extranjero: usar RUT genérico para Bsale
                $documentNumber = '55.555.555-5';
                Log::channel('bsale')->info('BsaleService: DNI detectado, usando RUT genérico', [
                    'order_detail_id' => $orderDetail->id,
                    'original_document' => $data['document_number'],
                ]);
            } else {
                // RUT chileno: formatear con puntos y guión para Bsale/SII
                $originalDocumentNumber = $documentNumber;
                if ($documentNumber && strlen($documentNumber) >= 8) {
                    $documentNumber = \App\Helpers\RutHelper::format($documentNumber);

                    Log::channel('bsale')->info('BsaleService: RUT formateado para Bsale', [
                        'order_detail_id' => $orderDetail->id,
                        'original_rut' => $originalDocumentNumber,
                        'formatted_rut' => $documentNumber,
                    ]);
                }
            }

            // Obtener nombre - intentar datos estructurados del buyer_data de la suscripción primero
            $nameParts = null;
            $subscription = $orderDetail->order->subscription ?? null;
            if ($subscription && !empty($subscription->buyer_data)) {
                $buyerData = $subscription->buyer_data;
                $firstName = trim($buyerData['first_name'] ?? '');
                $lastName = trim($buyerData['first_last_name'] ?? '');
                if (!empty($firstName) && !empty($lastName)) {
                    $nameParts = ['firstName' => $firstName, 'lastName' => $lastName];
                    Log::channel('bsale')->info('BsaleService: Usando nombres estructurados de buyer_data', [
                        'firstName' => $firstName,
                        'lastName' => $lastName,
                    ]);
                }
            }

            // Fallback: dividir el nombre completo de forma heurística
            if (!$nameParts) {
                $customerName = $orderDetail->name;
                if (empty($customerName) && $participant) {
                    $customerName = $participant->full_name;
                }
                $nameParts = $this->splitFullName($customerName);
            }

            // Obtener email - usar OrderDetail o email por defecto
            $customerEmail = $orderDetail->email;
            if (empty($customerEmail)) {
                $customerEmail = 'pagos@latitud90.com';
            }

            $customerData = [
                'firstName' => $nameParts['firstName'],
                'lastName' => $nameParts['lastName'],
                'email' => $customerEmail,
                'code' => $documentNumber, // BSale uses 'code' field for RUT
                'documentNumber' => $documentNumber,
                'documentTypeId' => $this->getDocumentTypeId($orderDetail->document_type),
                // NO incluir 'company' para que Bsale lo trate como persona natural
                'address' => $address ?: null, // Ensure it's not empty string
                'city' => $comuna ?: null, // Ensure it's not empty string
                'activity' => null, // Explicitly null to avoid "Sin Giro"
                'municipality' => $comuna ?: null, // Comuna field
                // Remove unwanted fields: phone, contacto, oc_referencia, total_abonado
            ];



            // Buscar cliente existente por RUT/código primero
            $existingCustomerByCode = $this->findCustomerByCode($documentNumber);
            if ($existingCustomerByCode) {
                Log::channel('bsale')->info('BsaleService: Cliente encontrado por código', [
                    'customer_id' => $existingCustomerByCode['id'],
                    'code' => $documentNumber,
                ]);
                return $existingCustomerByCode['id'];
            }

            // Buscar cliente existente por email como fallback
            // IMPORTANTE: Validar que el RUT coincida antes de reutilizar el cliente
            $existingCustomer = $this->findCustomerByEmail($orderDetail->email);
            if ($existingCustomer) {
                $existingCode = $existingCustomer['code'] ?? null;
                // Normalizar ambos RUTs (quitar puntos, guiones) para comparar
                $normalizedExisting = preg_replace('/[.\-\s]/', '', $existingCode ?? '');
                $normalizedExpected = preg_replace('/[.\-\s]/', '', $documentNumber ?? '');
                if ($normalizedExisting && $normalizedExpected && strtoupper($normalizedExisting) === strtoupper($normalizedExpected)) {
                    // RUT coincide, reutilizar cliente
                    Log::channel('bsale')->info('BsaleService: Cliente encontrado por email con RUT coincidente', [
                        'customer_id' => $existingCustomer['id'],
                        'email' => $orderDetail->email,
                        'code' => $existingCode,
                    ]);
                    return $existingCustomer['id'];
                } else {
                    // RUT NO coincide, no reutilizar - se creará uno nuevo
                    Log::channel('bsale')->warning('BsaleService: Cliente encontrado por email pero RUT no coincide, creando nuevo cliente', [
                        'existing_customer_id' => $existingCustomer['id'],
                        'existing_code' => $existingCode,
                        'expected_code' => $documentNumber,
                        'email' => $orderDetail->email,
                    ]);
                }
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
                Log::channel('bsale')->error('BSale New Customer Creation Failed:', [
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

        // IMPORTANTE: La boleta va a nombre del PAGADOR (OrderDetail.name)
        // pero la DESCRIPCIÓN debe indicar para QUIÉN es el programa (PARTICIPANTE)

        // Obtener el nombre del PARTICIPANTE para la descripción del item
        $participant = $orderDetail->order->participant;
        $participantName = 'PARTICIPANTE';

        if ($participant) {
            // Construir nombre completo del participante (NOMBRES + APELLIDOS)
            $participantName = trim(implode(' ', array_filter([
                $participant->first_name,
                $participant->second_name,
                $participant->first_last_name,
                $participant->second_last_name
            ]))) ?: 'PARTICIPANTE';
            $participantName = ucwords(strtolower($participantName));
        }

        // Item description: "Programa de Estudio" + nombre del PARTICIPANTE (para quién es)
        $itemDetail = "Programa de Estudio\n    " . $participantName;

        // Determinar si es boleta exenta (IDs 29, 41) o afecta
        $isExempt = in_array($this->documentTypeId, [29, 41]);

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
                    'taxId' => $isExempt ? 0 : 1, // 0 para exento, 1 para IVA
                ]
            ],
            'payments' => [
                [
                    'paymentTypeId' => 10, // WEBPAY
                    'amount' => $payment->amount,
                ]
            ]
        ];


        $response = Http::withHeaders([
            'access_token' => $this->token,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/documents.json', $documentData);


        if (!$response->successful()) {
            Log::channel('bsale')->error('BSale Document Creation Failed:', [
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
     * Dividir nombre completo en nombres y apellidos de forma inteligente
     * Asume formato: "NOMBRE(S) APELLIDO(S)"
     */
    private function splitFullName(string $fullName): array
    {
        $parts = array_values(array_filter(explode(' ', trim($fullName))));
        $totalParts = count($parts);

        if ($totalParts === 0) {
            return ['firstName' => '', 'lastName' => ''];
        }

        if ($totalParts === 1) {
            // Solo un nombre
            return ['firstName' => $parts[0], 'lastName' => ''];
        }

        if ($totalParts === 2) {
            // Nombre Apellido
            return ['firstName' => $parts[0], 'lastName' => $parts[1]];
        }

        if ($totalParts === 3) {
            // Nombre Apellido1 Apellido2
            return [
                'firstName' => $parts[0],
                'lastName' => $parts[1] . ' ' . $parts[2]
            ];
        }

        // 4 o más palabras: asumir primeras 2 son nombres, resto son apellidos
        $firstName = $parts[0] . ' ' . $parts[1];
        $lastName = implode(' ', array_slice($parts, 2));

        return [
            'firstName' => $firstName,
            'lastName' => $lastName
        ];
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

                // Registrar evento en payment_confirmation_logs
                $orderDetail = $payment->orderDetail;
                if ($orderDetail) {
                    PaymentConfirmationLog::logBsaleInvoiceGenerated(
                        $payment,
                        $orderDetail,
                        $payment->bsale_document_id,
                        $bsaleNumber,
                        $filePath,
                        ['file_size' => filesize($filePath)]
                    );
                }
            } else {
                Log::channel('bsale')->error('BSale PDF Download Failed:', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }

        } catch (\Exception $e) {
            Log::channel('bsale')->error('BSale PDF Download Exception:', [
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

    /**
     * Buscar documento en BSale por número de boleta
     *
     * @param string $number Número de boleta (ej: "23509")
     * @return array|null Datos del documento incluyendo token, id, etc.
     */
    public function findDocumentByNumber(string $number): ?array
    {
        try {
            $response = Http::withHeaders([
                'access_token' => $this->token,
            ])->get($this->baseUrl . '/documents.json', [
                'number' => $number,
                'limit' => 10,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $items = $data['items'] ?? [];

                if (!empty($items)) {
                    // Retornar el primer documento encontrado
                    $document = $items[0];
                    return [
                        'id' => $document['id'] ?? null,
                        'number' => $document['number'] ?? null,
                        'token' => $document['token'] ?? null,
                        'urlPdf' => $document['urlPdf'] ?? null,
                        'emissionDate' => $document['emissionDate'] ?? null,
                        'totalAmount' => $document['totalAmount'] ?? null,
                    ];
                }
            }

            Log::channel('bsale')->info('BsaleService::findDocumentByNumber - No se encontró documento', [
                'number' => $number,
                'response_status' => $response->status(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::channel('bsale')->error('BsaleService::findDocumentByNumber - Error', [
                'number' => $number,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Sincronizar token de BSale para un payment que tiene número pero no token
     *
     * @param Payment $payment
     * @return bool True si se actualizó correctamente
     */
    public function syncPaymentToken(Payment $payment): bool
    {
        if (empty($payment->bsale_number)) {
            return false;
        }

        // Ya tiene token, no necesita sincronizar
        if (!empty($payment->bsale_token)) {
            return true;
        }

        $document = $this->findDocumentByNumber($payment->bsale_number);

        if ($document && !empty($document['token'])) {
            $payment->update([
                'bsale_token' => $document['token'],
                'bsale_document_id' => $document['id'] ?? $payment->bsale_document_id,
            ]);

            Log::channel('bsale')->info('BsaleService::syncPaymentToken - Token sincronizado', [
                'payment_id' => $payment->id,
                'bsale_number' => $payment->bsale_number,
                'bsale_token' => $document['token'],
            ]);

            return true;
        }

        return false;
    }

    /**
     * Determinar si un pago es de suscripción (PAT/cuota) o pago total (contado)
     *
     * @param Payment $payment
     * @param mixed $order Order o InstallmentPlan
     * @return bool true si es pago de suscripción, false si es pago total
     */
    private function isSubscriptionPayment(Payment $payment, $order): bool
    {
        // 1. Si payment_type es 'total', NUNCA es suscripción (salir inmediatamente)
        $paymentType = $order->payment_type ?? null;
        if ($paymentType === 'total') {
            Log::channel('bsale')->debug('BsaleService::isSubscriptionPayment - Es pago total (payment_type=total)', [
                'payment_id' => $payment->id,
                'payment_type' => $paymentType,
            ]);
            return false;
        }

        // 2. Verificar payment_type de la orden (monthly, subscription, pat)
        if (in_array($paymentType, ['monthly', 'subscription', 'pat'])) {
            Log::channel('bsale')->debug('BsaleService::isSubscriptionPayment - Detectado por payment_type', [
                'payment_id' => $payment->id,
                'payment_type' => $paymentType,
            ]);
            return true;
        }

        // 3. Verificar si tiene external_payment_id (típico de charges de suscripción VirtualPOS)
        // NOTA: Este check solo aplica si payment_type NO es 'total' (ya verificado arriba)
        if (!empty($payment->external_payment_id)) {
            Log::channel('bsale')->debug('BsaleService::isSubscriptionPayment - Detectado por external_payment_id', [
                'payment_id' => $payment->id,
                'external_payment_id' => $payment->external_payment_id,
            ]);
            return true;
        }

        // 3. Verificar si el order_number indica suscripción
        $orderNumber = $order->order_number ?? '';
        if (str_starts_with($orderNumber, 'SUB-')) {
            Log::channel('bsale')->debug('BsaleService::isSubscriptionPayment - Detectado por order_number SUB-', [
                'payment_id' => $payment->id,
                'order_number' => $orderNumber,
            ]);
            return true;
        }

        // 4. Verificar si existe una ProgramSubscription activa para este participante/programa
        if ($order->participant_id && $order->program_id) {
            $hasSubscription = \App\Models\ProgramSubscription::where('participant_id', $order->participant_id)
                ->where('program_id', $order->program_id)
                ->whereIn('status', ['ACTIVA', 'PAUSADA'])
                ->exists();

            if ($hasSubscription) {
                Log::channel('bsale')->debug('BsaleService::isSubscriptionPayment - Detectado por ProgramSubscription existente', [
                    'payment_id' => $payment->id,
                    'participant_id' => $order->participant_id,
                    'program_id' => $order->program_id,
                ]);
                return true;
            }
        }

        // 5. Fallback: no es pago de suscripción
        Log::channel('bsale')->debug('BsaleService::isSubscriptionPayment - Es pago total (no suscripción)', [
            'payment_id' => $payment->id,
            'payment_type' => $paymentType,
        ]);
        return false;
    }
}
