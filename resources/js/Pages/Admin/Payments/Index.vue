<template>
    <AdminLayout>
        <Head title="Gestión de Pagos" />

        <div class="bg-white min-h-screen">
            <!-- Header -->
            <PaymentsHeader 
                subtitle="Visualización y administración de pagos"
                :show-create-button="true"
                @create-payment="handleCreatePayment"
            />

            <!-- Estadísticas -->
            <div class="px-8 py-6 bg-gray-50">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Ingresos Totales</p>
                                <p class="text-xl font-bold text-green-600">${{ formatPrice(stats.total_revenue) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Completados</p>
                                <p class="text-xl font-bold text-blue-600">{{ stats.completed }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Pendientes</p>
                                <p class="text-xl font-bold text-yellow-600">{{ stats.pending }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <div class="p-2 bg-red-100 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Fallidos</p>
                                <p class="text-xl font-bold text-red-600">{{ stats.failed }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficas de Análisis -->
            <div class="px-8 py-6 bg-white">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Análisis del Funnel de Conversión</h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Funnel de Conversión -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Funnel de Conversión</h3>
                        <div class="h-80">
                            <canvas ref="funnelChart"></canvas>
                        </div>
                    </div>

                    <!-- Métodos de Pago -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Métodos de Pago</h3>
                        <div class="h-80">
                            <canvas ref="paymentMethodsChart"></canvas>
                        </div>
                    </div>

                    <!-- Términos Aceptados -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Aceptación de Términos</h3>
                        <div class="h-80">
                            <canvas ref="termsChart"></canvas>
                        </div>
                    </div>

                    <!-- Clientes Frecuentes -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Tipo de Cliente</h3>
                        <div class="h-80">
                            <canvas ref="clientTypeChart"></canvas>
                        </div>
                    </div>

                    <!-- Programas Más Vistos -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Programas Más Vistos</h3>
                        <div class="h-80">
                            <canvas ref="programViewsChart"></canvas>
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
import { ref, reactive, onMounted, nextTick } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaymentsHeader from '@/Components/Payments/PaymentsHeader.vue';
import PaymentsFilters from '@/Components/Payments/PaymentsFilters.vue';
import PaymentsTable from '@/Components/Payments/PaymentsTable.vue';
import Pagination from '@/Components/Pagination.vue';
import PaymentDetailModal from '@/Components/Payments/PaymentDetailModal.vue';

Chart.register(...registerables);

const props = defineProps({
    payments: {
        type: Object,
        default: () => ({ data: [], links: [] })
    },
    stats: {
        type: Object,
        default: () => ({
            total_revenue: 0,
            completed: 0,
            pending: 0,
            failed: 0,
            authorized: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    analyticsData: {
        type: Object,
        default: () => ({})
    }
});

const selectedPayment = ref(null);
const showModal = ref(false);

// Referencias para las gráficas
const funnelChart = ref(null);
const paymentMethodsChart = ref(null);
const termsChart = ref(null);
const clientTypeChart = ref(null);
const programViewsChart = ref(null);

// Instancias de las gráficas
let funnelChartInstance = null;
let paymentMethodsChartInstance = null;
let termsChartInstance = null;
let clientTypeChartInstance = null;
let programViewsChartInstance = null;

const formatPrice = (amount) => {
    return new Intl.NumberFormat('es-CL').format(amount || 0);
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
    router.visit(route('admin.payments.create'));
};



const handleFiltersChanged = (newFilters) => {
    router.get(route('admin.payments.index'), newFilters, {
        preserveState: true,
        preserveScroll: true
    });
};

// Función para procesar datos de analytics
const processAnalyticsData = () => {
    const data = props.analyticsData || {};
    
    // Procesar funnel de conversión
    const funnelData = {
        heroSearch: data.hero_search_count || 0,
        programSelection: data.program_selection_count || 0,
        programDetailView: data.program_detail_view_count || 0,
        paymentSelection: data.payment_selection_count || 0,
        buyerFormData: data.buyer_form_data_count || 0,
        paymentInitiated: data.payment_initiated_count || 0,
        paymentCompleted: data.payment_completed_count || 0,
        paymentFailed: data.payment_failed_count || 0
    };

    // Procesar métodos de pago
    const paymentMethods = data.payment_methods || {};
    
    // Procesar términos aceptados
    const termsData = {
        accepted: data.terms_accepted_count || 0,
        notAccepted: data.terms_not_accepted_count || 0
    };

    // Procesar tipo de cliente
    const clientTypeData = {
        frequent: data.frequent_clients_count || 0,
        new: data.new_clients_count || 0
    };

    // Procesar programas más vistos
    const programViews = data.program_views || {};

    return { funnelData, paymentMethods, termsData, clientTypeData, programViews };
};

// Crear gráfica del funnel de conversión
const createFunnelChart = () => {
    if (!funnelChart.value) return;

    if (funnelChartInstance) {
        funnelChartInstance.destroy();
    }

    const { funnelData } = processAnalyticsData();
    const ctx = funnelChart.value.getContext('2d');

    funnelChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [
                'Búsqueda Hero',
                'Selección Programa',
                'Vista Detalle Programa',
                'Vista Pago',
                'Datos Comprador',
                'Pago Iniciado',
                'Pago Completado',
                'Pago Fallido'
            ],
            datasets: [{
                label: 'Usuarios',
                data: [
                    funnelData.heroSearch,
                    funnelData.programSelection,
                    funnelData.programDetailView,
                    funnelData.paymentSelection,
                    funnelData.buyerFormData,
                    funnelData.paymentInitiated,
                    funnelData.paymentCompleted,
                    funnelData.paymentFailed
                ],
                backgroundColor: [
                    '#3B82F6', // Azul
                    '#10B981', // Verde
                    '#F59E0B', // Amarillo
                    '#8B5CF6', // Púrpura
                    '#EF4444', // Rojo
                    '#06B6D4', // Cyan
                    '#6B7280', // Gris
                    '#EC4899'  // Rosa
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Usuarios: ${context.parsed.y}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
};

// Crear gráfica de métodos de pago
const createPaymentMethodsChart = () => {
    if (!paymentMethodsChart.value) return;

    if (paymentMethodsChartInstance) {
        paymentMethodsChartInstance.destroy();
    }

    const { paymentMethods } = processAnalyticsData();
    const ctx = paymentMethodsChart.value.getContext('2d');

    const labels = Object.keys(paymentMethods);
    const data = Object.values(paymentMethods);

    paymentMethodsChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    '#3B82F6',
                    '#10B981',
                    '#F59E0B',
                    '#EF4444',
                    '#8B5CF6',
                    '#06B6D4'
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: ${context.parsed} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
};

// Crear gráfica de términos aceptados
const createTermsChart = () => {
    if (!termsChart.value) return;

    if (termsChartInstance) {
        termsChartInstance.destroy();
    }

    const { termsData } = processAnalyticsData();
    const ctx = termsChart.value.getContext('2d');

    termsChartInstance = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Aceptados', 'No Aceptados'],
            datasets: [{
                data: [termsData.accepted, termsData.notAccepted],
                backgroundColor: ['#10B981', '#EF4444'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: ${context.parsed} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
};

// Crear gráfica de tipo de cliente
const createClientTypeChart = () => {
    if (!clientTypeChart.value) return;

    if (clientTypeChartInstance) {
        clientTypeChartInstance.destroy();
    }

    const { clientTypeData } = processAnalyticsData();
    const ctx = clientTypeChart.value.getContext('2d');

    clientTypeChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Clientes Frecuentes', 'Clientes Nuevos'],
            datasets: [{
                label: 'Cantidad',
                data: [clientTypeData.frequent, clientTypeData.new],
                backgroundColor: ['#8B5CF6', '#06B6D4'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Clientes: ${context.parsed.y}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
};

// Crear gráfica de programas más vistos
const createProgramViewsChart = () => {
    if (!programViewsChart.value) return;

    if (programViewsChartInstance) {
        programViewsChartInstance.destroy();
    }

    const { programViews } = processAnalyticsData();
    const ctx = programViewsChart.value.getContext('2d');

    const labels = Object.keys(programViews);
    const data = Object.values(programViews);

    programViewsChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Vistas',
                data: data,
                backgroundColor: [
                    '#3B82F6',
                    '#10B981',
                    '#F59E0B',
                    '#EF4444',
                    '#8B5CF6'
                ],
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Vistas: ${context.parsed.x}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                },
                y: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    });
};

// Crear todas las gráficas
const createAllCharts = () => {
    nextTick(() => {
        createFunnelChart();
        createPaymentMethodsChart();
        createTermsChart();
        createClientTypeChart();
        createProgramViewsChart();
    });
};

// Inicializar gráficas al montar el componente
onMounted(() => {
    createAllCharts();
});
</script>
