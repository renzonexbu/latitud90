<template>
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900">
      <h3 class="text-lg font-semibold mb-4">Cronograma de Recuperación de Cuotas</h3>

      <!-- Filtros -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Programa</label>
          <select
            v-model="filters.programId"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Todos los programas</option>
            <option
              v-for="program in programs"
              :key="program.id"
              :value="program.id"
            >
              {{ program.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Estado de Cuota</label>
          <select
            v-model="filters.installmentStatus"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Todos los estados</option>
            <option value="pending">Pendiente</option>
            <option value="paid">Pagada</option>
            <option value="overdue">Vencida</option>
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

        <div class="flex items-end">
          <button
            @click="generateReport"
            class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg"
          >
            Generar Reporte
          </button>
        </div>
      </div>

      <!-- Resumen de Cuotas -->
      <div v-if="reportData.length > 0" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">Cuotas Pactadas</p>
          <p class="text-xl font-bold text-blue-600">
            {{ totalInstallments }}
          </p>
        </div>
        <div class="bg-green-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">Cuotas Pagadas</p>
          <p class="text-xl font-bold text-green-600">
            {{ paidInstallments }}
          </p>
        </div>
        <div class="bg-yellow-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">Cuotas Pendientes</p>
          <p class="text-xl font-bold text-yellow-600">
            {{ pendingInstallments }}
          </p>
        </div>
        <div class="bg-red-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">Cuotas Vencidas</p>
          <p class="text-xl font-bold text-red-600">
            {{ overdueInstallments }}
          </p>
        </div>
      </div>

      <!-- Gráfico de Cuotas por Estado -->
      <div v-if="reportData.length > 0" class="mb-6">
        <h4 class="text-md font-semibold mb-4">Distribución de Cuotas por Estado</h4>
        <div class="h-64">
          <canvas ref="installmentChart"></canvas>
        </div>
      </div>

      <!-- Gráfico de Vencimientos Futuros -->
      <div v-if="futureDueDates.length > 0" class="mb-6">
        <h4 class="text-md font-semibold mb-4">Vencimientos Futuros (Próximos 30 días)</h4>
        <div class="h-64">
          <canvas ref="dueDateChart"></canvas>
        </div>
      </div>

      <!-- Tabla de Cuotas -->
      <div v-if="reportData.length > 0" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Participante
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Programa
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Fecha Final Pago
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                N° Cuota
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Monto Cuota
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Fecha Vencimiento
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Estado
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Fecha Pago
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="installment in reportData" :key="installment.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ installment.participant_name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ installment.program_name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ installment.final_payment_date ? formatDate(installment.final_payment_date) : '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ installment.installment_number }}/{{ installment.total_installments }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${{ installment.amount?.toLocaleString() }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDate(installment.due_date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  :class="getStatusClass(installment.status)"
                >
                  {{ getStatusLabel(installment.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ installment.paid_at ? formatDate(installment.paid_at) : '-' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Estado vacío -->
      <div v-else-if="hasSearched" class="text-center py-8">
        <p class="text-gray-500">No se encontraron cuotas para los filtros seleccionados</p>
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
  programs: {
    type: Array,
    default: () => []
  },
  initialData: {
    type: Array,
    default: () => []
  }
})

const filters = ref({
  programId: '',
  installmentStatus: '',
  dateFrom: ''
})

const reportData = ref(props.initialData || [])
const hasSearched = ref(false)
const installmentChart = ref(null)
const dueDateChart = ref(null)

const totalInstallments = computed(() => reportData.value.length)
const paidInstallments = computed(() => reportData.value.filter(i => i.status === 'paid').length)
const pendingInstallments = computed(() => reportData.value.filter(i => i.status === 'pending').length)
const overdueInstallments = computed(() => reportData.value.filter(i => i.status === 'overdue').length)

const futureDueDates = computed(() => {
  const today = new Date()
  const thirtyDaysFromNow = new Date(today.getTime() + (30 * 24 * 60 * 60 * 1000))
  
  return reportData.value
    .filter(installment => {
      const dueDate = new Date(installment.due_date)
      return dueDate >= today && dueDate <= thirtyDaysFromNow && installment.status === 'pending'
    })
    .sort((a, b) => new Date(a.due_date) - new Date(b.due_date))
})

const generateReport = () => {
  hasSearched.value = true
  router.get('/admin/reports/installment-schedule', filters.value, {
    preserveState: true,
    onSuccess: (page) => {
      reportData.value = page.props.installmentSchedule || []
      nextTick(() => {
        createCharts()
      })
    }
  })
}

const getStatusClass = (status) => {
  const classes = {
    'paid': 'bg-green-100 text-green-800',
    'pending': 'bg-yellow-100 text-yellow-800',
    'overdue': 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status) => {
  const labels = {
    'paid': 'Pagada',
    'pending': 'Pendiente',
    'overdue': 'Vencida'
  }
  return labels[status] || status
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('es-CL')
}

const createCharts = () => {
  // Gráfico de distribución de cuotas
  if (installmentChart.value) {
    const ctx = installmentChart.value.getContext('2d')
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Pagadas', 'Pendientes', 'Vencidas'],
        datasets: [{
          data: [paidInstallments.value, pendingInstallments.value, overdueInstallments.value],
          backgroundColor: ['#10B981', '#F59E0B', '#EF4444'],
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
          }
        }
      }
    })
  }

  // Gráfico de vencimientos futuros
  if (dueDateChart.value && futureDueDates.value.length > 0) {
    const ctx = dueDateChart.value.getContext('2d')
    
    const dueDateData = futureDueDates.value.reduce((acc, installment) => {
      const date = formatDate(installment.due_date)
      acc[date] = (acc[date] || 0) + 1
      return acc
    }, {})

    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: Object.keys(dueDateData),
        datasets: [{
          label: 'Cuotas por Vencer',
          data: Object.values(dueDateData),
          backgroundColor: '#3B82F6',
          borderColor: '#2563EB',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: 1
            }
          }
        },
        plugins: {
          legend: {
            display: false
          }
        }
      }
    })
  }
}

onMounted(() => {
  // Establecer fecha por defecto (hoy)
  filters.value.dateFrom = new Date().toISOString().split('T')[0]
  
  if (reportData.value.length > 0) {
    nextTick(() => {
      createCharts()
    })
  }
})
</script>
