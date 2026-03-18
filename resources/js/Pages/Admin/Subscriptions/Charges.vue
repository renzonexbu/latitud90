<template>
    <AdminLayout>
        <Head title="Cuotas de Suscripciones" />

        <div class="bg-white min-h-screen">
            <!-- Header -->
            <div class="px-8 py-6 border-b border-gray-200">
                <h1 class="text-2xl font-bold text-gray-900">Cuotas de Suscripciones</h1>
                <p class="text-sm text-gray-500 mt-1">Visualización de todas las cuotas y sus estados reales en VirtualPos</p>
            </div>

            <!-- Tabs -->
            <div class="px-8 pt-4 border-b border-gray-200">
                <div class="flex gap-6">
                    <a
                        href="/admin/subscriptions"
                        class="pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent"
                    >
                        Suscripciones
                    </a>
                    <span class="pb-3 text-sm font-medium text-turquesa border-b-2 border-turquesa">
                        Cuotas
                    </span>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="px-8 py-6 bg-gray-50">
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                    <div class="bg-white p-3 rounded-lg shadow-sm text-center cursor-pointer hover:ring-2 hover:ring-blue-300"
                         :class="{ 'ring-2 ring-blue-500': localFilters.charge_status === 'all' }"
                         @click="filterByStatus('all')">
                        <p class="text-xs text-gray-500">Total</p>
                        <p class="text-lg font-bold text-blue-600">{{ stats.total }}</p>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm text-center cursor-pointer hover:ring-2 hover:ring-green-300"
                         :class="{ 'ring-2 ring-green-500': localFilters.charge_status === 'pagado' }"
                         @click="filterByStatus('pagado')">
                        <p class="text-xs text-gray-500">Pagadas</p>
                        <p class="text-lg font-bold text-green-600">{{ stats.pagado }}</p>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm text-center cursor-pointer hover:ring-2 hover:ring-yellow-300"
                         :class="{ 'ring-2 ring-yellow-500': localFilters.charge_status === 'pendiente' }"
                         @click="filterByStatus('pendiente')">
                        <p class="text-xs text-gray-500">Pendientes</p>
                        <p class="text-lg font-bold text-yellow-600">{{ stats.pendiente }}</p>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm text-center cursor-pointer hover:ring-2 hover:ring-red-300"
                         :class="{ 'ring-2 ring-red-500': localFilters.charge_status === 'rechazado' }"
                         @click="filterByStatus('rechazado')">
                        <p class="text-xs text-gray-500">Rechazadas</p>
                        <p class="text-lg font-bold text-red-600">{{ stats.rechazado }}</p>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm text-center cursor-pointer hover:ring-2 hover:ring-orange-300"
                         :class="{ 'ring-2 ring-orange-500': localFilters.charge_status === 'reintentando' }"
                         @click="filterByStatus('reintentando')">
                        <p class="text-xs text-gray-500">Reintentando</p>
                        <p class="text-lg font-bold text-orange-600">{{ stats.reintentando }}</p>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm text-center cursor-pointer hover:ring-2 hover:ring-gray-300"
                         :class="{ 'ring-2 ring-gray-500': localFilters.charge_status === 'cancelado' }"
                         @click="filterByStatus('cancelado')">
                        <p class="text-xs text-gray-500">Canceladas</p>
                        <p class="text-lg font-bold text-gray-600">{{ stats.cancelado }}</p>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm text-center cursor-pointer hover:ring-2 hover:ring-blue-300"
                         :class="{ 'ring-2 ring-blue-500': localFilters.charge_status === 'procesando' }"
                         @click="filterByStatus('procesando')">
                        <p class="text-xs text-gray-500">Procesando</p>
                        <p class="text-lg font-bold text-blue-500">{{ stats.procesando }}</p>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="px-8 py-4">
                <div class="flex flex-wrap gap-4 items-center">
                    <!-- Búsqueda -->
                    <div class="relative w-[280px]">
                        <input
                            v-model="localFilters.search"
                            type="text"
                            placeholder="Buscar participante, ID, código..."
                            class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-[12px] font-normal outline-none pr-12"
                            @input="performSearch"
                        />
                        <button
                            class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-[#007e93] rounded-full w-[30px] h-[30px] flex items-center justify-center"
                            @click="performSearch"
                        >
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                                <path d="M21 21L16.514 16.506L21 21ZM19 10.5C19 15.194 15.194 19 10.5 19C5.806 19 2 15.194 2 10.5C2 5.806 5.806 2 10.5 2C15.194 2 19 5.806 19 10.5Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Estado de cuota -->
                    <select
                        v-model="localFilters.charge_status"
                        class="h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-[12px] font-normal outline-none appearance-none pr-8 w-[180px]"
                        @change="performSearch"
                    >
                        <option value="all">Todos los estados</option>
                        <option value="pagado">Pagado</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="rechazado">Rechazado</option>
                        <option value="reintentando">Reintentando</option>
                        <option value="cancelado">Cancelado</option>
                        <option value="procesando">Procesando</option>
                    </select>

                    <!-- Estado de suscripción -->
                    <select
                        v-model="localFilters.subscription_status"
                        class="h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-[12px] font-normal outline-none appearance-none pr-8 w-[200px]"
                        @change="performSearch"
                    >
                        <option value="all">Todas las suscripciones</option>
                        <option value="ACTIVA">Activa</option>
                        <option value="SUSCRIBIENDO">Suscribiendo</option>
                        <option value="SUSCRIPCION_FALLIDA">Suscripción Fallida</option>
                        <option value="CANCELADA">Cancelada</option>
                    </select>

                    <!-- Limpiar -->
                    <button
                        @click="clearFilters"
                        class="h-[46px] px-6 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-[50px] border border-gray-300 text-[12px] font-normal transition-colors flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Limpiar Filtros
                    </button>
                </div>
            </div>

            <!-- Tabla -->
            <div class="px-8 py-6">
                <div class="overflow-x-auto bg-white rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sub ID</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cód. Inscripción</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Suscriptor</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cuota</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Cobro</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado Cuota</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado Sub.</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Charge</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="charge in charges.data"
                                :key="charge.charge_id + '-' + charge.subscription_id"
                                class="hover:bg-gray-50 cursor-pointer"
                                @click="goToSubscription(charge.subscription_id)"
                            >
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-blue-600">
                                    #{{ charge.subscription_id }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 font-mono">
                                    {{ charge.enrollment_code || '-' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                    {{ charge.subscriber_name }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 font-medium">
                                    {{ charge.installment_number }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                    ${{ formatAmount(charge.amount) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    {{ charge.charge_date || '-' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getChargeStatusClass(charge.status)]">
                                        {{ getChargeStatusLabel(charge.status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getSubStatusClass(charge.subscription_status)]">
                                        {{ getSubStatusLabel(charge.subscription_status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-400 font-mono">
                                    {{ charge.charge_id ? charge.charge_id.substring(0, 16) + '...' : '-' }}
                                </td>
                            </tr>
                            <tr v-if="charges.data.length === 0">
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                    No se encontraron cuotas con los filtros seleccionados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="mt-6">
                    <Pagination :links="charges.links" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Pagination from "@/Components/Pagination.vue";
import _ from "lodash";

const props = defineProps({
    charges: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const localFilters = ref({
    charge_status: props.filters.charge_status || "all",
    search: props.filters.search || "",
    subscription_status: props.filters.subscription_status || "all",
});

const performSearch = _.debounce(() => {
    router.get("/admin/subscriptions/charges", { ...localFilters.value, page: 1 }, {
        preserveState: true,
        preserveScroll: true,
    });
}, 300);

const filterByStatus = (status) => {
    localFilters.value.charge_status = status;
    performSearch();
};

const clearFilters = () => {
    localFilters.value = {
        charge_status: "all",
        search: "",
        subscription_status: "all",
    };
    performSearch();
};

const goToSubscription = (id) => {
    router.visit(`/admin/subscriptions/${id}`);
};

const formatAmount = (amount) => {
    return new Intl.NumberFormat("es-CL").format(amount);
};

const getChargeStatusLabel = (status) => {
    const labels = {
        pagado: "Pagado",
        pendiente: "Pendiente",
        rechazado: "Rechazado",
        reintentando: "Reintentando",
        cancelado: "Cancelado",
        procesando: "Procesando",
    };
    return labels[status] || status || "Desconocido";
};

const getChargeStatusClass = (status) => {
    const classes = {
        pagado: "bg-green-100 text-green-800",
        pendiente: "bg-yellow-100 text-yellow-800",
        rechazado: "bg-red-100 text-red-800",
        reintentando: "bg-orange-100 text-orange-800",
        cancelado: "bg-gray-100 text-gray-800",
        procesando: "bg-blue-100 text-blue-800",
    };
    return classes[status] || "bg-gray-100 text-gray-800";
};

const getSubStatusLabel = (status) => {
    const labels = {
        ACTIVA: "Activa",
        SUSCRIBIENDO: "Suscribiendo",
        SUSCRIPCION_FALLIDA: "Fallida",
        CANCELADA: "Cancelada",
        FINALIZADA: "Finalizada",
    };
    return labels[status] || status;
};

const getSubStatusClass = (status) => {
    const classes = {
        ACTIVA: "bg-green-100 text-green-800",
        SUSCRIBIENDO: "bg-yellow-100 text-yellow-800",
        SUSCRIPCION_FALLIDA: "bg-red-100 text-red-800",
        CANCELADA: "bg-gray-100 text-gray-800",
        FINALIZADA: "bg-blue-100 text-blue-800",
    };
    return classes[status] || "bg-gray-100 text-gray-800";
};
</script>

<style scoped>
.border-turquesa {
    border-color: #007e93;
}
.text-turquesa {
    color: #007e93;
}
</style>
