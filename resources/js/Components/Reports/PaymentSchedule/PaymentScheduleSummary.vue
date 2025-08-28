<template>
    <div class="bg-white rounded-[20px] overflow-hidden mb-6">
        <div class="p-6 text-gray-900">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-4">
                <h3 class="text-lg font-semibold">Resumen General Por Ejecutivo y Programa</h3>
                
                <!-- Botón de Exportar -->
                <button
                    @click="openExportModal"
                    class="mt-2 lg:mt-0 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#1c4f4a] hover:bg-[#164136] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1c4f4a]"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Exportar Cronograma
                </button>
            </div>
            
            <!-- Filtros -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <!-- Filtro Ejecutivo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Ejecutivo Comercial
                    </label>
                    <select
                        :value="filters.salesExecutiveId || ''"
                        @change="$emit('update-filter', 'salesExecutiveId', $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1c4f4a] focus:border-[#1c4f4a]"
                    >
                        <option value="">Todos los ejecutivos</option>
                        <option
                            v-for="executive in salesExecutives"
                            :key="executive.id"
                            :value="executive.id"
                        >
                            {{ executive.name }}
                        </option>
                    </select>
                </div>

                <!-- Filtro Programa -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Programa
                    </label>
                    <select
                        :value="filters.programId || ''"
                        @change="$emit('update-filter', 'programId', $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1c4f4a] focus:border-[#1c4f4a]"
                    >
                        <option value="">Todos los programas</option>
                        <option
                            v-for="program in programs"
                            :key="program.id"
                            :value="program.id"
                        >
                            {{ program.code }} - {{ program.name }}
                        </option>
                    </select>
                </div>

                <!-- Fecha Desde -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Fecha Desde
                    </label>
                    <input
                        type="date"
                        :value="filters.dateFrom || ''"
                        @change="$emit('update-filter', 'dateFrom', $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1c4f4a] focus:border-[#1c4f4a]"
                    />
                </div>

                <!-- Fecha Hasta -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Fecha Hasta
                    </label>
                    <input
                        type="date"
                        :value="filters.dateTo || ''"
                        @change="$emit('update-filter', 'dateTo', $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1c4f4a] focus:border-[#1c4f4a]"
                    />
                </div>
            </div>

            <!-- Contenido del resumen por ejecutivo -->
            <div v-if="paginatedExecutiveSummary.length > 0" class="space-y-6">
                <!-- Tabla principal con el mismo estilo que DailyPaymentsTable -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <!-- Table Header -->
                        <thead class="bg-[#1c4f4a]">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                                    Ejecutivo Comercial
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                                    Código
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                                    Programa
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                                    Mes
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                                    Cuotas No Pagadas N°
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                                    Cuotas Pagadas TC N°
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                                    Cuotas Pagadas PAT N°
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-white uppercase tracking-wider">
                                    No Pagadas $
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-white uppercase tracking-wider">
                                    Pagadas TC $
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-white uppercase tracking-wider">
                                    Pagadas PAT $
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>

                        <!-- Table Body -->
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template v-for="(executiveData, execIndex) in paginatedExecutiveSummary" :key="executiveData.sales_executive_id">
                                <tr
                                    v-for="(month, monthIndex) in executiveData.months"
                                    :key="`${executiveData.sales_executive_id}-${month.year_month}`"
                                    :class="[
                                        'hover:bg-gray-50 transition-colors',
                                        (execIndex * 100 + monthIndex) % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                                    ]"
                                >
                                    <!-- Ejecutivo Comercial -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ executiveData.sales_executive_name || 'N/A' }}
                                        </div>
                                    </td>

                                    <!-- Código -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ executiveData.program_code || 'N/A' }}
                                        </div>
                                    </td>

                                    <!-- Programa -->
                                    <td class="px-4 py-4">
                                        <div class="text-sm text-[#1c4f4a] font-medium max-w-xs">
                                            {{ executiveData.program_name || 'N/A' }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ executiveData.program_destination || '' }}
                                        </div>
                                    </td>

                                    <!-- Mes -->
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ month.month_name }}
                                        </div>
                                    </td>

                                    <!-- Cuotas No Pagadas N° -->
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        <div class="text-sm font-bold text-red-600">
                                            {{ month.cuotas_no_pagadas_count }}
                                        </div>
                                    </td>

                                    <!-- Cuotas Pagadas TC N° -->
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        <div class="text-sm font-bold text-blue-600">
                                            {{ month.cuotas_pagadas_tc_count }}
                                        </div>
                                    </td>

                                    <!-- Cuotas Pagadas PAT N° -->
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        <div class="text-sm font-bold text-green-600">
                                            {{ month.cuotas_pagadas_pat_count }}
                                        </div>
                                    </td>

                                    <!-- No Pagadas $ -->
                                    <td class="px-4 py-4 whitespace-nowrap text-right">
                                        <div class="text-sm font-bold text-red-600">
                                            ${{ formatCurrency(month.cuotas_no_pagadas_amount) }}
                                        </div>
                                    </td>

                                    <!-- Pagadas TC $ -->
                                    <td class="px-4 py-4 whitespace-nowrap text-right">
                                        <div class="text-sm font-bold text-blue-600">
                                            ${{ formatCurrency(month.cuotas_pagadas_tc_amount) }}
                                        </div>
                                    </td>

                                    <!-- Pagadas PAT $ -->
                                    <td class="px-4 py-4 whitespace-nowrap text-right">
                                        <div class="text-sm font-bold text-green-600">
                                            ${{ formatCurrency(month.cuotas_pagadas_pat_amount) }}
                                        </div>
                                    </td>

                                    <!-- Acciones -->
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        <button
                                            @click="viewDetails(executiveData, month)"
                                            class="text-[#1c4f4a] hover:text-[#0f2e29] transition-colors"
                                            title="Ver detalles"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="flex flex-row items-center justify-between mt-4">
                    <div class="text-[#9ca3af] font-normal text-sm">
                        Mostrando {{ startIndex + 1 }} - {{ Math.min(endIndex, totalRows) }} de {{ totalRows }} registros
                    </div>
                    
                    <div class="flex flex-row gap-2 items-center justify-center">
                        <button
                            v-if="currentPage > 1"
                            @click="goToPage(currentPage - 1)"
                            class="flex w-8 h-8 items-center justify-center rounded-full bg-white border border-[#e5e7eb] text-[#6b7280] hover:border-[#1c4f4a] cursor-pointer transition-colors"
                        >
                            <span class="text-sm">‹</span>
                        </button>
                        
                        <div
                            v-for="page in validPages"
                            :key="page"
                            @click="goToPage(page)"
                            :class="[
                                'flex w-8 h-8 items-center justify-center rounded-full cursor-pointer transition-colors',
                                currentPage === page
                                    ? 'bg-[#1c4f4a] text-white'
                                    : 'bg-white border border-[#e5e7eb] text-[#6b7280] hover:border-[#1c4f4a]',
                            ]"
                        >
                            <span class="text-sm font-medium">{{ page }}</span>
                        </div>
                        
                        <button
                            v-if="currentPage < totalPages"
                            @click="goToPage(currentPage + 1)"
                            class="flex w-8 h-8 items-center justify-center rounded-full bg-white border border-[#e5e7eb] text-[#6b7280] hover:border-[#1c4f4a] cursor-pointer transition-colors"
                        >
                            <span class="text-sm">›</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mensaje cuando no hay datos -->
            <div v-else class="p-8 text-center">
                <div class="text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-lg font-medium">No hay datos para mostrar</p>
                    <p class="text-sm">
                        No se encontraron datos de cronograma para los filtros seleccionados
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Exportación -->
    <div v-if="showExportModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center" @click.self="closeExportModal">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Exportar Cronograma</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Alcance</label>
                    <div class="flex items-center gap-6">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="radio" value="all" v-model="exportMode" class="text-[#1c4f4a] focus:ring-[#1c4f4a]" />
                            <span>Todos los Ejecutivos y Programas</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="radio" value="specific" v-model="exportMode" class="text-[#1c4f4a] focus:ring-[#1c4f4a]" />
                            <span>Uno Específico</span>
                        </label>
                    </div>
                </div>

                <div v-if="exportMode === 'specific'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ejecutivo Comercial</label>
                        <select v-model="selectedExecutiveId" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1c4f4a] focus:border-[#1c4f4a]">
                            <option value="">Seleccione</option>
                            <option v-for="exec in salesExecutives" :key="exec.id" :value="exec.id">{{ exec.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Programa</label>
                        <select v-model="selectedProgramId" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1c4f4a] focus:border-[#1c4f4a]">
                            <option value="">Seleccione</option>
                            <option v-for="program in filteredPrograms" :key="program.id" :value="program.id">{{ program.code }} - {{ program.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button @click="closeExportModal" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Cancelar</button>
                    <button @click="confirmExport" class="px-4 py-2 rounded-lg bg-[#1c4f4a] text-white hover:bg-[#164136]">Exportar</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    executiveSummary: {
        type: Array,
        default: () => []
    },
    salesExecutives: {
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
    }
});

const emit = defineEmits(['update-filter', 'view-details']);

// Paginación
const currentPage = ref(1);
const itemsPerPage = ref(10); // Número de filas (no ejecutivos) por página

// Aplana los datos para paginación por filas individuales
const flattenedData = computed(() => {
    console.log('🔄 FLATTENING DATA - ExecutiveSummary received:', props.executiveSummary);
    
    const flattened = [];
    
    if (!props.executiveSummary || !Array.isArray(props.executiveSummary)) {
        console.warn('ExecutiveSummary is not an array:', props.executiveSummary);
        return flattened;
    }
    
    props.executiveSummary.forEach(executive => {
        if (!executive) return;
        
        // Verificar si months es un array o un objeto/Collection
        let months = [];
        if (Array.isArray(executive.months)) {
            months = executive.months;
                 } else if (executive.months && typeof executive.months === 'object') {
             // Convertir a array SIN reordenar - mantener el orden del backend
             months = Object.values(executive.months);
            
            // Log para debug del ordenamiento en frontend
            console.log('Frontend sorting for:', executive.sales_executive_name, '-', executive.program_name, {
                'months_after_frontend_sort': months.map(m => ({
                    month_name: m.month_name,
                    year: m.year,
                    month: m.month,
                    sort_value: (m.year * 1000) + m.month
                }))
            });
        }
        
        if (months.length === 0) {
            console.warn('No months found for executive:', executive);
            return;
        }
        
        months.forEach(month => {
            flattened.push({
                ...executive,
                currentMonth: month
            });
        });
    });
    
    return flattened;
});

// Total de filas
const totalRows = computed(() => flattenedData.value.length);

// Total de páginas
const totalPages = computed(() => {
    return Math.max(1, Math.ceil(totalRows.value / itemsPerPage.value));
});

// Índices para paginación
const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value);
const endIndex = computed(() => Math.min(startIndex.value + itemsPerPage.value, totalRows.value));

// Datos paginados
const paginatedData = computed(() => {
    return flattenedData.value.slice(startIndex.value, endIndex.value);
});

// Reagrupar datos paginados por ejecutivo para mantener estructura
const paginatedExecutiveSummary = computed(() => {
    const grouped = {};
    
    paginatedData.value.forEach(item => {
        const key = `${item.sales_executive_id}-${item.program_id}`;
        if (!grouped[key]) {
            grouped[key] = {
                sales_executive_id: item.sales_executive_id,
                sales_executive_name: item.sales_executive_name,
                program_id: item.program_id,
                program_code: item.program_code,
                program_name: item.program_name,
                program_destination: item.program_destination,
                months: []
            };
        }
        grouped[key].months.push(item.currentMonth);
    });
    
              // NO reordenar - mantener el orden del backend
     Object.values(grouped).forEach(executiveData => {
         // Log para debug del orden final (sin reordenar)
         console.log('Final order (backend order) for:', executiveData.sales_executive_name, '-', executiveData.program_name, {
             'months_final_order': executiveData.months.map(m => ({
                 month_name: m.month_name,
                 year: m.year,
                 month: m.month,
                 sort_value: (m.year * 1000) + m.month
             }))
         });
     });
    
    return Object.values(grouped);
});

// Páginas válidas para mostrar
const validPages = computed(() => {
    const pages = [];
    const total = totalPages.value;
    
    if (total <= 1) return [1];
    
    // Mostrar hasta 5 páginas alrededor de la actual
    const start = Math.max(1, currentPage.value - 2);
    const end = Math.min(total, start + 4);
    
    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    
    return pages;
});

// Métodos de paginación
const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

// Método para ver detalles
const viewDetails = (executiveData, month) => {
    console.log('👁️ VIEW DETAILS clicked for:', {
        executive: executiveData.sales_executive_name,
        program: executiveData.program_name,
        month: month.month_name,
        year_month: month.year_month
    });
    
    const detailFilters = {
        salesExecutiveId: executiveData.sales_executive_id,
        programId: executiveData.program_id,
        yearMonth: month.year_month,
        executiveName: executiveData.sales_executive_name,
        programName: executiveData.program_name,
        monthName: month.month_name
    };
    
    console.log('📤 Emitting view-details with filters:', detailFilters);
    emit('view-details', detailFilters);
};

// Modal exportación
const showExportModal = ref(false);
const exportMode = ref('all'); // 'all' | 'specific'
const selectedExecutiveId = ref('');
const selectedProgramId = ref('');

const openExportModal = () => {
    // Preseleccionar con filtros actuales
    exportMode.value = 'all';
    selectedExecutiveId.value = props.filters.salesExecutiveId || '';
    selectedProgramId.value = props.filters.programId || '';
    showExportModal.value = true;
};

const closeExportModal = () => {
    showExportModal.value = false;
};

const confirmExport = () => {
    const params = new URLSearchParams();
    // Pasar filtros globales de fecha si existen
    if (props.filters.dateFrom) params.append('dateFrom', props.filters.dateFrom);
    if (props.filters.dateTo) params.append('dateTo', props.filters.dateTo);

    if (exportMode.value === 'specific') {
        if (selectedExecutiveId.value) params.append('salesExecutiveId', selectedExecutiveId.value);
        if (selectedProgramId.value) params.append('programId', selectedProgramId.value);
    }

    // Descargar
    window.location.href = `/admin/reports/export/payment-schedule?${params.toString()}`;
    showExportModal.value = false;
};

// Programas filtrados por ejecutivo seleccionado
const filteredPrograms = computed(() => {
    if (!selectedExecutiveId.value) {
        return props.programs;
    }
    // Si los programas vienen con sales_executive_id, filtrar por coincidencia
    return props.programs.filter(p => String(p.sales_executive_id || '') === String(selectedExecutiveId.value));
});

// Al cambiar ejecutivo, si el programa seleccionado no pertenece, resetear
watch(selectedExecutiveId, () => {
    if (
        selectedProgramId.value &&
        !filteredPrograms.value.some(p => String(p.id) === String(selectedProgramId.value))
    ) {
        selectedProgramId.value = '';
    }
});

const formatCurrency = (value) => {
    // Convertir a número y redondear para evitar decimales
    const numericValue = Math.round(Number(value) || 0);
    // Formatear solo el número sin el símbolo de moneda, ya que lo agregamos manualmente
    return numericValue.toLocaleString("es-CL");
};
</script>
