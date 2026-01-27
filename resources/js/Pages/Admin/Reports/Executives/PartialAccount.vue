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
import { reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ExecutivesPartialAccountTable from '@/Components/Reports/Executives/PartialAccountTable.vue';
import ConsolidatedFilters from '@/Components/Reports/Executives/ConsolidatedFilters.vue';
import ConsolidatedPagination from '@/Components/Reports/Executives/ConsolidatedPagination.vue';

const props = defineProps({
    partialAccounts: { type: Object, required: true },
    filters: { type: Object, required: true },
    programs: { type: Array, default: () => [] },
});

const localFilters = reactive({
    dateFrom: props.filters.dateFrom || '',
    dateTo: props.filters.dateTo || '',
    programId: props.filters.programId || '',
});

const exportData = () => {
    const params = new URLSearchParams(localFilters);
    window.open(`/admin/reports/executives/export/partial-account?${params.toString()}`, '_blank');
};

const onFiltersChanged = (filters) => {
    Object.assign(localFilters, filters);
    router.get('/admin/reports/executives/partial-account', localFilters, { preserveState: true, preserveScroll: true });
};

const onPageChanged = (page) => {
    router.get('/admin/reports/executives/partial-account', { ...localFilters, page }, { preserveState: true, preserveScroll: true });
};

const goBack = () => {
    router.get('/admin/reports/executives');
};
</script>


