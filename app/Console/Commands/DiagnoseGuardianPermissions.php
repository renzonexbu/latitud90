<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GuardianUser;
use App\Models\Participant;
use App\Models\GuardianEmergencyContact;
use App\Models\EmergencyContact;

class DiagnoseGuardianPermissions extends Command
{
    protected $signature = 'guardian:diagnose {guardian_email} {participant_document}';
    protected $description = 'Diagnosticar permisos de guardian para un participante';

    public function handle()
    {
        $guardianEmail = $this->argument('guardian_email');
        $participantDocument = $this->argument('participant_document');

        // Buscar guardian
        $guardian = GuardianUser::where('email', $guardianEmail)->first();
        if (!$guardian) {
            $this->error("Guardian no encontrado: {$guardianEmail}");
            return 1;
        }

        // Buscar participante
        $cleanDocument = preg_replace('/[.-]/', '', $participantDocument);
        $participant = Participant::where('document_number', $cleanDocument)->first();
        if (!$participant) {
            $this->error("Participante no encontrado: {$participantDocument}");
            return 1;
        }

        $this->info("=== DIAGNÓSTICO DE PERMISOS ===");
        $this->info("Guardian: {$guardian->name} (ID: {$guardian->id})");
        $this->info("Participante: {$participant->full_name} (ID: {$participant->id})");
        $this->newLine();

        // Buscar emergency_contact del participante
        $emergencyContact = EmergencyContact::where('participant_id', $participant->id)->first();
        if (!$emergencyContact) {
            $this->error("❌ No existe emergency_contact para este participante");
            $this->info("Solución: Crear un emergency_contact primero");
            return 1;
        }

        $this->info("✅ Emergency Contact encontrado (ID: {$emergencyContact->id})");
        $this->newLine();

        // Buscar relación guardian_emergency_contact
        $link = GuardianEmergencyContact::where('guardian_user_id', $guardian->id)
            ->where('emergency_contact_id', $emergencyContact->id)
            ->first();

        if (!$link) {
            $this->error("❌ No existe vínculo en guardian_emergency_contact");
            $this->info("Solución: Crear el vínculo con:");
            $this->info("  guardian_user_id: {$guardian->id}");
            $this->info("  emergency_contact_id: {$emergencyContact->id}");
            return 1;
        }

        $this->info("✅ Vínculo encontrado en guardian_emergency_contact (ID: {$link->id})");
        $this->newLine();

        // Verificar campos del vínculo
        $this->info("Estado del vínculo:");
        $this->info("  - invitation_status: " . ($link->invitation_status ?? 'NULL'));
        $this->info("  - can_pay: " . ($link->can_pay ? 'true' : 'false'));
        $this->info("  - is_primary: " . ($link->is_primary ? 'true' : 'false'));
        $this->newLine();

        // Verificar cada condición
        $problems = [];

        if ($link->invitation_status !== 'accepted') {
            $problems[] = "invitation_status debe ser 'accepted' (actual: " . ($link->invitation_status ?? 'NULL') . ")";
        }

        if (!$link->can_pay) {
            $problems[] = "can_pay debe ser true (actual: false)";
        }

        if (count($problems) > 0) {
            $this->error("❌ PROBLEMAS ENCONTRADOS:");
            foreach ($problems as $problem) {
                $this->error("  - {$problem}");
            }
            $this->newLine();
            $this->info("Solución: Actualizar el registro con:");
            $this->info("UPDATE guardian_emergency_contact SET invitation_status = 'accepted', can_pay = 1 WHERE id = {$link->id}");
        } else {
            $this->info("✅ TODOS LOS PERMISOS ESTÁN CORRECTOS");
            $this->info("El método canPayFor() debería retornar TRUE");
        }

        // Probar canPayFor()
        $this->newLine();
        $canPay = $guardian->canPayFor($participant->id);
        $this->info("Resultado de canPayFor(): " . ($canPay ? '✅ TRUE' : '❌ FALSE'));

        return 0;
    }
}
