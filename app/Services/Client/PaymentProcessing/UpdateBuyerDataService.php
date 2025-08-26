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
                $dataToUpdate['payment_gateway_id'] = 3; // VirtualPOS
            } else {
                $dataToUpdate['payment_gateway_id'] = 1; // Transbank (legacy)
            }

            // SIEMPRE actualizar el payment_option_id según el método de pago y tipo de pago
            $dataToUpdate['payment_option_id'] = $this->resolvePaymentOptionIdForUpdate(
                $orderDetail->order->program_id,
                $paymentData
            );

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
                case 'debit':
                    $code = 'full_debit_virtualpos';
                    break;
                case 'debit_credit_0':
                    $code = 'full_debit_virtualpos';
                    break;
                case 'debit_credit_3':
                    $code = 'full_credit_virtualpos_3';
                    break;
                case 'debit_credit_6':
                    $code = 'full_credit_virtualpos_6';
                    break;
                case 'debit_credit_9':
                    $code = 'full_credit_virtualpos_6';
                    break; // 9 cuotas usa la misma configuración que 6
                case 'debit_credit_12':
                    $code = 'full_credit_virtualpos_12';
                    break;
                // Legacy Transbank
                case 'credit_0':
                    $code = 'full_credit_webpay_0';
                    break;
                case 'credit_3':
                    $code = 'full_credit_webpay_3';
                    break;
                case 'credit_6':
                    $code = 'full_credit_webpay_6';
                    break;
                case 'credit_9':
                    $code = 'full_credit_webpay_9';
                    break;
                case 'credit_12':
                    $code = 'full_credit_webpay_12';
                    break;
                default:
                    if (strpos($method, 'debit_credit') === 0) {
                        $suffix = trim(str_replace('debit_credit', '', $method), '_');
                        $n = $suffix !== '' ? (int)$suffix : 0;
                        // 9 cuotas usa la misma configuración que 6
                        if ($n === 9) {
                            $code = 'full_credit_virtualpos_6';
                        } else {
                            $code = $n > 0 ? 'full_credit_virtualpos_' . $n : 'full_debit_virtualpos';
                        }
                    } else if (strpos($method, 'credit') === 0) {
                        $suffix = trim(str_replace('credit', '', $method), '_');
                        $n = $suffix !== '' ? (int)$suffix : 0;
                        $code = 'full_credit_webpay_' . $n;
                    }
                    break;
            }
        } else {
            switch ($method) {
                case 'khipu':
                    $code = 'lat90_transfer_khipu';
                    break;
                case 'debit':
                    $code = 'lat90_debit_virtualpos';
                    break;
                case 'debit_credit_0':
                    $code = 'lat90_debit_virtualpos';
                    break;
                case 'credit':
                    $code = 'lat90_credit_0';
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

        $enabled = DB::table('program_payment_option')
            ->where('program_id', $programId)
            ->where('payment_option_id', $optionId)
            ->where('enabled', true)
            ->exists();

        return $enabled ? (int)$optionId : null;
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
