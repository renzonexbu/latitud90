<template>
  <AdminLayout>
    <Head title="Reporte de Pagos Diarios" />

    <div class="py-12">
      <div class="max-w-full mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <DailyPaymentsHeader
            title="Reporte de Pagos Diarios"
            subtitle="Pagos realizados diariamente con información detallada"
            :show-export-button="true"
            @export-clicked="openExportModal"
        />

        <!-- Filtros -->
        <div class="bg-white overflow-hidden shadow-sm rounded-[20px] mb-6 p-6">
            <DailyPaymentsFilters
                :initial-filters="localFilters"
                :programs="programs"
                :sales-executives="salesExecutives"
                :financing-types="financingTypes"
                :payment-methods="paymentMethods"
                @filters-changed="handleFiltersChanged"
            />
        </div>

        <!-- Resumen -->
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-6 mb-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Órdenes</p>
                            <p class="text-xl font-bold text-blue-600">{{ summary.total_orders || 0 }}</p>
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
                            <p class="text-sm text-gray-600">Total Pagos</p>
                            <p class="text-xl font-bold text-green-600">{{ summary.total_payments || 0 }}</p>
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
                            <p class="text-sm text-gray-600">Monto Total Pagado</p>
                            <p class="text-xl font-bold text-yellow-600">${{ formatCurrency(summary.total_amount_paid || 0) }}</p>
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
                            <p class="text-sm text-gray-600">Promedio Pago</p>
                            <p class="text-xl font-bold text-purple-600">${{ formatCurrency(summary.average_payment || 0) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-orange-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Pagos Hoy</p>
                            <p class="text-xl font-bold text-orange-600">{{ summary.payments_today || 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-red-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Registros</p>
                            <p class="text-xl font-bold text-red-600">{{ summary.total_records || 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Pagos Diarios -->
        <div v-if="filteredPayments.length === 0" class="bg-white overflow-hidden shadow-sm rounded-[20px] p-6">
            <div class="text-center py-12">
                <div class="text-gray-400 mb-4">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No hay pagos disponibles</h3>
                <p class="text-gray-600 mb-6">Intenta ajustar los filtros o verifica que existan pagos en el rango de fechas seleccionado.</p>
            </div>
        </div>

        <div v-else class="space-y-6">
            <div class="bg-white rounded-[20px] p-0 overflow-hidden">
                <DailyPaymentsTable 
                    :payments="filteredPayments" 
                    @view-details="handleViewDetails"
                />
                
                <!-- Pagination & Per Page -->
                <div class="p-4 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600">Mostrar</label>
                        <select
                            v-model="paymentsPerPage"
                            @change="handlePerPageChanged"
                            class="px-2 py-1 text-sm border border-gray-300 rounded-lg focus:ring-[#1c4f4a] focus:border-[#1c4f4a]"
                        >
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                            <option value="all">Todas</option>
                        </select>
                        <span class="text-sm text-gray-600">por página</span>
                    </div>
                    <DailyPaymentsPagination
                        v-if="paymentsPerPage !== 'all'"
                        :current-page="currentPage"
                        :total-payments="totalPayments"
                        :payments-per-page="Number(paymentsPerPage)"
                        @page-changed="handlePageChanged"
                    />
                </div>
            </div>
        </div>
      </div>
    </div>

    <!-- Modal de Detalles -->
    <div v-if="showDetailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <!-- Header del Modal -->
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">
                        Detalles de Pago - {{ selectedPayment?.participant_name }}
                    </h3>
                    <button @click="closeDetailModal" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">
                        &times;
                    </button>
                </div>

                <div v-if="selectedPayment" class="space-y-6">
                    <!-- Información del Participante -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-lg font-semibold text-gray-900 mb-3">Información del Participante</h4>
                            <div class="space-y-2">
                                <p><span class="font-medium">Nombre del Alumno:</span> {{ selectedPayment.participant_name || 'N/A' }}</p>
                                <p><span class="font-medium">N° Documento:</span> {{ selectedPayment.transaction_document_number || 'N/A' }}</p>
                                <p><span class="font-medium">Tipo de Dcto:</span> {{ selectedPayment.document_type || 'RUT' }}</p>
                            </div>
                        </div>

                        <!-- Información del Ejecutivo -->
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h4 class="text-lg font-semibold text-gray-900 mb-3">Ejecutivo Comercial</h4>
                            <div class="space-y-2">
                                <p><span class="font-medium">Nombre:</span> {{ selectedPayment.sales_executive_name || 'N/A' }}</p>
                                <p><span class="font-medium">Email:</span> {{ selectedPayment.sales_executive_email || 'N/A' }}</p>
                                <p><span class="font-medium">Teléfono:</span> {{ selectedPayment.sales_executive_phone || 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Información del Programa -->
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-gray-900 mb-3">Información del Programa</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Código (Programa)</p>
                                <p class="font-bold text-gray-900">{{ selectedPayment.program_code || 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Programa (Nombre)</p>
                                <p class="font-bold text-gray-900">{{ selectedPayment.program_name || 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Fecha de Inicio</p>
                                <p class="font-bold text-gray-900">{{ formatDate(selectedPayment.program_departure_date) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">$ Programa</p>
                                <p class="font-bold text-green-600">${{ formatCurrency(selectedPayment.program_price) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Información de Pagos -->
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-gray-900 mb-3">Información de Pagos</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Forma de Pago</p>
                                <p class="font-bold text-blue-600">{{ selectedPayment.payment_form_code || 'N/A' }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Abonos + becas</p>
                                <p class="font-bold text-purple-600">${{ formatCurrency(selectedPayment.scholarships_amount || 0) }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Valor alumno liberado</p>
                                <p class="font-bold text-indigo-600">${{ formatCurrency(selectedPayment.released_amount || 0) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Cuotas y Montos -->
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-gray-900 mb-3">Cuotas y Montos</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="text-center">
                                <p class="text-sm text-gray-600">N° Cuotas Pagadas</p>
                                <p class="text-xl font-bold text-green-600">{{ selectedPayment.paid_installments_display || '0' }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Monto Total Pagado</p>
                                <p class="text-xl font-bold text-green-600">${{ formatCurrency(selectedPayment.total_paid_amount) }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-600">N° Cuotas No Pagadas</p>
                                <p class="text-xl font-bold text-red-600">{{ selectedPayment.overdue_installments_display || '0' }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Monto Total por Cobrar</p>
                                <p class="text-xl font-bold text-red-600">${{ formatCurrency(selectedPayment.total_pending_amount) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones del Modal -->
                <div class="flex justify-end space-x-3 mt-6">
                    <button @click="closeDetailModal" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Exportación -->
    <div v-if="showExportModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <!-- Header del Modal -->
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Exportar Pagos Diarios</h3>
                    <button @click="closeExportModal" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">
                        &times;
                    </button>
                </div>

                <div class="space-y-6">
                    <!-- Selección de Campos -->
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">
                            Seleccionar Campos para Exportar
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Columnas de la Tabla (siempre incluidas por defecto) -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h5 class="font-semibold text-gray-800 mb-3">Columnas de la Tabla</h5>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="exportFields.table.enrollmentCode" class="mr-2">
                                        <span class="text-sm">Cód. Inscripción</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="exportFields.table.participant" class="mr-2">
                                        <span class="text-sm">Participante</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="exportFields.table.paymentForm" class="mr-2">
                                        <span class="text-sm">Forma de Pago</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="exportFields.table.documentType" class="mr-2">
                                        <span class="text-sm">Tipo Documento</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="exportFields.table.documentNumber" class="mr-2">
                                        <span class="text-sm">N° Documento</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="exportFields.table.totalPaid" class="mr-2">
                                        <span class="text-sm">Monto Pagado</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="exportFields.table.pendingAmount" class="mr-2">
                                        <span class="text-sm">Saldo Pendiente</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="exportFields.table.executive" class="mr-2">
                                        <span class="text-sm">Ejecutivo Comercial</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Columnas Adicionales -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h5 class="font-semibold text-gray-800 mb-3">Columnas Adicionales</h5>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="exportFields.extra.programCode" class="mr-2">
                                        <span class="text-sm">Código Programa</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="exportFields.extra.programName" class="mr-2">
                                        <span class="text-sm">Nombre Programa</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="exportFields.extra.departureDate" class="mr-2">
                                        <span class="text-sm">Fecha de Inicio</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="exportFields.extra.programPrice" class="mr-2">
                                        <span class="text-sm">$ Programa</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="exportFields.extra.scholarships" class="mr-2">
                                        <span class="text-sm">Abonos + Becas</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="exportFields.extra.releasedAmount" class="mr-2">
                                        <span class="text-sm">Valor Alumno Liberado</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="exportFields.extra.paidInstallments" class="mr-2">
                                        <span class="text-sm">N° Cuotas Pagadas</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="exportFields.extra.unpaidInstallments" class="mr-2">
                                        <span class="text-sm">N° Cuotas No Pagadas</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Opciones de Exportación -->
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h5 class="font-semibold text-blue-800 mb-3">Opciones de Exportación</h5>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Formato de Archivo</label>
                            <select v-model="exportOptions.format" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="xlsx">Excel (.xlsx)</option>
                                <option value="csv">CSV (.csv)</option>
                            </select>
                        </div>
                        <p class="text-xs text-blue-600 mt-2">Se exportarán todos los registros según los filtros aplicados.</p>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end space-x-3">
                        <button @click="closeExportModal" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg">
                            Cancelar
                        </button>
                        <button @click="exportReport" :disabled="!hasSelectedFields" class="bg-green-500 hover:bg-green-700 disabled:bg-gray-400 text-white font-bold py-2 px-4 rounded-lg">
                            Exportar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </AdminLayout>
</template>

<script>
import { Head, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import DailyPaymentsHeader from "@/Components/Reports/DailyReports/DailyPaymentsHeader.vue";
import DailyPaymentsFilters from "@/Components/Reports/DailyReports/DailyPaymentsFilters.vue";
import DailyPaymentsTable from "@/Components/Reports/DailyReports/DailyPaymentsTable.vue";
import DailyPaymentsPagination from "@/Components/Reports/DailyReports/DailyPaymentsPagination.vue";

export default {
    name: "DailyPayments",
    components: {
        Head,
        AdminLayout,
        DailyPaymentsHeader,
        DailyPaymentsFilters,
        DailyPaymentsTable,
        DailyPaymentsPagination,
    },
    props: {
        dailyPayments: {
            type: Object,
            default: () => ({}),
        },
        programs: {
            type: Array,
            default: () => [],
        },
        salesExecutives: {
            type: Array,
            default: () => [],
        },
        financingTypes: {
            type: Object,
            default: () => ({}),
        },
        paymentMethods: {
            type: Array,
            default: () => [],
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
        summary: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            showDetailModal: false,
            selectedPayment: null,
            showExportModal: false,
            currentPage: 1,
            paymentsPerPage: this.filters.perPage || 25,
            localFilters: {
                programId: this.filters.programId || "",
                salesExecutiveId: this.filters.salesExecutiveId || "",
                financingType: this.filters.financingType || "",
                paymentMethodId: this.filters.paymentMethodId || "",
                dateFrom: this.filters.dateFrom || "",
                dateTo: this.filters.dateTo || "",
            },
            exportOptions: {
                format: "xlsx",
            },
            exportFields: {
                table: {
                    enrollmentCode: true,
                    participant: true,
                    paymentForm: true,
                    documentType: true,
                    documentNumber: true,
                    totalPaid: true,
                    pendingAmount: true,
                    executive: true,
                },
                extra: {
                    programCode: false,
                    programName: false,
                    departureDate: false,
                    programPrice: false,
                    scholarships: false,
                    releasedAmount: false,
                    paidInstallments: false,
                    unpaidInstallments: false,
                },
            },
        };
    },
    computed: {
        filteredPayments() {
            const payments = this.dailyPayments;

            // Si es un objeto de paginación (del backend), usar data directamente
            if (payments && payments.data && Array.isArray(payments.data)) {
                return payments.data;
            }

            // Si es un array simple, aplicar paginación del frontend
            if (Array.isArray(payments)) {
                const startIndex = (this.currentPage - 1) * this.paymentsPerPage;
                const endIndex = startIndex + this.paymentsPerPage;
                return payments.slice(startIndex, endIndex);
            }

            // Si no es ninguno de los anteriores, retornar array vacío
            return [];
        },
        totalPayments() {
            if (this.dailyPayments && this.dailyPayments.total) {
                return this.dailyPayments.total;
            }
            if (Array.isArray(this.dailyPayments)) {
                return this.dailyPayments.length;
            }
            return 0;
        },
        hasSelectedFields() {
            return Object.values(this.exportFields).some(fields => 
                Object.values(fields).some(field => field)
            );
        }
    },
    methods: {
        handleFiltersChanged(newFilters) {
            this.localFilters = newFilters;
            this.currentPage = 1;

            router.get("/admin/reports/daily-payments", { ...newFilters, perPage: this.paymentsPerPage }, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        handlePageChanged(page) {
            this.currentPage = page;

            if (this.dailyPayments && this.dailyPayments.data) {
                const filters = { ...this.localFilters, page: page, perPage: this.paymentsPerPage };
                router.get("/admin/reports/daily-payments", filters, {
                    preserveState: true,
                    preserveScroll: true,
                });
            }
        },
        handlePerPageChanged() {
            this.currentPage = 1;
            router.get("/admin/reports/daily-payments", { ...this.localFilters, perPage: this.paymentsPerPage, page: 1 }, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        handleViewDetails(payment) {
            this.selectedPayment = payment;
            this.showDetailModal = true;
        },
        closeDetailModal() {
            this.showDetailModal = false;
            this.selectedPayment = null;
        },
        openExportModal() {
            this.showExportModal = true;
        },
        closeExportModal() {
            this.showExportModal = false;
        },
        exportReport() {
            const params = new URLSearchParams(this.localFilters);
            const selectedFields = Object.keys(this.exportFields).reduce((acc, key) => {
                acc[key] = Object.keys(this.exportFields[key]).filter(field => this.exportFields[key][field]);
                return acc;
            }, {});

            params.append('fields', JSON.stringify(selectedFields));
            params.append('format', this.exportOptions.format);

            window.open(
                `/admin/reports/export/daily-payments?${params.toString()}`,
                "_blank"
            );
            this.closeExportModal();
        },
        formatCurrency(value) {
            const numericValue = Math.round(Number(value) || 0);
            return numericValue.toLocaleString("es-CL");
        },
        formatDate(date) {
            if (!date) return "N/A";
            return new Date(date).toLocaleDateString("es-CL");
        },
        formatRut(rut) {
            if (!rut) return "N/A";
            
            // Si ya viene formateado del backend (con puntos), devolverlo tal como está
            if (rut.includes('.')) {
                return rut;
            }
            
            // Limpiar el RUT de puntos y guiones
            let rutLimpio = rut.toString().replace(/\./g, "").replace(/-/g, "");
            
            // Verificar si es un RUT (7-8 dígitos + dígito verificador)
            if (rutLimpio.length >= 7 && rutLimpio.length <= 9 && /^\d{7,8}[\dK]$/.test(rutLimpio)) {
                // Es un RUT, formatearlo
                let dv = rutLimpio.slice(-1);
                let numero = rutLimpio.slice(0, -1);
                
                // Formatear número con puntos
                let numeroFormateado = numero.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                
                // Retornar RUT formateado
                return `${numeroFormateado}-${dv.toUpperCase()}`;
            } else {
                // No es un RUT, devolver tal como está
                return rut;
            }
        },
    },
};
</script>
