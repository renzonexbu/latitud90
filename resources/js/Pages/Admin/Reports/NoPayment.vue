<template>
    <AdminLayout title="Participantes Sin Pagos">
        <Head title="Participantes Sin Pagos" />
        <div class="py-12">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Header con botones de acción -->
                <div class="flex items-center justify-between">
                    <ReportsHeader
                        title="Participantes Sin Pagos Iniciados"
                        subtitle="Participantes que no han iniciado el proceso de pago por ecommerce"
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
                            Volver al Cronograma
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
                        <div class="relative flex-1 min-w-[250px]">
                            <select
                                v-model="filters.program_id"
                                class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                                @change="applyFilters"
                            >
                                <option value="">Todos los programas</option>
                                <option v-for="program in programs" :key="program.id" :value="program.id">
                                    {{ program.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Ejecutivos -->
                        <div class="relative flex-1 min-w-[250px]">
                            <select
                                v-model="filters.sales_executive_id"
                                class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                                @change="applyFilters"
                            >
                                <option value="">Todos los ejecutivos</option>
                                <option v-for="executive in salesExecutives" :key="executive.id" :value="executive.id">
                                    {{ executive.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Búsqueda -->
                        <div class="relative flex-1 min-w-[250px]">
                            <input
                                v-model="filters.search"
                                @input="debounceSearch"
                                type="text"
                                placeholder="Buscar por nombre o documento..."
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

                <!-- Tabla de Participantes -->
                <div v-if="participantsWithoutPayments && participantsWithoutPayments.data && participantsWithoutPayments.data.length > 0">
                    <div class="bg-white rounded-[20px] overflow-hidden">
                        <!-- Table Container -->
                        <div class="flex flex-col gap-0">
                            <!-- Table Header -->
                            <div class="bg-turquesa rounded-t-[20px] px-5 py-[11px] flex items-center justify-between h-[61.51px]">
                                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[160px]">
                                    Participante
                                </div>
                                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]">
                                    Documento
                                </div>
                                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]">
                                    Contacto Emergencia
                                </div>
                                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[160px]">
                                    Email Contacto
                                </div>
                                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]">
                                    Fecha Inscripción
                                </div>
                                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]">
                                    Monto a Pagar
                                </div>
                                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]">
                                    Programa
                                </div>
                            </div>

                            <!-- Table Body -->
                            <div class="flex flex-col">
                                <div
                                    v-for="(participant, index) in participantsWithoutPayments.data"
                                    :key="participant.participant_id"
                                    :class="[
                                        'px-5 py-[14px] flex items-center justify-between',
                                        index % 2 === 0 ? 'bg-white' : 'bg-[#f9f9f9]',
                                    ]"
                                >
                                    <!-- Participante -->
                                    <div class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[160px]">
                                        {{ toCapitalCase(participant.first_name) }} {{ toCapitalCase(participant.first_last_name) }} {{ toCapitalCase(participant.second_last_name) }}
                                    </div>

                                    <!-- Documento -->
                                    <div class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]">
                                        {{ formatDocument(participant.document_number, participant.document_type) }}
                                    </div>

                                    <!-- Contacto de Emergencia -->
                                    <div class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]">
                                        {{ toCapitalCase(participant.emergency_contact_name) || 'Sin contacto' }}
                                    </div>

                                    <!-- Email Contacto -->
                                    <div class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[160px]">
                                        {{ participant.emergency_contact_email || 'Sin email' }}
                                    </div>

                                    <!-- Fecha Inscripción -->
                                    <div class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]">
                                        {{ formatDate(participant.incorporation_date) }}
                                    </div>

                                    <!-- Monto a Pagar -->
                                    <div class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]">
                                        <div>${{ formatCurrency(participant.final_amount) }}</div>
                                        <div v-if="participant.discount_amount > 0" class="text-[12px] text-[#4b8d7f]">
                                            Desc: ${{ formatCurrency(participant.discount_amount) }}
                                        </div>
                                    </div>

                                    <!-- Programa -->
                                    <div class="text-[#1c4f4a] font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]">
                                        <div>{{ participant.program_name }}</div>
                                        <div class="text-[12px] text-[#888888]">{{ participant.program_code }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paginación -->
                    <div class="mt-6 flex flex-row items-center justify-end gap-6 relative">
                        <!-- Total Items -->
                        <div class="text-[#9ca3af] font-normal text-sm">
                            Total {{ participantsWithoutPayments.total }} Registros
                        </div>
                        
                        <!-- Page Numbers -->
                        <div class="flex flex-row gap-2 items-center justify-center">
                            <div
                                v-for="page in validPages"
                                :key="page"
                                @click="goToPage(page)"
                                :class="[
                                    'flex w-8 h-8 items-center justify-center rounded-full cursor-pointer transition-colors',
                                    participantsWithoutPayments.current_page === page
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
                        No se encontraron participantes sin pagos iniciados
                    </div>
                    <p class="text-gray-400 mt-2">
                        Todos los participantes han iniciado su proceso de pago o están exentos
                    </p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ReportsHeader from '@/Components/Reports/ReportsHeader.vue'

// Props
const props = defineProps({
    participantsWithoutPayments: Object,
    programs: Array,
    salesExecutives: Array,
    filters: Object
})

// Reactive data - Initialize with empty strings to show default options
const filters = reactive({
    program_id: props.filters?.program_id || '',
    sales_executive_id: props.filters?.sales_executive_id || '',
    search: props.filters?.search || ''
})

const isExporting = ref(false)
let searchTimeout = null

// Methods
const formatDocument = (documentNumber, documentType) => {
    if (documentType === 'rut') {
        return formatRut(documentNumber)
    }
    return documentNumber
}

const formatRut = (rut) => {
    if (!rut) return ''
    
    // Remove any existing formatting
    const cleanRut = rut.toString().replace(/[^0-9kK]/g, '')
    
    if (cleanRut.length < 2) return cleanRut
    
    // Separate the verification digit
    const body = cleanRut.slice(0, -1)
    const dv = cleanRut.slice(-1).toUpperCase()
    
    // Add dots to the body
    const formattedBody = body.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
    
    return `${formattedBody}-${dv}`
}

const toCapitalCase = (str) => {
    if (!str) return ''
    return str.toLowerCase().replace(/\b\w/g, l => l.toUpperCase())
}

const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleDateString('es-CL')
}

const formatCurrency = (amount) => {
    if (!amount) return '0'
    return new Intl.NumberFormat('es-CL').format(amount)
}

// Computed properties for pagination
const validPages = computed(() => {
    if (!props.participantsWithoutPayments || !props.participantsWithoutPayments.last_page) {
        return [1]
    }
    
    const pages = []
    const totalPages = props.participantsWithoutPayments.last_page
    
    for (let i = 1; i <= Math.min(totalPages, 10); i++) {
        pages.push(i)
    }
    
    return pages
})

const applyFilters = () => {
    router.get(route('admin.reports.no-payment'), filters, {
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
    router.get(route('admin.reports.no-payment'), { ...filters, page }, {
        preserveState: true,
        preserveScroll: true
    })
}

const clearFilters = () => {
    filters.program_id = ''
    filters.sales_executive_id = ''
    filters.search = ''
    applyFilters()
}

const goBack = () => {
    router.get(route('admin.reports.payment-schedule'))
}

const exportToExcel = () => {
    isExporting.value = true

    // Crear parámetros de query
    const params = new URLSearchParams()

    if (filters.program_id) params.append('program_id', filters.program_id)
    if (filters.sales_executive_id) params.append('sales_executive_id', filters.sales_executive_id)
    if (filters.search) params.append('search', filters.search)

    // Crear la URL con los parámetros
    const url = route('admin.reports.export.no-payment') + (params.toString() ? '?' + params.toString() : '')

    // Abrir la URL en una nueva ventana para descargar
    window.open(url, '_blank')

    // Resetear el estado después de un segundo
    setTimeout(() => {
        isExporting.value = false
    }, 1000)
}
</script>

<style scoped>
/* Custom font classes */
.font-nexa-bold {
    font-family: "Nexa-Bold", sans-serif;
    font-weight: 700;
}

.font-nexa-xbold {
    font-family: "Nexa-XBold", sans-serif;
    font-weight: 400;
}

.bg-turquesa {
    background-color: #007e93;
}
</style>