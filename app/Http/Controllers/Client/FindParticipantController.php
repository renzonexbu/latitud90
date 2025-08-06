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
            'rut' => 'required|string|min:7'
        ]);

        $rut = $request->input('rut');
        $participant = $this->findParticipantService->findByRut($rut);

        return response()->json([
            'found' => !is_null($participant),
            'participant' => $participant
        ]);
    }
}
