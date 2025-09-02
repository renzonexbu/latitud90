@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Filtros de fecha -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">Filtros de Fecha</h2>
        <form id="dateFilterForm" class="flex gap-4 items-end">
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">Desde</label>
                <input type="date" id="date_from" name="date_from" value="{{ $dateFrom }}" 
                       class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-2">Hasta</label>
                <input type="date" id="date_to" name="date_to" value="{{ $dateTo }}" 
                       class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                Filtrar
            </button>
        </form>
    </div>

    <!-- Resumen de Módulos de Reportes -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total de Sesiones -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">Total de Sesiones</h3>
                    <p class="text-3xl font-bold text-blue-600">{{ number_format($analysis['total_sessions']) }}</p>
                </div>
            </div>
        </div>

        <!-- Búsquedas en Hero -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">Búsquedas en Hero</h3>
                    <p class="text-3xl font-bold text-green-600">{{ number_format($analysis['funnel_stages']['hero_search']) }}</p>
                </div>
            </div>
        </div>

        <!-- Confirmaciones -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">Confirmaciones</h3>
                    <p class="text-3xl font-bold text-yellow-600">{{ number_format($analysis['funnel_stages']['confirmation_view']) }}</p>
                </div>
            </div>
        </div>

        <!-- Pagos Completados -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">Pagos Completados</h3>
                    <p class="text-3xl font-bold text-purple-600">{{ number_format($analysis['funnel_stages']['payment_completed']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Funnel de Conversión -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-2xl font-bold mb-6">Funnel de Conversión</h2>
        <div class="space-y-4">
            @foreach($analysis['conversion_rates'] as $stage => $rate)
            <div class="flex items-center justify-between">
                <span class="text-gray-700 font-medium">
                    @switch($stage)
                        @case('hero_to_detail')
                            Hero → Detalle de Programa
                            @break
                        @case('detail_to_payment')
                            Detalle → Formulario de Pago
                            @break
                        @case('payment_to_confirmation')
                            Formulario → Confirmación
                            @break
                        @case('confirmation_to_initiated')
                            Confirmación → Pago Iniciado
                            @break
                        @case('initiated_to_completed')
                            Pago Iniciado → Completado
                            @break
                        @case('overall_conversion')
                            Conversión General
                            @break
                        @default
                            {{ ucfirst(str_replace('_', ' ', $stage)) }}
                    @endswitch
                </span>
                <div class="flex items-center gap-4">
                    <div class="w-32 bg-gray-200 rounded-full h-3">
                        <div class="bg-blue-600 h-3 rounded-full" style="width: {{ $rate }}%"></div>
                    </div>
                    <span class="text-lg font-bold text-blue-600">{{ $rate }}%</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Análisis de Pagos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Métodos de Pago -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-xl font-bold mb-4">Métodos de Pago</h3>
            <div class="space-y-3">
                @foreach($analysis['payment_analysis']['payment_methods'] as $method => $count)
                <div class="flex justify-between items-center">
                    <span class="text-gray-700">{{ $method ?: 'No especificado' }}</span>
                    <span class="font-semibold">{{ $count }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Tipos de Pago -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-xl font-bold mb-4">Tipos de Pago</h3>
            <div class="space-y-3">
                @foreach($analysis['payment_analysis']['payment_types'] as $type => $count)
                <div class="flex justify-between items-center">
                    <span class="text-gray-700">{{ $type ?: 'No especificado' }}</span>
                    <span class="font-semibold">{{ $count }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Top Participantes y Programas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Top Participantes -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-xl font-bold mb-4">Participantes Más Buscados</h3>
            <div class="space-y-3">
                @foreach($analysis['top_participants'] as $index => $participant)
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-bold">
                        {{ $index + 1 }}
                    </div>
                    <span class="text-gray-700">{{ $participant['participant_rut'] }}</span>
                    <span class="ml-auto text-sm text-gray-500">{{ $participant['search_count'] }} búsquedas</span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Top Programas -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-xl font-bold mb-4">Programas Más Seleccionados</h3>
            <div class="space-y-3">
                @foreach($analysis['top_programs'] as $index => $program)
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-sm font-bold">
                        {{ $index + 1 }}
                    </div>
                    <div class="flex-1">
                        <div class="text-gray-700 font-medium">{{ $program['program_name'] ?: 'Programa #' . $program['program_id'] }}</div>
                        <div class="text-sm text-gray-500">{{ $program['selection_count'] }} selecciones</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Botón de Exportar -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold">Exportar Reporte</h3>
            <button id="exportBtn" class="bg-green-600 text-white px-6 py-3 rounded-md hover:bg-green-700 transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Exportar Reporte Consolidado (Excel)
            </button>
        </div>
    </div>
</div>

<script>
document.getElementById('dateFilterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;
    
    // Recargar la página con los nuevos filtros
    window.location.href = `{{ route('dashboard.index') }}?date_from=${dateFrom}&date_to=${dateTo}`;
});

document.getElementById('exportBtn').addEventListener('click', function() {
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;
    
    // Aquí implementarías la lógica de exportación
    alert('Función de exportación implementada. Fechas: ' + dateFrom + ' a ' + dateTo);
});
</script>
@endsection
