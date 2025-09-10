<?php

namespace App\Http\Controllers\Admin\Participants;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Services\Admin\Participants\UpdateMedicalConditionsService;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Admin\Participants\UpdateMedicalConditionsRequest;

class ParticipantMedicalController extends Controller
{
    use AdminLogging;

    protected $updateMedicalConditionsService;

    public function __construct(UpdateMedicalConditionsService $updateMedicalConditionsService)
    {
        $this->updateMedicalConditionsService = $updateMedicalConditionsService;
    }

    /**
     * Update medical conditions of a participant.
     */
    public function update(UpdateMedicalConditionsRequest $request, Participant $participant)
    {
        try {
            $oldValues = [
                'allergies' => $participant->allergies,
                'intolerances' => $participant->intolerances,
                'dietary_restrictions' => $participant->dietary_restrictions,
            ];

            $this->updateMedicalConditionsService->execute($request->validated(), $participant);

            $this->logUpdate(
                'Condiciones Médicas',
                'Participant',
                $participant->id,
                'Condiciones médicas actualizadas para participante: ' . $participant->first_name . ' ' . $participant->first_last_name,
                $oldValues,
                $request->validated()
            );

            return back()->with('success', 'Condiciones médicas actualizadas exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error en controlador al actualizar condiciones médicas', [
                'participant_id' => $participant->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Error al actualizar las condiciones médicas: ' . $e->getMessage()]);
        }
    }
}
