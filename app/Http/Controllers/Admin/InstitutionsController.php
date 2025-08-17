<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Services\Admin\Institutions\CreateInstitutionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use App\Http\Requests\Admin\Institutions\CreateInstitutionRequest;

class InstitutionsController extends Controller
{
    protected $createInstitutionService;

    public function __construct(CreateInstitutionService $createInstitutionService)
    {
        $this->createInstitutionService = $createInstitutionService;
    }

    /**
     * Show the form for creating a new institution
     */
    public function create()
    {
        return Inertia::render('Admin/Institutions/Create');
    }

    /**
     * Get all active institutions for dropdown
     */
    public function index(): JsonResponse
    {
        $institutions = Institution::active()
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return response()->json($institutions);
    }

    /**
     * Store a newly created institution
     */
    public function store(CreateInstitutionRequest $request): JsonResponse
    {
        try {
            $institution = $this->createInstitutionService->execute($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Institución creada exitosamente.',
                'institution' => $institution
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la institución: ' . $e->getMessage()
            ], 500);
        }
    }
}
