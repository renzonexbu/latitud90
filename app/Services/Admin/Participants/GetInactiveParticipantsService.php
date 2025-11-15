<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use Illuminate\Http\Request;

class GetInactiveParticipantsService
{
    /**
     * Obtener participantes inactivos
     *
     * @param Request $request
     * @return array
     */
    public function execute(Request $request): array
    {
        $participants = Participant::with(['courses', 'courses.institution', 'courses.programCourses.program'])
            ->where('is_active', false)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return [
            'participants' => $participants,
            'filters' => $request->only(['search', 'institution', 'level', 'program', 'status'])
        ];
    }
}
