<template>
  <AdminLayout>
    <Head title="Reportes y Estadísticas" />

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Filtros de Reporte -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <h2 class="text-2xl font-bold mb-6">Reportes y Estadísticas</h2>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >Fecha Inicio</label
                >
                <input
                  v-model="filters.dateFrom"
                  type="date"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >Fecha Fin</label
                >
                <input
                  v-model="filters.dateTo"
                  type="date"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >Programa</label
                >
                <select
                  v-model="filters.program"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                  <option value="">Todos los programas</option>
                  <option
                    v-for="program in programs"
                    :key="program.id"
                    :value="program.id">
                    {{ program.name }}
                  </option>
                </select>
              </div>
              <div class="flex items-end">
                <button
                  @click="generateReport"
                  class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">
                  Generar Reporte
                </button>
              </div>
            </div>
          </div>
        </div>



        <!-- Resumen Ejecutivo -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <h3 class="text-lg font-semibold mb-4">Resumen Ejecutivo</h3>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
              <div class="bg-blue-50 p-4 rounded-lg">
                <div class="flex items-center">
                  <div class="p-2 bg-blue-100 rounded-lg mr-3">
                    <svg
                      class="w-6 h-6 text-blue-600"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">Ingresos Totales</p>
                    <p class="text-xl font-bold text-blue-600">
                      ${{ (totalRevenue || 0).toLocaleString() }}
                    </p>
                  </div>
                </div>
              </div>

              <div class="bg-green-50 p-4 rounded-lg">
                <div class="flex items-center">
                  <div class="p-2 bg-green-100 rounded-lg mr-3">
                    <svg
                      class="w-6 h-6 text-green-600"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">Reservas</p>
                    <p class="text-xl font-bold text-green-600">
                      {{ totalParticipants || 0 }}
                    </p>
                  </div>
                </div>
              </div>

              <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="flex items-center">
                  <div class="p-2 bg-yellow-100 rounded-lg mr-3">
                    <svg
                      class="w-6 h-6 text-yellow-600"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">Total Programas</p>
                    <p class="text-xl font-bold text-yellow-600">
                      {{ totalPrograms || 0 }}
                    </p>
                  </div>
                </div>
              </div>

              <div class="bg-purple-50 p-4 rounded-lg">
                <div class="flex items-center">
                  <div class="p-2 bg-purple-100 rounded-lg mr-3">
                    <svg
                      class="w-6 h-6 text-purple-600"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">Valor Promedio</p>
                    <p class="text-xl font-bold text-purple-600">
                      ${{ ((totalRevenue || 0) / (totalParticipants || 1)).toLocaleString() }}
                    </p>
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
              <h3 class="text-lg font-semibold">Gráfico de Ventas</h3>
              <div class="flex space-x-2">
                <button
                  @click="chartPeriod = 'daily'"
                  :class="[
                    'px-3 py-1 text-sm rounded',
                    chartPeriod === 'daily'
                      ? 'bg-blue-500 text-white'
                      : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                  ]">
                  Diario
                </button>
                <button
                  @click="chartPeriod = 'weekly'"
                  :class="[
                    'px-3 py-1 text-sm rounded',
                    chartPeriod === 'weekly'
                      ? 'bg-blue-500 text-white'
                      : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                  ]">
                  Semanal
                </button>
                <button
                  @click="chartPeriod = 'monthly'"
                  :class="[
                    'px-3 py-1 text-sm rounded',
                    chartPeriod === 'monthly'
                      ? 'bg-blue-500 text-white'
                      : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                  ]">
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
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
              <h3 class="text-lg font-semibold mb-4">
                Programas Más Populares
              </h3>

              <div class="space-y-3">
                <div
                  v-for="(program, index) in popularPrograms"
                  :key="program.id"
                  class="flex items-center justify-between p-3 bg-gray-50 rounded">
                  <div class="flex items-center">
                    <span
                      class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">
                      {{ index + 1 }}
                    </span>
                    <div>
                      <p class="font-medium text-gray-900">
                        {{ program.name || "Programa sin nombre" }}
                      </p>
                      <p class="text-sm text-gray-500">
                        {{ program.reservations_count || 0 }} reservas
                      </p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="font-bold text-gray-900">
                      ${{ (program.total_revenue || 0).toLocaleString() }}
                    </p>
                    <p class="text-sm text-gray-500">ingresos</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Métodos de Pago -->
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
              <h3 class="text-lg font-semibold mb-4">Métodos de Pago</h3>

              <div class="space-y-3">
                <div
                  v-for="payment in paymentMethods"
                  :key="payment.gateway"
                  class="flex items-center justify-between p-3 bg-gray-50 rounded">
                  <div class="flex items-center">
                    <span
                      :class="getGatewayClass(payment.gateway)"
                      class="px-2 py-1 text-xs font-semibold rounded-full mr-3">
                      {{ (typeof payment.gateway === 'string' ? payment.gateway : "desconocido").toUpperCase() }}
                    </span>
                    <span class="font-medium"
                      >{{ payment.count || 0 }} transacciones</span
                    >
                  </div>
                  <div class="text-right">
                    <p class="font-bold">
                      ${{ (payment.total || 0).toLocaleString() }}
                    </p>
                    <p class="text-sm text-gray-500">
                      {{ payment.percentage || 0 }}%
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
            <h3 class="text-lg font-semibold mb-4">Exportar Reportes</h3>

            <div class="flex space-x-4">
              <button
                @click="exportReport('pdf')"
                class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg flex items-center">
                <svg
                  class="w-4 h-4 mr-2"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Exportar PDF
              </button>

              <button
                @click="exportReport('excel')"
                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg flex items-center">
                <svg
                  class="w-4 h-4 mr-2"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Exportar Excel
              </button>

              <button
                @click="exportReport('csv')"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg flex items-center">
                <svg
                  class="w-4 h-4 mr-2"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 10v6m0 0l-3-3m3 3l3-3M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                </svg>
                Exportar CSV
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
  import { Chart, registerables } from 'chart.js';

  Chart.register(...registerables);

  const props = defineProps({
    programs: {
      type: Array,
      default: () => []
    },
    totalRevenue: {
      type: Number,
      default: 0
    },
    totalParticipants: {
      type: Number,
      default: 0
    },
    totalPrograms: {
      type: Number,
      default: 0
    },
    paymentMethods: {
      type: Array,
      default: () => []
    },
    popularPrograms: {
      type: Array,
      default: () => []
    },
    chartData: {
      type: Object,
      default: () => ({
        labels: [],
        datasets: []
      })
    }
  });

  const filters = reactive({
    dateFrom: "",
    dateTo: "",
    program: ""
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
      const ctx = salesChart.value.getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: props.chartData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Ingresos ($)'
              }
            }
          },
          plugins: {
            legend: {
              position: 'top'
            }
          }
        }
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
        }
      });
    } catch (error) {
      console.error("Error al generar reporte:", error);
    }
  };

  const getGatewayClass = (gateway) => {
    if (!gateway || typeof gateway !== 'string') return "bg-gray-100 text-gray-800";

    const classes = {
      transbank: "bg-blue-100 text-blue-800",
      khipu: "bg-green-100 text-green-800",
      presencial: "bg-purple-100 text-purple-800"
    };
    return classes[gateway.toLowerCase()] || "bg-gray-100 text-gray-800";
  };

  const exportReport = (format) => {
    try {
      const params = new URLSearchParams({
        ...filters,
        format: format || "csv"
      });

      window.open(`/admin/reports/export?${params.toString()}`, "_blank");
    } catch (error) {
      console.error("Error al exportar reporte:", error);
    }
  };
</script>
