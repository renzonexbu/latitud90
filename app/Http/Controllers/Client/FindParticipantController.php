<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\FindParticipantService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FindParticipantController extends Controller
{
    protected $findParticipantService;

    public function __construct(FindParticipantService $findParticipantService)
    {
        $this->findParticipantService = $findParticipantService;
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
        
        $participant = $this->findParticipantService->findByDocument($document, $documentType);

        return response()->json([
            'found' => !is_null($participant),
            'participant' => $participant
        ]);
    }
}
