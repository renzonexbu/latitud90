<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Test Suscripciones</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-8">
        <div class="max-w-4xl mx-auto px-4">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Configuración de Suscripciones VirtualPos</h1>
                <p class="text-gray-600">Módulo de testing - Ambiente {{ config('app.env') }}</p>
                <div class="mt-4">
                    <a href="{{ route('test.subscriptions.index') }}" class="text-blue-600 hover:text-blue-800">← Volver a lista</a>
                </div>
            </div>

            <!-- Config Status -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Estado de Configuración</h2>

                <div class="space-y-4">
                    <!-- API URL -->
                    <div class="flex items-center justify-between border-b pb-3">
                        <div>
                            <p class="font-medium text-gray-700">API URL</p>
                            <p class="text-sm text-gray-500">{{ $config['api_url'] }}</p>
                        </div>
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">Configurado</span>
                    </div>

                    <!-- API Key -->
                    <div class="flex items-center justify-between border-b pb-3">
                        <div>
                            <p class="font-medium text-gray-700">API Key</p>
                            <p class="text-sm text-gray-500 font-mono">{{ $config['api_key'] }}</p>
                        </div>
                        @if(config('services.virtualpos.api_key'))
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">✓ Configurado</span>
                        @else
                            <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">✗ Falta</span>
                        @endif
                    </div>

                    <!-- Secret Key -->
                    <div class="flex items-center justify-between border-b pb-3">
                        <div>
                            <p class="font-medium text-gray-700">Secret Key</p>
                            <p class="text-sm text-gray-500 font-mono">{{ $config['secret_key'] }}</p>
                        </div>
                        @if(config('services.virtualpos.secret_key'))
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">✓ Configurado</span>
                        @else
                            <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">✗ Falta</span>
                        @endif
                    </div>

                    <!-- Merchant Code -->
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-700">Merchant Code</p>
                            <p class="text-sm text-gray-500">{{ $config['merchant_code'] ?: 'No configurado' }}</p>
                        </div>
                        @if(config('services.virtualpos.merchant_code'))
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">✓ Configurado</span>
                        @else
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm">⚠ Opcional</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-blue-900 mb-3">📝 Cómo configurar las credenciales</h3>
                <ol class="list-decimal list-inside space-y-2 text-blue-900">
                    <li>Ir a <a href="https://www.virtualpos.cl" target="_blank" class="underline">www.virtualpos.cl</a> e iniciar sesión</li>
                    <li>Navegar a: <strong>Perfil → Configuración de cuenta VirtualPOS → Integración → API REST</strong></li>
                    <li>Copiar las credenciales (API Key, Secret Key, Merchant Code)</li>
                    <li>Agregarlas al archivo <code class="bg-blue-100 px-2 py-1 rounded">.env</code>:</li>
                </ol>
                <div class="mt-4 bg-blue-900 text-blue-100 p-4 rounded font-mono text-sm">
                    <div>VIRTUALPOS_API_URL=https://api.virtualpos.cl/v3</div>
                    <div>VIRTUALPOS_API_KEY=tu_api_key_aqui</div>
                    <div>VIRTUALPOS_SECRET_KEY=tu_secret_key_aqui</div>
                    <div>VIRTUALPOS_MERCHANT_CODE=tu_merchant_code_aqui</div>
                </div>
            </div>

            <!-- Test Card -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-green-900 mb-3">💳 Tarjeta de Prueba</h3>
                <div class="space-y-2 text-green-900">
                    <p><strong>Número:</strong> <code class="bg-green-100 px-2 py-1 rounded">4051885600446623</code></p>
                    <p><strong>CVV:</strong> <code class="bg-green-100 px-2 py-1 rounded">123</code></p>
                    <p><strong>Fecha de expiración:</strong> Cualquier fecha válida futura</p>
                    <p class="text-sm mt-3">Esta tarjeta genera transacciones aprobadas para pruebas</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-8 flex gap-4">
                <a href="{{ route('test.subscriptions.create') }}"
                   class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Crear Suscripción de Prueba
                </a>
                <a href="{{ route('test.subscriptions.index') }}"
                   class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                    Ver Suscripciones
                </a>
            </div>
        </div>
    </div>
</body>
</html>
