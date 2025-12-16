<template>
    <AdminLayout title="Reporte TI - Recaudación">
        <Head title="Reporte TI" />
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Header con botones de acción -->
                <div class="flex items-center justify-between">
                    <ReportsHeader
                        title="Reporte TI"
                        subtitle="Número de Negocio y Monto Recaudado"
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

                <!-- Totales -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg shadow p-4 text-center">
                        <p class="text-sm text-gray-500">Total Recaudado</p>
                        <p class="text-2xl font-bold text-[#1c4f4a]">{{ formatCurrency(totals.total_collected) }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 text-center">
                        <p class="text-sm text-gray-500">Total Cuotas Pagadas</p>
                        <p class="text-2xl font-bold text-[#1c4f4a]">{{ totals.total_installments }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 text-center">
                        <p class="text-sm text-gray-500">Programas con Pagos</p>
                        <p class="text-2xl font-bold text-[#1c4f4a]">{{ totals.total_programs }}</p>
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

                <!-- Tabla Simple -->
                <div v-if="reportData && reportData.data && reportData.data.length > 0">
                    <div class="bg-white rounded-[20px] overflow-hidden">
                        <!-- Table Container -->
                        <div class="flex flex-col gap-0">
                            <!-- Table Header -->
                            <div class="bg-turquesa rounded-t-[20px] px-5 py-[11px] flex items-center justify-between h-[61.51px]">
                                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[200px]">
                                    Nro. Negocio
                                </div>
                                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center flex-1">
                                    Monto Recaudado
                                </div>
                                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[150px]">
                                    Cuotas
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
                                    <!-- Nro. Negocio -->
                                    <div class="text-[#1c4f4a] font-nexa-bold text-[16px] leading-[18px] text-center w-[200px]">
                                        {{ record.program_code }}
                                    </div>

                                    <!-- Monto Recaudado -->
                                    <div class="text-[#1c4f4a] font-nexa-bold text-[18px] leading-[18px] text-center flex-1">
                                        {{ formatCurrency(record.total_collected) }}
                                    </div>

                                    <!-- Cuotas -->
                                    <div class="text-[#5b5b5b] font-nexa-regular text-[14px] leading-[18px] text-center w-[150px]">
                                        {{ record.installments_count }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paginación -->
                    <div class="mt-6 flex flex-row items-center justify-end gap-6 relative">
                        <!-- Total Items -->
                        <div class="text-[#9ca3af] font-normal text-sm">
                            Total {{ reportData.total }} Programas
                        </div>

                        <!-- Page Numbers -->
                        <div class="flex flex-row gap-2 items-center justify-center">
                            <div
                                v-for="page in validPages"
                                :key="page"
                                @click="goToPage(page)"
                                :class="[
                                    'flex w-8 h-8 items-center justify-center rounded-full cursor-pointer transition-colors',
                                    reportData.current_page === page
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
                        No se encontraron datos
                    </div>
                    <p class="text-gray-400 mt-2">
                        No hay cuotas pagadas registradas
                    </p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ReportsHeader from '@/Components/Reports/ReportsHeader.vue'

// Props
const props = defineProps({
    reportData: Object,
    programs: Array,
    filters: Object,
    totals: Object
})

// Reactive data
const filters = reactive({
    program_id: props.filters?.program_id || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
})

const isExporting = ref(false)

// Methods
const formatCurrency = (amount) => {
    if (!amount) return '$0'
    return '$' + parseInt(amount).toLocaleString('es-CL')
}

// Computed properties for pagination
const validPages = computed(() => {
    if (!props.reportData || !props.reportData.last_page) {
        return [1]
    }

    const pages = []
    const totalPages = props.reportData.last_page

    for (let i = 1; i <= Math.min(totalPages, 10); i++) {
        pages.push(i)
    }

    return pages
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
    filters.program_id = ''
    filters.date_from = ''
    filters.date_to = ''
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
