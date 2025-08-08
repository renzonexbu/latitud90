<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <Header />

        <!-- Main Content -->
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Success Icon and Title -->
            <div class="text-center mb-8">
                <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">¡Pago Exitoso!</h1>
                <p class="text-lg text-gray-600">Tu pago ha sido procesado correctamente</p>
            </div>

            <!-- Payment Details Card -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Detalles de la Transacción</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Program Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Información del Programa</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm font-medium text-gray-500">Programa:</span>
                                <p class="text-gray-900">{{ paymentData.program?.name || 'N/A' }}</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Destino:</span>
                                <p class="text-gray-900">{{ paymentData.program?.destination || 'N/A' }}</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Fecha de Salida:</span>
                                <p class="text-gray-900">{{ formatDate(paymentData.program?.departure_date) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Información del Pago</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm font-medium text-gray-500">Número de Orden:</span>
                                <p class="text-gray-900 font-mono">{{ paymentData.order_number || 'N/A' }}</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Método de Pago:</span>
                                <p class="text-gray-900">{{ getPaymentMethodName(paymentData.payment_method) }}</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Monto Pagado:</span>
                                <p class="text-2xl font-bold text-green-600">${{ formatCurrency(paymentData.amount) }}</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Fecha de Pago:</span>
                                <p class="text-gray-900">{{ formatDateTime(paymentData.paid_at) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Participant Information -->
                <div class="mt-8 pt-6 border-t">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Información del Participante</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <span class="text-sm font-medium text-gray-500">Nombre:</span>
                            <p class="text-gray-900">{{ paymentData.participant_name || 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">{{ documentLabel }}:</span>
                            <p class="text-gray-900 font-mono">{{ formattedDocument }}</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">Email:</span>
                            <p class="text-gray-900">{{ paymentData.participant_email || 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">Teléfono:</span>
                            <p class="text-gray-900">{{ paymentData.participant_phone || 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Transaction Details -->
                <div class="mt-8 pt-6 border-t">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Detalles de la Transacción</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <span class="text-sm font-medium text-gray-500">ID de Transacción:</span>
                            <p class="text-gray-900 font-mono text-sm">{{ paymentData.transaction_id || 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">Estado:</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Pagado
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button
                    @click="goToHome"
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-[#007E93] hover:bg-[#005a6b] transition-colors duration-300"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Volver al Inicio
                </button>
                
                <button
                    @click="downloadReceipt"
                    class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-300"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Descargar Comprobante
                </button>
            </div>
        </div>

        <!-- Footer -->
        <Footer class="mt-16" />
    </div>
</template>

<script>
import Header from "@/Components/Ecommerce/Header.vue";
import Footer from "@/Components/Ecommerce/Footer.vue";
import { router } from "@inertiajs/vue3";

export default {
    name: "SuccessfulPayment",
    components: {
        Header,
        Footer,
    },
    props: {
        paymentData: {
            type: Object,
            required: true,
        },
    },
    computed: {
        documentLabel() {
            const t = (this.paymentData.document_type_name || '').toLowerCase();
            if (t === 'rut') return 'RUT';
            if (t) return t.toUpperCase();
            return 'Documento';
        },
        formattedDocument() {
            const t = (this.paymentData.document_type_name || '').toLowerCase();
            const num = this.paymentData.document_number || '';
            if (!num) return 'N/A';
            if (t === 'rut') return this.formatRut(num);
            return String(num).toUpperCase();
        }
    },
    methods: {
        formatCurrency(amount) {
            if (!amount) return "0";
            return new Intl.NumberFormat("es-CL").format(amount);
        },
        formatDate(dateString) {
            if (!dateString) return "N/A";
            const date = new Date(dateString);
            const day = date.getDate();
            const month = date.getMonth() + 1;
            const year = date.getFullYear();
            return `${day} de ${this.getMonthName(month)} ${year}`;
        },
        formatDateTime(dateTimeString) {
            if (!dateTimeString) return "N/A";
            const date = new Date(dateTimeString);
            return date.toLocaleString("es-CL", {
                year: "numeric",
                month: "long",
                day: "numeric",
                hour: "2-digit",
                minute: "2-digit",
            });
        },
        getMonthName(month) {
            const months = [
                "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
                "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
            ];
            return months[month - 1];
        },
        getPaymentMethodName(method) {
            const methods = {
                'debit': 'Tarjeta de Débito',
                'credit': 'Tarjeta de Crédito',
                'khipu': 'Transferencia Khipu'
            };
            return methods[method] || method || 'N/A';
        },
        formatRut(raw) {
            // Normalizar: quitar puntos y guión si vienen
            const clean = String(raw).replace(/\./g, '').replace(/-/g, '').toUpperCase();
            if (clean.length < 2) return raw;
            const body = clean.slice(0, -1);
            const dv = clean.slice(-1);
            // Formatear body con puntos cada 3
            let formattedBody = '';
            for (let i = body.length - 1, j = 0; i >= 0; i--, j++) {
                if (j > 0 && j % 3 === 0) formattedBody = '.' + formattedBody;
                formattedBody = body[i] + formattedBody;
            }
            return `${formattedBody}-${dv}`;
        },
        goToHome() {
            router.visit('/');
        },
        downloadReceipt() {
            // Aquí puedes implementar la descarga del comprobante
            console.log('Descargando comprobante...');
            // Por ahora, mostrar un mensaje
            alert('Función de descarga de comprobante en desarrollo');
        }
    },
    mounted() {
        // Auto-scroll to top
        window.scrollTo(0, 0);
        
        // Limpiar localStorage después del pago exitoso
        localStorage.removeItem('selectedPaymentData');
        localStorage.removeItem('paymentFormData');
    }
};
</script>
