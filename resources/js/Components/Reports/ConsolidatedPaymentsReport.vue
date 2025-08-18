<template>
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900">
      <h3 class="text-lg font-semibold mb-4">Consolidado de Pagos</h3>

      <!-- Filtros -->
      <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
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

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Modalidad de Pago</label>
          <select
            v-model="filters.paymentMethod"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Todas las modalidades</option>
            <option value="VP">Venta Presencial</option>
            <option value="KP">Khipu</option>
            <option value="BX">Bsale</option>
            <option value="TD">Tarjeta de Débito</option>
            <option value="TE">Tarjeta de Crédito</option>
            <option value="WP">WebPay</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Transacción</label>
          <select
            v-model="filters.transactionType"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Todos</option>
            <option value="payment">Pago</option>
            <option value="refund">Devolución</option>
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

      <!-- Tabla de Resultados -->
      <div v-if="reportData.length > 0" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                ID Programa
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                N° Autorización
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                RUT Participante
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Pago/Devolución
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                N° Boleta
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Forma de Pago
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                N° Cuotas
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Fecha de Pago
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Contacto Pagador
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Email Contacto
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="payment in reportData" :key="payment.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ payment.program_id }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ payment.authorization_number || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ payment.participant_document }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm" :class="payment.amount < 0 ? 'text-red-600' : 'text-gray-900'">
                ${{ payment.amount?.toLocaleString() }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ payment.receipt_number || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ getPaymentMethodLabel(payment.payment_method) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ payment.installments_number || '1' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDate(payment.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ payment.buyer_name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ payment.buyer_email }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Estado vacío -->
      <div v-else-if="hasSearched" class="text-center py-8">
        <p class="text-gray-500">No se encontraron pagos para los filtros seleccionados</p>
      </div>

      <!-- Resumen -->
      <div v-if="reportData.length > 0" class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">Total Pagos</p>
          <p class="text-xl font-bold text-blue-600">
            ${{ totalPayments.toLocaleString() }}
          </p>
        </div>
        <div class="bg-red-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">Total Devoluciones</p>
          <p class="text-xl font-bold text-red-600">
            ${{ totalRefunds.toLocaleString() }}
          </p>
        </div>
        <div class="bg-green-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">Neto</p>
          <p class="text-xl font-bold text-green-600">
            ${{ netAmount.toLocaleString() }}
          </p>
        </div>
        <div class="bg-yellow-50 p-4 rounded-lg">
          <p class="text-sm text-gray-600">Cantidad Transacciones</p>
          <p class="text-xl font-bold text-yellow-600">
            {{ reportData.length }}
          </p>
        </div>
      </div>

      <!-- Botón Exportar -->
      <div v-if="reportData.length > 0" class="mt-6 flex justify-end">
        <button
          @click="exportToCSV"
          class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg"
        >
          Exportar a CSV
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  initialData: {
    type: Array,
    default: () => []
  }
})

const filters = ref({
  dateFrom: '',
  dateTo: '',
  paymentMethod: '',
  transactionType: ''
})

const reportData = ref(props.initialData || [])
const hasSearched = ref(false)

const totalPayments = computed(() => {
  return reportData.value
    .filter(payment => payment.amount > 0)
    .reduce((sum, payment) => sum + payment.amount, 0)
})

const totalRefunds = computed(() => {
  return reportData.value
    .filter(payment => payment.amount < 0)
    .reduce((sum, payment) => sum + Math.abs(payment.amount), 0)
})

const netAmount = computed(() => {
  return totalPayments.value - totalRefunds.value
})

const generateReport = () => {
  hasSearched.value = true
  router.get('/admin/reports/consolidated-payments', filters.value, {
    preserveState: true,
    onSuccess: (page) => {
      reportData.value = page.props.consolidatedPayments || []
    }
  })
}

const getPaymentMethodLabel = (method) => {
  const labels = {
    'VP': 'Venta Presencial',
    'KP': 'Khipu',
    'BX': 'Bsale',
    'TD': 'Tarjeta de Débito',
    'TE': 'Tarjeta de Crédito',
    'WP': 'WebPay'
  }
  return labels[method] || method
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('es-CL')
}

const exportToCSV = () => {
  const headers = [
    'ID Programa',
    'N° Autorización',
    'RUT Participante',
    'Pago/Devolución',
    'N° Boleta',
    'Forma de Pago',
    'N° Cuotas',
    'Fecha de Pago',
    'Contacto Pagador',
    'Email Contacto'
  ]

  const csvContent = [
    headers.join(','),
    ...reportData.value.map(payment => [
      payment.program_id,
      payment.authorization_number || 'N/A',
      payment.participant_document,
      payment.amount,
      payment.receipt_number || 'N/A',
      getPaymentMethodLabel(payment.payment_method),
      payment.installments_number || '1',
      formatDate(payment.created_at),
      payment.buyer_name,
      payment.buyer_email
    ].join(','))
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  const url = URL.createObjectURL(blob)
  link.setAttribute('href', url)
  link.setAttribute('download', `consolidado_pagos_${new Date().toISOString().split('T')[0]}.csv`)
  link.style.visibility = 'hidden'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

onMounted(() => {
  // Establecer fechas por defecto (últimos 30 días)
  const today = new Date()
  const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000))
  
  filters.value.dateFrom = thirtyDaysAgo.toISOString().split('T')[0]
  filters.value.dateTo = today.toISOString().split('T')[0]
})
</script>
