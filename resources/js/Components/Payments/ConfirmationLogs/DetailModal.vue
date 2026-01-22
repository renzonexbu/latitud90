<template>
    <div class="fixed inset-0 z-50 overflow-y-auto" @click.self="close">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay -->
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="close"></div>

            <!-- Modal -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <!-- Header -->
                <div class="bg-turquesa px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-white">
                            Historial de Confirmación - Pago #{{ paymentData?.payment?.order_number }}
                        </h3>
                        <button @click="close" class="text-white hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="px-6 py-8 text-center">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-turquesa"></div>
                    <p class="mt-2 text-gray-600">Cargando detalles...</p>
                </div>

                <!-- Error -->
                <div v-else-if="error" class="px-6 py-8 text-center">
                    <p class="text-red-600">{{ error }}</p>
                    <button @click="loadDetails" class="mt-4 text-turquesa hover:text-turquesa-dark">
                        Reintentar
                    </button>
                </div>

                <!-- Content -->
                <div v-else-if="paymentData" class="px-6 py-4">
                    <!-- Información del Pago -->
                    <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Participante</p>
                                <p class="font-medium">{{ paymentData.payment.participant.name }}</p>
                                <p class="text-sm text-gray-500">{{ paymentData.payment.participant.document }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Programa</p>
                                <p class="font-medium">{{ paymentData.payment.program.name }}</p>
                                <p class="text-sm text-gray-500">{{ paymentData.payment.program.destination }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Fecha de Pago</p>
                                <p class="font-medium">{{ paymentData.payment.payment_date }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Monto</p>
                                <p class="font-medium">${{ paymentData.payment.amount }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline de Eventos -->
                    <div class="mb-4">
                        <h4 class="font-medium text-gray-900 mb-4">Línea de Tiempo de Eventos</h4>

                        <div v-if="paymentData.logs.length === 0" class="text-center py-4 text-gray-500">
                            No hay eventos registrados para este pago
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="(log, index) in paymentData.logs"
                                :key="log.id"
                                class="relative pl-8 pb-4"
                                :class="{ 'border-l-2 border-gray-200': index < paymentData.logs.length - 1 }"
                            >
                                <!-- Icono del evento -->
                                <div class="absolute left-0 top-0 -translate-x-1/2">
                                    <div
                                        class="w-8 h-8 rounded-full flex items-center justify-center text-white"
                                        :class="getEventColor(log.status)"
                                    >
                                        <span v-if="log.status === 'success'">✓</span>
                                        <span v-else-if="log.status === 'failed'">✗</span>
                                        <span v-else>-</span>
                                    </div>
                                </div>

                                <!-- Contenido del evento -->
                                <div class="bg-white border rounded-lg p-4 shadow-sm">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h5 class="font-medium text-gray-900">
                                                {{ getEventLabel(log.event_type) }}
                                            </h5>
                                            <p class="text-sm text-gray-500">{{ log.created_at }}</p>
                                        </div>
                                        <span
                                            class="px-2 py-1 text-xs font-medium rounded-full"
                                            :class="getStatusBadgeClass(log.status)"
                                        >
                                            {{ getStatusLabel(log.status) }}
                                        </span>
                                    </div>

                                    <!-- Error message -->
                                    <div v-if="log.error_message" class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-sm text-red-700">
                                        <strong>Error:</strong> {{ log.error_message }}
                                    </div>

                                    <!-- File info -->
                                    <div v-if="log.file_name" class="mt-2 text-sm text-gray-600">
                                        <strong>Archivo:</strong> {{ log.file_name }}
                                    </div>

                                    <!-- Bsale info -->
                                    <div v-if="log.bsale_number" class="mt-2 text-sm text-gray-600">
                                        <strong>N° Boleta Bsale:</strong> {{ log.bsale_number }}
                                    </div>

                                    <!-- Email info -->
                                    <div v-if="log.email_recipient" class="mt-2 text-sm text-gray-600">
                                        <strong>Destinatario:</strong> {{ log.email_recipient }}
                                    </div>

                                    <!-- Email attachments -->
                                    <div v-if="log.email_attachments && log.email_attachments.length > 0" class="mt-2">
                                        <p class="text-sm text-gray-600 font-medium">Adjuntos:</p>
                                        <ul class="list-disc list-inside text-sm text-gray-600">
                                            <li v-for="(attachment, idx) in log.email_attachments" :key="idx">
                                                {{ attachment.name || attachment.type }}
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Triggered by user -->
                                    <div v-if="log.triggered_by_user" class="mt-2 text-sm text-gray-500 italic">
                                        Realizado por: {{ log.triggered_by_user.name }}
                                    </div>

                                    <!-- Details expandable -->
                                    <div v-if="log.details && Object.keys(log.details).length > 0" class="mt-2">
                                        <button
                                            @click="toggleDetails(log.id)"
                                            class="text-sm text-turquesa hover:text-turquesa-dark"
                                        >
                                            {{ expandedDetails[log.id] ? '▼' : '▶' }} Ver detalles técnicos
                                        </button>
                                        <pre
                                            v-if="expandedDetails[log.id]"
                                            class="mt-2 p-2 bg-gray-100 rounded text-xs overflow-x-auto"
                                        >{{ JSON.stringify(log.details, null, 2) }}</pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 flex justify-end">
                    <button
                        @click="close"
                        class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import axios from 'axios';

const props = defineProps({
    paymentId: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['close']);

const loading = ref(true);
const error = ref(null);
const paymentData = ref(null);
const expandedDetails = reactive({});

const close = () => {
    emit('close');
};

const loadDetails = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await axios.get(route('admin.payments.confirmations.show', props.paymentId));
        paymentData.value = response.data;
    } catch (err) {
        error.value = err.response?.data?.message || 'Error al cargar los detalles';
    } finally {
        loading.value = false;
    }
};

const toggleDetails = (logId) => {
    expandedDetails[logId] = !expandedDetails[logId];
};

const getEventLabel = (eventType) => {
    const labels = {
        'payment_receipt_generated': '📄 Comprobante de Pago Generado',
        'contract_generated': '📋 Contrato Generado',
        'bsale_invoice_generated': '🧾 Boleta Bsale Generada',
        'email_sent': '📧 Email de Confirmación Enviado',
        'email_failed': '❌ Error al Enviar Email',
        'email_resent': '🔄 Email Reenviado'
    };
    return labels[eventType] || eventType;
};

const getStatusLabel = (status) => {
    const labels = {
        'success': 'Exitoso',
        'failed': 'Fallido',
        'skipped': 'Omitido'
    };
    return labels[status] || status;
};

const getStatusBadgeClass = (status) => {
    const classes = {
        'success': 'bg-green-100 text-green-800',
        'failed': 'bg-red-100 text-red-800',
        'skipped': 'bg-gray-100 text-gray-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getEventColor = (status) => {
    const colors = {
        'success': 'bg-green-500',
        'failed': 'bg-red-500',
        'skipped': 'bg-gray-500'
    };
    return colors[status] || 'bg-gray-500';
};

onMounted(() => {
    loadDetails();
});
</script>
