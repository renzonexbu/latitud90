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
          <h3 class="text-lg font-semibold mb-4">Filtros</h3>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Fecha Desde
              </label>
              <input
                v-model="filters.dateFrom"
                @change="applyFilters"
                type="date"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Fecha Hasta
              </label>
              <input
                v-model="filters.dateTo"
                @change="applyFilters"
                type="date"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Modalidad de Pago
              </label>
              <select
                v-model="filters.paymentMethodId"
                @change="applyFilters"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              >
                <option value="">Todas las modalidades</option>
                <option
                  v-for="method in paymentMethods"
                  :key="method.id"
                  :value="method.id"
                >
                  {{ method.name }}
                </option>
              </select>
            </div>
          </div>

          <div class="flex space-x-4">
            <button
              @click="openExportModal"
              class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg"
            >
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
        <ConsolidatedPaymentsTable
          :payments="consolidatedPayments.data"
          @view-details="viewDetails"
        />

        <!-- Paginación -->
        <div class="mt-6">
          <ReportsPagination
            :current-page="consolidatedPayments?.current_page || 1"
            :total-items="consolidatedPayments?.total || 0"
            :items-per-page="consolidatedPayments?.per_page || 10"
            @page-changed="handlePageChange"
          />
        </div>
      </div>
    </div>

    <!-- Modal de Exportación -->
    <div
      v-if="showExportModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
    >
      <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <!-- Header del Modal -->
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-900">
              Exportar Consolidado de Pagos
            </h3>
            <button
              @click="closeExportModal"
              class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
            >
              &times;
            </button>
          </div>

          <div class="space-y-6">
            <!-- Selección de Campos -->
            <div>
              <h4 class="text-lg font-semibold text-gray-900 mb-4">
                Seleccionar Campos para Exportar
              </h4>
              
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Información de Identificación -->
                <div class="bg-gray-50 p-4 rounded-lg">
                  <h5 class="font-semibold text-gray-800 mb-3">Identificación</h5>
                  <div class="space-y-2">
                    <label class="flex items-center">
                      <input type="checkbox" v-model="exportFields.identification.programId" class="mr-2">
                      <span class="text-sm">ID Programa</span>
                    </label>
                    <label class="flex items-center">
                      <input type="checkbox" v-model="exportFields.identification.authorizationCode" class="mr-2">
                      <span class="text-sm">N° Autorización</span>
                    </label>
                    <label class="flex items-center">
                      <input type="checkbox" v-model="exportFields.identification.participantRut" class="mr-2">
                      <span class="text-sm">RUT Participante</span>
                    </label>
                  </div>
                </div>

                <!-- Información del Pago -->
                <div class="bg-gray-50 p-4 rounded-lg">
                  <h5 class="font-semibold text-gray-800 mb-3">Información del Pago</h5>
                  <div class="space-y-2">
                    <label class="flex items-center">
                      <input type="checkbox" v-model="exportFields.payment.amount" class="mr-2">
                      <span class="text-sm">Pago/Devolución</span>
                    </label>
                    <label class="flex items-center">
                      <input type="checkbox" v-model="exportFields.payment.invoiceNumber" class="mr-2">
                      <span class="text-sm">N° de Boleta</span>
                    </label>
                    <label class="flex items-center">
                      <input type="checkbox" v-model="exportFields.payment.paymentMethod" class="mr-2">
                      <span class="text-sm">Forma de Pago</span>
                    </label>
                    <label class="flex items-center">
                      <input type="checkbox" v-model="exportFields.payment.installmentsNumber" class="mr-2">
                      <span class="text-sm">N° de Cuotas</span>
                    </label>
                    <label class="flex items-center">
                      <input type="checkbox" v-model="exportFields.payment.paymentDate" class="mr-2">
                      <span class="text-sm">Fecha de Pago</span>
                    </label>
                  </div>
                </div>

                <!-- Información del Pagador -->
                <div class="bg-gray-50 p-4 rounded-lg">
                  <h5 class="font-semibold text-gray-800 mb-3">Información del Pagador</h5>
                  <div class="space-y-2">
                    <label class="flex items-center">
                      <input type="checkbox" v-model="exportFields.payer.name" class="mr-2">
                      <span class="text-sm">Nombre Contacto Pagador</span>
                    </label>
                    <label class="flex items-center">
                      <input type="checkbox" v-model="exportFields.payer.email" class="mr-2">
                      <span class="text-sm">E-mail Contacto Pagador</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Opciones de Exportación -->
            <div class="bg-blue-50 p-4 rounded-lg">
              <h5 class="font-semibold text-blue-800 mb-3">Opciones de Exportación</h5>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Formato de Archivo
                  </label>
                  <select v-model="exportOptions.format" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="xlsx">Excel (.xlsx)</option>
                    <option value="csv">CSV (.csv)</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Incluir Todos los Registros
                  </label>
                  <select v-model="exportOptions.includeAll" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="current">Solo página actual</option>
                    <option value="all">Todos los registros</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3">
              <button
                @click="closeExportModal"
                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg"
              >
                Cancelar
              </button>
              <button
                @click="exportReport"
                :disabled="!hasSelectedFields"
                class="bg-green-500 hover:bg-green-700 disabled:bg-gray-400 text-white font-bold py-2 px-4 rounded-lg"
              >
                Exportar
              </button>
            </div>
          </div>
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
import ConsolidatedPaymentsTable from './ConsolidatedPaymentsTable.vue'
import ConsolidatedPaymentDetailsModal from './ConsolidatedPaymentDetailsModal.vue'
import ReportsPagination from './ReportsPagination.vue'

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
  paymentMethodId: props.filters.paymentMethodId || ''
})

// Modal states
const showExportModal = ref(false)
const showDetailsModal = ref(false)
const selectedPayment = ref(null)

// Export fields
const exportFields = reactive({
  identification: {
    programId: true,
    authorizationCode: true,
    participantRut: true
  },
  payment: {
    amount: true,
    invoiceNumber: true,
    paymentMethod: true,
    installmentsNumber: true,
    paymentDate: true
  },
  payer: {
    name: true,
    email: true
  }
})

const exportOptions = reactive({
  format: "xlsx",
  includeAll: "current",
})

// Computed
const hasSelectedFields = computed(() => {
  return Object.values(exportFields).some(section => 
    Object.values(section).some(field => field)
  )
})

// Methods
const applyFilters = () => {
  router.get(route('reports.consolidated-payments'), filters, {
    preserveState: true,
    preserveScroll: true
  })
}

const openExportModal = () => {
  showExportModal.value = true
  // Resetear campos de exportación al abrir
  Object.keys(exportFields).forEach(key => {
    Object.keys(exportFields[key]).forEach(field => {
      exportFields[key][field] = true // Por defecto todos seleccionados
    });
  });
  exportOptions.format = "xlsx";
  exportOptions.includeAll = "current";
}

const closeExportModal = () => {
  showExportModal.value = false
}

const exportReport = () => {
  const params = new URLSearchParams(filters)
  const selectedFields = Object.keys(exportFields).reduce((acc, key) => {
    acc[key] = Object.keys(exportFields[key]).filter(field => exportFields[key][field]);
    return acc;
  }, {});
  params.append('fields', JSON.stringify(selectedFields));
  params.append('format', exportOptions.format);
  params.append('include_all', exportOptions.includeAll);

  window.open(
    `/admin/reports/export/consolidated-payments?${params.toString()}`,
    "_blank"
  );
  closeExportModal();
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
