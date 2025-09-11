<template>
    <AdminLayout>
        <Head title="Documentos BSale" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">
                                    Documentos BSale
                                </h2>
                                <p class="text-sm text-gray-600 mt-1">
                                    Gestión y descarga de documentos PDF generados por BSale
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
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Year Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Año
                                </label>
                                <select
                                    v-model="filters.year"
                                    @change="loadDocuments"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="">Todos los años</option>
                                    <option v-for="year in availableYears" :key="year" :value="year">
                                        {{ year }}
                                    </option>
                                </select>
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
                                    placeholder="Número BSale, ID de pago o nombre de archivo..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>

                            <!-- Per Page -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Documentos por página
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
                        </div>
                    </div>
                </div>

                <!-- Documents List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Documentos Almacenados
                                <span v-if="pagination.total" class="text-sm font-normal text-gray-500">
                                    ({{ pagination.total }} documentos)
                                </span>
                            </h3>
                            <div class="flex space-x-2">
                                <button
                                    @click="downloadAllDocuments"
                                    :disabled="loading || downloadingZip || documents.length === 0"
                                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg flex items-center disabled:opacity-50"
                                >
                                    <svg
                                        class="w-4 h-4 mr-2"
                                        :class="{ 'animate-spin': downloadingZip }"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 10v6m0 0l-3-3m3 3l3-3M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                        ></path>
                                    </svg>
                                    {{ downloadingZip ? 'Generando ZIP...' : 'Descargar Todo (ZIP)' }}
                                </button>
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
                            <p class="text-gray-600">No se encontraron documentos BSale</p>
                            <p class="text-sm text-gray-500 mt-1">
                                Los documentos se generan automáticamente cuando se procesan pagos con BSale
                            </p>
                        </div>

                        <!-- Documents Table -->
                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Documento
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Número BSale
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            ID Pago
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Año
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Tamaño
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Fecha Modificación
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="document in documents" :key="document.filename" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                </svg>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ document.filename }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span v-if="document.bsale_number" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ document.bsale_number }}
                                            </span>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span v-if="document.payment_id" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                #{{ document.payment_id }}
                                            </span>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span v-if="document.year" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ document.year }}
                                            </span>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ document.size_formatted }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ document.modified_at_formatted }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a
                                                :href="route('admin.reports.bsale-documents.download', document.filename)"
                                                target="_blank"
                                                class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                            >
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                Descargar
                                            </a>
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
                                de {{ pagination.total }} documentos
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
const availableYears = ref([])
const loading = ref(false)
const downloadingZip = ref(false)
const error = ref(null)

const filters = ref({
    year: '',
    search: '',
    perPage: 50
})

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 50,
    total: 0
})

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
            year: filters.value.year,
            search: filters.value.search,
            per_page: filters.value.perPage
        }

        const response = await axios.get(route('admin.reports.bsale-documents.list'), { params })
        
        documents.value = response.data.documents
        availableYears.value = response.data.available_years
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            per_page: response.data.per_page,
            total: response.data.total
        }
    } catch (err) {
        error.value = err.response?.data?.error || 'Error al cargar los documentos'
        console.error('Error loading BSale documents:', err)
    } finally {
        loading.value = false
    }
}

// Download all documents as ZIP
const downloadAllDocuments = async () => {
    downloadingZip.value = true
    
    try {
        // Build URL with query parameters
        const baseUrl = route('admin.reports.bsale-documents.download-zip')
        const params = new URLSearchParams()
        
        if (filters.value.year) {
            params.append('year', filters.value.year)
        }
        if (filters.value.search) {
            params.append('search', filters.value.search)
        }
        
        const url = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl

        // Create a temporary link to trigger download
        const link = document.createElement('a')
        link.href = url
        link.download = ''
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        
    } catch (err) {
        error.value = err.response?.data?.error || 'Error al descargar los documentos en ZIP'
        console.error('Error downloading BSale documents ZIP:', err)
    } finally {
        downloadingZip.value = false
    }
}

// Change page
const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        loadDocuments(page)
    }
}

// Initialize
onMounted(() => {
    loadDocuments()
})
</script>