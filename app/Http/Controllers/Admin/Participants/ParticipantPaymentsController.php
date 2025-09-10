<?php

namespace App\Http\Controllers\Admin\Participants;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Services\Admin\Participants\GetParticipantPaymentsService;
use Illuminate\Http\Request;

class ParticipantPaymentsController extends Controller
{
    protected $getParticipantPaymentsService;

    public function __construct(GetParticipantPaymentsService $getParticipantPaymentsService)
    {
        $this->getParticipantPaymentsService = $getParticipantPaymentsService;
    }

    /**
     * Get participant payments.
     */
    public function show(Participant $participant)
    {
        $payments = $this->getParticipantPaymentsService->execute($participant);

        return response()->json($payments);
    }

    /**
     * Export participants to Excel.
     */
    public function export(Request $request)
    {
        // Implementation would depend on your Excel export package
        // For example, using Laravel Excel
        return response()->json(['message' => 'Export functionality to be implemented']);
    }
}
