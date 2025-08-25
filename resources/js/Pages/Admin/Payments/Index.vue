<template>
    <AdminLayout>
        <Head title="Gestión de Pagos" />

        <div class="bg-white min-h-screen">
            <!-- Header -->
            <PaymentsHeader
                subtitle="Visualización y administración de pagos"
                :show-create-button="true"
                @create-payment="handleCreatePayment"
                @create-refund="handleCreateRefund"
            />

            <!-- Estadísticas -->
            <div class="px-8 py-6 bg-gray-50">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <div class="flex items-center">
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
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">
                                    Ingresos Totales
                                </p>
                                <p class="text-xl font-bold text-green-600">
                                    ${{ formatPrice(stats.total_revenue) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg mr-3">
                                <svg
                                    class="w-6 h-6 text-blue-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Completados</p>
                                <p class="text-xl font-bold text-blue-600">
                                    {{ stats.completed }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg mr-3">
                                <svg
                                    class="w-6 h-6 text-yellow-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Pendientes</p>
                                <p class="text-xl font-bold text-yellow-600">
                                    {{ stats.pending }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <div class="p-2 bg-red-100 rounded-lg mr-3">
                                <svg
                                    class="w-6 h-6 text-red-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Fallidos</p>
                                <p class="text-xl font-bold text-red-600">
                                    {{ stats.failed }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="px-8 py-4">
                <PaymentsFilters
                    :initial-filters="filters"
                    @filters-changed="handleFiltersChanged"
                />
            </div>

            <!-- Tabla -->
            <div class="px-8 py-6">
                <PaymentsTable
                    :payments="payments.data"
                    @show-payment-details="showPaymentModal"
                />

                <!-- Paginación -->
                <div class="mt-6">
                    <Pagination :links="payments.links" />
                </div>
            </div>
        </div>

        <!-- Modal de Detalles del Pago -->
        <PaymentDetailModal
            v-if="selectedPayment"
            :payment="selectedPayment"
            :show="showModal"
            @close="closeModal"
        />
    </AdminLayout>
</template>

<script setup>
import { ref, reactive } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import PaymentsHeader from "@/Components/Payments/PaymentsHeader.vue";
import PaymentsFilters from "@/Components/Payments/PaymentsFilters.vue";
import PaymentsTable from "@/Components/Payments/PaymentsTable.vue";
import Pagination from "@/Components/Pagination.vue";
import PaymentDetailModal from "@/Components/Payments/PaymentDetailModal.vue";

const props = defineProps({
    payments: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_revenue: 0,
            completed: 0,
            pending: 0,
            failed: 0,
            authorized: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const selectedPayment = ref(null);
const showModal = ref(false);

const formatPrice = (amount) => {
    return new Intl.NumberFormat("es-CL").format(amount || 0);
};

const showPaymentModal = (payment) => {
    selectedPayment.value = payment;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedPayment.value = null;
};

const handleCreatePayment = () => {
    router.visit(route("admin.payments.presential.create"));
};

const handleCreateRefund = () => {
    router.visit(route("admin.payments.refunds.create"));
};

const handleFiltersChanged = (newFilters) => {
    router.get(route("admin.payments.index"), newFilters, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>
