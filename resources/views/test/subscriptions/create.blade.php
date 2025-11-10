<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Suscripción - Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-8">
        <div class="max-w-3xl mx-auto px-4">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Crear Suscripción de Prueba</h1>
                <p class="text-gray-600">Testing de integración VirtualPos Subscriptions API</p>
                <div class="mt-4">
                    <a href="{{ route('test.subscriptions.index') }}" class="text-blue-600 hover:text-blue-800">← Volver a lista</a>
                </div>
            </div>

            <!-- Error Messages -->
            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <h3 class="font-semibold mb-2">Error</h3>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li class="text-sm">{{ $error }}</li>
                        @endforeach
                    </ul>
                    @if(session('error_details'))
                        <details class="mt-3">
                            <summary class="cursor-pointer text-sm font-semibold">Ver detalles técnicos</summary>
                            <pre class="mt-2 text-xs bg-red-50 p-2 rounded overflow-auto">{{ session('error_details') }}</pre>
                        </details>
                    @endif
                </div>
            @endif

            <!-- Client Info -->
            <div class="mb-6 p-6 bg-green-50 border-2 border-green-300 rounded-lg">
                <h3 class="font-semibold text-green-900 mb-3 text-lg">👤 Datos del Cliente de Prueba (Hardcodeados)</h3>
                <div class="grid grid-cols-2 gap-4 text-sm text-green-900">
                    <div>
                        <p><strong>Nombre:</strong> María González</p>
                        <p><strong>Email:</strong> maria.gonzalez.test@gmail.com</p>
                        <p><strong>RUT:</strong> 11111111-1</p>
                    </div>
                    <div>
                        <p><strong>Teléfono:</strong> +56987654321</p>
                        <p><strong>Dirección:</strong> Av. Libertador 456, Santiago, Chile</p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form action="{{ route('test.subscriptions.store') }}" method="POST" class="bg-white rounded-lg shadow-md p-6">
                @csrf

                <div class="space-y-6">
                    <!-- Plan ID -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Plan *
                        </label>
                        @if($plans && isset($plans['plans']) && count($plans['plans']) > 0)
                            <select name="plan_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">-- Selecciona un plan --</option>
                                @foreach($plans['plans'] as $plan)
                                    <option value="{{ $plan['id'] }}" {{ old('plan_id') == $plan['id'] ? 'selected' : '' }}>
                                        {{ $plan['name'] }} - ${{ number_format($plan['amount'], 0, ',', '.') }} {{ $plan['currency'] }} ({{ $plan['frequency_type'] }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-sm text-gray-500">
                                Selecciona un plan existente en VirtualPos.
                                <a href="{{ route('test.subscriptions.plans') }}" class="text-blue-600 hover:text-blue-800" target="_blank">Ver/Crear planes →</a>
                            </p>
                        @else
                            <input type="text" name="plan_id" value="{{ old('plan_id', 'plan_test_' . time()) }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="plan_test_123456">
                            <p class="mt-1 text-sm text-yellow-700">
                                ⚠️ No se pudieron cargar los planes disponibles. Ingresa manualmente el ID del plan.
                                <a href="{{ route('test.subscriptions.plans') }}" class="text-blue-600 hover:text-blue-800" target="_blank">Ir a gestión de planes →</a>
                            </p>
                        @endif
                    </div>

                    <!-- Amount -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Monto *
                        </label>
                        <input type="number" name="amount" value="{{ old('amount', 50000) }}" required min="1000" step="1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="50000">
                        <p class="mt-1 text-sm text-gray-500">Monto de la suscripción recurrente (sin puntos ni comas)</p>
                    </div>

                    <!-- Currency -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Moneda *
                        </label>
                        <select name="currency" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="CLP" {{ old('currency', 'CLP') == 'CLP' ? 'selected' : '' }}>CLP - Peso Chileno</option>
                            <option value="UF" {{ old('currency') == 'UF' ? 'selected' : '' }}>UF - Unidad de Fomento</option>
                        </select>
                    </div>
                </div>

                <!-- Test Card Info -->
                <div class="mt-6 p-4 bg-purple-50 border-2 border-purple-300 rounded-lg">
                    <h3 class="font-semibold text-purple-900 mb-3">💳 Tarjetas de Prueba VirtualPos</h3>
                    <div class="text-sm text-purple-900 space-y-3">
                        <div>
                            <p class="font-semibold">✅ Tarjeta Exitosa:</p>
                            <p><strong>Número:</strong> 4051885600446623</p>
                            <p><strong>CVV:</strong> 123</p>
                            <p><strong>Fecha:</strong> Cualquier fecha futura válida (ej: 12/2025)</p>
                        </div>
                        <div class="pt-2 border-t border-purple-200">
                            <p class="font-semibold">❌ Tarjeta Rechazada (para probar errores):</p>
                            <p><strong>Número:</strong> 4513700200000008</p>
                            <p><strong>CVV:</strong> 123</p>
                        </div>
                    </div>
                </div>

                <!-- Important Notes -->
                <div class="mt-6 p-4 bg-yellow-50 border border-yellow-300 rounded-lg">
                    <h3 class="font-semibold text-yellow-900 mb-2">⚠️ Notas Importantes</h3>
                    <ul class="text-sm text-yellow-900 list-disc list-inside space-y-1">
                        <li>Estás en ambiente <strong>SANDBOX</strong> - no se harán cobros reales</li>
                        <li>Al crear la suscripción, VirtualPos redirigirá a una página de pago</li>
                        <li>Completa el formulario de pago con los datos de tarjeta de prueba</li>
                        <li>Podrás probar: sincronización, cambio de tarjeta, cancelación, etc.</li>
                    </ul>
                </div>

                <!-- Submit -->
                <div class="mt-6 flex gap-4">
                    <button type="submit"
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                        🚀 Crear Suscripción
                    </button>
                    <a href="{{ route('test.subscriptions.index') }}"
                       class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
