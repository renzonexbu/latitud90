<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Services\Admin\Institutions\CreateInstitutionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

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
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:institutions,name',
            'type' => 'required|string|in:school,university,other',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
        ], [
            'name.required' => 'El nombre de la institución es obligatorio.',
            'name.unique' => 'Ya existe una institución con ese nombre.',
            'type.required' => 'El tipo de institución es obligatorio.',
            'type.in' => 'El tipo de institución seleccionado no es válido.',
        ]);

        try {
            $institution = $this->createInstitutionService->execute($request->all());
            
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
