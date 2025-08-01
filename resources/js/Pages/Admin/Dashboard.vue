<template>
    <AdminLayout>
        <Head title="Dashboard Administrativo" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg
                                        class="h-8 w-8 text-blue-500"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"
                                        />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt
                                            class="text-sm font-medium text-gray-500 truncate"
                                        >
                                            Total Pasajeros
                                        </dt>
                                        <!-- <dd class="text-lg font-medium text-gray-900">{{ stats.total_passengers }}</dd> -->
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg
                                        class="h-8 w-8 text-green-500"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt
                                            class="text-sm font-medium text-gray-500 truncate"
                                        >
                                            Ingresos del Mes
                                        </dt>
                                        <dd
                                            class="text-lg font-medium text-gray-900"
                                        >
                                            <!-- ${{ formatPrice(stats.monthly_revenue) }} -->
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg
                                        class="h-8 w-8 text-yellow-500"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt
                                            class="text-sm font-medium text-gray-500 truncate"
                                        >
                                            Programas Activos
                                        </dt>
                                        <dd
                                            class="text-lg font-medium text-gray-900"
                                        >
                                            <!-- {{ stats.active_programs }} -->
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg
                                        class="h-8 w-8 text-red-500"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt
                                            class="text-sm font-medium text-gray-500 truncate"
                                        >
                                            Pagos Pendientes
                                        </dt>
                                        <dd
                                            class="text-lg font-medium text-gray-900"
                                        >
                                            <!-- {{ stats.pending_payments }} -->
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg mb-8">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            Acciones Rápidas
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <Link
                                :href="route('admin.programs.create')"
                                class="inline-flex items-center px-4 py-2 bg-turquesa border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150"
                            >
                                <svg
                                    class="w-4 h-4 mr-2"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Nuevo Programa
                            </Link>

                            <!-- Link temporalmente comentado - ruta passengers no disponible
              <Link
                :href="route('admin.passengers.index')"
                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                <svg
                  class="w-4 h-4 mr-2"
                  fill="currentColor"
                  viewBox="0 0 20 20">
                  <path
                    d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                </svg>
                Ver Pasajeros
              </Link>
              -->

                            <Link
                                :href="route('admin.payments.index')"
                                class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 active:bg-yellow-900 focus:outline-none focus:border-yellow-900 focus:ring ring-yellow-300 disabled:opacity-25 transition ease-in-out duration-150"
                            >
                                <svg
                                    class="w-4 h-4 mr-2"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Gestionar Pagos
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Reservations -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Reservas Recientes
                            </h3>
                            <div class="space-y-4">
                                <div
                                    v-for="reservation in recentReservations"
                                    :key="reservation.id"
                                    class="flex items-center space-x-4"
                                >
                                    <div class="flex-shrink-0">
                                        <div
                                            class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center"
                                        >
                                            <!-- <span class="text-sm font-medium text-indigo-800">{{
                        reservation.full_name?.charAt(0) || "N"
                      }}</span> -->
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p
                                            class="text-sm font-medium text-gray-900 truncate"
                                        >
                                            <!-- {{ reservation.full_name || "Sin nombre" }} -->
                                        </p>
                                        <p
                                            class="text-sm text-gray-500 truncate"
                                        >
                                            <!-- {{ reservation.program?.name }} -->
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span
                                            :class="
                                                getStatusColor(
                                                    reservation.status
                                                )
                                            "
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        >
                                            <!-- {{ getStatusText(reservation.status) }} -->
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <!-- Link temporalmente comentado - ruta passengers no disponible
                <Link
                  :href="route('admin.passengers.index')"
                  class="text-sm text-indigo-600 hover:text-indigo-500">
                  Ver todas las reservas →
                </Link>
                -->
                            </div>
                        </div>
                    </div>

                    <!-- Recent Payments -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Pagos Recientes
                            </h3>
                            <div class="space-y-4">
                                <div
                                    v-for="payment in recentPayments"
                                    :key="payment.id"
                                    class="flex items-center space-x-4"
                                >
                                    <div class="flex-shrink-0">
                                        <div
                                            class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center"
                                        >
                                            <svg
                                                class="h-4 w-4 text-green-600"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p
                                            class="text-sm font-medium text-gray-900 truncate"
                                        >
                                            <!-- ${{ formatPrice(payment.amount) }} -->
                                        </p>
                                        <p
                                            class="text-sm text-gray-500 truncate"
                                        >
                                            <!-- {{ payment.passenger?.full_name || "Sin nombre" }} -->
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span
                                            :class="
                                                getPaymentStatusColor(
                                                    payment.status
                                                )
                                            "
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        >
                                            <!-- {{ getPaymentStatusText(payment.status) }} -->
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <Link
                                    :href="route('admin.payments.index')"
                                    class="text-sm text-indigo-600 hover:text-indigo-500"
                                >
                                    Ver todos los pagos →
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import { Head, Link } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

export default {
    name: "AdminDashboard",
    components: {
        Head,
        Link,
        AdminLayout,
    },
    props: {
        stats: {
            type: Object,
            required: true,
        },
        recentReservations: {
            type: Array,
            default: () => [],
        },
        recentPayments: {
            type: Array,
            default: () => [],
        },
    },
    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat("es-CL", {
                style: "currency",
                currency: "CLP",
            })
                .format(price)
                .replace("CLP", "")
                .trim();
        },
        getStatusColor(status) {
            const colors = {
                confirmed: "bg-green-100 text-green-800",
                pending_payment: "bg-yellow-100 text-yellow-800",
                cancelled: "bg-red-100 text-red-800",
                completed: "bg-blue-100 text-blue-800",
            };
            return colors[status] || "bg-gray-100 text-gray-800";
        },
        getStatusText(status) {
            const texts = {
                confirmed: "Confirmado",
                pending_payment: "Pago Pendiente",
                cancelled: "Cancelado",
                completed: "Completado",
            };
            return texts[status] || "Desconocido";
        },
        getPaymentStatusColor(status) {
            const colors = {
                completed: "bg-green-100 text-green-800",
                pending: "bg-yellow-100 text-yellow-800",
                failed: "bg-red-100 text-red-800",
                refunded: "bg-blue-100 text-blue-800",
            };
            return colors[status] || "bg-gray-100 text-gray-800";
        },
        getPaymentStatusText(status) {
            const texts = {
                completed: "Completado",
                pending: "Pendiente",
                failed: "Fallido",
                refunded: "Reembolsado",
            };
            return texts[status] || "Desconocido";
        },
    },
};
</script>
