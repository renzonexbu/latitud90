<template>
    <AdminLayout title="Monitor BSale">
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Monitor BSale</h1>
                        <p class="text-gray-600">Gestión de solicitudes de boletas electrónicas</p>
                    </div>
                    <button
                        @click="refreshData"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg flex items-center"
                        :disabled="loading"
                    >
                        <svg class="w-4 h-4 mr-2" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Actualizar
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="text-2xl font-bold text-yellow-700">{{ stats.pending }}</div>
                        <div class="text-sm text-yellow-600">Pendientes</div>
                    </div>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="text-2xl font-bold text-blue-700">{{ stats.processing }}</div>
                        <div class="text-sm text-blue-600">En Proceso</div>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="text-2xl font-bold text-green-700">{{ stats.completed_today }}</div>
                        <div class="text-sm text-green-600">Completados Hoy</div>
                    </div>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="text-2xl font-bold text-red-700">{{ stats.failed }}</div>
                        <div class="text-sm text-red-600">Fallidos</div>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <div class="text-2xl font-bold text-gray-700">{{ stats.total_today }}</div>
                        <div class="text-sm text-gray-600">Total Hoy</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                            <select v-model="filters.status" @change="fetchData" class="w-full border-gray-300 rounded-lg shadow-sm">
                                <option value="all">Todos</option>
                                <option value="pending">Pendiente</option>
                                <option value="processing">Procesando</option>
                                <option value="completed">Completado</option>
                                <option value="failed">Fallido</option>
                                <option value="cancelled">Cancelado</option>
                                <option value="skipped">Omitido</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                            <select v-model="filters.document_type" @change="fetchData" class="w-full border-gray-300 rounded-lg shadow-sm">
                                <option value="all">Todos</option>
                                <option value="B2">Boleta (B2)</option>
                                <option value="NC">Nota Crédito (NC)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
                            <input type="date" v-model="filters.date_from" @change="fetchData" class="w-full border-gray-300 rounded-lg shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
                            <input type="date" v-model="filters.date_to" @change="fetchData" class="w-full border-gray-300 rounded-lg shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                            <input
                                type="text"
                                v-model="filters.search"
                                @input="debouncedSearch"
                                placeholder="Payment ID, Boleta, Nombre..."
                                class="w-full border-gray-300 rounded-lg shadow-sm"
                            >
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Participante</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Boleta</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Intentos</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Error</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="request in requests.data" :key="request.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900">#{{ request.id }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="text-blue-600 font-medium">#{{ request.payment_id }}</span>
                                        <span v-if="request.payment" class="text-gray-500 text-xs block">
                                            ${{ formatPrice(request.payment.amount) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div v-if="request.participant">
                                            <div class="font-medium text-gray-900">{{ request.participant.name }}</div>
                                            <div class="text-gray-500 text-xs">{{ request.program?.code }}</div>
                                        </div>
                                        <span v-else class="text-gray-400">N/A</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="request.status_class" class="px-2 py-1 text-xs font-semibold rounded-full">
                                            {{ request.status_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span v-if="request.bsale_number" class="text-green-600 font-medium">
                                            {{ request.bsale_number }}
                                        </span>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ request.attempts }}/{{ request.max_attempts }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span v-if="request.error_message" class="text-red-600 text-xs" :title="request.error_message">
                                            {{ truncate(request.error_message, 30) }}
                                        </span>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ formatDate(request.created_at) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex space-x-2">
                                            <button
                                                @click="showDetail(request)"
                                                class="text-blue-600 hover:text-blue-800"
                                                title="Ver detalle"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </button>
                                            <button
                                                v-if="request.can_retry"
                                                @click="retryRequest(request)"
                                                class="text-yellow-600 hover:text-yellow-800"
                                                title="Reintentar"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                            </button>
                                            <button
                                                v-if="request.status !== 'completed' && request.status !== 'cancelled'"
                                                @click="cancelRequest(request)"
                                                class="text-red-600 hover:text-red-800"
                                                title="Cancelar"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!loading && requests.data?.length === 0">
                                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                        No se encontraron solicitudes
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="requests.last_page > 1" class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200">
                        <div class="text-sm text-gray-700">
                            Mostrando {{ requests.from }} a {{ requests.to }} de {{ requests.total }} resultados
                        </div>
                        <div class="flex space-x-2">
                            <button
                                @click="goToPage(requests.current_page - 1)"
                                :disabled="requests.current_page === 1"
                                class="px-3 py-1 border rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Anterior
                            </button>
                            <button
                                @click="goToPage(requests.current_page + 1)"
                                :disabled="requests.current_page === requests.last_page"
                                class="px-3 py-1 border rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Siguiente
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <div v-if="showDetailModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b flex justify-between items-center">
                    <h2 class="text-xl font-bold">Detalle Solicitud #{{ selectedRequest?.id }}</h2>
                    <button @click="closeDetail" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div v-if="detailLoading" class="p-6 text-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                </div>
                <div v-else-if="detailData" class="p-6 space-y-6">
                    <!-- Info General -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm text-gray-500">Estado</label>
                            <div><span :class="detailData.request.status_class" class="px-2 py-1 text-sm font-semibold rounded-full">{{ detailData.request.status_label }}</span></div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-500">Tipo Documento</label>
                            <div class="font-medium">{{ detailData.request.document_type === 'B2' ? 'Boleta' : 'Nota Crédito' }}</div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-500">Payment ID</label>
                            <div class="font-medium text-blue-600">#{{ detailData.request.payment_id }}</div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-500">Boleta BSale</label>
                            <div class="font-medium text-green-600">{{ detailData.request.bsale_number || 'N/A' }}</div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-500">Intentos</label>
                            <div class="font-medium">{{ detailData.request.attempts }}/{{ detailData.request.max_attempts }}</div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-500">Origen</label>
                            <div class="font-medium">{{ detailData.request.source || 'N/A' }}</div>
                        </div>
                    </div>

                    <!-- Error -->
                    <div v-if="detailData.request.error_message" class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <h4 class="font-medium text-red-800 mb-2">Error</h4>
                        <p class="text-red-700 text-sm">{{ detailData.request.error_message }}</p>
                        <p v-if="detailData.request.error_code" class="text-red-600 text-xs mt-1">Código: {{ detailData.request.error_code }}</p>
                    </div>

                    <!-- Fechas -->
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><span class="text-gray-500">Creado:</span> {{ detailData.request.created_at }}</div>
                        <div><span class="text-gray-500">Programado:</span> {{ detailData.request.scheduled_at || 'N/A' }}</div>
                        <div><span class="text-gray-500">Procesado:</span> {{ detailData.request.processed_at || 'N/A' }}</div>
                        <div><span class="text-gray-500">Último intento:</span> {{ detailData.request.last_attempt_at || 'N/A' }}</div>
                    </div>

                    <!-- Response Data -->
                    <div v-if="detailData.request.response_data">
                        <h4 class="font-medium text-gray-900 mb-2">Respuesta BSale</h4>
                        <pre class="bg-gray-100 p-3 rounded text-xs overflow-auto max-h-48">{{ JSON.stringify(detailData.request.response_data, null, 2) }}</pre>
                    </div>

                    <!-- Request Data -->
                    <div v-if="detailData.request.request_data">
                        <h4 class="font-medium text-gray-900 mb-2">Datos Enviados</h4>
                        <pre class="bg-gray-100 p-3 rounded text-xs overflow-auto max-h-48">{{ JSON.stringify(detailData.request.request_data, null, 2) }}</pre>
                    </div>
                </div>
                <div class="p-6 border-t flex justify-end space-x-3">
                    <button
                        v-if="detailData?.request?.can_retry"
                        @click="retryRequest(detailData.request); closeDetail()"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg"
                    >
                        Reintentar
                    </button>
                    <button
                        @click="forceReprocess(detailData?.request); closeDetail()"
                        class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg"
                    >
                        Forzar Reproceso
                    </button>
                    <button @click="closeDetail" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const loading = ref(false);
const processing = ref(false);
const showDetailModal = ref(false);
const detailLoading = ref(false);
const selectedRequest = ref(null);
const detailData = ref(null);

const stats = ref({
    pending: 0,
    processing: 0,
    completed_today: 0,
    failed: 0,
    total_today: 0,
});

const requests = ref({
    data: [],
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    total: 0,
});

const filters = reactive({
    status: 'all',
    document_type: 'all',
    date_from: '',
    date_to: '',
    search: '',
    page: 1,
});

let searchTimeout = null;

const fetchData = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        Object.entries(filters).forEach(([key, value]) => {
            if (value && value !== 'all') params.append(key, value);
        });

        const response = await axios.get(`/admin/bsale-monitor/list?${params.toString()}`);
        requests.value = response.data.requests;
        stats.value = response.data.stats;
    } catch (error) {
        console.error('Error fetching data:', error);
    } finally {
        loading.value = false;
    }
};

const refreshData = () => {
    fetchData();
};

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        filters.page = 1;
        fetchData();
    }, 300);
};

const goToPage = (page) => {
    filters.page = page;
    fetchData();
};

const processQueue = async () => {
    if (!confirm('¿Procesar la cola de solicitudes BSale pendientes?')) return;

    processing.value = true;
    try {
        const response = await axios.post('/admin/bsale-monitor/process-queue', { limit: 10 });
        alert(response.data.message);
        fetchData();
    } catch (error) {
        alert('Error al procesar cola: ' + (error.response?.data?.message || error.message));
    } finally {
        processing.value = false;
    }
};

const showDetail = async (request) => {
    selectedRequest.value = request;
    showDetailModal.value = true;
    detailLoading.value = true;

    try {
        const response = await axios.get(`/admin/bsale-monitor/${request.id}`);
        detailData.value = response.data;
    } catch (error) {
        console.error('Error fetching detail:', error);
    } finally {
        detailLoading.value = false;
    }
};

const closeDetail = () => {
    showDetailModal.value = false;
    selectedRequest.value = null;
    detailData.value = null;
};

const retryRequest = async (request) => {
    if (!confirm('¿Reintentar esta solicitud?')) return;

    try {
        await axios.post(`/admin/bsale-monitor/${request.id}/retry`);
        fetchData();
    } catch (error) {
        alert('Error: ' + (error.response?.data?.message || error.message));
    }
};

const cancelRequest = async (request) => {
    if (!confirm('¿Cancelar esta solicitud?')) return;

    try {
        await axios.post(`/admin/bsale-monitor/${request.id}/cancel`);
        fetchData();
    } catch (error) {
        alert('Error: ' + (error.response?.data?.message || error.message));
    }
};

const forceReprocess = async (request) => {
    if (!confirm('¿Forzar reprocesamiento? Esto reseteará los intentos.')) return;

    try {
        await axios.post(`/admin/bsale-monitor/${request.id}/force-reprocess`);
        fetchData();
    } catch (error) {
        alert('Error: ' + (error.response?.data?.message || error.message));
    }
};

const formatPrice = (amount) => {
    return new Intl.NumberFormat('es-CL').format(amount || 0);
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('es-CL', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const truncate = (text, length) => {
    if (!text) return '';
    return text.length > length ? text.substring(0, length) + '...' : text;
};

onMounted(() => {
    fetchData();
});
</script>
