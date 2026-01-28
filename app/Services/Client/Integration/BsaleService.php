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
     * Solo genera boleta si el document_type del pago es "B2" (boleta)
     * Si es "AC" (anticipo/contrato) no genera documento en Bsale
     */
    public function generateInvoice(OrderDetail $orderDetail, Payment $payment): ?array
    {
        try {
            // Kill switch: verificar si BSale está habilitado globalmente
            // Configurar BSALE_ENABLED=false en .env para desactivar generación de boletas
            if (!config('services.bsale.enabled', true)) {
                Log::info('BsaleService: Generación de boletas DESACTIVADA por configuración (BSALE_ENABLED=false)', [
                    'payment_id' => $payment->id,
                    'order_detail_id' => $orderDetail->id,
                ]);
                return null;
            }

            // IMPORTANTE: Verificar si el Payment ya tiene una boleta generada para evitar duplicados
            if (!empty($payment->bsale_document_id) || !empty($payment->bsale_number)) {
                Log::info('BsaleService: Payment ya tiene boleta generada, omitiendo generación duplicada', [
                    'payment_id' => $payment->id,
                    'bsale_document_id' => $payment->bsale_document_id,
                    'bsale_number' => $payment->bsale_number,
                ]);
                return null;
            }

            // Verificar flags según tipo de pago (suscripción vs total)
            $order = $orderDetail->order;
            $isSubscription = $this->isSubscriptionPayment($payment, $order);

            if ($isSubscription) {
                if (!config('services.bsale.subscription_enabled', true)) {
                    Log::info('BsaleService: Generación DESACTIVADA para suscripciones (BSALE_SUBSCRIPTION_ENABLED=false)', [
                        'payment_id' => $payment->id,
                        'order_payment_type' => $order->payment_type ?? 'N/A',
                    ]);
                    return null;
                }
            } else {
                if (!config('services.bsale.total_enabled', true)) {
                    Log::info('BsaleService: Generación DESACTIVADA para pagos totales (BSALE_TOTAL_ENABLED=false)', [
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

            Log::info('BsaleService: Verificando generación de boleta', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'payment_document_type' => $documentType,
                'should_generate_boleta' => $shouldGenerateBoleta,
                'program_name' => $program->name ?? null,
                'departure_date' => $program->departure_date ?? null,
            ]);

            if (!$shouldGenerateBoleta) {
                Log::info('BsaleService: No se genera boleta - document_type es AC (anticipo/contrato)', [
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

            Log::info('BsaleService: Boleta generada exitosamente', [
                'payment_id' => $payment->id,
                'bsale_number' => $documentData['number'] ?? null,
                'bsale_token' => $documentData['token'] ?? null,
            ]);

            return $documentData;

        } catch (\Exception $e) {
            Log::error('BsaleService: Error generando boleta', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
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

            // Handle passport document type - use fixed RUT 55.555.555-5
            // Usar document_number del OrderDetail, o del participante como fallback
            $documentNumber = $data['document_number'];
            if (empty($documentNumber) && $participant) {
                $documentNumber = $participant->document_number;
                // Formatear RUT si no tiene formato
                if ($documentNumber && strlen($documentNumber) >= 8 && !str_contains($documentNumber, '-')) {
                    $dv = substr($documentNumber, -1);
                    $numero = substr($documentNumber, 0, -1);
                    $documentNumber = number_format((int)$numero, 0, '', '.') . '-' . $dv;
                }
            }

            // Si aún no hay document_number, no podemos crear cliente en Bsale
            if (empty($documentNumber)) {
                Log::warning('BsaleService: No se puede crear cliente - document_number vacío', [
                    'order_detail_id' => $orderDetail->id,
                ]);
                return null;
            }

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
            }

            // Obtener nombre - usar OrderDetail o participante como fallback
            $customerName = $orderDetail->name;
            if (empty($customerName) && $participant) {
                $customerName = $participant->full_name;
            }

            // Obtener email - usar OrderDetail o email por defecto
            $customerEmail = $orderDetail->email;
            if (empty($customerEmail)) {
                $customerEmail = 'pagos@latitud90.com';
            }

            // For BSale document type 3, company field is required
            // Use customer's full name as company to satisfy BSale requirement
            $fullName = $this->extractFirstName($customerName) . ' ' . $this->extractLastName($customerName);

            $customerData = [
                'firstName' => $this->extractFirstName($customerName),
                'lastName' => $this->extractLastName($customerName),
                'email' => $customerEmail,
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

    /**
     * Determinar si un pago es de suscripción (PAT/cuota) o pago total (contado)
     *
     * @param Payment $payment
     * @param mixed $order Order o InstallmentPlan
     * @return bool true si es pago de suscripción, false si es pago total
     */
    private function isSubscriptionPayment(Payment $payment, $order): bool
    {
        // 1. Verificar payment_type de la orden
        $paymentType = $order->payment_type ?? null;
        if (in_array($paymentType, ['monthly', 'subscription', 'pat'])) {
            Log::debug('BsaleService::isSubscriptionPayment - Detectado por payment_type', [
                'payment_id' => $payment->id,
                'payment_type' => $paymentType,
            ]);
            return true;
        }

        // 2. Verificar si tiene external_payment_id (típico de charges de suscripción VirtualPOS)
        if (!empty($payment->external_payment_id)) {
            Log::debug('BsaleService::isSubscriptionPayment - Detectado por external_payment_id', [
                'payment_id' => $payment->id,
                'external_payment_id' => $payment->external_payment_id,
            ]);
            return true;
        }

        // 3. Verificar si el order_number indica suscripción
        $orderNumber = $order->order_number ?? '';
        if (str_starts_with($orderNumber, 'SUB-')) {
            Log::debug('BsaleService::isSubscriptionPayment - Detectado por order_number SUB-', [
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
                Log::debug('BsaleService::isSubscriptionPayment - Detectado por ProgramSubscription existente', [
                    'payment_id' => $payment->id,
                    'participant_id' => $order->participant_id,
                    'program_id' => $order->program_id,
                ]);
                return true;
            }
        }

        // 5. Fallback: no es pago de suscripción
        Log::debug('BsaleService::isSubscriptionPayment - Es pago total (no suscripción)', [
            'payment_id' => $payment->id,
            'payment_type' => $paymentType,
        ]);
        return false;
    }
}
