<template>
    <!-- Modal Overlay -->
    <div 
        v-if="show"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        @click="closeModal"
    >
        <div 
            class="relative top-8 mx-auto p-0 border w-11/12 max-w-7xl shadow-lg rounded-md bg-white"
            @click.stop
        >
            <!-- Modal Header -->
            <div class="bg-[#1c4f4a] rounded-t-md px-6 py-4">
                <div class="flex justify-between items-center">
                    <div class="text-white">
                        <h3 class="text-xl font-bold">
                            Detalle de Cuotas - {{ detailFilters.monthName }}
                        </h3>
                        <p class="text-sm opacity-90">
                            {{ detailFilters.executiveName }} • {{ detailFilters.programName }}
                        </p>
                    </div>
                    <button
                        @click="closeModal"
                        class="text-white hover:text-gray-300 text-2xl font-bold transition-colors"
                    >
                        &times;
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-6 max-h-[80vh] overflow-y-auto">
                <!-- Filtros dentro del modal -->
                <div v-if="!loading && !error" class="mb-4 p-4 bg-gray-50 rounded-lg">
                    <div class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-48">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Estado de Cuotas
                            </label>
                            <select
                                v-model="statusFilter"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1c4f4a] focus:border-[#1c4f4a]"
                            >
                                <option value="">Todos los estados</option>
                                <option value="paid">Pagadas</option>
                                <option value="pending">Pendientes</option>
                                <option value="overdue">Vencidas</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button
                                @click="clearFilters"
                                class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50"
                            >
                                Limpiar Filtros
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="flex justify-center items-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#1c4f4a]"></div>
                    <span class="ml-3 text-gray-600">Cargando detalles...</span>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="p-8 text-center">
                    <div class="text-red-500">
                        <svg class="mx-auto h-12 w-12 text-red-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-lg font-medium text-red-900 mb-2">Error al cargar datos</h3>
                        <p class="text-red-700">{{ error }}</p>
                    </div>
                </div>

                <!-- Content Area -->
                <div v-else>
                    <!-- Sección de Participantes Liberados -->
                    <div v-if="liberatedParticipants.length > 0" class="mb-6">
                        <h4 class="text-lg font-semibold text-green-700 mb-3 bg-green-50 p-3 rounded-lg">
                            🎯 Participantes Liberados ({{ liberatedParticipants.length }})
                        </h4>
                        <div class="bg-green-50 rounded-lg p-4 mb-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                <div v-for="participant in liberatedParticipants" :key="participant.participant_id" 
                                     class="bg-white p-3 rounded border border-green-200">
                                    <p class="font-medium text-gray-900">{{ participant.participant_name }}</p>
                                    <p class="text-sm text-gray-600">{{ formatDocument(participant.participant_document, participant.document_type_code) }}</p>
                                    <p class="text-xs text-green-600 font-medium">✅ Completamente Pagado</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Detalle de Cuotas -->
                    <div v-if="filteredSchedules.length > 0" class="bg-white rounded-[12px] overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-[#1c4f4a]">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Ejecutivo Comercial</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Código Programa</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Nombre Programa</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Nombre del Alumno</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Tipo de Dcto</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">N° Documento</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Fecha de Inicio de Programa</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Fecha Vencimiento</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-white uppercase tracking-wider">Valor Total Prog.</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-white uppercase tracking-wider">Abonos + Becas</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-white uppercase tracking-wider">Valor Alumno Liberado</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-white uppercase tracking-wider">Monto Total por Cobrar</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">Estado de Cuotas</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr 
                                    v-for="(row, index) in filteredSchedules"
                                    :key="index"
                                    :class="[
                                        'hover:bg-gray-50 transition-colors',
                                        index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                                    ]"
                                >
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ row.sales_executive_name || 'N/A' }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ row.program_code || 'N/A' }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-sm text-[#1c4f4a] font-medium max-w-xs">{{ row.program_name || 'N/A' }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-sm text-gray-900">{{ row.participant_name || 'N/A' }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">{{ row.document_type_code || 'N/A' }}</span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ formatDocument(row.participant_document, row.document_type_code) }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ formatDate(row.program_departure_date) }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ formatDate(row.next_due_date) }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right">
                                        <div class="text-sm font-bold text-gray-900">${{ formatPrice(row.program_price) }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right">
                                        <div class="text-sm font-bold text-blue-600">${{ formatPrice(row.abonos_becas_amount ?? (Number(row.total_paid_amount||0) + Number(row.scholarships_amount||0))) }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right">
                                        <div class="text-sm font-bold text-purple-600">${{ formatPrice(row.released_amount) }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right">
                                        <div class="text-sm font-bold" :class="getBalanceClass(row.total_pending_amount)">${{ formatPrice(row.total_pending_amount) }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        <div class="text-sm font-bold text-gray-900">{{ row.paid_installments_display || '0/0' }}</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    </div>

                    <!-- Estado vacío cuando no hay cuotas ni liberados -->
                    <div v-if="filteredSchedules.length === 0 && liberatedParticipants.length === 0" class="text-center py-8">
                        <p class="text-gray-500">No se encontraron registros para los filtros seleccionados.</p>
                    </div>
                </div>

                <!-- Paginación si es necesaria -->
                <div v-if="!loading && totalPages > 1" class="mt-6">
                    <div class="flex flex-row items-center justify-between">
                        <div class="text-[#9ca3af] font-normal text-sm">
                            Mostrando {{ startIndex + 1 }} - {{ Math.min(endIndex, totalSchedules) }} de {{ totalSchedules }} cuotas
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
            </div>

            <!-- Modal Footer -->
            <div class="bg-gray-50 rounded-b-md px-6 py-4 flex justify-end">
                <button
                    @click="closeModal"
                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition-colors"
                >
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <!-- Detail Modal for Individual Schedule -->
    <div
        v-if="showDetailModal"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-[60]"
        @click="closeDetailModal"
    >
        <div
            class="relative top-20 mx-auto p-5 border w-11/12 max-w-6xl shadow-lg rounded-md bg-white"
            @click.stop
        >
            <!-- Aquí iría el contenido del modal de detalle individual -->
            <!-- Por ahora solo mostramos la información básica -->
            <div class="mt-3">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">
                        Detalles de Cuota - {{ selectedSchedule?.participant_name }}
                    </h3>
                    <button
                        @click="closeDetailModal"
                        class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
                    >
                        &times;
                    </button>
                </div>

                <div v-if="selectedSchedule" class="grid grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-semibold mb-2">Información del Participante</h4>
                        <p><strong>Nombre:</strong> {{ selectedSchedule.participant_name }}</p>
                        <p><strong>Email:</strong> {{ selectedSchedule.participant_email }}</p>
                        <p><strong>Documento:</strong> {{ selectedSchedule.participant_document }}</p>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-2">Información de la Cuota</h4>
                        <p><strong>N° Cuota:</strong> {{ selectedSchedule.installment_number || 'N/A (Liberado)' }}</p>
                        <p><strong>Monto:</strong> ${{ formatPrice(selectedSchedule.installment_amount) }}</p>
                        <p><strong>Fecha Vencimiento:</strong> {{ formatDate(selectedSchedule.due_date) }}</p>
                        <p><strong>Estado:</strong> {{ getParticipantStatusLabel(selectedSchedule.participant_status) }}</p>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button
                        @click="closeDetailModal"
                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import PaymentScheduleTable from './PaymentScheduleTable.vue';
import axios from 'axios';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    detailFilters: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['close']);

// Estado del modal
const loading = ref(false);
const showDetailModal = ref(false);
const selectedSchedule = ref(null);
const schedules = ref([]);
const liberatedParticipants = ref([]);
const error = ref('');
const statusFilter = ref('');

// Paginación
const currentPage = ref(1);
const itemsPerPage = ref(10);

// Obtener datos cuando se abra el modal
const fetchScheduleDetails = async () => {
    if (!props.detailFilters.yearMonth) {
        // Falta yearMonth para filtrar cuotas del mes
        return;
    }

    loading.value = true;
    error.value = '';
    
    try {
        // Cargar detalles del mes
        const response = await axios.get('/admin/reports/payment-schedule/details', {
            params: {
                salesExecutiveId: props.detailFilters.salesExecutiveId,
                programId: props.detailFilters.programId,
                yearMonth: props.detailFilters.yearMonth
            }
        });

        if (response.data.success) {
            schedules.value = response.data.data;
            liberatedParticipants.value = response.data.liberated || [];
        } else {
            error.value = response.data.message || 'Error al obtener los datos';
        }
    } catch (err) {
        console.error('Error fetching schedule details:', err);
        error.value = 'Error al obtener los detalles del cronograma';
    } finally {
        loading.value = false;
    }
};

// Los schedules filtrados por estado
const filteredSchedules = computed(() => {
    let result = schedules.value || [];

    // Filtrar por estado si está seleccionado. Para filas agregadas (participantes sin cuotas), no filtrar.
    if (statusFilter.value) {
        result = result.filter(row => {
            if (row.installment_number === undefined && row.paid_installments_display) {
                return true; // mantener filas agregadas
            }
            return row.status === statusFilter.value;
        });
    }

    // Orden: por nombre de participante asc
    return [...result].sort((a, b) => (a.participant_name || '').localeCompare(b.participant_name || ''));
});

// Cálculos de paginación
const totalSchedules = computed(() => filteredSchedules.value.length);
const totalPages = computed(() => Math.max(1, Math.ceil(totalSchedules.value / itemsPerPage.value)));
const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value);
const endIndex = computed(() => Math.min(startIndex.value + itemsPerPage.value, totalSchedules.value));

const validPages = computed(() => {
    const pages = [];
    const total = totalPages.value;
    
    if (total <= 1) return [1];
    
    const start = Math.max(1, currentPage.value - 2);
    const end = Math.min(total, start + 4);
    
    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    
    return pages;
});

// Métodos
const closeModal = () => {
    currentPage.value = 1; // Reset pagination
    emit('close');
};

const openDetailModal = (schedule) => {
    selectedSchedule.value = schedule;
    showDetailModal.value = true;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedSchedule.value = null;
};

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

// Formatters
const formatPrice = (price) => {
    if (!price) return "0";
    const numericPrice = Math.round(Number(price) || 0);
    return numericPrice.toLocaleString("es-CL");
};

const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("es-CL");
};

const getParticipantStatusLabel = (status) => {
    const labels = {
        'liberado': 'Liberado (Pagos Completos)',
        'pagado': 'Cuota Pagada',
        'vencido': 'Cuota Vencida',
        'pendiente': 'Cuota Pendiente',
        'sin_cuotas': 'Sin Cuotas'
    };
    return labels[status] || status || 'N/A';
};

const formatRut = (rut) => {
    if (!rut) return 'N/A';
    let clean = rut.toString().replace(/\./g, '').replace(/-/g, '');
    if (clean.length < 2) return rut;
    const dv = clean.slice(-1).toUpperCase();
    const num = clean.slice(0, -1).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return `${num}-${dv}`;
};

// Formatear documento respetando el tipo: RUT se formatea, PASAPORTE se muestra en uppercase
const formatDocument = (documentNumber, documentTypeCode) => {
    if (!documentNumber) return 'N/A';
    const type = String(documentTypeCode || '').toUpperCase();
    if (type === 'RUT' || type === 'RUN') {
        return formatRut(documentNumber);
    }
    if (type === 'PASAPORTE' || type === 'PASSPORT') {
        return String(documentNumber).toUpperCase();
    }
    // Fallback: no formatear si no es claramente RUT
    return String(documentNumber);
};

const getBalanceClass = (amount) => {
    const val = Number(amount) || 0;
    if (val <= 0) return 'text-green-600';
    if (val <= 100000) return 'text-yellow-600';
    return 'text-red-600';
};

// Cargar datos cuando se abra el modal o cambien los filtros
watch(() => props.show, (newShow) => {
    if (newShow) {
        fetchScheduleDetails();
    }
});

watch(() => props.detailFilters, () => {
    currentPage.value = 1;
    if (props.show) {
        fetchScheduleDetails();
    }
}, { deep: true });

// Reset pagination when status filter changes
watch(statusFilter, () => {
    currentPage.value = 1;
});

// Función para limpiar filtros
const clearFilters = () => {
    statusFilter.value = '';
};
</script>
