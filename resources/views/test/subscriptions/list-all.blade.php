<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todas las Suscripciones - VirtualPos API</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-8">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Todas las Suscripciones (API VirtualPos)</h1>
                <p class="text-gray-600">Listado directo desde la API de VirtualPos</p>
                <div class="mt-4">
                    <a href="{{ route('test.subscriptions.index') }}" class="text-blue-600 hover:text-blue-800">← Volver a lista local</a>
                </div>
            </div>

            <!-- Error Messages -->
            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Filters -->
            <form method="GET" class="bg-white rounded-lg shadow-md p-4 mb-6">
                <div class="flex gap-4 items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">Todos</option>
                            <option value="ACTIVA" {{ request('status') == 'ACTIVA' ? 'selected' : '' }}>ACTIVA</option>
                            <option value="SUSCRIBIENDO" {{ request('status') == 'SUSCRIBIENDO' ? 'selected' : '' }}>SUSCRIBIENDO</option>
                            <option value="CANCELADA" {{ request('status') == 'CANCELADA' ? 'selected' : '' }}>CANCELADA</option>
                            <option value="SUSCRIPCION_FALLIDA" {{ request('status') == 'SUSCRIPCION_FALLIDA' ? 'selected' : '' }}>SUSCRIPCION_FALLIDA</option>
                            <option value="FINALIZADA" {{ request('status') == 'FINALIZADA' ? 'selected' : '' }}>FINALIZADA</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Límite</label>
                        <input type="number" name="limit" value="{{ request('limit', 10) }}" min="1" max="100"
                               class="w-24 px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Filtrar
                    </button>
                </div>
            </form>

            <!-- Response -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Respuesta de la API</h2>

                @if(isset($response['data']) && is_array($response['data']))
                    <div class="mb-4 text-sm text-gray-600">
                        Total de resultados: {{ count($response['data']) }}
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($response['data'] as $subscription)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-mono">{{ $subscription['id'] ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $subscription['plan_name'] ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            {{ $subscription['client']['name'] ?? 'N/A' }}
                                            {{ $subscription['client']['surname'] ?? '' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            ${{ number_format($subscription['amount'] ?? 0, 0, ',', '.') }}
                                            {{ $subscription['currency'] ?? 'CLP' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @php
                                                $statusColors = [
                                                    'ACTIVA' => 'bg-green-100 text-green-800',
                                                    'SUSCRIBIENDO' => 'bg-blue-100 text-blue-800',
                                                    'CANCELADA' => 'bg-red-100 text-red-800',
                                                    'SUSCRIPCION_FALLIDA' => 'bg-red-100 text-red-800',
                                                    'FINALIZADA' => 'bg-gray-100 text-gray-800',
                                                ];
                                                $status = $subscription['status'] ?? 'unknown';
                                                $color = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                                            @endphp
                                            <span class="px-2 py-1 text-xs rounded-full {{ $color }}">
                                                {{ $status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm">{{ $subscription['suscription_date'] ?? 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500">No se encontraron suscripciones o la respuesta tiene un formato inesperado.</p>
                @endif

                <!-- Raw Response (Debug) -->
                <details class="mt-6 bg-gray-800 text-gray-100 rounded-lg p-4">
                    <summary class="cursor-pointer font-semibold">🔍 Respuesta Completa (JSON)</summary>
                    <pre class="mt-4 text-xs overflow-auto">{{ json_encode($response, JSON_PRETTY_PRINT) }}</pre>
                </details>
            </div>
        </div>
    </div>
</body>
</html>
