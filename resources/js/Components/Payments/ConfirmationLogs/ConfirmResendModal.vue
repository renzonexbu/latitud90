<template>
    <div class="fixed inset-0 z-50 overflow-y-auto" @click.self="cancel">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay -->
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="cancel"></div>

            <!-- Modal -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <!-- Header -->
                <div class="bg-blue-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-white">
                            Reenviar Email de Confirmación
                        </h3>
                        <button @click="cancel" class="text-white hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-6 py-4">
                    <div class="mb-4">
                        <p class="text-gray-700 mb-2">
                            ¿Está seguro que desea reenviar el email de confirmación para este pago?
                        </p>
                    </div>

                    <!-- Payment Info -->
                    <div class="bg-gray-50 p-4 rounded-lg mb-4">
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-gray-600"># Orden:</p>
                                <p class="font-medium">{{ payment.order_number }}</p>
                            </div>
                            <div>
                                <p class="text-gray-600">Monto:</p>
                                <p class="font-medium">${{ payment.amount }}</p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-gray-600">Participante:</p>
                                <p class="font-medium">{{ payment.participant.name }}</p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-gray-600">Destinatario:</p>
                                <p class="font-medium">{{ payment.email_recipient }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Documents that will be sent -->
                    <div class="mb-4">
                        <p class="text-sm font-medium text-gray-700 mb-2">
                            Documentos que se enviarán:
                        </p>
                        <ul v-if="hasDocuments" class="space-y-1">
                            <li v-if="payment.events?.payment_receipt" class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Comprobante de Pago
                            </li>
                            <li v-if="payment.events?.contract" class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Contrato de Reserva
                            </li>
                            <li v-if="payment.events?.bsale_invoice" class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Boleta Bsale
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-500 italic">
                            Los documentos se generarán automáticamente (comprobante de pago, contrato si aplica, boleta si existe).
                        </p>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <div class="flex">
                            <svg class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm text-blue-800">
                                Se enviará el email de confirmación con todos los documentos correspondientes al destinatario.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                    <button
                        @click="cancel"
                        class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="confirm"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Reenviar Email
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    payment: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['confirm', 'cancel']);

const hasDocuments = computed(() => {
    return props.payment.events?.payment_receipt ||
           props.payment.events?.contract ||
           props.payment.events?.bsale_invoice;
});

const confirm = () => {
    emit('confirm');
};

const cancel = () => {
    emit('cancel');
};
</script>
