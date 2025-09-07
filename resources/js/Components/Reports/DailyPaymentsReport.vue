<template>
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900">
      <h3 class="text-lg font-semibold mb-4">Reporte de Pagos Diarios - Solo Pagos Completados</h3>
      <p class="text-sm text-gray-600 mb-4">Este reporte muestra únicamente los pagos que han sido aprobados y completados exitosamente.</p>

      <!-- Filtros -->
      <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Número de Programa</label>
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
              {{ program.code }} - {{ program.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Ejecutivo Comercial</label>
          <select
            v-model="filters.executiveId"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Todos los ejecutivos</option>
            <option
              v-for="executive in executives"
              :key="executive.id"
              :value="executive.id"
            >
              {{ executive.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Forma de Financiamiento</label>
          <select
            v-model="filters.paymentMethod"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Todas las formas</option>
            <option value="VP">Venta Presencial</option>
            <option value="KP">Khipu</option>
            <option value="BX">Bsale</option>
            <option value="TD">Tarjeta de Débito</option>
            <option value="TE">Tarjeta de Crédito</option>
            <option value="WP">WebPay</option>
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
            @click="generateReport"
            class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg"
          >
            Generar Reporte
          </button>
        </div>
      </div>


      <!-- Tabla de Resultados -->
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
                Precio Programa
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Monto Abonado
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Monto Liberado
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Aporte Externo
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Saldo por Pagar
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Fecha Pago
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="payment in reportData" :key="payment.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ payment.participant_name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ payment.program_name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${{ payment.program_price?.toLocaleString() }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${{ payment.amount?.toLocaleString() }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${{ payment.released_amount?.toLocaleString() || '0' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${{ payment.external_contribution?.toLocaleString() || '0' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${{ payment.remaining_balance?.toLocaleString() || '0' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDate(payment.created_at) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Estado vacío -->
      <div v-else-if="hasSearched" class="text-center py-8">
        <p class="text-gray-500">No se encontraron pagos completados para los filtros seleccionados</p>
        <p class="text-sm text-gray-400 mt-2">Recuerda que solo se muestran pagos aprobados y exitosos</p>
      </div>

      <!-- Resumen -->
      <div v-if="reportData.length > 0" class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">Total Pagado (Completado)</p>
          <p class="text-xl font-bold text-blue-600">
            ${{ totalAmount.toLocaleString() }}
          </p>
        </div>
        <div class="bg-green-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">Total Liberado</p>
          <p class="text-xl font-bold text-green-600">
            ${{ totalReleased.toLocaleString() }}
          </p>
        </div>
        <div class="bg-yellow-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">Total Aporte Externo</p>
          <p class="text-xl font-bold text-yellow-600">
            ${{ totalExternal.toLocaleString() }}
          </p>
        </div>
        <div class="bg-red-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">Total Saldo Pendiente</p>
          <p class="text-xl font-bold text-red-600">
            ${{ totalRemaining.toLocaleString() }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  data: {
    type: Object,
    default: () => ({})
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

// Inicializar filtros desde props o con valores por defecto
const filters = ref({
  programId: props.filters?.programId || '',
  executiveId: props.filters?.executiveId || '',
  paymentMethod: props.filters?.paymentMethod || '',
  dateFrom: props.filters?.dateFrom || '',
  dateTo: props.filters?.dateTo || ''
})

// Extraer datos de las props
const programs = computed(() => {
  return props.data?.programs || []
})

const executives = computed(() => {
  return props.data?.salesExecutives || []
})

// Inicializar datos desde props
const reportData = ref(props.data?.dailyPayments?.data || [])
const hasSearched = ref(!!(props.data?.dailyPayments?.data && props.data.dailyPayments.data.length > 0))

const totalAmount = computed(() => {
  return reportData.value.reduce((sum, payment) => sum + (payment.amount || 0), 0)
})

const totalReleased = computed(() => {
  return reportData.value.reduce((sum, payment) => sum + (payment.released_amount || 0), 0)
})

const totalExternal = computed(() => {
  return reportData.value.reduce((sum, payment) => sum + (payment.external_contribution || 0), 0)
})

const totalRemaining = computed(() => {
  return reportData.value.reduce((sum, payment) => sum + (payment.remaining_balance || 0), 0)
})

// Watcher para monitorear cambios en reportData
watch(reportData, (newData, oldData) => {
  // Actualizar hasSearched cuando cambien los datos
  hasSearched.value = newData && newData.length > 0
}, { deep: true, immediate: true })

const generateReport = () => {
  hasSearched.value = true
  
  router.get('/admin/reports/daily-payments', filters.value, {
    preserveState: false,
    onSuccess: (page) => {
      // Los datos vienen en page.props.data
      if (page.props.data) {
        // Actualizar reportData
        if (page.props.data.dailyPayments) {
          reportData.value = page.props.data.dailyPayments.data || []
        }
      } else {
        reportData.value = []
      }
    },
    onError: (errors) => {
      console.error('Error al generar reporte:', errors)
    }
  })
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('es-CL')
}

onMounted(() => {
  // Solo establecer fechas por defecto si no vienen en las props
  if (!filters.value.dateFrom || !filters.value.dateTo) {
    const today = new Date()
    const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000))
    
    filters.value.dateFrom = filters.value.dateFrom || thirtyDaysAgo.toISOString().split('T')[0]
    filters.value.dateTo = filters.value.dateTo || today.toISOString().split('T')[0]
  }
})
</script>
