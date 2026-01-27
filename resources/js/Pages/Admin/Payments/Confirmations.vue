<template>
    <AdminLayout>
        <Head title="Historial de Confirmaciones de Pago" />

        <!-- Sistema de Alertas -->
        <AlertWrapper ref="alertWrapper" />

        <div class="bg-white min-h-screen">
            <!-- Header -->
            <div class="py-5 flex flex-row items-center justify-between relative px-8">
                <div class="flex flex-row gap-4 items-center justify-start flex-shrink-0 relative">
                    <div class="text-[30px] leading-9 font-normal text-turquesa font-nexa-xbold">
                        Historial de Confirmaciones
                    </div>
                    <div class="w-0 h-[35px] relative overflow-visible">
                        <div class="w-px h-full bg-gray-300"></div>
                    </div>
                    <div class="text-[20px] leading-7 font-normal text-[#5b5b5b] font-nexa-regular">
                        Seguimiento de emails de confirmación de pagos
                    </div>
                </div>

                <!-- Botón volver -->
                <Link
                    :href="route('admin.payments.index')"
                    class="bg-gray-500 text-white rounded-[112.89px] px-[18px] py-[14px] flex flex-row gap-[11.29px] items-center justify-center hover:bg-gray-600 transition-colors"
                >
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    <span class="font-nexa-bold text-base">Volver a Pagos</span>
                </Link>
            </div>

            <!-- Filtros -->
            <div class="px-8 py-4 bg-gray-50">
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                        <!-- Fecha Desde -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Fecha Desde
                            </label>
                            <input
                                v-model="filters.date_from"
                                type="date"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-turquesa focus:ring-turquesa"
                                @change="applyFilters"
                            />
                        </div>

                        <!-- Fecha Hasta -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Fecha Hasta
                            </label>
                            <input
                                v-model="filters.date_to"
                                type="date"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-turquesa focus:ring-turquesa"
                                @change="applyFilters"
                            />
                        </div>

                        <!-- Programa -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Programa
                            </label>
                            <select
                                v-model="filters.program_id"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-turquesa focus:ring-turquesa"
                                @change="applyFilters"
                            >
                                <option value="">Todos los programas</option>
                                <option v-for="program in programs" :key="program.id" :value="program.id">
                                    {{ program.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Participante -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Participante (RUT o Nombre)
                            </label>
                            <input
                                v-model="filters.participant_search"
                                type="text"
                                placeholder="Buscar..."
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-turquesa focus:ring-turquesa"
                                @keyup.enter="applyFilters"
                            />
                        </div>

                        <!-- Estado -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Estado
                            </label>
                            <select
                                v-model="filters.status"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-turquesa focus:ring-turquesa"
                                @change="applyFilters"
                            >
                                <option value="all">Todos</option>
                                <option value="success">Exitosos</option>
                                <option value="failed">Fallidos</option>
                            </select>
                        </div>

                        <!-- Tipo de Pago -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Tipo de Pago
                            </label>
                            <select
                                v-model="filters.payment_type"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-turquesa focus:ring-turquesa"
                                @change="applyFilters"
                            >
                                <option value="all">Todos</option>
                                <option value="gateway">Pasarela</option>
                                <option value="manual">Manual/Offline</option>
                            </select>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="mt-4 flex gap-2 justify-between">
                        <div class="flex gap-2">
                            <button
                                @click="applyFilters"
                                class="bg-turquesa text-white px-4 py-2 rounded-md hover:bg-turquesa-dark transition-colors"
                            >
                                Aplicar Filtros
                            </button>
                            <button
                                @click="clearFilters"
                                class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors"
                            >
                                Limpiar
                            </button>
                        </div>

                        <!-- Botón envío masivo -->
                        <button
                            v-if="selectedPayments.length > 0"
                            @click="confirmBulkResend"
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors flex items-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Enviar Masivo ({{ selectedPayments.length }})
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="px-4 md:px-8 py-6">
                <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-2 py-3 text-left">
                                    <input
                                        type="checkbox"
                                        :checked="isAllSelected"
                                        :indeterminate="isIndeterminate"
                                        @change="toggleSelectAll"
                                        class="rounded border-gray-300 text-turquesa focus:ring-turquesa"
                                    />
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                    Fecha/Hora
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                    # Orden
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Participante
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Programa
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                    Monto
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                    Tipo
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Eventos
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Email
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="payments.length === 0">
                                <td colspan="10" class="px-3 py-4 text-center text-gray-500">
                                    No se encontraron registros
                                </td>
                            </tr>
                            <tr v-for="payment in payments" :key="payment.payment_id" class="hover:bg-gray-50">
                                <td class="px-2 py-3">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected(payment.payment_id)"
                                        @change="toggleSelection(payment)"
                                        class="rounded border-gray-300 text-turquesa focus:ring-turquesa"
                                    />
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900">
                                    {{ payment.payment_date }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ payment.order_number }}
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-900">
                                    <div class="max-w-[140px] truncate" :title="payment.participant.name">{{ payment.participant.name }}</div>
                                    <div class="text-xs text-gray-500">{{ payment.participant.document }}</div>
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-900">
                                    <div class="max-w-[150px] truncate" :title="payment.program.name">{{ payment.program.name }}</div>
                                    <div class="text-xs text-gray-500">{{ payment.program.destination }}</div>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900">
                                    ${{ payment.amount }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm">
                                    <span
                                        :class="payment.payment_type === 'gateway'
                                            ? 'bg-blue-100 text-blue-800'
                                            : 'bg-yellow-100 text-yellow-800'"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    >
                                        {{ payment.payment_type_label }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-sm">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-if="payment.events.payment_receipt"
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                            title="Comprobante generado"
                                        >
                                            📄 Comprobante
                                        </span>
                                        <span
                                            v-if="payment.events.contract"
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                            title="Contrato generado"
                                        >
                                            📋 Contrato
                                        </span>
                                        <span
                                            v-if="payment.events.bsale_invoice"
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
                                            title="Boleta Bsale generada"
                                        >
                                            🧾 Boleta
                                        </span>
                                        <span
                                            v-if="payment.events.email_sent"
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                            title="Email enviado"
                                        >
                                            ✅ Email
                                        </span>
                                        <span
                                            v-if="payment.events.has_failed"
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800"
                                            title="Algún evento falló"
                                        >
                                            ❌ Error
                                        </span>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-500">
                                    <span class="max-w-[160px] truncate block" :title="payment.email_recipient">{{ payment.email_recipient }}</span>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm font-medium">
                                    <div class="flex gap-2">
                                        <button
                                            @click="viewDetails(payment.payment_id)"
                                            class="text-turquesa hover:text-turquesa-dark"
                                            title="Ver detalles"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>
                                        <button
                                            @click="confirmResend(payment)"
                                            class="text-blue-600 hover:text-blue-800"
                                            title="Reenviar email de confirmación"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="pagination.last_page > 1" class="mt-6 flex justify-between items-center">
                    <div class="text-sm text-gray-700">
                        Mostrando página {{ pagination.current_page }} de {{ pagination.last_page }} ({{ pagination.total }} registros)
                    </div>
                    <div class="flex gap-2">
                        <button
                            @click="changePage(pagination.current_page - 1)"
                            :disabled="pagination.current_page === 1"
                            class="px-4 py-2 border rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Anterior
                        </button>
                        <button
                            @click="changePage(pagination.current_page + 1)"
                            :disabled="pagination.current_page === pagination.last_page"
                            class="px-4 py-2 border rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Siguiente
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Detalles -->
        <DetailModal
            v-if="showDetailModal"
            :payment-id="selectedPaymentId"
            @close="closeDetailModal"
        />

        <!-- Modal de Confirmación de Reenvío -->
        <ConfirmResendModal
            v-if="showResendModal"
            :payment="selectedPayment"
            @confirm="resendEmail"
            @cancel="closeResendModal"
        />

        <!-- Modal de Envío Masivo -->
        <div v-if="showBulkResendModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeBulkResendModal">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="closeBulkResendModal"></div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-blue-500 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-white">Envío Masivo de Confirmaciones</h3>
                            <button @click="closeBulkResendModal" class="text-white hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-gray-700 mb-4">
                            ¿Está seguro que desea reenviar el email de confirmación para <strong>{{ selectedPayments.length }}</strong> pago(s)?
                        </p>
                        <div class="bg-gray-50 p-4 rounded-lg max-h-60 overflow-y-auto">
                            <ul class="space-y-2">
                                <li v-for="payment in selectedPayments" :key="payment.payment_id" class="text-sm text-gray-600 flex justify-between">
                                    <span>{{ payment.order_number }} - {{ payment.participant.name }}</span>
                                    <span class="text-gray-400">{{ payment.email_recipient }}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mt-4">
                            <div class="flex">
                                <svg class="w-5 h-5 text-yellow-600 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-sm text-yellow-800">
                                    Esta operación enviará múltiples emails. Los documentos se generarán automáticamente si no existen.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                        <button
                            @click="closeBulkResendModal"
                            class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors"
                            :disabled="isBulkSending"
                        >
                            Cancelar
                        </button>
                        <button
                            @click="bulkResendEmails"
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors flex items-center"
                            :disabled="isBulkSending"
                        >
                            <svg v-if="isBulkSending" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ isBulkSending ? `Enviando (${bulkProgress}/${selectedPayments.length})...` : 'Enviar Todos' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AlertWrapper from '@/Components/Admin/AlertWrapper.vue';
import DetailModal from '@/Components/Payments/ConfirmationLogs/DetailModal.vue';
import ConfirmResendModal from '@/Components/Payments/ConfirmationLogs/ConfirmResendModal.vue';
import axios from 'axios';

const props = defineProps({
    payments: {
        type: Array,
        default: () => []
    },
    pagination: {
        type: Object,
        default: () => ({
            current_page: 1,
            last_page: 1,
            per_page: 25,
            total: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    programs: {
        type: Array,
        default: () => []
    }
});

const filters = reactive({
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    program_id: props.filters.program_id || '',
    participant_search: props.filters.participant_search || '',
    status: props.filters.status || 'all',
    payment_type: props.filters.payment_type || 'all',
    per_page: props.filters.per_page || 25
});

const showDetailModal = ref(false);
const selectedPaymentId = ref(null);
const showResendModal = ref(false);
const selectedPayment = ref(null);
const alertWrapper = ref(null);

// Selección múltiple
const selectedPayments = ref([]);
const showBulkResendModal = ref(false);
const isBulkSending = ref(false);
const bulkProgress = ref(0);

const isAllSelected = computed(() => {
    return props.payments.length > 0 && selectedPayments.value.length === props.payments.length;
});

const isIndeterminate = computed(() => {
    return selectedPayments.value.length > 0 && selectedPayments.value.length < props.payments.length;
});

const isSelected = (paymentId) => {
    return selectedPayments.value.some(p => p.payment_id === paymentId);
};

const toggleSelection = (payment) => {
    const index = selectedPayments.value.findIndex(p => p.payment_id === payment.payment_id);
    if (index === -1) {
        selectedPayments.value.push(payment);
    } else {
        selectedPayments.value.splice(index, 1);
    }
};

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedPayments.value = [];
    } else {
        selectedPayments.value = [...props.payments];
    }
};

const confirmBulkResend = () => {
    showBulkResendModal.value = true;
};

const closeBulkResendModal = () => {
    showBulkResendModal.value = false;
};

const bulkResendEmails = async () => {
    isBulkSending.value = true;
    bulkProgress.value = 0;
    let successCount = 0;
    let errorCount = 0;

    for (const payment of selectedPayments.value) {
        try {
            await axios.post(route('admin.payments.confirmations.resend', payment.payment_id));
            successCount++;
        } catch (error) {
            errorCount++;
        }
        bulkProgress.value++;
    }

    isBulkSending.value = false;
    closeBulkResendModal();
    selectedPayments.value = [];

    if (errorCount === 0) {
        alertWrapper.value?.showSuccess('Éxito', `Se enviaron ${successCount} emails correctamente.`);
    } else {
        alertWrapper.value?.showWarning('Completado con errores', `Enviados: ${successCount}, Errores: ${errorCount}`);
    }

    router.reload();
};

const applyFilters = () => {
    router.get(route('admin.payments.confirmations.index'), filters, {
        preserveState: true,
        preserveScroll: true
    });
};

const clearFilters = () => {
    filters.date_from = '';
    filters.date_to = '';
    filters.program_id = '';
    filters.participant_search = '';
    filters.status = 'all';
    filters.payment_type = 'all';
    applyFilters();
};

const changePage = (page) => {
    router.get(route('admin.payments.confirmations.index'), {
        ...filters,
        page
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

const viewDetails = (paymentId) => {
    selectedPaymentId.value = paymentId;
    showDetailModal.value = true;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedPaymentId.value = null;
};

const confirmResend = (payment) => {
    selectedPayment.value = payment;
    showResendModal.value = true;
};

const closeResendModal = () => {
    showResendModal.value = false;
    selectedPayment.value = null;
};

const resendEmail = async () => {
    try {
        const response = await axios.post(
            route('admin.payments.confirmations.resend', selectedPayment.value.payment_id)
        );

        if (response.data.success) {
            alertWrapper.value?.showSuccess('Éxito', response.data.message);
            closeResendModal();
            // Recargar la página para ver el nuevo log
            router.reload();
        }
    } catch (error) {
        const message = error.response?.data?.message || 'Error al reenviar el email';
        alertWrapper.value?.showError('Error', message);
    }
};
</script>
