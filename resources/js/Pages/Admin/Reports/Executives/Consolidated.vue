<template>
    <AdminLayout>
        <Head title="Consolidado Área Ingresos (Apoderados)" />

        <div class="py-12">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full">
                    <div class="p-4 text-gray-900 w-full">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-bold">Consolidado Área Ingresos</h2>
                            <button 
                                @click="goBack" 
                                class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg flex items-center space-x-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                <span>Volver Atrás</span>
                            </button>
                        </div>

                        <ExecutivesConsolidatedFilters 
                            :initial-filters="filters"
                            :programs="programs"
                            :sales-executives="salesExecutives"
                            @filters-changed="applyFilters"
                        />

                        <!-- Espacio entre filtros y tarjetas de resumen -->
                        <div class="mt-8 mb-6"></div>

                        <div class="flex justify-between items-center mb-4">
                            <div class="space-x-2">
                                <button @click="exportData" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg">Exportar</button>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="bg-blue-50 p-3 rounded border border-blue-200">
                                    <p class="text-xs text-gray-500">Participantes</p>
                                    <p class="text-lg font-bold text-blue-700">{{ summary.totalParticipants || 0 }}</p>
                                </div>
                                <div class="bg-indigo-50 p-3 rounded border border-indigo-200">
                                    <p class="text-xs text-gray-500">Pagos</p>
                                    <p class="text-lg font-bold text-indigo-700">{{ summary.totalPayments || 0 }}</p>
                                </div>
                                <div class="bg-green-50 p-3 rounded border border-green-200">
                                    <p class="text-xs text-gray-500">Total Liberado</p>
                                    <p class="text-lg font-bold text-green-700">${{ (summary.totalLiberated || 0).toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</p>
                                </div>
                                <div class="bg-purple-50 p-3 rounded border border-purple-200">
                                    <p class="text-xs text-gray-500">Monto Total</p>
                                    <p class="text-lg font-bold text-purple-700">${{ (summary.totalAmount || 0).toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</p>
                                </div>
                            </div>
                        </div>

                        <ExecutivesConsolidatedTable :items="consolidated" />
                        
                        <ExecutivesConsolidatedPagination 
                            :current-page="consolidated.current_page"
                            :total-participants="consolidated.total"
                            :participants-per-page="25"
                            @page-changed="goToPage"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ExecutivesConsolidatedTable from '@/Components/Reports/Executives/ConsolidatedTable.vue';
import ExecutivesConsolidatedFilters from '@/Components/Reports/Executives/ConsolidatedFilters.vue';
import ExecutivesConsolidatedPagination from '@/Components/Reports/Executives/ConsolidatedPagination.vue';

const props = defineProps({
    consolidated: { type: Object, required: true },
    filters: { type: Object, required: true },
    summary: { type: Object, required: true },
    programs: { type: Array, default: () => [] },
    salesExecutives: { type: Array, default: () => [] },
});

const applyFilters = (filters) => {
    router.get('/admin/reports/executives/consolidated', filters, { preserveState: true, preserveScroll: true });
};

const goToPage = (page) => {
    const currentFilters = { ...props.filters, page };
    router.get('/admin/reports/executives/consolidated', currentFilters, { preserveState: true, preserveScroll: true });
};

const exportData = () => {
    const params = new URLSearchParams(props.filters);
    window.open(`/admin/reports/executives/export/consolidated?${params.toString()}`, '_blank');
};

const goBack = () => {
    router.get('/admin/reports/executives', {}, { preserveState: false });
};
</script>


