<template>
    <AdminLayout title="Recaudación Global">
        <Head title="Recaudación Global" />
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Header con botones de acción -->
                <div class="flex items-center justify-between">
                    <ReportsHeader
                        title="Recaudación Global"
                        subtitle="Resumen financiero por programa"
                    />

                    <div class="flex gap-3">
                        <button
                            @click="goBack"
                            class="h-[46px] px-6 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-[50px] border border-gray-300 font-nexa-regular text-[12px] leading-[18px] font-normal transition-colors duration-200 flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Volver
                        </button>

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

                <!-- Totales -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg shadow p-4 text-center">
                        <p class="text-sm text-gray-500">Total Recaudado</p>
                        <p class="text-2xl font-bold text-[#1c4f4a]">{{ formatCurrency(totals.total_collected) }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 text-center">
                        <p class="text-sm text-gray-500">Meta</p>
                        <p class="text-2xl font-bold text-[#1c4f4a]">{{ formatCurrency(totals.meta) }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 text-center">
                        <p class="text-sm text-gray-500">Porcentaje</p>
                        <p class="text-2xl font-bold" :class="totals.percentage >= 100 ? 'text-green-600' : 'text-[#1c4f4a]'">{{ totals.percentage }}%</p>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="flex flex-col gap-[18px] items-start justify-start relative bg-white rounded-lg shadow p-6">
                    <div class="flex flex-row items-center justify-between w-full relative">
                        <div class="text-[20px] leading-7 font-normal text-[#1c4f4a] font-nexa-regular">
                            Filtros de búsqueda
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-[15px] items-center justify-start w-full relative">
                        <!-- Programa (búsqueda predictiva) -->
                        <div class="relative flex-1 min-w-[280px]" ref="programSearchContainer">
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <input
                                    v-model="programSearchText"
                                    type="text"
                                    placeholder="Buscar por código o nombre de programa..."
                                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] pl-10 pr-8 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none"
                                    @input="onProgramSearch"
                                    @focus="showProgramDropdown = true"
                                    @keydown.enter.prevent="applyProgramSearch"
                                />
                                <button
                                    v-if="programSearchText"
                                    @click="clearProgramSearch"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <!-- Dropdown de sugerencias -->
                            <div
                                v-if="showProgramDropdown && filteredPrograms.length > 0 && programSearchText.length > 0"
                                class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-[200px] overflow-y-auto"
                            >
                                <div
                                    v-for="program in filteredPrograms"
                                    :key="program.id"
                                    @mousedown.prevent="selectProgram(program)"
                                    class="px-4 py-2 text-xs cursor-pointer hover:bg-[#f0faf9] transition-colors border-b border-gray-100 last:border-b-0"
                                >
                                    <span class="font-bold text-[#1c4f4a]">{{ program.code }}</span>
                                    <span class="text-gray-500 ml-1">{{ program.name }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Fecha Desde (filtra programas por fecha de salida) -->
                        <div class="relative min-w-[180px]">
                            <label class="absolute -top-2 left-4 bg-white px-1 text-[10px] text-gray-400">Salida desde</label>
                            <input
                                v-model="filters.date_from"
                                type="date"
                                class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none"
                                @change="applyFilters"
                            >
                        </div>

                        <!-- Fecha Hasta -->
                        <div class="relative min-w-[180px]">
                            <label class="absolute -top-2 left-4 bg-white px-1 text-[10px] text-gray-400">Salida hasta</label>
                            <input
                                v-model="filters.date_to"
                                type="date"
                                class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none"
                                @change="applyFilters"
                            >
                        </div>

                        <!-- Ordenar por -->
                        <div class="relative min-w-[200px]">
                            <select
                                v-model="sortValue"
                                class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                                @change="applySort"
                            >
                                <option value="program_code|asc">N° Programa (A-Z)</option>
                                <option value="program_code|desc">N° Programa (Z-A)</option>
                                <option value="total_to_collect|desc">Total a Recaudar (Mayor)</option>
                                <option value="total_to_collect|asc">Total a Recaudar (Menor)</option>
                                <option value="payer_payments|desc">Abono Pagadores (Mayor)</option>
                                <option value="balance|desc">Saldo (Mayor)</option>
                                <option value="balance|asc">Saldo (Menor)</option>
                            </select>
                        </div>

                        <!-- Limpiar filtros -->
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

                <!-- Tabla -->
                <div v-if="reportData && reportData.data && reportData.data.length > 0">
                    <div class="bg-white rounded-[20px] overflow-hidden">
                        <div class="flex flex-col gap-0">
                            <!-- Table Header -->
                            <div class="bg-turquesa rounded-t-[20px] px-5 py-[11px] flex items-center justify-between h-[61.51px]">
                                <div class="text-white font-nexa-bold text-[13px] leading-[18px] text-center w-[140px]">
                                    N° de Programa
                                </div>
                                <div class="text-white font-nexa-bold text-[13px] leading-[18px] text-center flex-1">
                                    Total a Recaudar
                                </div>
                                <div class="text-white font-nexa-bold text-[13px] leading-[18px] text-center flex-1">
                                    Abono Pagadores
                                </div>
                                <div class="text-white font-nexa-bold text-[13px] leading-[18px] text-center flex-1">
                                    Aporte/Beca
                                </div>
                                <div class="text-white font-nexa-bold text-[13px] leading-[18px] text-center flex-1">
                                    Monto Liberado
                                </div>
                                <div class="text-white font-nexa-bold text-[13px] leading-[18px] text-center flex-1">
                                    Saldo
                                </div>
                            </div>

                            <!-- Table Body -->
                            <div class="flex flex-col">
                                <div
                                    v-for="(record, index) in reportData.data"
                                    :key="record.program_id"
                                    :class="[
                                        'px-5 py-[14px] flex items-center justify-between',
                                        index % 2 === 0 ? 'bg-white' : 'bg-[#f9f9f9]',
                                    ]"
                                >
                                    <div class="text-[#1c4f4a] font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]">
                                        {{ record.program_code }}
                                    </div>
                                    <div class="text-[#1c4f4a] font-nexa-bold text-[14px] leading-[18px] text-center flex-1">
                                        {{ formatCurrency(record.total_to_collect) }}
                                    </div>
                                    <div class="text-[#5b5b5b] font-nexa-regular text-[14px] leading-[18px] text-center flex-1">
                                        {{ formatCurrency(record.payer_payments) }}
                                    </div>
                                    <div class="text-[#5b5b5b] font-nexa-regular text-[14px] leading-[18px] text-center flex-1">
                                        {{ formatCurrency(record.aporte_beca) }}
                                    </div>
                                    <div class="text-[#5b5b5b] font-nexa-regular text-[14px] leading-[18px] text-center flex-1">
                                        {{ formatCurrency(record.released) }}
                                    </div>
                                    <div class="font-nexa-bold text-[14px] leading-[18px] text-center flex-1" :class="record.balance > 0 ? 'text-red-600' : record.balance < 0 ? 'text-blue-600' : 'text-green-600'">
                                        {{ formatBalance(record.balance) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paginación -->
                    <div class="mt-6 flex flex-row items-center justify-end gap-4 relative">
                        <div class="text-[#9ca3af] font-normal text-sm">
                            Total {{ reportData.total }} Programas
                        </div>
                        <div class="flex flex-row gap-1.5 items-center justify-center">
                            <div
                                @click="reportData.current_page > 1 && goToPage(reportData.current_page - 1)"
                                :class="['flex w-8 h-8 items-center justify-center rounded-full transition-colors', reportData.current_page > 1 ? 'cursor-pointer bg-white border border-[#e5e7eb] text-[#6b7280] hover:border-[#1c4f4a]' : 'cursor-not-allowed bg-[#f3f4f6] text-[#d1d5db]']"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            </div>
                            <template v-for="item in visiblePages" :key="item.key">
                                <div v-if="item.type === 'page'" @click="goToPage(item.value)" :class="['flex w-8 h-8 items-center justify-center rounded-full cursor-pointer transition-colors', reportData.current_page === item.value ? 'bg-[#1c4f4a] text-white' : 'bg-white border border-[#e5e7eb] text-[#6b7280] hover:border-[#1c4f4a]']">
                                    <span class="text-sm font-medium">{{ item.value }}</span>
                                </div>
                                <div v-else class="flex w-8 h-8 items-center justify-center text-[#9ca3af] text-sm">...</div>
                            </template>
                            <div
                                @click="reportData.current_page < reportData.last_page && goToPage(reportData.current_page + 1)"
                                :class="['flex w-8 h-8 items-center justify-center rounded-full transition-colors', reportData.current_page < reportData.last_page ? 'cursor-pointer bg-white border border-[#e5e7eb] text-[#6b7280] hover:border-[#1c4f4a]' : 'cursor-not-allowed bg-[#f3f4f6] text-[#d1d5db]']"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado vacío -->
                <div v-else class="bg-white rounded-lg shadow p-8 text-center">
                    <div class="text-gray-500 text-lg">
                        No se encontraron datos
                    </div>
                    <p class="text-gray-400 mt-2">
                        No hay programas con recaudación registrada
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

const props = defineProps({
    reportData: Object,
    programs: Array,
    filters: Object,
    totals: Object
})

const filters = reactive({
    program_search: props.filters?.program_search || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    sort_by: props.filters?.sort_by || 'program_code',
    sort_dir: props.filters?.sort_dir || 'asc',
})

const sortValue = ref((props.filters?.sort_by || 'program_code') + '|' + (props.filters?.sort_dir || 'asc'))

const isExporting = ref(false)
const programSearchText = ref(props.filters?.program_search || '')
const showProgramDropdown = ref(false)
const programSearchContainer = ref(null)

// Filtrar programas por texto de búsqueda
const filteredPrograms = computed(() => {
    if (!programSearchText.value || programSearchText.value.length < 1) return []
    const search = programSearchText.value.toLowerCase()
    return (props.programs || []).filter(p =>
        (p.code && p.code.toLowerCase().includes(search)) ||
        (p.name && p.name.toLowerCase().includes(search))
    ).slice(0, 10)
})

// Cerrar dropdown al hacer click fuera
const handleClickOutside = (e) => {
    if (programSearchContainer.value && !programSearchContainer.value.contains(e.target)) {
        showProgramDropdown.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside)
})

let searchTimeout = null
const onProgramSearch = () => {
    showProgramDropdown.value = true
    // Debounce: aplicar búsqueda al dejar de escribir
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        filters.program_search = programSearchText.value
        applyFilters()
    }, 500)
}

const selectProgram = (program) => {
    programSearchText.value = program.code
    filters.program_search = program.code
    showProgramDropdown.value = false
    applyFilters()
}

const clearProgramSearch = () => {
    programSearchText.value = ''
    filters.program_search = ''
    applyFilters()
}

const applySort = () => {
    const [sortBy, sortDir] = sortValue.value.split('|')
    filters.sort_by = sortBy
    filters.sort_dir = sortDir
    applyFilters()
}

const applyProgramSearch = () => {
    clearTimeout(searchTimeout)
    filters.program_search = programSearchText.value
    showProgramDropdown.value = false
    applyFilters()
}

const formatCurrency = (amount) => {
    if (!amount) return '0'
    const value = parseInt(amount)
    return value.toLocaleString('es-CL')
}

const formatBalance = (amount) => {
    if (!amount) return '0'
    const value = parseInt(amount)
    if (value > 0) return '(' + value.toLocaleString('es-CL') + ')'
    if (value < 0) return Math.abs(value).toLocaleString('es-CL')
    return '0'
}

const visiblePages = computed(() => {
    if (!props.reportData || !props.reportData.last_page) return [{ type: 'page', value: 1, key: 'page-1' }]
    const total = props.reportData.last_page
    const current = props.reportData.current_page
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
    router.get(route('admin.reports.it-simple'), filters, {
        preserveState: true,
        preserveScroll: true
    })
}

const goToPage = (page) => {
    router.get(route('admin.reports.it-simple'), { ...filters, page }, {
        preserveState: true,
        preserveScroll: true
    })
}

const clearFilters = () => {
    programSearchText.value = ''
    filters.program_search = ''
    filters.date_from = ''
    filters.date_to = ''
    filters.sort_by = 'program_code'
    filters.sort_dir = 'asc'
    sortValue.value = 'program_code|asc'
    applyFilters()
}

const goBack = () => {
    router.get(route('admin.reports.index'))
}

const exportToExcel = () => {
    isExporting.value = true

    const params = new URLSearchParams()

    if (filters.program_search) params.append('program_search', filters.program_search)
    if (filters.date_from) params.append('date_from', filters.date_from)
    if (filters.date_to) params.append('date_to', filters.date_to)

    const url = route('admin.reports.export.it-simple') + (params.toString() ? '?' + params.toString() : '')

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
