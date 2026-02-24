<template>
    <AdminLayout>
        <Head title="Documentos Generados" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">
                                    Documentos Generados
                                </h2>
                                <p class="text-sm text-gray-600 mt-1">
                                    Descarga de comprobantes de pago, contratos de reserva y boletas BSale
                                </p>
                            </div>
                            <Link
                                :href="route('admin.reports.index')"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg flex items-center"
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
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18"
                                    ></path>
                                </svg>
                                Volver
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Filtros</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                            <!-- Document Type Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Tipo de Documento
                                </label>
                                <select
                                    v-model="filters.documentType"
                                    @change="loadDocuments"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="">Todos los tipos</option>
                                    <option value="bsale_invoice">Boleta BSale</option>
                                    <option value="payment_receipt">Comprobante de Pago</option>
                                    <option value="contract">Contrato de Reserva</option>
                                </select>
                            </div>

                            <!-- Fecha Desde -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Fecha Desde
                                </label>
                                <input
                                    v-model="filters.dateFrom"
                                    type="date"
                                    @change="loadDocuments"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>

                            <!-- Fecha Hasta -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Fecha Hasta
                                </label>
                                <input
                                    v-model="filters.dateTo"
                                    type="date"
                                    @change="loadDocuments"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>

                            <!-- Search -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Buscar
                                </label>
                                <input
                                    v-model="filters.search"
                                    @input="debounceSearch"
                                    type="text"
                                    placeholder="Participante, programa, N° boleta..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>

                            <!-- Per Page -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Por página
                                </label>
                                <select
                                    v-model="filters.perPage"
                                    @change="loadDocuments"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>

                            <!-- Limpiar filtros -->
                            <div class="flex items-end">
                                <button
                                    @click="clearFilters"
                                    class="w-full px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300 rounded-md text-sm transition-colors"
                                >
                                    Limpiar filtros
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Documents List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Documentos de Pagos
                                <span v-if="pagination.total" class="text-sm font-normal text-gray-500">
                                    ({{ pagination.total }} pagos)
                                </span>
                            </h3>
                            <button
                                @click="loadDocuments"
                                :disabled="loading"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg flex items-center disabled:opacity-50"
                            >
                                <svg
                                    class="w-4 h-4 mr-2"
                                    :class="{ 'animate-spin': loading }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                    ></path>
                                </svg>
                                Actualizar
                            </button>
                        </div>

                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-8">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                            <p class="mt-2 text-gray-600">Cargando documentos...</p>
                        </div>

                        <!-- Error State -->
                        <div v-else-if="error" class="text-center py-8">
                            <div class="text-red-500 mb-2">
                                <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <p class="text-red-600 font-medium">{{ error }}</p>
                            <button
                                @click="loadDocuments"
                                class="mt-2 text-blue-500 hover:text-blue-700 underline"
                            >
                                Intentar nuevamente
                            </button>
                        </div>

                        <!-- Empty State -->
                        <div v-else-if="documents.length === 0" class="text-center py-8">
                            <div class="text-gray-400 mb-2">
                                <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <p class="text-gray-600">No se encontraron pagos con documentos</p>
                        </div>

                        <!-- Documents Table -->
                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Participante
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Programa
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Monto
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Tipo
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            N° Boleta
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Fecha
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Documentos
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="doc in documents" :key="doc.id" class="hover:bg-gray-50">
                                        <!-- Participante -->
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ doc.participant_name }}
                                            </div>
                                            <div v-if="doc.order_number" class="text-xs text-gray-500">
                                                Orden: {{ doc.order_number }}
                                            </div>
                                        </td>
                                        <!-- Programa -->
                                        <td class="px-4 py-3">
                                            <div class="text-sm text-gray-900">
                                                {{ doc.program_name }}
                                            </div>
                                        </td>
                                        <!-- Monto -->
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            {{ doc.amount_formatted }}
                                        </td>
                                        <!-- Tipo -->
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                :class="getDocTypeClass(doc.document_type)"
                                            >
                                                {{ doc.document_type_label }}
                                            </span>
                                        </td>
                                        <!-- N° Boleta -->
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            {{ doc.bsale_number || '-' }}
                                        </td>
                                        <!-- Fecha -->
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            {{ doc.created_at }}
                                        </td>
                                        <!-- Documentos (botones de descarga) -->
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                                            <div class="flex flex-wrap gap-1">
                                                <a
                                                    v-if="doc.available_documents.includes('bsale_invoice')"
                                                    :href="route('admin.reports.bsale-documents.download-document', [doc.id, 'bsale_invoice'])"
                                                    target="_blank"
                                                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded text-white bg-green-600 hover:bg-green-700"
                                                    title="Descargar Boleta BSale"
                                                >
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                    Boleta
                                                </a>
                                                <a
                                                    v-if="doc.available_documents.includes('payment_receipt')"
                                                    :href="route('admin.reports.bsale-documents.download-document', [doc.id, 'payment_receipt'])"
                                                    target="_blank"
                                                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700"
                                                    title="Descargar Comprobante de Pago"
                                                >
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                    Comprobante
                                                </a>
                                                <a
                                                    v-if="doc.available_documents.includes('contract')"
                                                    :href="route('admin.reports.bsale-documents.download-document', [doc.id, 'contract'])"
                                                    target="_blank"
                                                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded text-white bg-purple-600 hover:bg-purple-700"
                                                    title="Descargar Contrato de Reserva"
                                                >
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                    Contrato
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="pagination.last_page > 1" class="mt-6 flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Mostrando {{ ((pagination.current_page - 1) * pagination.per_page) + 1 }} a
                                {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}
                                de {{ pagination.total }} pagos
                            </div>
                            <div class="flex space-x-2">
                                <button
                                    @click="changePage(pagination.current_page - 1)"
                                    :disabled="pagination.current_page <= 1"
                                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Anterior
                                </button>
                                <span class="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md">
                                    {{ pagination.current_page }} de {{ pagination.last_page }}
                                </span>
                                <button
                                    @click="changePage(pagination.current_page + 1)"
                                    :disabled="pagination.current_page >= pagination.last_page"
                                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Siguiente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import axios from 'axios'

// Reactive data
const documents = ref([])
const loading = ref(false)
const error = ref(null)

const filters = ref({
    documentType: '',
    dateFrom: '',
    dateTo: '',
    search: '',
    perPage: 50
})

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 50,
    total: 0
})

// Document type badge classes
const getDocTypeClass = (type) => {
    const classes = {
        'B2': 'bg-green-100 text-green-800',
        'AC': 'bg-blue-100 text-blue-800',
        'VC': 'bg-red-100 text-red-800',
        'RA': 'bg-orange-100 text-orange-800',
        'CT': 'bg-gray-100 text-gray-800',
    }
    return classes[type] || 'bg-gray-100 text-gray-800'
}

// Search debounce
let searchTimeout = null
const debounceSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        loadDocuments()
    }, 500)
}

// Load documents
const loadDocuments = async (page = 1) => {
    loading.value = true
    error.value = null

    try {
        const params = {
            page,
            document_type: filters.value.documentType,
            date_from: filters.value.dateFrom,
            date_to: filters.value.dateTo,
            search: filters.value.search,
            per_page: filters.value.perPage
        }

        const response = await axios.get(route('admin.reports.bsale-documents.list'), { params })

        documents.value = response.data.documents
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            per_page: response.data.per_page,
            total: response.data.total
        }
    } catch (err) {
        error.value = err.response?.data?.error || 'Error al cargar los documentos'
        console.error('Error loading documents:', err)
    } finally {
        loading.value = false
    }
}

// Change page
const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        loadDocuments(page)
    }
}

// Clear all filters
const clearFilters = () => {
    filters.value.documentType = ''
    filters.value.dateFrom = ''
    filters.value.dateTo = ''
    filters.value.search = ''
    filters.value.perPage = 50
    loadDocuments()
}

// Initialize
onMounted(() => {
    loadDocuments()
})
</script>
