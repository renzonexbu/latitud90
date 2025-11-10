<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suscripción #{{ $subscription->id }} - Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-8">
        <div class="max-w-5xl mx-auto px-4">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">Suscripción #{{ $subscription->id }}</h1>
                        <p class="text-gray-600">
                            Creada el {{ $subscription->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
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
                    <span class="px-4 py-2 text-lg rounded-full {{ $color }} font-semibold">
                        {{ $subscription->status }}
                    </span>
                </div>
                <div class="mt-4">
                    <a href="{{ route('test.subscriptions.index') }}" class="text-blue-600 hover:text-blue-800">← Volver a lista</a>
                </div>
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Info -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Información Básica</h2>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">VirtualPos ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $subscription->virtualpos_subscription_id ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Plan ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $subscription->virtualpos_plan_id ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Plan Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $subscription->plan_name ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Monto</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-semibold text-lg">
                                ${{ number_format($subscription->amount, 0, ',', '.') }} {{ $subscription->currency }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Renovación Automática</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $subscription->automatic_renewal == 'T' ? 'Sí' : 'No' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Canal</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $subscription->channel }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Client Info -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Información del Cliente</h2>
                    @if($subscription->client_data)
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Nombre</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ $subscription->client_data['name'] ?? 'N/A' }}
                                    {{ $subscription->client_data['surname'] ?? '' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $subscription->client_data['email'] ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">RUT</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $subscription->client_data['rut'] ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Teléfono</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $subscription->client_data['phone'] ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Dirección</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ $subscription->client_data['address']['street'] ?? '' }}
                                    {{ $subscription->client_data['address']['city'] ?? '' }}
                                    {{ $subscription->client_data['address']['country'] ?? '' }}
                                </dd>
                            </div>
                        </dl>
                    @else
                        <p class="text-gray-500 text-sm">No hay datos de cliente disponibles</p>
                    @endif
                </div>
            </div>

            <!-- Payment Method -->
            @if($subscription->payment_method)
                <div class="mt-6 bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Método de Pago</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Marca</p>
                            <p class="font-medium">{{ $subscription->payment_method['brand'] ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Últimos 4 dígitos</p>
                            <p class="font-medium font-mono">**** {{ $subscription->payment_method['last4'] ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Expiración</p>
                            <p class="font-medium">{{ $subscription->payment_method['exp_date'] ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Emisor</p>
                            <p class="font-medium">{{ $subscription->payment_method['issuer'] ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Charge Program -->
            @if($subscription->charge_program && count($subscription->charge_program) > 0)
                <div class="mt-6 bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Programa de Cobros</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($subscription->charge_program as $charge)
                                    <tr>
                                        <td class="px-4 py-2 text-sm font-mono">{{ $charge['id'] ?? 'N/A' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $charge['scheduled_date'] ?? 'N/A' }}</td>
                                        <td class="px-4 py-2 text-sm">${{ number_format($charge['amount'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-sm">
                                            @php
                                                $chargeStatusColors = [
                                                    'paid' => 'bg-green-100 text-green-800',
                                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                                    'processing' => 'bg-blue-100 text-blue-800',
                                                    'rejected' => 'bg-red-100 text-red-800',
                                                    'canceled' => 'bg-gray-100 text-gray-800',
                                                ];
                                                $chargeStatus = $charge['status'] ?? 'unknown';
                                                $chargeColor = $chargeStatusColors[$chargeStatus] ?? 'bg-gray-100 text-gray-800';
                                            @endphp
                                            <span class="px-2 py-1 text-xs rounded-full {{ $chargeColor }}">
                                                {{ $chargeStatus }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-sm">{{ $charge['description'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Actions -->
            <div class="mt-6 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Acciones</h2>
                <div class="flex flex-wrap gap-3">
                    @if($subscription->virtualpos_subscription_id)
                        <form action="{{ route('test.subscriptions.sync', $subscription->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                🔄 Sincronizar
                            </button>
                        </form>

                        @if($subscription->status === 'ACTIVA')
                            <form action="{{ route('test.subscriptions.card-change-link', $subscription->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                                    💳 Generar Link Cambio Tarjeta
                                </button>
                            </form>

                            <form action="{{ route('test.subscriptions.cancel', $subscription->id) }}" method="POST" class="inline"
                                  onsubmit="return confirm('¿Estás seguro de cancelar esta suscripción?')">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                    ❌ Cancelar Suscripción
                                </button>
                            </form>
                        @endif
                    @endif
                </div>

                @if($subscription->card_change_link)
                    <div class="mt-4 p-4 bg-purple-50 border border-purple-200 rounded">
                        <p class="text-sm font-medium text-purple-900 mb-2">Link de Cambio de Tarjeta:</p>
                        <a href="{{ $subscription->card_change_link }}" target="_blank"
                           class="text-sm text-purple-600 hover:underline break-all">
                            {{ $subscription->card_change_link }}
                        </a>
                        <p class="text-xs text-purple-700 mt-2">
                            Generado: {{ $subscription->card_change_link_generated_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                @endif
            </div>

            <!-- API Response (Debug) -->
            @if($subscription->api_response)
                <details class="mt-6 bg-gray-800 text-gray-100 rounded-lg shadow-md p-6">
                    <summary class="cursor-pointer font-semibold">🔍 Respuesta de API (Debug)</summary>
                    <pre class="mt-4 text-xs overflow-auto">{{ json_encode($subscription->api_response, JSON_PRETTY_PRINT) }}</pre>
                </details>
            @endif
        </div>
    </div>
</body>
</html>
