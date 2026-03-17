<?php

namespace App\Services\Client\PaymentProcessing;

use App\Models\OrderDetail;
use App\Models\Country;
use App\Models\Region;
use App\Models\Comune;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use App\Traits\SystemLogging;

class UpdateBuyerDataService
{
    use SystemLogging;

    /**
     * Actualizar datos del comprador en OrderDetail    
     *
     * @param OrderDetail $orderDetail
     * @param array $formData
     * @param array $paymentData
     * @return void
     */
    public function execute(OrderDetail $orderDetail, array $formData, array $paymentData): void
    {
        try {
            // Aceptar buyerData anidado
            if (isset($formData['buyerData']) && is_array($formData['buyerData'])) {
                $formData = array_merge($formData, $formData['buyerData']);
            }

            $name = $formData['name'] ?? ($formData['fullName'] ?? null);
            $email = $formData['email'] ?? null;
            $codePhone = $formData['code_phone'] ?? null;
            $phone = $formData['phone'] ?? null;
            $documentType = $this->resolveDocumentTypeId($formData['documentType'] ?? null);
            $documentNumber = $formData['documentNumber'] ?? null;

            // Bloquear datos genéricos/de prueba que causan problemas con Bsale
            $blockedEmails = ['pagos@latitud90.cl', 'pagos@latitud90.com'];
            $blockedRuts = ['123456789'];
            $cleanedDoc = preg_replace('/[.\-\s]/', '', $documentNumber ?? '');

            if ($email && in_array(strtolower(trim($email)), $blockedEmails)) {
                $this->logError('UpdateBuyerData: Email bloqueado detectado', [
                    'order_detail_id' => $orderDetail->id,
                    'blocked_email' => $email,
                ]);
                throw new \InvalidArgumentException('El correo electrónico ingresado no está permitido. Use su correo personal.');
            }

            if ($cleanedDoc && in_array(strtoupper($cleanedDoc), $blockedRuts)) {
                $this->logError('UpdateBuyerData: RUT bloqueado detectado', [
                    'order_detail_id' => $orderDetail->id,
                    'blocked_rut' => $documentNumber,
                ]);
                throw new \InvalidArgumentException('El RUT ingresado no está permitido. Use su RUT personal.');
            }

            $country = $this->resolveCountryId($formData['countryId'] ?? ($formData['country'] ?? null));
            $region = $this->resolveRegionId($formData['regionId'] ?? ($formData['region'] ?? null));
            $city = $this->resolveCityId($formData['cityId'] ?? ($formData['city'] ?? null));

            $billingAddress = $formData['billing_address'] ?? null;
            $billingCity = $formData['cityName'] ?? ($formData['billing_city'] ?? null);
            $billingCountry = $formData['countryName'] ?? ($formData['billing_country'] ?? null);
            $billingPostalCode = $formData['billing_postal_code'] ?? null;

            $dataToUpdate = [
                'name' => $name,
                'email' => $email,
                'code_phone' => $codePhone,
                'phone' => $phone,
                'document_type' => $documentType,
                'document_number' => $documentNumber,
                'country' => $country,
                'region' => $region,
                'city' => $city,
                'billing_address' => $billingAddress,
                'billing_city' => $billingCity,
                'billing_country' => $billingCountry,
                'billing_postal_code' => $billingPostalCode,
            ];

            // SIEMPRE actualizar el payment_gateway_id según el método de pago actual
            if ($paymentData['paymentMethod'] === 'khipu') {
                $dataToUpdate['payment_gateway_id'] = 2; // Khipu
            } else if (stripos($paymentData['paymentMethod'], 'debit_credit') !== false) {
                $dataToUpdate['payment_gateway_id'] = 1; // VirtualPOS se relaciona con Transbank
            } else {
                $dataToUpdate['payment_gateway_id'] = 1; // Transbank (legacy)
            }

            // SIEMPRE actualizar el payment_option_id según el método de pago y tipo de pago
            $resolvedPaymentOptionId = $this->resolvePaymentOptionIdForUpdate(
                $orderDetail->order->program_id,
                $paymentData
            );
            $dataToUpdate['payment_option_id'] = $resolvedPaymentOptionId;

            $orderDetail->update($dataToUpdate);

            $this->logInfo('OrderDetail buyer data updated successfully', [
                'order_detail_id' => $orderDetail->id,
                'installment_number' => $orderDetail->installment_number,
                'payment_gateway_id' => $dataToUpdate['payment_gateway_id'],
                'payment_option_id' => $dataToUpdate['payment_option_id'],
                'payment_method' => $paymentData['paymentMethod'],
                'buyer_name' => $name,
                'buyer_email' => $email
            ]);
        } catch (\Throwable $e) {
            $this->logError('Error updating buyer data on OrderDetail', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetail->id,
            ]);
        }
    }

    /**
     * Resolver payment_option_id para actualización
     */
    private function resolvePaymentOptionIdForUpdate(int $programId, array $paymentData): ?int
    {
        $mode = ($paymentData['paymentType'] ?? 'total') === 'monthly' ? 'lat90' : 'full';
        $method = $paymentData['paymentMethod'] ?? 'debit';
        $code = null;

        if ($mode === 'full') {
            switch ($method) {
                case 'khipu': 
                    $code = 'full_transfer_khipu'; 
                    break;
                case 'debit_credit_0': 
                    $code = 'full_debit_credit_0'; 
                    break;
                case 'debit_credit_3': 
                    $code = 'full_debit_credit_3'; 
                    break;
                case 'debit_credit_6': 
                    $code = 'full_debit_credit_6'; 
                    break;
                case 'debit_credit_9': 
                    $code = 'full_debit_credit_9'; 
                    break;
                case 'debit_credit_12': 
                    $code = 'full_debit_credit_12'; 
                    break;
                case 'international': 
                    $code = 'full_international'; 
                    break;
                default:
                    // Fallback para códigos legacy
                    if ($method === 'debit') {
                        $code = 'full_debit_credit_0';
                    } elseif (strpos($method, 'credit') === 0) {
                        $suffix = trim(str_replace('credit', '', $method), '_');
                        $n = $suffix !== '' ? (int)$suffix : 0;
                        $code = 'full_debit_credit_' . $n;
                    }
                    break;
            }
        } else {
            switch ($method) {
                case 'khipu': 
                    $code = 'lat90_transfer_khipu'; 
                    break;
                case 'debit_credit_0': 
                    $code = 'lat90_debit_credit_0'; 
                    break;
                default:
                    // Fallback para códigos legacy
                    if ($method === 'debit') {
                        $code = 'lat90_debit_credit_0';
                    } elseif ($method === 'credit') {
                        $code = 'lat90_debit_credit_0';
                    }
                    break;
            }
        }

        if (!$code) {
            return null;
        }

        $optionId = DB::table('payment_options')->where('code', $code)->value('id');
        if (!$optionId) {
            return null;
        }

        // Verificar si hay configuración de pivot para este program_course
        $pivotExists = DB::table('program_course_payment_option')
            ->where('program_course_id', $programId)
            ->exists();

        // Si hay registros en el pivot, validar que esté habilitado
        // Si no hay registros en el pivot, permitir la opción (modo legacy)
        if ($pivotExists) {
            $enabled = DB::table('program_course_payment_option')
                ->where('program_course_id', $programId)
                ->where('payment_option_id', $optionId)
                ->where('enabled', true)
                ->exists();
            return $enabled ? (int)$optionId : null;
        }

        // Modo legacy: sin configuración de pivot, permitir cualquier opción
        return (int)$optionId;
    }

    // Métodos de resolución de IDs
    private function resolveCountryId($value): ?int
    {
        if (empty($value)) {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        $string = trim((string) $value);
        if (strlen($string) <= 3) {
            $id = Country::where('code', $string)->value('id');
            if ($id) {
                return (int) $id;
            }
        }
        $id = Country::where('name', $string)->value('id');
        if ($id) {
            return (int) $id;
        }
        $id = Country::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }

    private function resolveRegionId($value): ?int
    {
        if (empty($value)) {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        $string = trim((string) $value);
        $id = Region::where('name', $string)->value('id');
        if ($id) {
            return (int) $id;
        }
        $id = Region::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }

    private function resolveCityId($value): ?int
    {
        if (empty($value)) {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        $string = trim((string) $value);
        $id = Comune::where('name', $string)->value('id');
        if ($id) {
            return (int) $id;
        }
        $id = Comune::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }

    private function resolveDocumentTypeId($value): ?int
    {
        if (empty($value)) {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        $string = trim((string) $value);
        $id = Document::where('name', $string)->value('id');
        if ($id) {
            return (int) $id;
        }
        $id = Document::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }
}
