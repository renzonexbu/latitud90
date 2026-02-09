<template>
    <AdminLayout>
        <Head title="Consolidado Área Ingresos (Apoderados)" />

        <div class="py-6 px-4 lg:px-6">
            <div class="bg-white shadow-sm rounded-lg">
                <div class="p-4">
                    <!-- Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <h2 class="text-xl font-bold">Consolidado Área Ingresos</h2>
                        <button
                            @click="goBack"
                            class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg flex items-center space-x-2 self-start sm:self-auto"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            <span>Volver Atrás</span>
                        </button>
                    </div>

                    <!-- Filtros -->
                    <ExecutivesConsolidatedFilters
                        :initial-filters="filters"
                        :programs="programs"
                        :sales-executives="salesExecutives"
                        @filters-changed="applyFilters"
                    />

                    <!-- Resumen y Exportar -->
                    <div class="mt-6 mb-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <button @click="exportData" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg self-start">
                            Exportar
                        </button>
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                            <div class="bg-blue-50 p-2 rounded border border-blue-200">
                                <p class="text-[10px] text-gray-500">Participantes</p>
                                <p class="text-sm font-bold text-blue-700">{{ summary.totalParticipants || 0 }}</p>
                            </div>
                            <div class="bg-indigo-50 p-2 rounded border border-indigo-200">
                                <p class="text-[10px] text-gray-500">Pagos</p>
                                <p class="text-sm font-bold text-indigo-700">{{ summary.totalPayments || 0 }}</p>
                            </div>
                            <div class="bg-green-50 p-2 rounded border border-green-200">
                                <p class="text-[10px] text-gray-500">Total Liberado</p>
                                <p class="text-sm font-bold text-green-700">${{ (summary.totalLiberated || 0).toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</p>
                            </div>
                            <div class="bg-purple-50 p-2 rounded border border-purple-200">
                                <p class="text-[10px] text-gray-500">Monto Total</p>
                                <p class="text-sm font-bold text-purple-700">${{ (summary.totalAmount || 0).toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla -->
                    <ExecutivesConsolidatedTable :items="consolidated" />

                    <!-- Paginación -->
                    <ExecutivesConsolidatedPagination
                        :current-page="consolidated.current_page"
                        :total-participants="consolidated.total"
                        :participants-per-page="25"
                        @page-changed="goToPage"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ExecutivesConsolidatedTable from '@/Components/Reports/Executives/ConsolidatedTable.vue';
import ExecutivesConsolidatedFilters from '@/Components/Reports/Executives/ConsolidatedFilters.vue';
import ExecutivesConsolidatedPagination from '@/Components/Reports/Executives/ConsolidatedPagination.vue';

const page = usePage();
const shouldGoToExecutivesIndex = computed(() => {
    const roles = page.props.auth?.user?.roles || [];
    const isEjecutivo = roles.includes('ejecutivo_comercial');
    const isMarketing = roles.includes('marketing');
    return (isEjecutivo || isMarketing) && !roles.includes('contabilidad') && !roles.includes('super_admin');
});

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
    const backUrl = shouldGoToExecutivesIndex.value ? '/admin/reports/executives' : '/admin/reports';
    router.get(backUrl, {}, { preserveState: false });
};
</script>


