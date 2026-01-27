<template>
    <AdminLayout>
        <Head title="Programas por Medio de Pago" />

        <div class="py-6 px-4 lg:px-6">
            <div class="bg-white shadow-sm rounded-lg">
                <div class="p-4">
                    <!-- Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <h2 class="text-xl font-bold text-[#1c4f4a]">Programas por Medio de Pago</h2>
                        <button
                            @click="goBack"
                            class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg flex items-center space-x-2 self-start sm:self-auto"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            <span>Volver</span>
                        </button>
                    </div>

                    <!-- Filtros -->
                    <PaymentOptionsProgramFilters
                        :initial-filters="filters"
                        :payment-options="paymentOptions"
                        @filters-changed="applyFilters"
                    />

                    <!-- Resumen y Exportar -->
                    <div class="mt-6 mb-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <button
                            @click="exportData"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg self-start flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Exportar Excel
                        </button>
                        <div class="grid grid-cols-3 gap-2">
                            <div class="bg-blue-50 p-2 rounded border border-blue-200">
                                <p class="text-[10px] text-gray-500">Total Programas</p>
                                <p class="text-sm font-bold text-blue-700">{{ summary.total_programs || 0 }}</p>
                            </div>
                            <div class="bg-green-50 p-2 rounded border border-green-200">
                                <p class="text-[10px] text-gray-500">Activos</p>
                                <p class="text-sm font-bold text-green-700">{{ summary.active_programs || 0 }}</p>
                            </div>
                            <div class="bg-red-50 p-2 rounded border border-red-200">
                                <p class="text-[10px] text-gray-500">Inactivos</p>
                                <p class="text-sm font-bold text-red-700">{{ summary.inactive_programs || 0 }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla -->
                    <PaymentOptionsProgramTable :programs="programs" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaymentOptionsProgramFilters from '@/Components/Courses/PaymentOptionsProgramFilters.vue';
import PaymentOptionsProgramTable from '@/Components/Courses/PaymentOptionsProgramTable.vue';

const props = defineProps({
    programs: { type: Array, default: () => [] },
    paymentOptions: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    summary: { type: Object, default: () => ({}) },
});

const applyFilters = (filters) => {
    router.get('/admin/courses/payment-options-programs', filters, {
        preserveState: true,
        preserveScroll: true
    });
};

const exportData = () => {
    const params = new URLSearchParams(props.filters);
    window.open(`/admin/courses/payment-options-programs/export?${params.toString()}`, '_blank');
};

const goBack = () => {
    router.get('/admin/courses', {}, { preserveState: false });
};
</script>
