<template>
    <AdminLayout title="Registro de Cuotas">
        <Head title="Registro de Cuotas" />
        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Header con botones de acción -->
                <div class="flex items-center justify-between">
                    <ReportsHeader
                        title="Registro de Cuotas"
                        subtitle="Registro de cuotas de suscripción pagadas"
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
                        <!-- Programas (Autocomplete) -->
                        <div class="relative flex-1 min-w-[250px]" ref="programDropdown">
                            <input
                                v-model="programSearch"
                                type="text"
                                placeholder="Todos los programas"
                                @focus="showProgramDropdown = true"
                                @input="showProgramDropdown = true"
                                class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none pr-10"
                            />
                            <button
                                v-if="filters.program_id"
                                @click="clearProgramFilter"
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            <span v-else class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                            <!-- Dropdown opciones -->
                            <div
                                v-if="showProgramDropdown && filteredPrograms.length > 0"
                                class="absolute z-[100] w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                            >
                                <div
                                    v-for="program in filteredPrograms"
                                    :key="program.id"
                                    @click="selectProgram(program)"
                                    class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm text-gray-700"
                                >
                                    {{ program.code }} - {{ program.name }}
                                </div>
                            </div>
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
                                placeholder="Buscar por participante..."
                                class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none"
                            >
                        </div>

                        <!-- Estado Participante -->
                        <div class="relative min-w-[150px]">
                            <select
                                v-model="filters.participant_status"
                                class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                                @change="applyFilters"
                            >
                                <option value="">Todos</option>
                                <option value="active">Activos</option>
                                <option value="inactive">De baja</option>
                            </select>
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

                <!-- Tabla de Cuotas Pagadas -->
                <div v-if="paidInstallmentsData && paidInstallmentsData.data && paidInstallmentsData.data.length > 0">
                    <div class="bg-white rounded-[20px] overflow-hidden">
                        <!-- Table Container -->
                        <div class="flex flex-col gap-0 overflow-x-auto">
                            <!-- Table Header -->
                            <div class="bg-turquesa rounded-t-[20px] px-5 py-[11px] grid grid-cols-[90px_1fr_150px_100px_80px_70px_100px_90px] items-center gap-2 min-w-[850px] h-[61.51px]">
                                <div class="text-white font-nexa-bold text-[13px] leading-[18px] text-center">
                                    Fecha
                                </div>
                                <div class="text-white font-nexa-bold text-[13px] leading-[18px] text-left pl-2">
                                    Suscriptor
                                </div>
                                <div class="text-white font-nexa-bold text-[13px] leading-[18px] text-center">
                                    Cód. Participante
                                </div>
                                <div class="text-white font-nexa-bold text-[13px] leading-[18px] text-right pr-2">
                                    Monto Cuota
                                </div>
                                <div class="text-white font-nexa-bold text-[13px] leading-[18px] text-center">
                                    Boleta
                                </div>
                                <div class="text-white font-nexa-bold text-[13px] leading-[18px] text-center">
                                    Cuota
                                </div>
                                <div class="text-white font-nexa-bold text-[13px] leading-[18px] text-right pr-2">
                                    Total Pagado
                                </div>
                                <div class="text-white font-nexa-bold text-[13px] leading-[18px] text-right pr-2">
                                    Saldo
                                </div>
                            </div>

                            <!-- Table Body -->
                            <div class="flex flex-col min-w-[850px]">
                                <div
                                    v-for="(record, index) in paidInstallmentsData.data"
                                    :key="record.id"
                                    :class="[
                                        'px-5 py-[12px] grid grid-cols-[90px_1fr_150px_100px_80px_70px_100px_90px] items-center gap-2',
                                        index % 2 === 0 ? 'bg-white' : 'bg-[#f9f9f9]',
                                    ]"
                                >
                                    <!-- Fecha -->
                                    <div class="text-[#5b5b5b] font-nexa-regular text-[12px] leading-[18px] text-center">
                                        {{ record.paid_at || '-' }}
                                    </div>

                                    <!-- Suscriptor -->
                                    <div class="text-[#5b5b5b] font-nexa-bold text-[13px] leading-[18px] text-left pl-2 truncate" :title="record.subscriber_name">
                                        {{ record.subscriber_name }}
                                    </div>

                                    <!-- Cód. Participante -->
                                    <div class="text-[#1c4f4a] font-nexa-bold text-[13px] leading-[18px] text-center">
                                        {{ record.enrollment_code }}
                                    </div>

                                    <!-- Monto de Cuota -->
                                    <div class="text-[#1c4f4a] font-nexa-bold text-[13px] leading-[18px] text-right pr-2">
                                        {{ formatCurrency(record.amount) }}
                                    </div>

                                    <!-- Boleta -->
                                    <div class="text-[#5b5b5b] font-nexa-regular text-[12px] leading-[18px] text-center">
                                        {{ record.bsale_number || '-' }}
                                    </div>

                                    <!-- Cuota -->
                                    <div class="text-[#5b5b5b] font-nexa-bold text-[13px] leading-[18px] text-center">
                                        {{ record.installment_label }}
                                    </div>

                                    <!-- Total Pagado -->
                                    <div class="text-[#1c4f4a] font-nexa-bold text-[13px] leading-[18px] text-right pr-2">
                                        {{ formatCurrency(record.total_paid) }}
                                    </div>

                                    <!-- Saldo -->
                                    <div :class="[
                                        'font-nexa-bold text-[13px] leading-[18px] text-right pr-2',
                                        record.saldo > 0 ? 'text-red-600' : 'text-green-600'
                                    ]">
                                        {{ formatCurrency(record.saldo) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paginación -->
                    <div class="mt-6 flex flex-row items-center justify-end gap-4 relative">
                        <div class="text-[#9ca3af] font-normal text-sm">
                            Total {{ paidInstallmentsData.total }} Registros
                        </div>
                        <div class="flex flex-row gap-1.5 items-center justify-center">
                            <div
                                @click="paidInstallmentsData.current_page > 1 && goToPage(paidInstallmentsData.current_page - 1)"
                                :class="['flex w-8 h-8 items-center justify-center rounded-full transition-colors', paidInstallmentsData.current_page > 1 ? 'cursor-pointer bg-white border border-[#e5e7eb] text-[#6b7280] hover:border-[#1c4f4a]' : 'cursor-not-allowed bg-[#f3f4f6] text-[#d1d5db]']"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            </div>
                            <template v-for="item in visiblePages" :key="item.key">
                                <div v-if="item.type === 'page'" @click="goToPage(item.value)" :class="['flex w-8 h-8 items-center justify-center rounded-full cursor-pointer transition-colors', paidInstallmentsData.current_page === item.value ? 'bg-[#1c4f4a] text-white' : 'bg-white border border-[#e5e7eb] text-[#6b7280] hover:border-[#1c4f4a]']">
                                    <span class="text-sm font-medium">{{ item.value }}</span>
                                </div>
                                <div v-else class="flex w-8 h-8 items-center justify-center text-[#9ca3af] text-sm">...</div>
                            </template>
                            <div
                                @click="paidInstallmentsData.current_page < paidInstallmentsData.last_page && goToPage(paidInstallmentsData.current_page + 1)"
                                :class="['flex w-8 h-8 items-center justify-center rounded-full transition-colors', paidInstallmentsData.current_page < paidInstallmentsData.last_page ? 'cursor-pointer bg-white border border-[#e5e7eb] text-[#6b7280] hover:border-[#1c4f4a]' : 'cursor-not-allowed bg-[#f3f4f6] text-[#d1d5db]']"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado vacío -->
                <div v-else class="bg-white rounded-lg shadow p-8 text-center">
                    <div class="text-gray-500 text-lg">
                        No se encontraron cuotas pagadas
                    </div>
                    <p class="text-gray-400 mt-2">
                        Aún no hay cuotas registradas como pagadas
                    </p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ReportsHeader from '@/Components/Reports/ReportsHeader.vue'

// Props
const props = defineProps({
    paidInstallmentsData: Object,
    programs: Array,
    filters: Object
})

// Reactive data
const filters = reactive({
    program_id: props.filters?.program_id || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    search: props.filters?.search || '',
    participant_status: props.filters?.participant_status || ''
})

const isExporting = ref(false)
let searchTimeout = null

// Program autocomplete
const programSearch = ref('')
const showProgramDropdown = ref(false)
const programDropdown = ref(null)

// Inicializar texto del programa seleccionado
if (props.filters?.program_id) {
    const selected = props.programs?.find(p => p.id == props.filters.program_id)
    if (selected) {
        programSearch.value = `${selected.code} - ${selected.name}`
    }
}

const filteredPrograms = computed(() => {
    const term = programSearch.value.trim().toLowerCase()
    if (!term) return props.programs || []
    return (props.programs || []).filter(p => {
        const code = (p.code || '').toLowerCase()
        const name = (p.name || '').toLowerCase()
        return code.includes(term) || name.includes(term)
    })
})

const selectProgram = (program) => {
    filters.program_id = program.id
    programSearch.value = `${program.code} - ${program.name}`
    showProgramDropdown.value = false
    applyFilters()
}

const clearProgramFilter = () => {
    filters.program_id = ''
    programSearch.value = ''
    applyFilters()
}

const handleClickOutsideProgram = (event) => {
    if (programDropdown.value && !programDropdown.value.contains(event.target)) {
        showProgramDropdown.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutsideProgram)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutsideProgram)
})

// Methods
const formatCurrency = (amount) => {
    if (!amount) return '$0'
    return '$' + parseInt(amount).toLocaleString('es-CL')
}

// Computed properties for pagination
const visiblePages = computed(() => {
    if (!props.paidInstallmentsData || !props.paidInstallmentsData.last_page) return [{ type: 'page', value: 1, key: 'page-1' }]
    const total = props.paidInstallmentsData.last_page
    const current = props.paidInstallmentsData.current_page
    const items = []
    if (total <= 7) {
        for (let i = 1; i <= total; i++) items.push({ type: 'page', value: i, key: `page-${i}` })
        return items
    }
    items.push({ type: 'page', value: 1, key: 'page-1' })
    if (current <= 3) {
        for (let i = 2; i <= 4; i++) items.push({ type: 'page', value: i, key: `page-${i}` })
        items.push({ type: 'ellipsis', key: 'ellipsis-end' })
    } else if (current >= total - 2) {
        items.push({ type: 'ellipsis', key: 'ellipsis-start' })
        for (let i = total - 3; i <= total - 1; i++) items.push({ type: 'page', value: i, key: `page-${i}` })
    } else {
        items.push({ type: 'ellipsis', key: 'ellipsis-start' })
        for (let i = current - 1; i <= current + 1; i++) items.push({ type: 'page', value: i, key: `page-${i}` })
        items.push({ type: 'ellipsis', key: 'ellipsis-end' })
    }
    items.push({ type: 'page', value: total, key: `page-${total}` })
    return items
})

const applyFilters = () => {
    router.get(route('admin.reports.paid-installments'), filters, {
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
    router.get(route('admin.reports.paid-installments'), { ...filters, page }, {
        preserveState: true,
        preserveScroll: true
    })
}

const clearFilters = () => {
    filters.program_id = ''
    filters.date_from = ''
    filters.date_to = ''
    filters.search = ''
    filters.participant_status = ''
    programSearch.value = ''
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

    const url = route('admin.reports.export.paid-installments') + (params.toString() ? '?' + params.toString() : '')

    window.open(url, '_blank')

    setTimeout(() => {
        isExporting.value = false
    }, 1000)
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
