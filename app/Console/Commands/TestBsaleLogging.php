<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class TestBsaleLogging extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bsale:test-logging {--clear : Limpiar logs de Bsale antes de la prueba}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba la configuración de logs de Bsale y verifica que se crean correctamente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Iniciando prueba de logging de Bsale...');
        $this->newLine();

        // 1. Verificar configuración del canal
        $this->info('1️⃣ Verificando configuración del canal de Bsale...');

        $config = config('logging.channels.bsale');
        if (!$config) {
            $this->error('❌ No se encontró la configuración del canal "bsale" en config/logging.php');
            return Command::FAILURE;
        }

        $this->info('   ✅ Canal configurado correctamente');
        $this->line('   Driver: ' . $config['driver']);
        $this->line('   Path: ' . $config['path']);
        $this->line('   Days: ' . $config['days']);
        $this->newLine();

        // 2. Verificar y crear directorio si no existe
        $this->info('2️⃣ Verificando directorio de logs...');

        $logDir = storage_path('logs/bsale');
        if (!File::exists($logDir)) {
            $this->warn('   ⚠️  Directorio no existe, creándolo...');
            File::makeDirectory($logDir, 0755, true);
            $this->info('   ✅ Directorio creado: ' . $logDir);
        } else {
            $this->info('   ✅ Directorio existe: ' . $logDir);
        }
        $this->newLine();

        // 3. Limpiar logs si se solicita
        if ($this->option('clear')) {
            $this->info('3️⃣ Limpiando logs existentes...');
            $files = File::files($logDir);
            foreach ($files as $file) {
                File::delete($file);
            }
            $this->info('   ✅ ' . count($files) . ' archivo(s) eliminado(s)');
            $this->newLine();
        }

        // 4. Generar logs de prueba
        $this->info('4️⃣ Generando logs de prueba...');

        $testData = [
            'test_id' => uniqid(),
            'timestamp' => now()->toIso8601String(),
            'user' => 'test-command',
        ];

        try {
            // Log DEBUG
            Log::channel('bsale')->debug('Test DEBUG - Este es un log de prueba nivel debug', $testData);
            $this->line('   ✅ Log DEBUG generado');

            // Log INFO
            Log::channel('bsale')->info('Test INFO - Boleta de prueba generada', array_merge($testData, [
                'payment_id' => 99999,
                'bsale_number' => 'TEST-12345',
            ]));
            $this->line('   ✅ Log INFO generado');

            // Log WARNING
            Log::channel('bsale')->warning('Test WARNING - Cliente no encontrado en Bsale', array_merge($testData, [
                'client_id' => 88888,
            ]));
            $this->line('   ✅ Log WARNING generado');

            // Log ERROR
            Log::channel('bsale')->error('Test ERROR - Error al crear documento en Bsale', array_merge($testData, [
                'error' => 'undefined method estado_usuario for nil:NilClass',
                'payment_id' => 77777,
            ]));
            $this->line('   ✅ Log ERROR generado');

        } catch (\Exception $e) {
            $this->error('   ❌ Error generando logs: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->newLine();

        // 5. Verificar que se creó el archivo
        $this->info('5️⃣ Verificando archivo de log generado...');

        $todayLogFile = storage_path('logs/bsale/bsale-' . date('Y-m-d') . '.log');

        if (!File::exists($todayLogFile)) {
            $this->error('   ❌ No se encontró el archivo: ' . $todayLogFile);

            // Listar archivos que SÍ existen
            $this->warn('   Archivos encontrados en ' . $logDir . ':');
            $files = File::files($logDir);
            foreach ($files as $file) {
                $this->line('      - ' . basename($file));
            }

            return Command::FAILURE;
        }

        $this->info('   ✅ Archivo creado: ' . basename($todayLogFile));

        // Leer y mostrar el contenido
        $content = File::get($todayLogFile);
        $lines = explode("\n", trim($content));
        $lineCount = count($lines);

        $this->line('   📊 Tamaño: ' . File::size($todayLogFile) . ' bytes');
        $this->line('   📊 Líneas: ' . $lineCount);
        $this->newLine();

        // 6. Mostrar últimas líneas del log
        $this->info('6️⃣ Contenido del log (últimas 10 líneas):');
        $this->line('   ' . str_repeat('─', 70));

        $lastLines = array_slice($lines, -10);
        foreach ($lastLines as $line) {
            if (!empty(trim($line))) {
                // Colorear según nivel
                if (str_contains($line, '.ERROR:')) {
                    $this->line('   <fg=red>' . substr($line, 0, 150) . '</>' . (strlen($line) > 150 ? '...' : ''));
                } elseif (str_contains($line, '.WARNING:')) {
                    $this->line('   <fg=yellow>' . substr($line, 0, 150) . '</>' . (strlen($line) > 150 ? '...' : ''));
                } elseif (str_contains($line, '.INFO:')) {
                    $this->line('   <fg=green>' . substr($line, 0, 150) . '</>' . (strlen($line) > 150 ? '...' : ''));
                } else {
                    $this->line('   <fg=gray>' . substr($line, 0, 150) . '</>' . (strlen($line) > 150 ? '...' : ''));
                }
            }
        }

        $this->line('   ' . str_repeat('─', 70));
        $this->newLine();

        // 7. Resumen final
        $this->info('✅ Prueba completada exitosamente!');
        $this->newLine();

        $this->line('📁 Ubicación del archivo: ' . $todayLogFile);
        $this->line('📝 Puedes ver el archivo completo con:');
        $this->line('   tail -f ' . $todayLogFile);
        $this->line('   o');
        $this->line('   cat ' . $todayLogFile);

        return Command::SUCCESS;
    }
}
