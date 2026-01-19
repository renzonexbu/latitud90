<?php

namespace App\Console\Commands;

use App\Services\Client\Integration\BsaleService;
use Illuminate\Console\Command;

class TestBsaleConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bsale:test-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar la conexión con la API de Bsale';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Probando conexión con Bsale API...');

        $bsaleService = new BsaleService();

        if ($bsaleService->testConnection()) {
            $this->info('✅ Conexión exitosa con Bsale API');
            $this->info('Token configurado correctamente');

            $this->newLine();
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            // Obtener tipos de documento
            $this->info('📄 Obteniendo tipos de documento...');
            $documentTypes = $bsaleService->getDocumentTypes();

            if ($documentTypes) {
                $this->info('✅ Tipos de documento obtenidos:');

                // Verificar si la respuesta tiene la estructura esperada
                if (isset($documentTypes['items']) && is_array($documentTypes['items'])) {
                    $this->line("Total de tipos: {$documentTypes['count']}");
                    $this->newLine();

                    // Mostrar información detallada incluyendo folios disponibles
                    $configuredDocTypeId = (int) config('services.bsale.document_type_id', 3);

                    foreach ($documentTypes['items'] as $type) {
                        $isConfigured = $type['id'] === $configuredDocTypeId ? ' ⭐ (CONFIGURADO)' : '';
                        $this->line("  📝 ID: {$type['id']}, Nombre: {$type['name']}{$isConfigured}");

                        // Mostrar folios disponibles si existe el campo
                        if (isset($type['numbers_available'])) {
                            $foliosStatus = $type['numbers_available'] > 0 ? '✅' : '⚠️';
                            $this->line("     {$foliosStatus} Folios disponibles: {$type['numbers_available']}");
                        }

                        // Mostrar último folio usado si existe
                        if (isset($type['last'])) {
                            $this->line("     📊 Último folio usado: {$type['last']}");
                        }

                        // Mostrar si es documento electrónico
                        if (isset($type['isElectronicDocument'])) {
                            $electronico = $type['isElectronicDocument'] ? 'Sí' : 'No';
                            $this->line("     🔌 Electrónico: {$electronico}");
                        }

                        $this->newLine();
                    }

                    // Resumen del tipo de documento configurado
                    $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                    $this->info("📌 Tipo de documento configurado en .env: ID {$configuredDocTypeId}");

                    // Buscar el tipo configurado
                    $configuredType = collect($documentTypes['items'])->firstWhere('id', $configuredDocTypeId);
                    if ($configuredType) {
                        $this->info("   Nombre: {$configuredType['name']}");
                        if (isset($configuredType['numbers_available'])) {
                            $folios = $configuredType['numbers_available'];
                            if ($folios > 0) {
                                $this->info("   ✅ Folios disponibles: {$folios}");
                                $this->info("   🎉 ¡Listo para generar boletas!");
                            } else {
                                $this->error("   ⚠️ Folios disponibles: {$folios}");
                                $this->error("   ❌ No puedes generar boletas sin folios disponibles");
                                $this->warn("   💡 Contacta a Bsale para habilitar folios");
                            }
                        }
                    }

                    // Consultar detalles específicos de documentos electrónicos
                    $this->newLine();
                    $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                    $this->info('🔍 Consultando detalles de documentos electrónicos...');
                    $this->newLine();

                    // IDs de documentos electrónicos relevantes
                    $electronicDocIds = [1, 29]; // Boleta electrónica y Boleta exenta

                    // También consultar el tipo configurado si no está en la lista
                    if (!in_array($configuredDocTypeId, $electronicDocIds)) {
                        $electronicDocIds[] = $configuredDocTypeId;
                    }

                    foreach ($electronicDocIds as $docId) {
                        $details = $bsaleService->getDocumentTypeDetails($docId);

                        if ($details) {
                            $isConfigured = $docId === $configuredDocTypeId ? ' ⭐ (CONFIGURADO)' : '';
                            $this->line("📋 ID {$docId}: {$details['name']}{$isConfigured}");

                            if (isset($details['isElectronicDocument'])) {
                                $electronic = $details['isElectronicDocument'] ? '✅ Sí' : '❌ No';
                                $this->line("   🔌 Documento electrónico: {$electronic}");
                            }

                            // Consultar folios disponibles específicamente
                            $foliosInfo = $bsaleService->getAvailableFolios($docId);
                            if ($foliosInfo) {
                                if (isset($foliosInfo['numbers_available'])) {
                                    $folios = $foliosInfo['numbers_available'];
                                    $status = $folios > 0 ? '✅' : '❌';
                                    $this->line("   {$status} Folios disponibles: {$folios}");
                                }
                                if (isset($foliosInfo['last'])) {
                                    $this->line("   📊 Último folio usado: {$foliosInfo['last']}");
                                }
                            }

                            // Consultar información del CAF
                            $cafInfo = $bsaleService->getCafDetails($docId);
                            if ($cafInfo) {
                                $this->line("   📜 Información CAF:");

                                if (isset($cafInfo['start'])) {
                                    $this->line("      - Folio inicial: {$cafInfo['start']}");
                                }
                                if (isset($cafInfo['end'])) {
                                    $this->line("      - Folio final: {$cafInfo['end']}");
                                }
                                if (isset($cafInfo['numbers_available'])) {
                                    $this->line("      - Folios disponibles en CAF: {$cafInfo['numbers_available']}");
                                }
                                if (isset($cafInfo['expirationDate'])) {
                                    $this->line("      - Fecha vencimiento: {$cafInfo['expirationDate']}");
                                }
                                if (isset($cafInfo['expired'])) {
                                    $expired = $cafInfo['expired'] ? '❌ Sí' : '✅ No';
                                    $this->line("      - CAF expirado: {$expired}");
                                }
                            } else {
                                $this->warn("   ⚠️ No se encontró CAF configurado para este tipo de documento");
                            }

                            $this->newLine();
                        }
                    }

                    // Resumen final
                    $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                    $this->info('📊 RESUMEN');

                    // Verificar si el tipo configurado tiene folios
                    $configuredFolios = $bsaleService->getAvailableFolios($configuredDocTypeId);
                    if ($configuredFolios && isset($configuredFolios['numbers_available'])) {
                        $folios = $configuredFolios['numbers_available'];
                        if ($folios > 0) {
                            $this->info("✅ Tu tipo de documento configurado (ID {$configuredDocTypeId}) tiene {$folios} folios disponibles");
                            $this->info("🎉 ¡Puedes empezar a generar documentos!");
                        } else {
                            $this->error("❌ Tu tipo de documento configurado (ID {$configuredDocTypeId}) NO tiene folios disponibles");
                        }
                    } else {
                        $this->warn("⚠️ No se pudo verificar folios para el tipo de documento configurado (ID {$configuredDocTypeId})");
                    }

                    $configuredType = collect($documentTypes['items'])->firstWhere('id', $configuredDocTypeId);
                    if ($configuredType && !$configuredType['isElectronicDocument']) {
                        $this->warn("💡 Nota: Estás usando '{$configuredType['name']}' que NO es un documento electrónico");
                        $this->warn("   Si quieres emitir boletas electrónicas, considera cambiar a:");
                        $this->warn("   - BOLETA ELECTRÓNICA T (ID: 1)");
                    }

                    $this->newLine();

                } else {
                    $this->line('Estructura inesperada: ' . json_encode($documentTypes, JSON_PRETTY_PRINT));
                }
            } else {
                $this->warn('⚠️ No se pudieron obtener los tipos de documento');
            }

            $this->newLine();
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            // Obtener listas de precios
            $this->info('💰 Obteniendo listas de precios...');
            $priceLists = $bsaleService->getPriceLists();

            if ($priceLists) {
                $this->info('✅ Listas de precios obtenidas:');

                if (isset($priceLists['items']) && is_array($priceLists['items'])) {
                    $this->line("Total de listas: {$priceLists['count']}");
                    $configuredPriceListId = (int) config('services.bsale.price_list_id', 2);

                    foreach ($priceLists['items'] as $list) {
                        $isConfigured = $list['id'] === $configuredPriceListId ? ' ⭐ (CONFIGURADO)' : '';
                        $this->line("  - ID: {$list['id']}, Nombre: {$list['name']}{$isConfigured}");
                    }
                } else {
                    $this->line('Estructura inesperada: ' . json_encode($priceLists, JSON_PRETTY_PRINT));
                }
            } else {
                $this->warn('⚠️ No se pudieron obtener las listas de precios');
            }

        } else {
            $this->error('❌ Error de conexión con Bsale API');
            $this->error('Verifica que el token BSALE_TOKEN esté configurado en el .env');
            $this->error('Revisa los logs para más detalles: storage/logs/laravel.log');
        }

        return 0;
    }
}
