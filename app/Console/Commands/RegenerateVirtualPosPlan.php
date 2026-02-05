<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProgramCourse;
use App\Models\VirtualPosPlan;
use App\Models\ProgramSubscription;
use App\Services\Subscription\VirtualPosPlanService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegenerateVirtualPosPlan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'virtualpos:regenerate-plan {program_course_id : ID del ProgramCourse}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenera el plan de VirtualPos para un programa (solo si no hay suscripciones activas)';

    protected $virtualPosPlanService;

    public function __construct(VirtualPosPlanService $virtualPosPlanService)
    {
        parent::__construct();
        $this->virtualPosPlanService = $virtualPosPlanService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $programCourseId = (int) $this->argument('program_course_id');

        $this->info("🔄 Regenerando plan de VirtualPos para ProgramCourse ID: {$programCourseId}");

        try {
            DB::beginTransaction();

            // 1. Cargar el ProgramCourse con relaciones necesarias
            $programCourse = ProgramCourse::with(['program', 'course.institution'])
                ->findOrFail($programCourseId);

            $this->info("📋 Programa: {$programCourse->code} - {$programCourse->name}");

            // 2. Verificar que tenga un plan de VirtualPos
            if (!$programCourse->virtualpos_plan_id) {
                $this->error('❌ Este programa no tiene un plan de VirtualPos asociado.');
                return 1;
            }

            $oldPlanId = $programCourse->virtualpos_plan_id;
            $this->info("🔍 Plan actual de VirtualPos: {$oldPlanId}");

            // 3. Verificar que no haya suscripciones activas en el plan genérico
            $activeSubscriptions = ProgramSubscription::where('program_id', $programCourseId)
                ->where('virtualpos_plan_id', $oldPlanId)
                ->whereIn('status', ['ACTIVA', 'SUSCRIBIENDO'])
                ->whereNull('participant_id') // Solo plan genérico (no personalizado)
                ->count();

            if ($activeSubscriptions > 0) {
                $this->error("❌ No se puede regenerar el plan: hay {$activeSubscriptions} suscripción(es) activa(s) usando el plan genérico.");
                $this->warn('💡 Tip: Solo se pueden regenerar planes sin suscripciones activas.');
                return 1;
            }

            $this->info('✅ No hay suscripciones activas en el plan genérico.');

            // 4. Desactivar el plan anterior en la base de datos local
            $localPlan = VirtualPosPlan::where('virtualpos_plan_id', $oldPlanId)
                ->where('program_course_id', $programCourseId)
                ->whereNull('participant_id') // Solo plan genérico
                ->first();

            if ($localPlan) {
                $localPlan->update(['is_active' => false]);
                $this->info("🗑️  Plan local ID {$localPlan->id} marcado como inactivo.");
            }

            // 5. Preparar datos para el nuevo plan
            $planData = [
                'code' => $programCourse->code,
                'name' => $programCourse->name,
                'trip_price' => $programCourse->trip_price,
                'max_installments' => $programCourse->subscription_max_months,
                'immediate_first_charge' => $programCourse->immediate_first_charge ?? true,
                'trip_description' => $programCourse->program->trip_description ??
                    "Programa {$programCourse->program->name} - {$programCourse->course->institution->name}",
            ];

            $this->info('📝 Datos del nuevo plan:');
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['Código', $planData['code']],
                    ['Nombre', $planData['name']],
                    ['Precio', '$' . number_format($planData['trip_price'], 0, ',', '.')],
                    ['Cuotas', $planData['max_installments']],
                    ['Cobro inmediato', $planData['immediate_first_charge'] ? 'Sí' : 'No'],
                ]
            );

            // 6. Crear el nuevo plan en VirtualPos
            $this->info('🚀 Creando nuevo plan en VirtualPos...');
            $result = $this->virtualPosPlanService->createPlan($planData);

            if (!$result || !$result['success']) {
                $errorMsg = $result['error'] ?? 'Error desconocido';
                throw new \Exception("Error al crear plan en VirtualPos: {$errorMsg}");
            }

            $newPlanId = $result['plan_id'];
            $this->info("✅ Nuevo plan creado en VirtualPos: {$newPlanId}");

            // 7. Actualizar el ProgramCourse con el nuevo plan_id
            $programCourse->update(['virtualpos_plan_id' => $newPlanId]);
            $this->info("✅ ProgramCourse actualizado con el nuevo plan_id.");

            // 8. Guardar el nuevo plan en la tabla local virtualpos_plans
            $monthlyAmount = (int) floor(($planData['trip_price'] / $planData['max_installments']) + 0.4);

            VirtualPosPlan::create([
                'virtualpos_plan_id' => $newPlanId,
                'program_course_id' => $programCourseId,
                'code' => $planData['code'],
                'name' => $planData['name'],
                'description' => $planData['trip_description'],
                'trip_price' => $planData['trip_price'],
                'monthly_amount' => $monthlyAmount,
                'max_installments' => $planData['max_installments'],
                'immediate_first_charge' => $planData['immediate_first_charge'],
                'currency' => 'CLP',
                'frequency_type' => 'Mensual',
                'plan_type' => 'PROGRAMA_DE_PAGOS',
                'is_active' => true,
                'api_response' => $result['data'] ?? null,
            ]);

            $this->info("✅ Nuevo plan guardado en la base de datos local.");

            // 9. Log del cambio
            Log::info('Plan de VirtualPos regenerado exitosamente', [
                'program_course_id' => $programCourseId,
                'old_plan_id' => $oldPlanId,
                'new_plan_id' => $newPlanId,
                'program_code' => $programCourse->code,
            ]);

            DB::commit();

            $this->newLine();
            $this->info('🎉 ¡Plan regenerado exitosamente!');
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['Plan anterior', $oldPlanId],
                    ['Plan nuevo', $newPlanId],
                    ['Programa', $programCourse->code],
                    ['Nombre', $programCourse->name],
                ]
            );

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error al regenerar el plan: ' . $e->getMessage());
            Log::error('Error regenerando plan de VirtualPos', [
                'program_course_id' => $programCourseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}
