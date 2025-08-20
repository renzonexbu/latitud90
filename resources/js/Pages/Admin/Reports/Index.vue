<template>
    <AdminLayout>
        <Head title="Reportes y Estadísticas" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Menú de Navegación de Reportes -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h2 class="text-2xl font-bold mb-6">
                            Sistema de Reportes
                        </h2>

                        <div
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6"
                        >
                            <!-- Estado de Cuenta Parcial -->
                            <Link
                                :href="route('admin.reports.partial-account')"
                                class="bg-blue-50 hover:bg-blue-100 p-6 rounded-lg border border-blue-200 transition-colors"
                            >
                                <div class="flex items-center mb-3">
                                    <div
                                        class="p-2 bg-blue-100 rounded-lg mr-3"
                                    >
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
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                            ></path>
                                        </svg>
                                    </div>
                                    <h3
                                        class="text-lg font-semibold text-blue-800"
                                    >
                                        Estado de Cuenta Parcial
                                    </h3>
                                </div>
                                <p class="text-sm text-blue-600">
                                    Informe de estado de cuenta que L90
                                    reenviará
                                </p>
                            </Link>

                            <!-- Cronograma de Recuperación -->
                            <Link
                                :href="route('admin.reports.payment-schedule')"
                                class="bg-green-50 hover:bg-green-100 p-6 rounded-lg border border-green-200 transition-colors"
                            >
                                <div class="flex items-center mb-3">
                                    <div
                                        class="p-2 bg-green-100 rounded-lg mr-3"
                                    >
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
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                            ></path>
                                        </svg>
                                    </div>
                                    <h3
                                        class="text-lg font-semibold text-green-800"
                                    >
                                        Cronograma de Recuperación
                                    </h3>
                                </div>
                                <p class="text-sm text-green-600">
                                    Cuotas pactadas, vencimientos y cobranzas
                                </p>
                            </Link>

                            <!-- Reporte de Pagos Diarios -->
                            <Link
                                :href="route('admin.reports.daily-payments')"
                                class="bg-yellow-50 hover:bg-yellow-100 p-6 rounded-lg border border-yellow-200 transition-colors"
                            >
                                <div class="flex items-center mb-3">
                                    <div
                                        class="p-2 bg-yellow-100 rounded-lg mr-3"
                                    >
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
                                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"
                                            ></path>
                                        </svg>
                                    </div>
                                    <h3
                                        class="text-lg font-semibold text-yellow-800"
                                    >
                                        Pagos Diarios
                                    </h3>
                                </div>
                                <p class="text-sm text-yellow-600">
                                    Reporte detallado de pagos por día
                                </p>
                            </Link>

                            <!-- Consolidado de Pagos -->
                            <Link
                                :href="
                                    route('admin.reports.consolidated-payments')
                                "
                                class="bg-purple-50 hover:bg-purple-100 p-6 rounded-lg border border-purple-200 transition-colors"
                            >
                                <div class="flex items-center mb-3">
                                    <div
                                        class="p-2 bg-purple-100 rounded-lg mr-3"
                                    >
                                        <svg
                                            class="w-6 h-6 text-purple-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                            ></path>
                                        </svg>
                                    </div>
                                    <h3
                                        class="text-lg font-semibold text-purple-800"
                                    >
                                        Consolidado de Pagos
                                    </h3>
                                </div>
                                <p class="text-sm text-purple-600">
                                    Resumen consolidado de todos los pagos
                                </p>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Reporte General (Actual) -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-xl font-bold mb-6">Reporte General</h3>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                    >Fecha Inicio</label
                                >
                                <input
                                    v-model="filters.dateFrom"
                                    type="date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                    >Fecha Fin</label
                                >
                                <input
                                    v-model="filters.dateTo"
                                    type="date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                    >Programa</label
                                >
                                <select
                                    v-model="filters.program"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">
                                        Todos los programas
                                    </option>
                                    <option
                                        v-for="program in programs"
                                        :key="program.id"
                                        :value="program.id"
                                    >
                                        {{ program.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button
                                    @click="generateReport"
                                    class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg"
                                >
                                    Generar Reporte
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resumen de los 4 Módulos -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-6">
                            Resumen de Módulos de Reportes
                        </h3>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Pagos Diarios -->
                            <div class="bg-yellow-50 p-6 rounded-lg border border-yellow-200">
                                <div class="flex items-center mb-4">
                                    <div class="p-2 bg-yellow-100 rounded-lg mr-3">
                                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                    </div>
                                    <h4 class="text-lg font-semibold text-yellow-800">Pagos Diarios</h4>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-600">Total Pagos</p>
                                        <p class="text-xl font-bold text-yellow-600">{{ summary.dailyPayments.totalPayments || 0 }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Monto Total</p>
                                        <p class="text-xl font-bold text-yellow-600">${{ (summary.dailyPayments.totalAmount || 0).toLocaleString() }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Pagos Hoy</p>
                                        <p class="text-lg font-semibold text-yellow-600">{{ summary.dailyPayments.paymentsToday || 0 }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Promedio</p>
                                        <p class="text-lg font-semibold text-yellow-600">${{ (summary.dailyPayments.averageAmount || 0).toLocaleString() }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Consolidado de Pagos -->
                            <div class="bg-purple-50 p-6 rounded-lg border border-purple-200">
                                <div class="flex items-center mb-4">
                                    <div class="p-2 bg-purple-100 rounded-lg mr-3">
                                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                    <h4 class="text-lg font-semibold text-purple-800">Consolidado de Pagos</h4>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-600">Programas</p>
                                        <p class="text-xl font-bold text-purple-600">{{ summary.consolidatedPayments.totalPrograms || 0 }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Transacciones</p>
                                        <p class="text-xl font-bold text-purple-600">{{ summary.consolidatedPayments.totalTransactions || 0 }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Monto Total</p>
                                        <p class="text-lg font-semibold text-purple-600">${{ (summary.consolidatedPayments.totalAmount || 0).toLocaleString() }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Promedio/Programa</p>
                                        <p class="text-lg font-semibold text-purple-600">${{ (summary.consolidatedPayments.averagePerProgram || 0).toLocaleString() }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Cronograma de Recuperación -->
                            <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                                <div class="flex items-center mb-4">
                                    <div class="p-2 bg-green-100 rounded-lg mr-3">
                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <h4 class="text-lg font-semibold text-green-800">Cronograma de Recuperación</h4>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-600">Programados</p>
                                        <p class="text-xl font-bold text-green-600">{{ summary.paymentSchedule.totalScheduled || 0 }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Pagados</p>
                                        <p class="text-xl font-bold text-green-600">{{ summary.paymentSchedule.totalPaid || 0 }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Pendientes</p>
                                        <p class="text-lg font-semibold text-green-600">{{ summary.paymentSchedule.totalPending || 0 }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Tasa Completitud</p>
                                        <p class="text-lg font-semibold text-green-600">{{ summary.paymentSchedule.completionRate || 0 }}%</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Estado de Cuenta Parcial -->
                            <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                                <div class="flex items-center mb-4">
                                    <div class="p-2 bg-blue-100 rounded-lg mr-3">
                                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <h4 class="text-lg font-semibold text-blue-800">Estado de Cuenta Parcial</h4>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-600">Participantes</p>
                                        <p class="text-xl font-bold text-blue-600">{{ summary.partialAccount.totalParticipants || 0 }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Activos</p>
                                        <p class="text-xl font-bold text-blue-600">{{ summary.partialAccount.activeParticipants || 0 }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Balance Total</p>
                                        <p class="text-lg font-semibold text-blue-600">${{ (summary.partialAccount.totalBalance || 0).toLocaleString() }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Promedio Balance</p>
                                        <p class="text-lg font-semibold text-blue-600">${{ (summary.partialAccount.averageBalance || 0).toLocaleString() }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de Ventas -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">
                                Gráfico de Ventas
                            </h3>
                            <div class="flex space-x-2">
                                <button
                                    @click="chartPeriod = 'daily'"
                                    :class="[
                                        'px-3 py-1 text-sm rounded',
                                        chartPeriod === 'daily'
                                            ? 'bg-blue-500 text-white'
                                            : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                                    ]"
                                >
                                    Diario
                                </button>
                                <button
                                    @click="chartPeriod = 'weekly'"
                                    :class="[
                                        'px-3 py-1 text-sm rounded',
                                        chartPeriod === 'weekly'
                                            ? 'bg-blue-500 text-white'
                                            : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                                    ]"
                                >
                                    Semanal
                                </button>
                                <button
                                    @click="chartPeriod = 'monthly'"
                                    :class="[
                                        'px-3 py-1 text-sm rounded',
                                        chartPeriod === 'monthly'
                                            ? 'bg-blue-500 text-white'
                                            : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                                    ]"
                                >
                                    Mensual
                                </button>
                            </div>
                        </div>

                        <div class="h-64">
                            <canvas ref="salesChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Programas Más Populares -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                    >
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-semibold mb-4">
                                Programas Más Populares
                            </h3>

                            <div class="space-y-3">
                                <div
                                    v-for="(program, index) in summary.consolidatedPayments.topPrograms"
                                    :key="index"
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded"
                                >
                                    <div class="flex items-center">
                                        <span
                                            class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3"
                                        >
                                            {{ index + 1 }}
                                        </span>
                                        <div>
                                            <p
                                                class="font-medium text-gray-900"
                                            >
                                                {{
                                                    program.name ||
                                                    "Programa sin nombre"
                                                }}
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                {{
                                                    program.count ||
                                                    0
                                                }}
                                                transacciones
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-gray-900">
                                            ${{
                                                (
                                                    program.amount || 0
                                                ).toLocaleString()
                                            }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            ingresos
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Métodos de Pago -->
                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                    >
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-semibold mb-4">
                                Métodos de Pago
                            </h3>

                            <div class="space-y-3">
                                <div
                                    v-for="payment in summary.dailyPayments.topPaymentMethods"
                                    :key="payment.gateway"
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded"
                                >
                                    <div class="flex items-center">
                                        <span
                                            :class="
                                                getGatewayClass(payment.gateway)
                                            "
                                            class="px-2 py-1 text-xs font-semibold rounded-full mr-3"
                                        >
                                            {{
                                                (typeof payment.gateway ===
                                                "string"
                                                    ? payment.gateway
                                                    : "desconocido"
                                                ).toUpperCase()
                                            }}
                                        </span>
                                        <span class="font-medium"
                                            >{{
                                                payment.count || 0
                                            }}
                                            transacciones</span
                                        >
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold">
                                            ${{
                                                (
                                                    payment.amount || 0
                                                ).toLocaleString()
                                            }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ ((payment.amount / (summary.dailyPayments.totalAmount || 1)) * 100).toFixed(1) }}%
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acciones de Exportación -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">
                            Exportar Reportes
                        </h3>

                        <div class="flex space-x-4">
                            <button
                                @click="exportConsolidatedReport"
                                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg flex items-center"
                            >
                                <svg
                                    class="w-4 h-4 mr-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    ></path>
                                </svg>
                                Exportar Reporte Consolidado (Excel)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted, computed, nextTick } from "vue";
import { Head, router, Link } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Chart, registerables } from "chart.js";

Chart.register(...registerables);

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({
            dailyPayments: {},
            consolidatedPayments: {},
            paymentSchedule: {},
            partialAccount: {},
            general: {}
        }),
    },
    programs: {
        type: Array,
        default: () => [],
    },
    chartData: {
        type: Object,
        default: () => ({
            labels: [],
            datasets: [],
        }),
    },
});

const filters = reactive({
    dateFrom: "",
    dateTo: "",
    program: "",
});

const chartPeriod = ref("daily");
const salesChart = ref(null);

// Establecer fechas por defecto (último mes)
onMounted(() => {
    const today = new Date();
    const lastMonth = new Date(
        today.getFullYear(),
        today.getMonth() - 1,
        today.getDate()
    );

    filters.dateTo = today.toISOString().split("T")[0];
    filters.dateFrom = lastMonth.toISOString().split("T")[0];

    // Crear gráfico después de que el DOM esté listo
    nextTick(() => {
        createChart();
    });
});

const createChart = () => {
    if (salesChart.value && props.chartData) {
        const ctx = salesChart.value.getContext("2d");
        new Chart(ctx, {
            type: "line",
            data: props.chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: "Ingresos ($)",
                        },
                    },
                },
                plugins: {
                    legend: {
                        position: "top",
                    },
                },
            },
        });
    }
};

const generateReport = () => {
    try {
        router.get("/admin/reports", filters, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                nextTick(() => {
                    createChart();
                });
            },
        });
    } catch (error) {
        console.error("Error al generar reporte:", error);
    }
};

const getGatewayClass = (gateway) => {
    if (!gateway || typeof gateway !== "string")
        return "bg-gray-100 text-gray-800";

    const gatewayLower = gateway.toLowerCase();
    const classes = {
        transbank: "bg-blue-100 text-blue-800",
        khipu: "bg-green-100 text-green-800",
        presencial: "bg-purple-100 text-purple-800",
        webpay: "bg-blue-100 text-blue-800",
        desconocido: "bg-gray-100 text-gray-800",
    };
    return classes[gatewayLower] || "bg-gray-100 text-gray-800";
};

const exportConsolidatedReport = () => {
    try {
        const params = new URLSearchParams({
            dateFrom: filters.dateFrom,
            dateTo: filters.dateTo,
            program: filters.program
        });

        window.open(`/admin/reports/export?${params.toString()}`, "_blank");
    } catch (error) {
        console.error("Error al exportar reporte consolidado:", error);
    }
};
</script>
