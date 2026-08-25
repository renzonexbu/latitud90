<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\FrequentClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Client\Programs\FrequentClientService;
use Illuminate\Support\Facades\Log;

class FrequentClientController extends Controller
{
    /**
     * Buscar cliente frecuente por tipo de documento y número
     */
    public function findByDocument(Request $request): JsonResponse
    {
        $request->validate([
            'document_id' => 'required|integer|exists:document,id',
            'document' => 'required|string|max:255',
        ]);

        $client = FrequentClientService::findByDocument($request->all());

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cliente encontrado',
            'data' => [
                'full_name' => $client->full_name,
                'document_id' => $client->document_id,
                'document' => $client->document,
                'email' => $client->email,
                'phone_code' => $client->phone_code,
                'phone' => $client->phone,
                'country_id' => $client->country_id,
                'region_id' => $client->region_id,
                'comune_id' => $client->comune_id,
                'terms_accepted' => $client->terms_accepted,
                'marketing_accepted' => $client->marketing_accepted,
            ]
        ]);
    }

    /**
     * Guardar o actualizar cliente frecuente
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'document_id' => 'required|integer|exists:document,id',
            'document' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_code' => 'required|string|max:10',
            'phone' => 'required|string|max:20',
            'country_id' => 'required|integer|exists:countries,id',
            'region_id' => 'nullable|integer|exists:regions,id',
            'comune_id' => 'nullable|integer|exists:comunes,id',
            'terms_accepted' => 'required|boolean',
            'marketing_accepted' => 'required|boolean',
        ]);

        try {
            $client = FrequentClientService::store($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Cliente guardado exitosamente',
                'data' => $client
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de clientes frecuentes
     */
    public function getStats(): JsonResponse
    {
        $stats = [
            'total_clients' => FrequentClient::count(),
            'most_used' => FrequentClient::orderBy('usage_count', 'desc')
                ->limit(5)
                ->get(['full_name', 'document', 'usage_count']),
            'recent_clients' => FrequentClient::orderBy('last_used_at', 'desc')
                ->limit(5)
                ->get(['full_name', 'document', 'last_used_at']),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
