<?php

namespace App\Services\Commands;

use App\Models\MarketingMail;
use App\Models\OrderDetail;
use App\Models\FrequentClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketingMailsService
{
    /**
     * Procesar y almacenar emails de marketing desde orders_detail y frequent_client
     */
    public function processMarketingEmails(): array
    {
        $results = [
            'total_processed' => 0,
            'new_emails_added' => 0,
            'existing_emails_updated' => 0,
            'errors' => []
        ];

        try {
            DB::beginTransaction();

            // Procesar emails desde orders_detail
            $orderDetailsResults = $this->processOrderDetailsEmails();
            $results['total_processed'] += $orderDetailsResults['processed'];
            $results['new_emails_added'] += $orderDetailsResults['added'];
            $results['existing_emails_updated'] += $orderDetailsResults['updated'];

            // Procesar emails desde frequent_client
            $frequentClientResults = $this->processFrequentClientEmails();
            $results['total_processed'] += $frequentClientResults['processed'];
            $results['new_emails_added'] += $frequentClientResults['added'];
            $results['existing_emails_updated'] += $frequentClientResults['updated'];

            DB::commit();

            Log::info('MarketingMailsService: Procesamiento completado', $results);

        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = "Error procesando emails de marketing: " . $e->getMessage();
            $results['errors'][] = $errorMessage;
            Log::error($errorMessage, ['exception' => $e]);
        }

        return $results;
    }

    /**
     * Procesar emails desde orders_detail
     */
    private function processOrderDetailsEmails(): array
    {
        $results = [
            'processed' => 0,
            'added' => 0,
            'updated' => 0
        ];

        try {
            // Obtener emails únicos de orders_detail con marketing_accepted = true
            $emails = OrderDetail::select('email')
                ->where('marketing_accepted', true)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->distinct()
                ->get()
                ->pluck('email')
                ->filter()
                ->map(function ($email) {
                    return strtolower(trim($email));
                })
                ->unique()
                ->values();

            $results['processed'] = $emails->count();

            foreach ($emails as $email) {
                if ($this->addOrUpdateMarketingEmail($email)) {
                    $results['added']++;
                } else {
                    $results['updated']++;
                }
            }

        } catch (\Exception $e) {
            Log::error('Error procesando emails de orders_detail', ['exception' => $e]);
        }

        return $results;
    }

    /**
     * Procesar emails desde frequent_client
     */
    private function processFrequentClientEmails(): array
    {
        $results = [
            'processed' => 0,
            'added' => 0,
            'updated' => 0
        ];

        try {
            // Obtener emails únicos de frequent_client con marketing_accepted = true
            $emails = FrequentClient::select('email')
                ->where('marketing_accepted', true)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->distinct()
                ->get()
                ->pluck('email')
                ->filter()
                ->map(function ($email) {
                    return strtolower(trim($email));
                })
                ->unique()
                ->values();

            $results['processed'] = $emails->count();

            foreach ($emails as $email) {
                if ($this->addOrUpdateMarketingEmail($email)) {
                    $results['added']++;
                } else {
                    $results['updated']++;
                }
            }

        } catch (\Exception $e) {
            Log::error('Error procesando emails de frequent_client', ['exception' => $e]);
        }

        return $results;
    }

    /**
     * Agregar o actualizar email de marketing
     */
    private function addOrUpdateMarketingEmail(string $email): bool
    {
        try {
            $email = strtolower(trim($email));
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return false;
            }

            // Buscar si ya existe el email (case insensitive)
            $existingMail = MarketingMail::whereRaw('LOWER(email) = ?', [$email])->first();

            if ($existingMail) {
                // Si existe pero está inactivo, activarlo
                if (!$existingMail->is_active) {
                    $existingMail->activate();
                    return false; // false porque es una actualización, no una adición
                }
                return false; // Ya existe y está activo
            }

            // Crear nuevo email de marketing
            MarketingMail::create([
                'email' => $email,
                'is_active' => true
            ]);

            return true; // true porque es una nueva adición

        } catch (\Exception $e) {
            Log::error('Error agregando/actualizando email de marketing', [
                'email' => $email,
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Obtener estadísticas de emails de marketing
     */
    public function getMarketingEmailsStats(): array
    {
        return [
            'total_emails' => MarketingMail::count(),
            'active_emails' => MarketingMail::active()->count(),
            'inactive_emails' => MarketingMail::inactive()->count(),
            'total_orders_with_marketing' => OrderDetail::where('marketing_accepted', true)->count(),
            'total_frequent_clients_with_marketing' => FrequentClient::where('marketing_accepted', true)->count(),
        ];
    }
}
