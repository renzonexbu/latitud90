<template>
  <div class="py-12">
    <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
      <!-- Header -->
      <ReportsHeader
        title="Consolidado de Pagos"
        subtitle="Reporte contable de pagos y devoluciones"
      />

      <!-- Filtros -->
      <div class="bg-white rounded-[20px] overflow-hidden">
        <div class="p-6 text-gray-900">
          <ConsolidatedPaymentsFilters
            :initial-filters="filters"
            :payment-methods="paymentMethods"
            :programs="programs"
            @filters-changed="onFiltersChanged"
          />
          <div class="flex space-x-4 mt-4">
            <button
              @click="exportReport"
              class="mt-2 lg:mt-0 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#1c4f4a] hover:bg-[#164136] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1c4f4a]"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Exportar
            </button>
          </div>
        </div>
      </div>

      <!-- Resumen -->
      <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex items-center">
              <div class="p-2 bg-blue-100 rounded-lg mr-3">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
              </div>
              <div>
                <p class="text-sm text-gray-600">Total Pagos</p>
                <p class="text-xl font-bold text-blue-600">{{ summary.total_payments || 0 }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex items-center">
              <div class="p-2 bg-green-100 rounded-lg mr-3">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <div>
                <p class="text-sm text-gray-600">Total Ingresos</p>
                <p class="text-xl font-bold text-green-600">{{ formatCurrency(summary.total_payments_amount || 0) }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex items-center">
              <div class="p-2 bg-red-100 rounded-lg mr-3">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                </svg>
              </div>
              <div>
                <p class="text-sm text-gray-600">Total Devoluciones</p>
                <p class="text-xl font-bold text-red-600">{{ formatCurrency(summary.total_refunds_amount || 0) }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex items-center">
              <div class="p-2 bg-purple-100 rounded-lg mr-3">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
              </div>
              <div>
                <p class="text-sm text-gray-600">Monto Neto</p>
                <p class="text-xl font-bold text-purple-600">{{ formatCurrency(summary.net_amount || 0) }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex items-center">
              <div class="p-2 bg-yellow-100 rounded-lg mr-3">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
              </div>
              <div>
                <p class="text-sm text-gray-600">Promedio Pago</p>
                <p class="text-xl font-bold text-yellow-600">{{ formatCurrency(summary.average_payment || 0) }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex items-center">
              <div class="p-2 bg-orange-100 rounded-lg mr-3">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
              </div>
              <div>
                <p class="text-sm text-gray-600">Total Registros</p>
                <p class="text-xl font-bold text-orange-600">{{ summary.total_records || 0 }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Lista de Pagos Consolidados -->
      <div>
        <ConsolidatedPaymentsTable :rows="consolidatedPayments.data" @view-details="viewDetails" />

        <!-- Paginación -->
        <div class="mt-6">
          <ConsolidatedPaymentsPagination
            :current-page="consolidatedPayments?.current_page || 1"
            :total-items="consolidatedPayments?.total || 0"
            :items-per-page="consolidatedPayments?.per_page || 10"
            @page-changed="handlePageChange"
          />
        </div>
      </div>
    </div>

    

    <!-- Modal de Detalles -->
    <ConsolidatedPaymentDetailsModal
      v-if="showDetailsModal"
      :payment="selectedPayment"
      @close="showDetailsModal = false"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import ReportsHeader from './ReportsHeader.vue'
import ConsolidatedPaymentsFilters from './ConsolidatedPayments/ConsolidatedPaymentsFilters.vue'
import ConsolidatedPaymentsTable from './ConsolidatedPayments/ConsolidatedPaymentsTable.vue'
import ConsolidatedPaymentDetailsModal from './ConsolidatedPayments/ConsolidatedPaymentDetailsModal.vue'
import ConsolidatedPaymentsPagination from './ConsolidatedPayments/ConsolidatedPaymentsPagination.vue'

const props = defineProps({
  consolidatedPayments: {
    type: Object,
    default: () => ({
      data: [],
      links: []
    })
  },
  paymentMethods: {
    type: Array,
    default: () => []
  },
  programs: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  summary: {
    type: Object,
    default: () => ({})
  }
})

// Reactive data
const filters = reactive({
  dateFrom: props.filters.dateFrom || '',
  dateTo: props.filters.dateTo || '',
  paymentMethodId: props.filters.paymentMethodId || '',
  programId: props.filters.programId || '',
  participantQuery: props.filters.participantQuery || ''
})

// Estados de detalle (sin modal de exportación)
const showDetailsModal = ref(false)
const selectedPayment = ref(null)

// Methods
const applyFilters = () => {
  router.get('/admin/reports/consolidated-payments', filters, {
    preserveState: true,
    preserveScroll: true
  })
}

const onFiltersChanged = (newFilters) => {
  filters.dateFrom = newFilters.dateFrom || ''
  filters.dateTo = newFilters.dateTo || ''
  filters.paymentMethodId = newFilters.paymentMethodId || ''
  filters.programId = newFilters.programId || ''
  filters.participantQuery = newFilters.participantQuery || ''
  applyFilters()
}

const exportReport = () => {
  const params = new URLSearchParams(filters)
  window.open(
    `/admin/reports/export/consolidated-payments?${params.toString()}`,
    "_blank"
  );
}

const viewDetails = (payment) => {
  selectedPayment.value = payment
  showDetailsModal.value = true
}

const handlePageChange = (page) => {
  // Actualizar los filtros con la nueva página
  filters.page = page;

  // Aplicar filtros al backend con la nueva página
  router.get("/admin/reports/consolidated-payments", filters, {
    preserveState: true,
    preserveScroll: true,
  });
}

const formatCurrency = (value) => {
  const numericValue = Math.round(Number(value) || 0);
  return numericValue.toLocaleString("es-CL");
}

// Lifecycle
onMounted(() => {
  // Set default date range if not provided
  if (!filters.dateFrom && !filters.dateTo) {
    const today = new Date()
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1)
    
    filters.dateFrom = firstDayOfMonth.toISOString().split('T')[0]
    filters.dateTo = today.toISOString().split('T')[0]
  }
})
</script>



