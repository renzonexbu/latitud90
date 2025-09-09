<?php

namespace App\Console\Commands;

use App\Services\Admin\Reports\PaymentSchedule\PaymentScheduleSummaryDataProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class MonthlyParticipantsWithoutPaymentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'participants:monthly-without-payments 
                           {--dry-run : Ejecutar sin enviar notificaciones}
                           {--program_id= : Filtrar por programa específico}
                           {--sales_executive_id= : Filtrar por ejecutivo de ventas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera reporte mensual de participantes que se inscribieron pero no han iniciado pagos';

    /**
     * Execute the console command.
     */
    public function handle(PaymentScheduleSummaryDataProvider $dataProvider)
    {
        $dryRun = $this->option('dry-run');
        $currentMonth = Carbon::now()->setTimezone('America/Santiago');
        
        if ($dryRun) {
            $this->warn('🔍 MODO DRY-RUN: No se enviarán notificaciones');
        }

        $this->info('📊 Iniciando reporte mensual de participantes sin pagos...');
        $this->info("📅 Fecha de ejecución: {$currentMonth->format('Y-m-d H:i:s')}");
        
        try {
            // Preparar filtros
            $filters = [];
            
            if ($this->option('program_id')) {
                $filters['program_id'] = $this->option('program_id');
                $this->info("🎯 Filtrando por programa ID: {$filters['program_id']}");
            }
            
            if ($this->option('sales_executive_id')) {
                $filters['sales_executive_id'] = $this->option('sales_executive_id');
                $this->info("👤 Filtrando por ejecutivo ID: {$filters['sales_executive_id']}");
            }

            // Obtener participantes sin pagos
            $participants = $dataProvider->getParticipantsWithoutPayments($filters);
            
            $totalParticipants = $participants->count();
            
            if ($totalParticipants === 0) {
                $this->info('✅ No se encontraron participantes sin pagos iniciados');
                
                Log::info('MonthlyParticipantsWithoutPayments: No participants found', [
                    'date' => $currentMonth->format('Y-m-d'),
                    'filters' => $filters
                ]);
                
                return 0;
            }

            $this->info("📋 Encontrados {$totalParticipants} participantes sin pagos iniciados");
            
            // Mostrar resumen por programa
            $this->displaySummaryByProgram($participants);
            
            // Mostrar resumen por ejecutivo de ventas
            $this->displaySummaryBySalesExecutive($participants);
            
            // Mostrar participantes más antiguos (top 10)
            $this->displayOldestParticipants($participants);
            
            // Log del reporte
            $this->logReport($participants, $filters, $currentMonth);
            
            // Enviar notificación por email (si no es dry-run)
            if (!$dryRun) {
                $this->sendEmailNotification($participants, $currentMonth);
            }
            
            $this->info('✅ Reporte completado exitosamente');
            
        } catch (\Exception $e) {
            $this->error('❌ Error durante la generación del reporte: ' . $e->getMessage());
            Log::error('Error en MonthlyParticipantsWithoutPaymentsCommand', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }

    /**
     * Mostrar resumen por programa
     */
    private function displaySummaryByProgram($participants): void
    {
        $this->info("\n📊 RESUMEN POR PROGRAMA:");
        $this->info(str_repeat('-', 80));
        
        $programSummary = $participants->groupBy('program_name')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('final_amount'),
                'program_code' => $group->first()->program_code ?? 'N/A'
            ];
        })->sortByDesc('count');

        $headers = ['Programa', 'Código', 'Participantes', 'Monto Total'];
        $rows = [];
        
        foreach ($programSummary as $programName => $data) {
            $rows[] = [
                $programName,
                $data['program_code'],
                $data['count'],
                '$' . number_format($data['total_amount'], 0, ',', '.')
            ];
        }
        
        $this->table($headers, $rows);
    }

    /**
     * Mostrar resumen por ejecutivo de ventas
     */
    private function displaySummaryBySalesExecutive($participants): void
    {
        $this->info("\n👥 RESUMEN POR EJECUTIVO DE VENTAS:");
        $this->info(str_repeat('-', 80));
        
        $executiveSummary = $participants->groupBy('sales_executive_name')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('final_amount')
            ];
        })->sortByDesc('count');

        $headers = ['Ejecutivo', 'Participantes', 'Monto Total'];
        $rows = [];
        
        foreach ($executiveSummary as $executiveName => $data) {
            $rows[] = [
                $executiveName ?: 'Sin asignar',
                $data['count'],
                '$' . number_format($data['total_amount'], 0, ',', '.')
            ];
        }
        
        $this->table($headers, $rows);
    }

    /**
     * Mostrar participantes más antiguos
     */
    private function displayOldestParticipants($participants): void
    {
        $this->info("\n⏰ TOP 10 PARTICIPANTES MÁS ANTIGUOS SIN PAGO:");
        $this->info(str_repeat('-', 80));
        
        $oldestParticipants = $participants->sortBy('incorporation_date')->take(10);
        
        $headers = ['Nombre', 'Email', 'Programa', 'Fecha Inscripción', 'Días sin pago'];
        $rows = [];
        
        foreach ($oldestParticipants as $participant) {
            $incorporationDate = Carbon::parse($participant->incorporation_date);
            $daysWithoutPayment = $incorporationDate->diffInDays(Carbon::now());
            
            $rows[] = [
                trim($participant->first_name . ' ' . $participant->first_last_name . ' ' . $participant->second_last_name),
                $participant->participant_email,
                $participant->program_name,
                $incorporationDate->format('Y-m-d'),
                $daysWithoutPayment . ' días'
            ];
        }
        
        $this->table($headers, $rows);
    }

    /**
     * Log del reporte
     */
    private function logReport($participants, $filters, $currentMonth): void
    {
        $programSummary = $participants->groupBy('program_name')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('final_amount')
            ];
        });

        Log::info('MonthlyParticipantsWithoutPayments: Report generated', [
            'date' => $currentMonth->format('Y-m-d'),
            'total_participants' => $participants->count(),
            'total_amount' => $participants->sum('final_amount'),
            'filters' => $filters,
            'program_summary' => $programSummary->toArray()
        ]);
    }

    /**
     * Enviar notificación por email
     */
    private function sendEmailNotification($participants, $currentMonth): void
    {
        // TODO: Implementar envío de email con resumen del reporte
        // Por ahora solo registramos que se debería enviar
        
        $this->info('📧 Preparando notificación por email...');
        
        Log::info('MonthlyParticipantsWithoutPayments: Email notification should be sent', [
            'date' => $currentMonth->format('Y-m-d'),
            'participant_count' => $participants->count(),
            'total_amount' => $participants->sum('final_amount')
        ]);
        
        $this->info('📧 Notificación registrada en logs (implementación de email pendiente)');
    }
}
