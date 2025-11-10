<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Planes - Test VirtualPos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-8">
        <div class="max-w-5xl mx-auto px-4">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestión de Planes</h1>
                <p class="text-gray-600">Crea y consulta planes de suscripción en VirtualPos</p>
                <div class="mt-4 flex gap-4">
                    <a href="{{ route('test.subscriptions.index') }}" class="text-blue-600 hover:text-blue-800">← Volver a lista</a>
                    <a href="{{ route('test.subscriptions.config') }}" class="text-blue-600 hover:text-blue-800">⚙️ Configuración</a>
                </div>
            </div>

            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <h3 class="font-semibold mb-2">❌ Error</h3>
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

            <!-- Plan Data Display -->
            @if(session('plan_data'))
                <div class="mb-6 bg-blue-50 border-2 border-blue-300 rounded-lg p-6">
                    <h3 class="font-semibold text-blue-900 mb-3 text-lg">📋 Respuesta de VirtualPos</h3>
                    <div class="bg-white rounded p-4 overflow-auto max-h-96">
                        <pre class="text-xs text-gray-800">{{ json_encode(session('plan_data'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                    <p class="text-sm text-blue-900 mt-3">
                        <strong>Tip:</strong> Guarda el Plan ID para usarlo al crear suscripciones.
                    </p>
                </div>
            @endif

            <!-- List All Plans Button -->
            <div class="mb-6">
                <a href="{{ route('test.subscriptions.plans', ['list_all' => 1]) }}"
                   class="inline-block px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold">
                    📜 Ver Todos los Planes Creados
                </a>
            </div>

            <!-- Plans List -->
            @if(isset($plans))
                <div class="mb-6 bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold mb-4">📜 Lista de Planes en VirtualPos</h3>
                    @if(isset($plans['plans']) && count($plans['plans']) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Periodicidad</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Moneda</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($plans['plans'] as $plan)
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-mono">{{ $plan['id'] ?? 'N/A' }}</td>
                                            <td class="px-4 py-3 text-sm">{{ $plan['name'] ?? 'N/A' }}</td>
                                            <td class="px-4 py-3 text-sm">{{ $plan['frequency_type'] ?? 'N/A' }}</td>
                                            <td class="px-4 py-3 text-sm">${{ number_format($plan['amount'] ?? 0, 0, ',', '.') }}</td>
                                            <td class="px-4 py-3 text-sm">{{ $plan['currency'] ?? 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500">No se encontraron planes.</p>
                    @endif

                    <!-- Raw Response -->
                    <details class="mt-4">
                        <summary class="cursor-pointer text-sm text-gray-600 hover:text-gray-800">Ver respuesta completa del API</summary>
                        <div class="mt-2 bg-gray-50 rounded p-4 overflow-auto max-h-96">
                            <pre class="text-xs text-gray-800">{{ json_encode($plans, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </details>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Create Plan Form -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">🆕 Crear Nuevo Plan</h2>

                    <form action="{{ route('test.subscriptions.plans.create') }}" method="POST" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Plan ID *</label>
                            <input type="text" name="plan_id" value="{{ old('plan_id', 'plan_test_' . time()) }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <p class="mt-1 text-xs text-gray-500">Identificador único del plan</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Plan *</label>
                            <input type="text" name="plan_name" value="{{ old('plan_name', 'Plan Mensual de Prueba') }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descripción *</label>
                            <textarea name="description" required rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">{{ old('description', 'Suscripción mensual para acceso al programa') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Descripción detallada del plan</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Periodicidad *</label>
                            <select name="periodicity" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="DAILY">Diario</option>
                                <option value="WEEKLY">Semanal</option>
                                <option value="MONTHLY" selected>Mensual</option>
                                <option value="SEMESTRAL">Semestral</option>
                                <option value="YEARLY">Anual</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Monto Base *</label>
                            <input type="number" name="amount" value="{{ old('amount', 50000) }}" required min="1"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <p class="mt-1 text-xs text-gray-500">Monto de referencia del plan. Cada suscripción puede usar su propio monto con descuentos aplicados.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Moneda *</label>
                            <select name="currency" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="CLP" selected>CLP - Peso Chileno</option>
                                <option value="UF">UF - Unidad de Fomento</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Días de Prueba (opcional)</label>
                            <input type="number" name="trial_period_days" value="{{ old('trial_period_days', 0) }}" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <p class="mt-1 text-xs text-gray-500">0 = sin período de prueba</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Número de Cobros (opcional)</label>
                            <input type="number" name="charges_number" value="{{ old('charges_number', 0) }}" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <p class="mt-1 text-xs text-gray-500">0 = cobros infinitos</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Día de Cobro</label>
                            <select name="fixed_amount_day_charge"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="01" selected>Día 1 del mes</option>
                                <option value="05">Día 5 del mes</option>
                                <option value="10">Día 10 del mes</option>
                                <option value="15">Día 15 del mes</option>
                                <option value="20">Día 20 del mes</option>
                                <option value="25">Día 25 del mes</option>
                                <option value="28">Día 28 del mes</option>
                                <option value="30">Día 30 del mes</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Día del mes en que se ejecutará el cobro</p>
                        </div>

                        <button type="submit"
                                class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                            ✅ Crear Plan
                        </button>
                    </form>
                </div>

                <!-- Get Plan Form -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">🔍 Consultar Plan Existente</h2>

                    <form action="{{ route('test.subscriptions.plans.get') }}" method="POST" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Plan ID *</label>
                            <input type="text" name="plan_id" value="{{ old('plan_id') }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                   placeholder="plan_test_123456">
                            <p class="mt-1 text-xs text-gray-500">ID del plan a consultar</p>
                        </div>

                        <button type="submit"
                                class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                            🔍 Buscar Plan
                        </button>
                    </form>

                    <!-- Info Box -->
                    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-300 rounded-lg">
                        <h3 class="font-semibold text-yellow-900 mb-2">💡 Nota Importante</h3>
                        <p class="text-sm text-yellow-900 mb-2">
                            Antes de crear suscripciones, necesitas crear un plan en VirtualPos.
                            El Plan ID que uses aquí es el que deberás usar al crear suscripciones.
                        </p>
                        <p class="text-sm text-yellow-900">
                            <strong>Arquitectura:</strong> Los planes definen la estructura (periodicidad, moneda),
                            pero cada suscripción puede tener su propio monto con descuentos aplicados.
                        </p>
                    </div>

                    <!-- Quick Action -->
                    <div class="mt-6">
                        <a href="{{ route('test.subscriptions.create') }}"
                           class="block w-full px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-center font-semibold">
                            → Crear Suscripción
                        </a>
                    </div>
                </div>
            </div>

            <!-- Documentation -->
            <div class="mt-8 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">📚 Información sobre Planes y Suscripciones</h2>
                <div class="prose prose-sm max-w-none">
                    <h3 class="font-semibold text-gray-900">¿Qué es un Plan?</h3>
                    <p class="text-gray-700">
                        Un plan define la <strong>estructura base</strong> de una suscripción recurrente: la frecuencia de cobro (diario, semanal, mensual, anual),
                        la moneda, el monto de referencia, y características como períodos de prueba o número límite de cobros.
                    </p>

                    <h3 class="font-semibold text-gray-900 mt-4">Planes vs Suscripciones</h3>
                    <div class="bg-blue-50 p-3 rounded text-gray-700 mb-2">
                        <p><strong>✅ Lo que sabemos de la API de VirtualPos:</strong></p>
                        <ul class="list-disc list-inside space-y-1 mt-2">
                            <li>Los planes requieren un <code>amount</code> obligatorio (monto base)</li>
                            <li>Cada suscripción tiene su propio campo <code>amount</code> independiente del plan</li>
                            <li>Las suscripciones pueden tener montos diferentes al plan (con descuentos, ajustes, etc.)</li>
                            <li>No existe forma de modificar una suscripción existente - hay que cancelar y crear una nueva</li>
                        </ul>
                    </div>

                    <h3 class="font-semibold text-gray-900 mt-4">Arquitectura Recomendada</h3>
                    <ul class="list-disc list-inside text-gray-700 space-y-1">
                        <li>Crear <strong>planes genéricos por programa</strong> (ej: "Plan Mensual Programa X")</li>
                        <li>El monto del plan es un <strong>monto de referencia</strong> (precio base)</li>
                        <li>Al crear cada suscripción, aplicar el monto real con descuentos del cliente</li>
                        <li>Si necesitas cambiar el monto a mitad de camino, cancela la suscripción actual y crea una nueva</li>
                    </ul>

                    <h3 class="font-semibold text-gray-900 mt-4">Periodicidad</h3>
                    <ul class="list-disc list-inside text-gray-700 space-y-1">
                        <li><strong>DAILY:</strong> Cobro diario</li>
                        <li><strong>WEEKLY:</strong> Cobro semanal</li>
                        <li><strong>MONTHLY:</strong> Cobro mensual (más común)</li>
                        <li><strong>YEARLY:</strong> Cobro anual</li>
                    </ul>

                    <h3 class="font-semibold text-gray-900 mt-4">Flujo de Trabajo</h3>
                    <ol class="list-decimal list-inside text-gray-700 space-y-1">
                        <li>Crear un Plan en VirtualPos con monto base (esta página)</li>
                        <li>Usar el Plan ID para crear Suscripciones con montos personalizados</li>
                        <li>Los clientes se cobran automáticamente según la periodicidad del plan</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
