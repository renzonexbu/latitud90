<template>
    <AdminLayout title="Aceptación de Términos y Condiciones">
        <Head title="Aceptación de Términos y Condiciones" />
        <div class="py-12">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Header con botones de acción -->
                <div class="flex items-center justify-between">
                    <ReportsHeader
                        title="Aceptación de Términos y Condiciones"
                        subtitle="Registro de quienes aceptaron los términos y condiciones comerciales"
                    />

                    <div class="flex gap-3">
                        <!-- Botón Volver -->
                        <button
                            @click="goBack"
                            class="h-[46px] px-6 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-[50px] border border-gray-300 font-nexa-regular text-[12px] leading-[18px] font-normal transition-colors duration-200 flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Volver
                        </button>

                        <!-- Botón Descargar PDFs seleccionados -->
                        <button
                            v-if="selectedIds.length > 0"
                            @click="downloadSelectedPdfs"
                            :disabled="isDownloadingMultiple"
                            class="h-[46px] px-6 bg-[#007e93] hover:bg-[#006577] text-white rounded-[50px] border-none font-nexa-regular text-[12px] leading-[18px] font-normal transition-colors duration-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg v-if="!isDownloadingMultiple" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11v6m0 0l-3-3m3 3l3-3"></path>
                            </svg>
                            <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ isDownloadingMultiple ? 'Descargando...' : `Descargar PDFs (${selectedIds.length})` }}
                        </button>

                        <!-- Botón Exportar -->
                        <button
                            @click="exportToExcel"
                            :disabled="isExporting"
                            class="h-[46px] px-6 bg-[#1c4f4a] hover:bg-[#163d39] text-white rounded-[50px] border-none font-nexa-regular text-[12px] leading-[18px] font-normal transition-colors duration-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg v-if="!isExporting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ isExporting ? 'Exportando...' : 'Exportar a Excel' }}
                        </button>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="flex flex-col gap-[18px] items-start justify-start relative bg-white rounded-lg shadow p-6">
                    <!-- Header Row con título -->
                    <div class="flex flex-row items-center justify-between w-full relative">
                        <!-- Título -->
                        <div class="text-[20px] leading-7 font-normal text-[#1c4f4a] font-nexa-regular">
                            Filtros de búsqueda
                        </div>
                    </div>

                    <!-- Filtros Row -->
                    <div class="flex flex-wrap gap-[15px] items-center justify-start w-full relative">
                        <!-- Programas -->
                        <div class="relative flex-1 min-w-[200px]">
                            <select
                                v-model="filters.program_id"
                                class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                                @change="applyFilters"
                            >
                                <option value="">Todos los programas</option>
                                <option v-for="program in programs" :key="program.id" :value="program.id">
                                    {{ program.code }} - {{ program.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Fecha Desde -->
                        <div class="relative min-w-[180px]">
                            <input
                                v-model="filters.date_from"
                                type="date"
                                class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none"
                                @change="applyFilters"
                            >
                        </div>

                        <!-- Fecha Hasta -->
                        <div class="relative min-w-[180px]">
                            <input
                                v-model="filters.date_to"
                                type="date"
                                class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none"
                                @change="applyFilters"
                            >
                        </div>

                        <!-- Búsqueda -->
                        <div class="relative flex-1 min-w-[250px]">
                            <input
                                v-model="filters.search"
                                @input="debounceSearch"
                                type="text"
                                placeholder="Buscar por nombre, documento o email..."
                                class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none"
                            >
                        </div>

                        <!-- Clear Filters Button -->
                        <button
                            @click="clearFilters"
                            class="h-[46px] px-6 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-[50px] border border-gray-300 text-left font-nexa-regular text-[12px] leading-[18px] font-normal transition-colors duration-200 flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Limpiar filtros
                        </button>
                    </div>
                </div>

                <!-- Tabla de Aceptación -->
                <div v-if="termsAcceptanceData && termsAcceptanceData.data && termsAcceptanceData.data.length > 0">
                    <div class="bg-white rounded-[20px] overflow-hidden">
                        <!-- Table Container -->
                        <div class="flex flex-col gap-0 overflow-x-auto">
                            <!-- Table Header -->
                            <div class="bg-turquesa rounded-t-[20px] px-5 py-[11px] flex items-center justify-between min-w-[1350px] h-[61.51px]">
                                <!-- Checkbox Seleccionar Todos -->
                                <div class="flex items-center justify-center w-[50px]">
                                    <input
                                        type="checkbox"
                                        ref="selectAllCheckbox"
                                        :checked="isAllSelected"
                                        @change="toggleSelectAll"
                                        class="w-4 h-4 text-[#1c4f4a] bg-white border-gray-300 rounded focus:ring-[#1c4f4a] focus:ring-2 cursor-pointer"
                                    >
                                </div>
                                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[150px]">
                                    Nombre
                                </div>
                                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]">
                                    RUT/Documento
                                </div>
                                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]">
                                    Email
                                </div>
                                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]">
                                    Fecha Aceptación
                                </div>
                                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]">
                                    IP
                                </div>
                                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]">
                                    Navegador
                                </div>
                                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]">
                                    Sistema Operativo
                                </div>
                                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[150px]">
                                    Programa
                                </div>
                                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[100px]">
                                    Acciones
                                </div>
                            </div>

                            <!-- Table Body -->
                            <div class="flex flex-col min-w-[1350px]">
                                <div
                                    v-for="(record, index) in termsAcceptanceData.data"
                                    :key="record.id"
                                    :class="[
                                        'px-5 py-[14px] flex items-center justify-between',
                                        index % 2 === 0 ? 'bg-white' : 'bg-[#f9f9f9]',
                                        selectedIds.includes(record.id) ? 'bg-[#e8f4f6]' : '',
                                    ]"
                                >
                                    <!-- Checkbox -->
                                    <div class="flex items-center justify-center w-[50px]">
                                        <input
                                            type="checkbox"
                                            :value="record.id"
                                            v-model="selectedIds"
                                            class="w-4 h-4 text-[#1c4f4a] bg-white border-gray-300 rounded focus:ring-[#1c4f4a] focus:ring-2 cursor-pointer"
                                        >
                                    </div>

                                    <!-- Nombre -->
                                    <div class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[150px]">
                                        {{ toCapitalCase(record.name) }}
                                    </div>

                                    <!-- Documento -->
                                    <div class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]">
                                        {{ formatDocument(record.document_number, record.document_type) }}
                                    </div>

                                    <!-- Email -->
                                    <div class="text-[#5b5b5b] font-nexa-regular text-[12px] leading-[18px] text-center w-[180px] truncate" :title="record.email">
                                        {{ record.email }}
                                    </div>

                                    <!-- Fecha Aceptación -->
                                    <div class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]">
                                        {{ record.terms_accepted_at || '-' }}
                                    </div>

                                    <!-- IP -->
                                    <div class="text-[#5b5b5b] font-nexa-regular text-[12px] leading-[18px] text-center w-[120px]">
                                        {{ record.ip_address || '-' }}
                                    </div>

                                    <!-- Navegador -->
                                    <div class="text-[#5b5b5b] font-nexa-regular text-[12px] leading-[18px] text-center w-[120px]">
                                        {{ record.browser || '-' }}
                                    </div>

                                    <!-- Sistema Operativo -->
                                    <div class="text-[#5b5b5b] font-nexa-regular text-[12px] leading-[18px] text-center w-[120px]">
                                        {{ record.operating_system || '-' }}
                                    </div>

                                    <!-- Programa -->
                                    <div class="text-[#1c4f4a] font-nexa-bold text-[14px] leading-[18px] text-center w-[150px]">
                                        <div class="truncate" :title="`${record.program_code} - ${record.program_name}`">{{ record.program_code }} - {{ record.program_name }}</div>
                                    </div>

                                    <!-- Acciones -->
                                    <div class="flex items-center justify-center w-[100px] gap-2">
                                        <!-- Botón descargar PDF -->
                                        <button
                                            @click="downloadPdf(record.id)"
                                            :disabled="downloadingPdfId === record.id"
                                            class="p-2 rounded-full hover:bg-gray-100 transition-colors duration-200 disabled:opacity-50"
                                            title="Descargar evidencia PDF"
                                        >
                                            <svg v-if="downloadingPdfId !== record.id" class="w-5 h-5 text-[#1c4f4a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11v6m0 0l-3-3m3 3l3-3"></path>
                                            </svg>
                                            <svg v-else class="w-5 h-5 text-[#1c4f4a] animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paginación -->
                    <div class="mt-6 flex flex-row items-center justify-end gap-6 relative">
                        <!-- Total Items -->
                        <div class="text-[#9ca3af] font-normal text-sm">
                            Total {{ termsAcceptanceData.total }} Registros
                        </div>

                        <!-- Page Numbers -->
                        <div class="flex flex-row gap-2 items-center justify-center">
                            <div
                                v-for="page in validPages"
                                :key="page"
                                @click="goToPage(page)"
                                :class="[
                                    'flex w-8 h-8 items-center justify-center rounded-full cursor-pointer transition-colors',
                                    termsAcceptanceData.current_page === page
                                        ? 'bg-[#1c4f4a] text-white'
                                        : 'bg-white border border-[#e5e7eb] text-[#6b7280] hover:border-[#1c4f4a]',
                                ]"
                            >
                                <span class="text-sm font-medium">
                                    {{ page }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado vacío -->
                <div v-else class="bg-white rounded-lg shadow p-8 text-center">
                    <div class="text-gray-500 text-lg">
                        No se encontraron registros de aceptación de términos
                    </div>
                    <p class="text-gray-400 mt-2">
                        Aún no hay compradores que hayan aceptado los términos y condiciones
                    </p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ReportsHeader from '@/Components/Reports/ReportsHeader.vue'

// Props
const props = defineProps({
    termsAcceptanceData: Object,
    programs: Array,
    filters: Object
})

// Reactive data
const filters = reactive({
    program_id: props.filters?.program_id || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    search: props.filters?.search || ''
})

const isExporting = ref(false)
const downloadingPdfId = ref(null)
const selectedIds = ref([])
const isDownloadingMultiple = ref(false)
const selectAllCheckbox = ref(null)
let searchTimeout = null

// Computed properties for selection
const isAllSelected = computed(() => {
    if (!props.termsAcceptanceData?.data?.length) return false
    return props.termsAcceptanceData.data.every(record => selectedIds.value.includes(record.id))
})

const isIndeterminate = computed(() => {
    if (!props.termsAcceptanceData?.data?.length) return false
    const selectedCount = props.termsAcceptanceData.data.filter(record => selectedIds.value.includes(record.id)).length
    return selectedCount > 0 && selectedCount < props.termsAcceptanceData.data.length
})

// Watcher para manejar el estado indeterminate del checkbox
watch(isIndeterminate, (newValue) => {
    nextTick(() => {
        if (selectAllCheckbox.value) {
            selectAllCheckbox.value.indeterminate = newValue
        }
    })
}, { immediate: true })

// Methods
const formatDocument = (documentNumber, documentType) => {
    if (documentType?.toLowerCase() === 'rut') {
        return formatRut(documentNumber)
    }
    return documentNumber
}

const formatRut = (rut) => {
    if (!rut) return ''

    const cleanRut = rut.toString().replace(/[^0-9kK]/g, '')

    if (cleanRut.length < 2) return cleanRut

    const body = cleanRut.slice(0, -1)
    const dv = cleanRut.slice(-1).toUpperCase()

    const formattedBody = body.replace(/\B(?=(\d{3})+(?!\d))/g, '.')

    return `${formattedBody}-${dv}`
}

const toCapitalCase = (str) => {
    if (!str) return ''
    return str.toLowerCase().replace(/\b\w/g, l => l.toUpperCase())
}

// Computed properties for pagination
const validPages = computed(() => {
    if (!props.termsAcceptanceData || !props.termsAcceptanceData.last_page) {
        return [1]
    }

    const pages = []
    const totalPages = props.termsAcceptanceData.last_page

    for (let i = 1; i <= Math.min(totalPages, 10); i++) {
        pages.push(i)
    }

    return pages
})

const applyFilters = () => {
    router.get(route('admin.reports.terms-acceptance'), filters, {
        preserveState: true,
        preserveScroll: true
    })
}

const debounceSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        applyFilters()
    }, 500)
}

const goToPage = (page) => {
    router.get(route('admin.reports.terms-acceptance'), { ...filters, page }, {
        preserveState: true,
        preserveScroll: true
    })
}

const clearFilters = () => {
    filters.program_id = ''
    filters.date_from = ''
    filters.date_to = ''
    filters.search = ''
    applyFilters()
}

const goBack = () => {
    router.get(route('admin.reports.index'))
}

const exportToExcel = () => {
    isExporting.value = true

    const params = new URLSearchParams()

    if (filters.program_id) params.append('program_id', filters.program_id)
    if (filters.date_from) params.append('date_from', filters.date_from)
    if (filters.date_to) params.append('date_to', filters.date_to)
    if (filters.search) params.append('search', filters.search)

    const url = route('admin.reports.export.terms-acceptance') + (params.toString() ? '?' + params.toString() : '')

    window.open(url, '_blank')

    setTimeout(() => {
        isExporting.value = false
    }, 1000)
}

const downloadPdf = (orderDetailId) => {
    downloadingPdfId.value = orderDetailId

    const url = route('admin.reports.download.terms-acceptance-pdf', { orderDetailId })
    window.open(url, '_blank')

    setTimeout(() => {
        downloadingPdfId.value = null
    }, 1000)
}

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        // Deseleccionar todos los de la página actual
        const currentPageIds = props.termsAcceptanceData.data.map(record => record.id)
        selectedIds.value = selectedIds.value.filter(id => !currentPageIds.includes(id))
    } else {
        // Seleccionar todos los de la página actual
        const currentPageIds = props.termsAcceptanceData.data.map(record => record.id)
        const newIds = currentPageIds.filter(id => !selectedIds.value.includes(id))
        selectedIds.value = [...selectedIds.value, ...newIds]
    }
}

const downloadSelectedPdfs = async () => {
    if (selectedIds.value.length === 0) return

    isDownloadingMultiple.value = true

    try {
        // Construir URL con los IDs seleccionados
        const params = new URLSearchParams()
        selectedIds.value.forEach(id => params.append('ids[]', id))

        const url = route('admin.reports.download.terms-acceptance-pdfs-zip') + '?' + params.toString()
        window.open(url, '_blank')

        // Limpiar selección después de descargar
        setTimeout(() => {
            selectedIds.value = []
            isDownloadingMultiple.value = false
        }, 2000)
    } catch (error) {
        console.error('Error al descargar PDFs:', error)
        isDownloadingMultiple.value = false
    }
}
</script>

<style scoped>
.font-nexa-bold {
    font-family: "Nexa-Bold", sans-serif;
    font-weight: 700;
}

.font-nexa-regular {
    font-family: "Nexa-Regular", sans-serif;
    font-weight: 400;
}

.bg-turquesa {
    background-color: #007e93;
}
</style>
