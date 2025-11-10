<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Suscripciones</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-8">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Test de Suscripciones VirtualPos</h1>
                <p class="text-gray-600">Ambiente: {{ config('app.env') }}</p>
            </div>

            <!-- Actions -->
            <div class="flex gap-4 mb-6">
                <a href="{{ route('test.subscriptions.create') }}"
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    + Nueva Suscripción
                </a>
                <a href="{{ route('test.subscriptions.config') }}"
                   class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    ⚙️ Configuración
                </a>
                <a href="{{ route('test.subscriptions.list-all') }}"
                   class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    📋 Listar todas (API)
                </a>
                <form action="{{ route('test.subscriptions.cancel-all') }}" method="POST"
                      onsubmit="return confirm('¿Estás seguro de cancelar TODAS las suscripciones activas en VirtualPos? Esta acción limpiará el cache de usuarios de prueba.')">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        🗑️ Cancelar Todas
                    </button>
                </form>
            </div>

            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Subscriptions List -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Participante</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Programa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($subscriptions as $subscription)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                                    #{{ $subscription->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $subscription->participant->first_name ?? 'N/A' }}
                                    {{ $subscription->participant->first_last_name ?? '' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $subscription->program->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${{ number_format($subscription->amount, 0, ',', '.') }} {{ $subscription->currency }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'ACTIVA' => 'bg-green-100 text-green-800',
                                            'SUSCRIBIENDO' => 'bg-blue-100 text-blue-800',
                                            'CANCELADA' => 'bg-red-100 text-red-800',
                                            'SUSCRIPCION_FALLIDA' => 'bg-red-100 text-red-800',
                                            'FINALIZADA' => 'bg-gray-100 text-gray-800',
                                        ];
                                        $color = $statusColors[$subscription->status] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 py-1 text-xs rounded-full {{ $color }}">
                                        {{ $subscription->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $subscription->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="{{ route('test.subscriptions.show', $subscription->id) }}"
                                       class="text-blue-600 hover:text-blue-900">
                                        Ver detalles →
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    No hay suscripciones registradas.
                                    <a href="{{ route('test.subscriptions.create') }}" class="text-blue-600 hover:underline">
                                        Crear una ahora
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($subscriptions->hasPages())
                <div class="mt-6">
                    {{ $subscriptions->links() }}
                </div>
            @endif
        </div>
    </div>
</body>
</html>
