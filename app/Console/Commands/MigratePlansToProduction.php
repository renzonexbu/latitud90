<?php

namespace App\Console\Commands;

use App\Models\VirtualPosPlan;
use App\Models\ProgramCourse;
use App\Services\Subscription\VirtualPosPlanService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigratePlansToProduction extends Command
{
    protected $signature = 'virtualpos:migrate-plans-to-production
                            {--dry-run : Solo mostrar qué se haría sin ejecutar cambios}
                            {--plan-id= : Migrar solo un plan específico por su ID local}';

    protected $description = 'Migrar planes de VirtualPOS sandbox a producción';

    private VirtualPosPlanService $planService;

    public function __construct(VirtualPosPlanService $planService)
    {
        parent::__construct();
        $this->planService = $planService;
    }

    public function handle()
    {
        $this->info('=== Migración de Planes VirtualPOS a Producción ===');
        $this->newLine();

        // Verificar configuración
        $apiUrl = config('services.virtualpos.api_url');
        $this->info("API URL configurada: {$apiUrl}");

        if (str_contains($apiUrl, 'sandbox')) {
            $this->error('La configuración apunta a SANDBOX. Cambia VIRTUALPOS_SUBSCRIPTION_ENV=production en .env');
            return 1;
        }

        $this->info('Configuración apunta a PRODUCCIÓN');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $specificPlanId = $this->option('plan-id');

        if ($dryRun) {
            $this->warn('MODO DRY-RUN: No se ejecutarán cambios reales');
            $this->newLine();
        }

        // Obtener planes a migrar
        $query = VirtualPosPlan::where('is_active', true);

        if ($specificPlanId) {
            $query->where('id', $specificPlanId);
        }

        $plans = $query->get();

        if ($plans->isEmpty()) {
            $this->warn('No hay planes activos para migrar');
            return 0;
        }

        $this->info("Planes a migrar: {$plans->count()}");
        $this->newLine();

        // Tabla de resumen
        $headers = ['ID', 'Código', 'Nombre', 'Tipo', 'Monto', 'Cuotas', 'VirtualPOS ID (actual)'];
        $rows = $plans->map(function ($plan) {
            return [
                $plan->id,
                $plan->code,
                substr($plan->name, 0, 40) . (strlen($plan->name) > 40 ? '...' : ''),
                $plan->participant_id ? 'Personalizado' : 'General',
                '$' . number_format($plan->trip_price, 0),
                $plan->max_installments,
                substr($plan->virtualpos_plan_id, 0, 20) . '...',
            ];
        })->toArray();

        $this->table($headers, $rows);
        $this->newLine();

        if (!$dryRun && !$this->confirm('¿Deseas continuar con la migración?')) {
            $this->info('Operación cancelada');
            return 0;
        }

        $success = 0;
        $failed = 0;
        $results = [];

        $bar = $this->output->createProgressBar($plans->count());
        $bar->start();

        foreach ($plans as $plan) {
            $bar->advance();

            $oldPlanId = $plan->virtualpos_plan_id;

            if ($dryRun) {
                $results[] = [
                    'id' => $plan->id,
                    'code' => $plan->code,
                    'status' => 'DRY-RUN',
                    'old_id' => $oldPlanId,
                    'new_id' => '(no ejecutado)',
                ];
                $success++;
                continue;
            }

            try {
                // Preparar datos para crear el plan en producción
                $planData = $this->preparePlanData($plan);

                // Crear nuevo plan en producción
                $result = $this->planService->createPlan($planData);

                if ($result && $result['success'] && isset($result['plan_id'])) {
                    $newPlanId = $result['plan_id'];

                    // Verificar que la respuesta sea de producción
                    $suscriptionUrl = $result['data']['plan']['suscription_url'] ?? '';
                    if (str_contains($suscriptionUrl, 'sandbox')) {
                        throw new \Exception('El plan se creó en SANDBOX, no en producción');
                    }

                    DB::beginTransaction();
                    try {
                        // Actualizar el plan local con el nuevo ID
                        $plan->virtualpos_plan_id = $newPlanId;
                        $plan->api_response = $result['data'];
                        $plan->save();

                        // Actualizar en program_courses si es un plan general
                        if (!$plan->participant_id && $plan->program_course_id) {
                            ProgramCourse::where('id', $plan->program_course_id)
                                ->where('virtualpos_plan_id', $oldPlanId)
                                ->update(['virtualpos_plan_id' => $newPlanId]);
                        }

                        DB::commit();

                        $results[] = [
                            'id' => $plan->id,
                            'code' => $plan->code,
                            'status' => 'OK',
                            'old_id' => substr($oldPlanId, 0, 15) . '...',
                            'new_id' => substr($newPlanId, 0, 15) . '...',
                        ];

                        Log::info('Plan migrado a producción exitosamente', [
                            'plan_id' => $plan->id,
                            'code' => $plan->code,
                            'old_virtualpos_id' => $oldPlanId,
                            'new_virtualpos_id' => $newPlanId,
                        ]);

                        $success++;

                    } catch (\Exception $e) {
                        DB::rollBack();
                        throw $e;
                    }

                } else {
                    $errorMsg = $result['error'] ?? 'Error desconocido';
                    throw new \Exception($errorMsg);
                }

            } catch (\Exception $e) {
                $results[] = [
                    'id' => $plan->id,
                    'code' => $plan->code,
                    'status' => 'ERROR',
                    'old_id' => substr($oldPlanId, 0, 15) . '...',
                    'new_id' => $e->getMessage(),
                ];

                Log::error('Error migrando plan a producción', [
                    'plan_id' => $plan->id,
                    'code' => $plan->code,
                    'error' => $e->getMessage(),
                ]);

                $failed++;
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Mostrar resultados
        $this->info('=== Resultados de la Migración ===');
        $this->newLine();

        $resultHeaders = ['ID', 'Código', 'Status', 'ID Anterior', 'ID Nuevo'];
        $this->table($resultHeaders, $results);

        $this->newLine();
        $this->info("Exitosos: {$success}");
        if ($failed > 0) {
            $this->error("Fallidos: {$failed}");
        }

        return $failed > 0 ? 1 : 0;
    }

    /**
     * Preparar datos del plan para enviar a VirtualPOS
     */
    private function preparePlanData(VirtualPosPlan $plan): array
    {
        return [
            'code' => $plan->code,
            'name' => $plan->name,
            'trip_price' => (float) $plan->trip_price,
            'max_installments' => $plan->max_installments,
            'immediate_first_charge' => $plan->immediate_first_charge,
            'trip_description' => $plan->description,
        ];
    }
}
