<template>
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900">
      <h3 class="text-lg font-semibold mb-4">Tendencia de Ingresos</h3>

      <!-- Filtros -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Período</label>
          <select
            v-model="filters.period"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="daily">Diario</option>
            <option value="weekly">Semanal</option>
            <option value="monthly">Mensual</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
          <input
            v-model="filters.dateFrom"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
          <input
            v-model="filters.dateTo"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="flex items-end">
          <button
            @click="generateChart"
            class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg"
          >
            Actualizar Gráfico
          </button>
        </div>
      </div>

      <!-- Resumen de Ingresos -->
      <div v-if="chartData.length > 0" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">Total Período</p>
          <p class="text-xl font-bold text-blue-600">
            ${{ totalRevenue.toLocaleString() }}
          </p>
        </div>
        <div class="bg-green-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">Promedio Diario</p>
          <p class="text-xl font-bold text-green-600">
            ${{ averageDaily.toLocaleString() }}
          </p>
        </div>
        <div class="bg-yellow-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">Día con Mayor Ingreso</p>
          <p class="text-xl font-bold text-yellow-600">
            ${{ maxRevenue.toLocaleString() }}
          </p>
        </div>
        <div class="bg-purple-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">Cantidad Transacciones</p>
          <p class="text-xl font-bold text-purple-600">
            {{ totalTransactions }}
          </p>
        </div>
      </div>

      <!-- Gráfico de Ingresos -->
      <div v-if="chartData.length > 0" class="mb-6">
        <div class="h-96">
          <canvas ref="revenueChart"></canvas>
        </div>
      </div>

      <!-- Gráfico de Métodos de Pago -->
      <div v-if="paymentMethodsData.length > 0" class="mb-6">
        <h4 class="text-md font-semibold mb-4">Distribución por Método de Pago</h4>
        <div class="h-64">
          <canvas ref="paymentMethodsChart"></canvas>
        </div>
      </div>

      <!-- Estado vacío -->
      <div v-else-if="hasSearched" class="text-center py-8">
        <p class="text-gray-500">No se encontraron datos para el período seleccionado</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

const props = defineProps({
  initialData: {
    type: Array,
    default: () => []
  },
  paymentMethodsData: {
    type: Array,
    default: () => []
  }
})

const filters = ref({
  period: 'daily',
  dateFrom: '',
  dateTo: ''
})

const chartData = ref(props.initialData || [])
const paymentMethodsData = ref(props.paymentMethodsData || [])
const hasSearched = ref(false)
const revenueChart = ref(null)
const paymentMethodsChart = ref(null)

const totalRevenue = computed(() => {
  return chartData.value.reduce((sum, item) => sum + (item.revenue || 0), 0)
})

const averageDaily = computed(() => {
  if (chartData.value.length === 0) return 0
  return Math.round(totalRevenue.value / chartData.value.length)
})

const maxRevenue = computed(() => {
  if (chartData.value.length === 0) return 0
  return Math.max(...chartData.value.map(item => item.revenue || 0))
})

const totalTransactions = computed(() => {
  return chartData.value.reduce((sum, item) => sum + (item.transactions || 0), 0)
})

const generateChart = () => {
  hasSearched.value = true
  router.get('/admin/reports/revenue-chart', filters.value, {
    preserveState: true,
    onSuccess: (page) => {
      chartData.value = page.props.revenueData || []
      paymentMethodsData.value = page.props.paymentMethodsData || []
      nextTick(() => {
        createCharts()
      })
    }
  })
}

const createCharts = () => {
  // Gráfico de ingresos
  if (revenueChart.value && chartData.value.length > 0) {
    const ctx = revenueChart.value.getContext('2d')
    
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: chartData.value.map(item => item.date),
        datasets: [{
          label: 'Ingresos',
          data: chartData.value.map(item => item.revenue),
          borderColor: '#3B82F6',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          borderWidth: 2,
          fill: true,
          tension: 0.4
        }, {
          label: 'Transacciones',
          data: chartData.value.map(item => item.transactions),
          borderColor: '#10B981',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          borderWidth: 2,
          fill: false,
          tension: 0.4,
          yAxisID: 'y1'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false,
        },
        scales: {
          x: {
            display: true,
            title: {
              display: true,
              text: 'Fecha'
            }
          },
          y: {
            type: 'linear',
            display: true,
            position: 'left',
            title: {
              display: true,
              text: 'Ingresos ($)'
            }
          },
          y1: {
            type: 'linear',
            display: true,
            position: 'right',
            title: {
              display: true,
              text: 'Transacciones'
            },
            grid: {
              drawOnChartArea: false,
            },
          }
        },
        plugins: {
          legend: {
            position: 'top',
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                if (context.datasetIndex === 0) {
                  return `Ingresos: $${context.parsed.y.toLocaleString()}`
                } else {
                  return `Transacciones: ${context.parsed.y}`
                }
              }
            }
          }
        }
      }
    })
  }

  // Gráfico de métodos de pago
  if (paymentMethodsChart.value && paymentMethodsData.value.length > 0) {
    const ctx = paymentMethodsChart.value.getContext('2d')
    
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: paymentMethodsData.value.map(item => item.method),
        datasets: [{
          data: paymentMethodsData.value.map(item => item.amount),
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
                const total = context.dataset.data.reduce((a, b) => a + b, 0)
                const percentage = ((context.parsed / total) * 100).toFixed(1)
                return `${context.label}: $${context.parsed.toLocaleString()} (${percentage}%)`
              }
            }
          }
        }
      }
    })
  }
}

onMounted(() => {
  // Establecer fechas por defecto (últimos 30 días)
  const today = new Date()
  const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000))
  
  filters.value.dateFrom = thirtyDaysAgo.toISOString().split('T')[0]
  filters.value.dateTo = today.toISOString().split('T')[0]
  
  if (chartData.value.length > 0) {
    nextTick(() => {
      createCharts()
    })
  }
})
</script>
