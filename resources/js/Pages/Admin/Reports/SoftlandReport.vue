<template>
    <AdminLayout>
        <Head title="Plantilla Softland" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">
                                    Plantilla Softland
                                </h2>
                                <p class="text-gray-600 mt-2">
                                    Genera la plantilla Excel con la estructura requerida para importar movimientos contables en Softland
                                </p>
                            </div>
                            <Link
                                :href="route('admin.reports.index')"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg flex items-center"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Volver
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Información de la Plantilla -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Información de la Plantilla</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                <div class="flex items-center mb-3">
                                    <div class="p-2 bg-blue-100 rounded-lg mr-3">
                                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <h4 class="text-lg font-semibold text-blue-800">Estructura</h4>
                                </div>
                                <ul class="text-sm text-blue-700 space-y-1">
                                    <li>• 91 columnas de encabezado</li>
                                    <li>• Formato compatible con Softland</li>
                                    <li>• Estructura base sin datos</li>
                                    <li>• Lista para completar e importar</li>
                                </ul>
                            </div>

                            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                <div class="flex items-center mb-3">
                                    <div class="p-2 bg-green-100 rounded-lg mr-3">
                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <h4 class="text-lg font-semibold text-green-800">Uso</h4>
                                </div>
                                <ul class="text-sm text-green-700 space-y-1">
                                    <li>• Descargar plantilla</li>
                                    <li>• Completar con datos contables</li>
                                    <li>• Importar en Softland</li>
                                    <li>• Validar movimientos</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columnas Principales -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Columnas Principales</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-semibold text-gray-800 mb-2">Información Básica</h4>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    <li>• Código Plan de Cuenta</li>
                                    <li>• Monto al Debe/Haber</li>
                                    <li>• Descripción Movimiento</li>
                                    <li>• Equivalencia Moneda</li>
                                </ul>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-semibold text-gray-800 mb-2">Documentación</h4>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    <li>• Tipo Documento</li>
                                    <li>• Número Documento</li>
                                    <li>• Fecha Emisión/Vencimiento</li>
                                    <li>• Código Auxiliar</li>
                                </ul>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-semibold text-gray-800 mb-2">Detalles Adicionales</h4>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    <li>• Centro de Costo</li>
                                    <li>• Concepto de Caja</li>
                                    <li>• Flujos de Efectivo</li>
                                    <li>• Montos Detalle Libro</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros de Exportación -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Filtros de Exportación</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                                <input
                                    v-model="filters.dateFrom"
                                    type="date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                                <input
                                    v-model="filters.dateTo"
                                    type="date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Programa</label>
                                <select
                                    v-model="filters.programId"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="">Todos los programas</option>
                                    <option v-for="program in programs" :key="program.id" :value="program.id">
                                        {{ program.code }} - {{ program.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="text-center">
                            <button
                                @click="generateTemplate"
                                :disabled="isGenerating"
                                class="bg-indigo-500 hover:bg-indigo-700 disabled:bg-indigo-300 text-white font-bold py-3 px-6 rounded-lg flex items-center mx-auto transition-colors"
                            >
                                <svg v-if="!isGenerating" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <svg v-else class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                {{ isGenerating ? 'Generando...' : 'Descargar Movimientos Contables' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Notas Importantes -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                    <div class="flex items-start">
                        <div class="p-2 bg-yellow-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-yellow-800 mb-2">Notas Importantes</h4>
                            <ul class="text-sm text-yellow-700 space-y-1">
                                <li>• La plantilla contiene solo la estructura de columnas, sin datos</li>
                                <li>• Para boletas de honorarios, usar Monto 1 (Bruto), Monto 2 (Retención), Monto Suma (Neto)</li>
                                <li>• Verificar que los códigos de plan de cuenta coincidan con Softland</li>
                                <li>• Las fechas deben estar en formato DD/MM/AAAA</li>
                                <li>• Validar la estructura antes de importar en el sistema contable</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

const props = defineProps({
    programs: {
        type: Array,
        default: () => [],
    },
});

const isGenerating = ref(false);
const filters = reactive({
    dateFrom: '',
    dateTo: '',
    programId: '',
});

onMounted(() => {
    // Establecer fechas por defecto (último mes)
    const today = new Date();
    const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
    
    filters.dateTo = today.toISOString().split('T')[0];
    filters.dateFrom = lastMonth.toISOString().split('T')[0];
});

const generateTemplate = async () => {
    try {
        isGenerating.value = true;
        
        // Construir URL con filtros
        const params = new URLSearchParams();
        if (filters.dateFrom) params.append('dateFrom', filters.dateFrom);
        if (filters.dateTo) params.append('dateTo', filters.dateTo);
        if (filters.programId) params.append('programId', filters.programId);
        
        const url = `${route('admin.reports.export.softland-template')}?${params.toString()}`;
        
        // Abrir la URL de descarga en una nueva pestaña
        window.open(url, '_blank');
        
        // Simular un pequeño delay para mostrar el estado de carga
        setTimeout(() => {
            isGenerating.value = false;
        }, 1000);
        
    } catch (error) {
        console.error('Error al generar la plantilla:', error);
        isGenerating.value = false;
    }
};
</script>
