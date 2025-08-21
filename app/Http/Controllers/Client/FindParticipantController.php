<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\FindParticipantService;
use App\Services\EcommerceAnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FindParticipantController extends Controller
{
    protected $findParticipantService;
    protected $analyticsService;

    public function __construct(
        FindParticipantService $findParticipantService,
        EcommerceAnalyticsService $analyticsService
    ) {
        $this->findParticipantService = $findParticipantService;
        $this->analyticsService = $analyticsService;
    }

    public function findParticipant()
    {
        return view('client.find-participant');
    }

    public function searchParticipant(Request $request)
    {
        $request->validate([
            'document' => 'required|string|min:3',
            'document_type' => 'nullable|string|in:RUT,PASAPORTE'
        ]);

        $document = $request->input('document');
        $documentType = $request->input('document_type');
        
        // Registrar búsqueda en analytics y obtener session_id
        $sessionId = $this->analyticsService->recordHeroSearch($request, $document, $documentType);
        
        $participant = $this->findParticipantService->findByDocument($document, $documentType);

        return response()->json([
            'found' => !is_null($participant),
            'participant' => $participant,
            'session_id' => $sessionId
        ]);
    }
}
