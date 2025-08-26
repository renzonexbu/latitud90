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
            
            // Obtener tipos de documento
            $this->info('Obteniendo tipos de documento...');
            $documentTypes = $bsaleService->getDocumentTypes();
            
            if ($documentTypes) {
                $this->info('✅ Tipos de documento obtenidos:');
                
                // Verificar si la respuesta tiene la estructura esperada
                if (isset($documentTypes['items']) && is_array($documentTypes['items'])) {
                    $this->line("Total de tipos: {$documentTypes['count']}");
                    foreach ($documentTypes['items'] as $type) {
                        $this->line("  - ID: {$type['id']}, Nombre: {$type['name']}");
                    }
                } else {
                    $this->line('Estructura inesperada: ' . json_encode($documentTypes, JSON_PRETTY_PRINT));
                }
            } else {
                $this->warn('⚠️ No se pudieron obtener los tipos de documento');
            }
            
            // Obtener listas de precios
            $this->info('Obteniendo listas de precios...');
            $priceLists = $bsaleService->getPriceLists();
            
            if ($priceLists) {
                $this->info('✅ Listas de precios obtenidas:');
                
                if (isset($priceLists['items']) && is_array($priceLists['items'])) {
                    $this->line("Total de listas: {$priceLists['count']}");
                    foreach ($priceLists['items'] as $list) {
                        $this->line("  - ID: {$list['id']}, Nombre: {$list['name']}");
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
