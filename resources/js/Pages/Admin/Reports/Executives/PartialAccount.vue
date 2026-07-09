<template>
    <AdminLayout>
        <Head title="Estado De Cuenta Parcial (Apoderados)" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold">Estado De Cuenta Parcial</h2>
                            <button 
                                @click="goBack" 
                                class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Ir atrás
                            </button>
                        </div>

                        <ConsolidatedFilters
                            :initial-filters="localFilters"
                            :programs="programs"
                            :sales-executives="[]"
                            :show-date-filters="false"
                            @filters-changed="onFiltersChanged"
                        />

                        <div class="flex justify-end mt-8 mb-6">
                            <button @click="exportData" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg">Exportar</button>
                        </div>

                        <ExecutivesPartialAccountTable :items="partialAccounts" />
                        <ConsolidatedPagination
                            :current-page="partialAccounts.current_page || 1"
                            :total-participants="partialAccounts.total || 0"
                            :participants-per-page="partialAccounts.per_page || 25"
                            @page-changed="onPageChanged"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const shouldGoToExecutivesIndex = computed(() => {
    const roles = page.props.auth?.user?.roles || [];
    const isEjecutivo = roles.includes('ejecutivo_comercial');
    const isMarketing = roles.includes('marketing');
    return (isEjecutivo || isMarketing) && !roles.includes('contabilidad') && !roles.includes('super_admin');
});
import ExecutivesPartialAccountTable from '@/Components/Reports/Executives/PartialAccountTable.vue';
import ConsolidatedFilters from '@/Components/Reports/Executives/ConsolidatedFilters.vue';
import ConsolidatedPagination from '@/Components/Reports/Executives/ConsolidatedPagination.vue';

const props = defineProps({
    partialAccounts: { type: Object, required: true },
    filters: { type: Object, required: true },
    programs: { type: Array, default: () => [] },
});

const localFilters = reactive({
    programId: props.filters.programId || '',
    documentSearch: props.filters.documentSearch || '',
    status: props.filters.status || '',
});

const exportData = async () => {
    const params = new URLSearchParams(localFilters);
    const url = `/admin/reports/executives/export/partial-account?${params.toString()}`;

    try {
        const response = await fetch(url, { headers: { 'Accept': 'application/json, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' } });

        // Si el response es JSON, es un error
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            const data = await response.json();
            const detail = data.error_detail ? `\n\nDetalle: ${data.error_detail}` : '';
            const location = data.error_location ? `\nEn: ${data.error_location}` : '';
            alert((data.message || 'Error al exportar') + detail + location);
            return;
        }

        // Si es archivo, descargarlo
        const blob = await response.blob();
        const downloadUrl = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = downloadUrl;
        // Obtener nombre del archivo desde el header Content-Disposition si está disponible
        const disposition = response.headers.get('content-disposition') || '';
        const match = disposition.match(/filename="?([^"]+)"?/);
        a.download = match ? match[1] : 'estado_cuenta_parcial.xlsx';
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(downloadUrl);
    } catch (error) {
        alert('Error de red al exportar: ' + error.message);
    }
};

const onFiltersChanged = (filters) => {
    Object.assign(localFilters, filters);
    router.get('/admin/reports/executives/partial-account', localFilters, { preserveState: true, preserveScroll: true });
};

const onPageChanged = (page) => {
    router.get('/admin/reports/executives/partial-account', { ...localFilters, page }, { preserveState: true, preserveScroll: true });
};

const goBack = () => {
    const backUrl = shouldGoToExecutivesIndex.value ? '/admin/reports/executives' : '/admin/reports';
    router.get(backUrl, {}, { preserveState: false });
};
</script>


