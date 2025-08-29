<template>
    <AdminLayout>
        <Head title="Estado De Cuenta Parcial (Apoderados)" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h2 class="text-2xl font-bold mb-6">Estado De Cuenta Parcial</h2>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                                <input v-model="localFilters.dateFrom" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"/>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                                <input v-model="localFilters.dateTo" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"/>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Programa</label>
                                <select v-model="localFilters.programId" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Todos</option>
                                    <option v-for="p in programs" :key="p.id" :value="p.id">{{ p.code }} - {{ p.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Apoderado</label>
                                <input v-model="localFilters.guardianQuery" type="text" placeholder="RUT o Nombre" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"/>
                            </div>
                        </div>

                        <div class="flex justify-between items-center mb-4">
                            <div class="space-x-2">
                                <button @click="applyFilters" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Aplicar Filtros</button>
                                <button @click="exportData" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg">Exportar</button>
                            </div>
                        </div>

                        <ExecutivesPartialAccountTable :items="partialAccounts" />
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

const props = defineProps({
    partialAccounts: { type: Object, required: true },
    filters: { type: Object, required: true },
    programs: { type: Array, default: () => [] },
});

const localFilters = reactive({
    dateFrom: props.filters.dateFrom || '',
    dateTo: props.filters.dateTo || '',
    programId: props.filters.programId || '',
    guardianQuery: props.filters.guardianQuery || '',
});

const applyFilters = () => {
    router.get('/admin/reports/executives/partial-account', localFilters, { preserveState: true, preserveScroll: true });
};

const exportData = () => {
    const params = new URLSearchParams(localFilters);
    window.open(`/admin/reports/executives/export/partial-account?${params.toString()}`, '_blank');
};
</script>


