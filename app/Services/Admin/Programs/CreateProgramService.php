<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CreateProgramService
{
    /**
     * Execute the program creation.
     */
    public function execute(array $programData): Program
    {
        try {
            DB::beginTransaction();

            // Procesar los pilares como string separado por comas
            $pillars = [];
            if (!empty($programData['pilar_aventura'])) {
                $pillars[] = $programData['pilar_aventura'];
            }
            if (!empty($programData['pilar_entretenimiento'])) {
                $pillars[] = $programData['pilar_entretenimiento'];
            }
            if (!empty($programData['pilar_educacion'])) {
                $pillars[] = $programData['pilar_educacion'];
            }
            if (!empty($programData['pilar_seguridad'])) {
                $pillars[] = $programData['pilar_seguridad'];
            }
            $programData['pillars'] = implode(', ', $pillars);

            // Procesar archivos si se proporcionaron
            $programData = $this->processFiles($programData);

            // Crear el programa
            $program = Program::create([
                'name' => $programData['name'],
                'destination' => $programData['destination'],
                'departure_date' => $programData['departure_date'],
                'trip_description' => $programData['description'] ?? $programData['trip_description'],
                'images_folder' => $programData['images_folder'] ?? null,
                'pillars' => $programData['pillars'] ?? null,
                'itinerary_description' => $programData['itinerary'] ?? null,
                'itinerary_file' => $programData['itinerary_file_path'] ?? null,
                'travel_assistance_coverage' => $programData['coverage_file_path'] ?? null,
                'equipment_list' => $programData['equipment_file_path'] ?? null,
                'trip_price' => $programData['total_price'] ?? $programData['trip_price'],
                'final_payment_date' => $programData['final_payment_date'],
                'seller_name' => $programData['sales_person'] ?? $programData['seller_name'],
                'payment_mode_id' => $this->getPaymentModeId($programData),
                'active' => $programData['active'] ?? true,
            ]);

            DB::commit();
            return $program;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process uploaded files and store them.
     */
    private function processFiles(array $programData): array
    {
        // Procesar archivo de itinerario
        if (isset($programData['itinerary_file']) && $programData['itinerary_file']) {
            $path = $programData['itinerary_file']->store('programs/files', 'public');
            $programData['itinerary_file_path'] = $path;
        }

        // Procesar archivo de cobertura
        if (isset($programData['coverage_file']) && $programData['coverage_file']) {
            $path = $programData['coverage_file']->store('programs/files', 'public');
            $programData['coverage_file_path'] = $path;
        }

        // Procesar archivo de lista de equipo
        if (isset($programData['equipment_file']) && $programData['equipment_file']) {
            $path = $programData['equipment_file']->store('programs/files', 'public');
            $programData['equipment_file_path'] = $path;
        }

        // Procesar imágenes si se proporcionaron
        if (isset($programData['images']) && is_array($programData['images'])) {
            $imagePaths = [];
            foreach ($programData['images'] as $image) {
                if ($image && $image->isValid()) {
                    $path = $image->store('programs/images', 'public');
                    $imagePaths[] = $path;
                }
            }
            if (!empty($imagePaths)) {
                $programData['images_folder'] = 'programs/images/' . uniqid();
            }
        }

        return $programData;
    }

    /**
     * Get payment mode ID based on payment options.
     */
    private function getPaymentModeId(array $programData): int
    {
        // Si se proporciona directamente payment_mode_id, usarlo
        if (isset($programData['payment_mode_id'])) {
            return $programData['payment_mode_id'];
        }

        // Si no, crear o encontrar un payment mode basado en las opciones
        $paymentOption = $programData['payment_option'] ?? null;
        $paymentMethod = null;

        if ($paymentOption === 'full_payment') {
            $paymentMethod = $programData['full_payment_method'] ?? 'todos_medios';
        } elseif ($paymentOption === 'installments') {
            $paymentMethod = $programData['installments_payment_method'] ?? 'todos_medios';
        }

        // Buscar o crear el payment mode
        $paymentMode = \App\Models\PaymentMode::firstOrCreate([
            'name' => $this->getPaymentModeName($paymentOption, $paymentMethod),
        ], [
            'code' => $this->getPaymentModeCode($paymentOption, $paymentMethod),
            'description' => $this->getPaymentModeDescription($paymentOption, $paymentMethod),
            'active' => true,
        ]);

        return $paymentMode->id;
    }

    /**
     * Get payment mode code based on options.
     */
    private function getPaymentModeCode(?string $paymentOption, ?string $paymentMethod): string
    {
        $methodCode = $this->getMethodCode($paymentMethod);
        
        if ($paymentOption === 'full_payment') {
            return 'full_payment_' . $methodCode;
        } elseif ($paymentOption === 'installments') {
            return 'installments_' . $methodCode;
        }

        return 'standard_payment';
    }

    /**
     * Get payment mode name based on options.
     */
    private function getPaymentModeName(?string $paymentOption, ?string $paymentMethod): string
    {
        if ($paymentOption === 'full_payment') {
            return 'Pago Total - ' . $this->getMethodDisplayName($paymentMethod);
        } elseif ($paymentOption === 'installments') {
            return 'Pago en Cuotas - ' . $this->getMethodDisplayName($paymentMethod);
        }

        return 'Pago Estándar';
    }

    /**
     * Get payment mode description based on options.
     */
    private function getPaymentModeDescription(?string $paymentOption, ?string $paymentMethod): string
    {
        $methodDesc = $this->getMethodDescription($paymentMethod);
        
        if ($paymentOption === 'full_payment') {
            return "Pago total del viaje. $methodDesc";
        } elseif ($paymentOption === 'installments') {
            return "Pago en cuotas mensuales. $methodDesc";
        }

        return "Método de pago estándar. $methodDesc";
    }

    /**
     * Get method display name.
     */
    private function getMethodDisplayName(?string $method): string
    {
        return match ($method) {
            'todos_medios' => 'Todos los medios',
            'solo_tarjeta' => 'Solo tarjeta',
            'solo_transferencia' => 'Solo transferencia',
            'solo_contado' => 'Solo contado',
            default => 'Todos los medios',
        };
    }

    /**
     * Get method code.
     */
    private function getMethodCode(?string $method): string
    {
        return match ($method) {
            'todos_medios' => 'all_methods',
            'solo_tarjeta' => 'card_only',
            'solo_transferencia' => 'transfer_only',
            'solo_contado' => 'cash_only',
            default => 'all_methods',
        };
    }

    /**
     * Get method description.
     */
    private function getMethodDescription(?string $method): string
    {
        return match ($method) {
            'todos_medios' => 'Acepta débito, crédito y transferencia',
            'solo_tarjeta' => 'Solo acepta tarjetas de débito y crédito',
            'solo_transferencia' => 'Solo acepta transferencias bancarias',
            'solo_contado' => 'Solo acepta débito y transferencia',
            default => 'Acepta todos los medios de pago',
        };
    }
} 