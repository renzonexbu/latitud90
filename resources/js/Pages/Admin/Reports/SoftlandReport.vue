<template>
    <AdminLayout>
        <Head title="Softland" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">
                                    Softland
                                </h2>
                            </div>
                            <Link
                                :href="route('admin.reports.index')"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg flex items-center"
                            >
                                <svg
                                    class="w-4 h-4 mr-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18"
                                    ></path>
                                </svg>
                                Volver
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Módulos de Exportación -->
                <div class="grid grid-cols-1 gap-6">
                    <!-- Documentos -->
                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                    >
                        <div class="p-6 text-gray-900">
                            <div class="flex items-center mb-4">
                                <div class="p-2 bg-green-100 rounded-lg mr-3">
                                    <svg
                                        class="w-6 h-6 text-green-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        ></path>
                                    </svg>
                                </div>
                                <h3
                                    class="text-lg font-semibold text-green-800"
                                >
                                    Documentos
                                </h3>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">
                                Generar archivo ZIP con auxiliares y movimientos
                                contables para importar en Softland
                            </p>

                            <!-- Filtros para Documentos -->
                            <div class="space-y-3 mb-4">
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-700 mb-1"
                                        >Fecha Inicio</label
                                    >
                                    <input
                                        v-model="filters.dateFrom"
                                        type="date"
                                        class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-green-500"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-700 mb-1"
                                        >Fecha Fin</label
                                    >
                                    <input
                                        v-model="filters.dateTo"
                                        type="date"
                                        class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-green-500"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-700 mb-1"
                                        >Programa</label
                                    >
                                    <select
                                        v-model="filters.programId"
                                        class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-green-500"
                                    >
                                        <option value="">
                                            Todos los programas
                                        </option>
                                        <option
                                            v-for="program in programs"
                                            :key="program.id"
                                            :value="program.id"
                                        >
                                            {{ program.code }} -
                                            {{ program.name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-700 mb-1"
                                        >Formato</label
                                    >
                                    <select
                                        v-model="documentosFormat"
                                        class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-green-500"
                                    >
                                        <option value="excel">
                                            Excel (.xlsx)
                                        </option>
                                        <option value="csv">CSV (.csv)</option>
                                    </select>
                                </div>
                            </div>

                            <button
                                @click="generateTemplate"
                                :disabled="isGenerating"
                                class="w-full bg-green-500 hover:bg-green-700 disabled:bg-green-300 text-white font-bold py-2 px-4 rounded-lg flex items-center justify-center transition-colors"
                            >
                                <svg
                                    v-if="!isGenerating"
                                    class="w-4 h-4 mr-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    ></path>
                                </svg>
                                <svg
                                    v-else
                                    class="w-4 h-4 mr-2 animate-spin"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                    ></path>
                                </svg>
                                {{
                                    isGenerating
                                        ? "Generando..."
                                        : "Descargar Ambos Archivos"
                                }}
                            </button>
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

// Estados de carga para cada módulo
const isGenerating = ref(false);
const isGeneratingCentralizacion = ref(false);

// Formatos seleccionados para cada módulo
const documentosFormat = ref("excel");
const centralizacionFormat = ref("excel");

// Filtros para el módulo de Documentos
const filters = reactive({
    dateFrom: "",
    dateTo: "",
    programId: "",
});

onMounted(() => {
    // Establecer fechas por defecto (último mes)
    const today = new Date();
    const lastMonth = new Date(
        today.getFullYear(),
        today.getMonth() - 1,
        today.getDate()
    );

    filters.dateTo = today.toISOString().split("T")[0];
    filters.dateFrom = lastMonth.toISOString().split("T")[0];
});

// Función para exportar ambos archivos en un ZIP
const generateTemplate = async () => {
    try {
        isGenerating.value = true;

        // Construir URL con filtros y formato
        const params = new URLSearchParams();
        if (filters.dateFrom) params.append("dateFrom", filters.dateFrom);
        if (filters.dateTo) params.append("dateTo", filters.dateTo);
        if (filters.programId) params.append("programId", filters.programId);
        params.append("format", documentosFormat.value);

        const url = `${route(
            "admin.reports.export.softland-zip"
        )}?${params.toString()}`;

        // Abrir la URL de descarga en una nueva pestaña
        window.open(url, "_blank");

        // Simular un pequeño delay para mostrar el estado de carga
        setTimeout(() => {
            isGenerating.value = false;
        }, 2000);
    } catch (error) {
        console.error("Error al generar el archivo ZIP:", error);
        isGenerating.value = false;
    }
};

// Función para el módulo de Centralización (placeholder)
const exportCentralizacion = async () => {
    try {
        isGeneratingCentralizacion.value = true;

        // TODO: Implementar lógica de exportación de centralización
        // Por ahora mostrar el formato seleccionado
        alert(
            `Módulo de Centralización documentos - En desarrollo\nFormato seleccionado: ${centralizacionFormat.value.toUpperCase()}`
        );

        setTimeout(() => {
            isGeneratingCentralizacion.value = false;
        }, 1000);
    } catch (error) {
        console.error("Error al exportar centralización:", error);
        isGeneratingCentralizacion.value = false;
    }
};
</script>
