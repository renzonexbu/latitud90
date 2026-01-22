<?php

namespace App\Console\Commands;

use App\Models\Participant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanOrphanParticipants extends Command
{
    protected $signature = 'participants:clean-orphans
                            {--dry-run : Solo mostrar qué se eliminaría sin hacer cambios}
                            {--force : Eliminar incluso si tienen órdenes/pagos (solo marca como inactivo)}';

    protected $description = 'Eliminar o desactivar participantes huérfanos (sin cursos asociados)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('=== Limpieza de Participantes Huérfanos ===');
        $this->newLine();

        // Encontrar participantes sin cursos
        $orphanParticipants = Participant::whereDoesntHave('courses')
            ->where('is_active', true) // Solo activos
            ->get();

        $this->info("Participantes activos sin cursos encontrados: {$orphanParticipants->count()}");
        $this->newLine();

        if ($orphanParticipants->isEmpty()) {
            $this->info('✅ No hay participantes huérfanos para limpiar.');
            return 0;
        }

        // Clasificar participantes
        $canDelete = collect();
        $hasData = collect();

        foreach ($orphanParticipants as $participant) {
            $hasOrders = $participant->orders()->exists();
            $hasGuardians = $participant->guardianUsers()->exists();

            if (!$hasOrders && !$hasGuardians) {
                $canDelete->push($participant);
            } else {
                $hasData->push([
                    'participant' => $participant,
                    'has_orders' => $hasOrders,
                    'has_guardians' => $hasGuardians,
                ]);
            }
        }

        // Mostrar participantes que se pueden eliminar
        if ($canDelete->isNotEmpty()) {
            $this->info("📋 Participantes sin datos asociados (se pueden eliminar): {$canDelete->count()}");
            $this->table(
                ['ID', 'RUT/Pasaporte', 'Nombre', 'Email', 'Creado'],
                $canDelete->map(fn($p) => [
                    $p->id,
                    $p->document_number,
                    "{$p->first_name} {$p->first_last_name}",
                    $p->email ?? 'N/A',
                    $p->created_at->format('Y-m-d'),
                ])->toArray()
            );
            $this->newLine();
        }

        // Mostrar participantes con datos asociados
        if ($hasData->isNotEmpty()) {
            $this->warn("⚠️  Participantes con datos asociados (se desactivarán): {$hasData->count()}");
            $this->table(
                ['ID', 'RUT/Pasaporte', 'Nombre', 'Tiene Órdenes', 'Tiene Apoderados'],
                $hasData->map(fn($item) => [
                    $item['participant']->id,
                    $item['participant']->document_number,
                    "{$item['participant']->first_name} {$item['participant']->first_last_name}",
                    $item['has_orders'] ? 'Sí' : 'No',
                    $item['has_guardians'] ? 'Sí' : 'No',
                ])->toArray()
            );
            $this->newLine();
        }

        if ($dryRun) {
            $this->info('🔍 Modo dry-run: No se realizaron cambios.');
            $this->info("   - Se eliminarían: {$canDelete->count()} participantes");
            $this->info("   - Se desactivarían: {$hasData->count()} participantes");
            return 0;
        }

        // Confirmar acción
        if (!$this->confirm('¿Deseas proceder con la limpieza?')) {
            $this->info('Operación cancelada.');
            return 0;
        }

        $deleted = 0;
        $deactivated = 0;

        DB::beginTransaction();
        try {
            // Eliminar participantes sin datos
            foreach ($canDelete as $participant) {
                // Eliminar relaciones primero
                $participant->emergencyContacts()->delete();
                $participant->medicalConditions()->delete();
                $participant->delete();
                $deleted++;
            }

            // Desactivar participantes con datos (o eliminar si --force)
            if ($force) {
                foreach ($hasData as $item) {
                    $item['participant']->update(['is_active' => false]);
                    $deactivated++;
                }
            } else {
                foreach ($hasData as $item) {
                    $item['participant']->update(['is_active' => false]);
                    $deactivated++;
                }
            }

            DB::commit();

            $this->newLine();
            $this->info('✅ Limpieza completada:');
            $this->info("   - Eliminados: {$deleted} participantes");
            $this->info("   - Desactivados: {$deactivated} participantes");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error durante la limpieza: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
