<template>
    <AdminLayout>
        <Head title="Gestión de Suscripciones" />

        <!-- Sistema de Alertas -->
        <AlertWrapper ref="alertWrapper" />

        <div class="bg-white min-h-screen">
            <!-- Header -->
            <SubscriptionsHeader
                subtitle="Visualización y administración de suscripciones"
            />

            <!-- Estadísticas -->
            <div class="px-8 py-6 bg-gray-50">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
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
                                <p class="text-sm text-gray-600">Total</p>
                                <p class="text-xl font-bold text-blue-600">
                                    {{ stats.total }}
                                </p>
                            </div>
                        </div>
                    </div>

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
                                        d="M5 13l4 4L19 7"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Activas</p>
                                <p class="text-xl font-bold text-green-600">
                                    {{ stats.active }}
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
                                <p class="text-sm text-gray-600">Suscribiendo</p>
                                <p class="text-xl font-bold text-yellow-600">
                                    {{ stats.subscribing }}
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
                                        d="M6 18L18 6M6 6l12 12"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Canceladas</p>
                                <p class="text-xl font-bold text-red-600">
                                    {{ stats.cancelled }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="px-8 py-4">
                <SubscriptionsFilters
                    :initial-filters="filters"
                    @filters-changed="handleFiltersChanged"
                    :programs="programs"
                />
            </div>

            <!-- Tabla -->
            <div class="px-8 py-6">
                <SubscriptionsTable
                    :subscriptions="subscriptions.data"
                    @show-subscription-details="showSubscriptionDetails"
                    @cancel-subscription="cancelSubscription"
                />

                <!-- Paginación -->
                <div class="mt-6">
                    <Pagination :links="subscriptions.links" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import SubscriptionsHeader from "@/Components/Subscriptions/SubscriptionsHeader.vue";
import SubscriptionsFilters from "@/Components/Subscriptions/SubscriptionsFilters.vue";
import SubscriptionsTable from "@/Components/Subscriptions/SubscriptionsTable.vue";
import Pagination from "@/Components/Pagination.vue";
import AlertWrapper from "@/Components/Admin/AlertWrapper.vue";

const props = defineProps({
    subscriptions: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            subscribing: 0,
            cancelled: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    programs: {
        type: Array,
        default: () => [],
    },
});

const showSubscriptionDetails = (subscription) => {
    router.visit(route("admin.subscriptions.show", subscription.id));
};

const handleFiltersChanged = (newFilters) => {
    router.get(route("admin.subscriptions.index"), newFilters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const cancelSubscription = (subscription) => {
    if (confirm(`¿Estás seguro de que deseas cancelar la suscripción de ${subscription.participant.name}?\n\nEsta acción no se puede deshacer y cancelará todos los cobros pendientes en VirtualPos.`)) {
        router.delete(route("admin.subscriptions.cancel", subscription.id), {
            preserveScroll: true,
            onSuccess: () => {
                // La página se recargará automáticamente
            },
        });
    }
};
</script>
