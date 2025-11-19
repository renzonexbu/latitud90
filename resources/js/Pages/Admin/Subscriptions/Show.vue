<template>
    <AdminLayout>
        <Head :title="`Suscripción #${subscription.id}`" />

        <!-- Sistema de Alertas -->
        <AlertWrapper ref="alertWrapper" />

        <div class="bg-white min-h-screen">
            <!-- Header -->
            <div class="px-8 py-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <Link
                            :href="route('admin.subscriptions.index')"
                            class="text-sm text-gray-600 hover:text-gray-900 mb-2 inline-flex items-center"
                        >
                            <svg
                                class="w-4 h-4 mr-1"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 19l-7-7 7-7"
                                ></path>
                            </svg>
                            Volver a Suscripciones
                        </Link>
                        <h1 class="text-2xl font-bold text-gray-900">
                            Detalle de Suscripción
                        </h1>
                        <p class="text-sm text-gray-600 mt-1">
                            ID VirtualPos: {{ subscription.virtualpos_subscription_id }}
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <button
                            v-if="canCancel"
                            @click="cancelSubscription"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2"
                        >
                            <svg
                                class="w-5 h-5"
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
                            Cancelar Suscripción
                        </button>
                        <button
                            @click="syncWithVirtualPos"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
                        >
                            <svg
                                class="w-5 h-5"
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
                            Sincronizar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Status Badge -->
            <div class="px-8 py-4 bg-gray-50 border-b border-gray-200">
                <div class="flex items-center gap-4">
                    <span
                        :class="[
                            'px-4 py-2 rounded-full text-sm font-medium',
                            getStatusClass(subscription.status),
                        ]"
                    >
                        {{ getStatusLabel(subscription.status) }}
                    </span>
                    <span class="text-sm text-gray-600">
                        Fecha de creación: {{ subscription.created_at }}
                    </span>
                </div>
            </div>

            <!-- Information Cards -->
            <div class="px-8 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Participant Card -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3
                            class="text-lg font-semibold text-gray-900 mb-4 flex items-center"
                        >
                            <svg
                                class="w-5 h-5 mr-2 text-blue-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                ></path>
                            </svg>
                            Información del Participante
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">Nombre</p>
                                <p class="text-base font-medium text-gray-900">
                                    {{ subscription.participant.name }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Documento</p>
                                <p class="text-base font-medium text-gray-900">
                                    {{ subscription.participant.document_type }}:
                                    {{ subscription.participant.document }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Email</p>
                                <p class="text-base font-medium text-gray-900">
                                    {{ subscription.participant.email }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Program Card -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3
                            class="text-lg font-semibold text-gray-900 mb-4 flex items-center"
                        >
                            <svg
                                class="w-5 h-5 mr-2 text-green-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                ></path>
                            </svg>
                            Información del Programa
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">Programa</p>
                                <p class="text-base font-medium text-gray-900">
                                    {{ subscription.program.name }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Destino</p>
                                <p class="text-base font-medium text-gray-900">
                                    {{ subscription.program.destination }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">
                                    Fecha de partida
                                </p>
                                <p class="text-base font-medium text-gray-900">
                                    {{ subscription.program.departure_date }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Institución</p>
                                <p class="text-base font-medium text-gray-900">
                                    {{ subscription.institution.name }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method Card -->
                <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <h3
                        class="text-lg font-semibold text-gray-900 mb-4 flex items-center"
                    >
                        <svg
                            class="w-5 h-5 mr-2 text-purple-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                            ></path>
                        </svg>
                        Método de Pago
                    </h3>
                    <div class="text-base font-medium text-gray-900">
                        {{ formatPaymentMethod(subscription.payment_method) }}
                    </div>
                </div>

                <!-- Installments Section -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3
                            class="text-lg font-semibold text-gray-900 flex items-center"
                        >
                            <svg
                                class="w-5 h-5 mr-2 text-orange-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                ></path>
                            </svg>
                            Plan de Cuotas
                        </h3>
                        <div class="text-sm text-gray-600">
                            <span class="font-medium text-green-600">
                                {{ subscription.paid_installments }}
                            </span>
                            de
                            <span class="font-medium text-gray-900">
                                {{ subscription.total_installments }}
                            </span>
                            cuotas pagadas
                        </div>
                    </div>

                    <div
                        v-if="subscription.installments.length > 0"
                        class="overflow-x-auto"
                    >
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Cuota
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Monto
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Fecha de vencimiento
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Estado
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Fecha de pago
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        ID VirtualPos
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="installment in subscription.installments"
                                    :key="installment.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"
                                    >
                                        Cuota {{ installment.installment_number }}
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        ${{ formatAmount(installment.amount) }}
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ installment.due_date }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            :class="[
                                                'px-2 py-1 text-xs font-medium rounded-full',
                                                getInstallmentStatusClass(
                                                    installment
                                                ),
                                            ]"
                                        >
                                            {{
                                                getInstallmentStatusLabel(
                                                    installment
                                                )
                                            }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{
                                            installment.paid_at
                                                ? installment.paid_at
                                                : "-"
                                        }}
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 font-mono"
                                    >
                                        {{
                                            installment.virtualpos_charge_id ||
                                            "-"
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-else
                        class="text-center py-8 text-gray-500"
                    >
                        No hay cuotas registradas para esta suscripción.
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { computed } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AlertWrapper from "@/Components/Admin/AlertWrapper.vue";

const props = defineProps({
    subscription: {
        type: Object,
        required: true,
    },
});

const canCancel = computed(() => {
    return (
        props.subscription.status === "ACTIVA" ||
        props.subscription.status === "SUSCRIBIENDO"
    );
});

const getStatusLabel = (status) => {
    const labels = {
        ACTIVA: "Activa",
        SUSCRIBIENDO: "Suscribiendo",
        PENDIENTE: "Pendiente",
        CANCELADA: "Cancelada",
        RECHAZADA: "Rechazada",
        SUSCRIPCION_FALLIDA: "Suscripción Fallida",
    };
    return labels[status] || status;
};

const getStatusClass = (status) => {
    const classes = {
        ACTIVA: "bg-green-100 text-green-800",
        SUSCRIBIENDO: "bg-yellow-100 text-yellow-800",
        PENDIENTE: "bg-blue-100 text-blue-800",
        CANCELADA: "bg-red-100 text-red-800",
        RECHAZADA: "bg-red-100 text-red-800",
        SUSCRIPCION_FALLIDA: "bg-red-100 text-red-800",
    };
    return classes[status] || "bg-gray-100 text-gray-800";
};

const formatPaymentMethod = (paymentMethod) => {
    if (!paymentMethod) return "N/A";

    if (typeof paymentMethod === "string") {
        return paymentMethod;
    }

    if (paymentMethod.card_brand && paymentMethod.last_four_digits) {
        return `${paymentMethod.card_brand.toUpperCase()} •••• ${paymentMethod.last_four_digits}`;
    }

    return "Suscripción";
};

const formatAmount = (amount) => {
    return new Intl.NumberFormat("es-CL").format(amount);
};

const getInstallmentStatusLabel = (installment) => {
    if (installment.is_paid) {
        return "Pagado";
    }

    const statuses = {
        pending: "Pendiente",
        paid: "Pagado",
        cancelled: "Cancelado",
        overdue: "Vencido",
    };

    return statuses[installment.status] || installment.status;
};

const getInstallmentStatusClass = (installment) => {
    if (installment.is_paid) {
        return "bg-green-100 text-green-800";
    }

    const classes = {
        pending: "bg-yellow-100 text-yellow-800",
        paid: "bg-green-100 text-green-800",
        cancelled: "bg-red-100 text-red-800",
        overdue: "bg-red-100 text-red-800",
    };

    return classes[installment.status] || "bg-gray-100 text-gray-800";
};

const cancelSubscription = () => {
    if (
        confirm(
            `¿Estás seguro de que deseas cancelar la suscripción de ${props.subscription.participant.name}?\n\nEsta acción no se puede deshacer y cancelará todos los cobros pendientes en VirtualPos.`
        )
    ) {
        router.delete(
            route("admin.subscriptions.cancel", props.subscription.id),
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.visit(route("admin.subscriptions.index"));
                },
            }
        );
    }
};

const syncWithVirtualPos = () => {
    router.post(
        route("admin.subscriptions.sync", props.subscription.id),
        {},
        {
            preserveScroll: true,
        }
    );
};
</script>
