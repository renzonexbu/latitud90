<template>
    <div class="min-h-screen bg-gray-50 w-full p-3">
        <!-- Header -->
        <Header class="bg-transparent text-blanco shadow-none"> </Header>

        <!-- Main Content -->
        <div class="max-w-6xl mx-auto py-8 px-4">
            <!-- Error Icon and Title -->
            <div class="text-center mb-8">
                <div
                    class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4"
                >
                    <svg
                        class="w-8 h-8 text-red-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        ></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    {{ failureTitle }}
                </h1>
                <p class="text-lg text-gray-600">{{ failureMessage }}</p>
            </div>

            <!-- Error Details Card -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">
                    Información del Pago
                </h2>

                <div
                    v-if="paymentData"
                    class="grid grid-cols-1 md:grid-cols-2 gap-8"
                >
                    <!-- Información del Programa -->
                    <div v-if="paymentData.program">
                        <h3
                            class="text-lg font-medium text-gray-900 border-b pb-2 mb-4"
                        >
                            Información del Programa
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm text-gray-500"
                                    >Programa:</span
                                >
                                <p class="text-gray-900 font-medium">
                                    {{ paymentData.program.name }}
                                </p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500"
                                    >Destino:</span
                                >
                                <p class="text-gray-900">
                                    {{ paymentData.program.destination }}
                                </p>
                            </div>
                            <div v-if="paymentData.program.departure_date">
                                <span class="text-sm text-gray-500"
                                    >Fecha de Salida:</span
                                >
                                <p class="text-gray-900">
                                    {{
                                        formatDate(
                                            paymentData.program.departure_date
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Información del Pago -->
                    <div>
                        <h3
                            class="text-lg font-medium text-gray-900 border-b pb-2 mb-4"
                        >
                            Detalles del Pago
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm text-gray-500"
                                    >Número de Orden:</span
                                >
                                <p class="text-gray-900 font-mono">
                                    {{
                                        paymentData.order?.order_number || "N/A"
                                    }}
                                </p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500"
                                    >Método de Pago:</span
                                >
                                <p class="text-gray-900">
                                    {{
                                        getPaymentMethodName(
                                            paymentData.gateway_type
                                        )
                                    }}
                                </p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500"
                                    >Monto:</span
                                >
                                <p
                                    class="font-bold text-xl text-red-600"
                                >
                                    ${{ formatCurrency(paymentData.amount) }}
                                </p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500"
                                    >Estado:</span
                                >
                                <p class="text-gray-900">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                                    >
                                        {{ getStatusText(paymentData.status || paymentData.payment?.status || paymentData.order_detail?.status) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensaje cuando no hay datos del pago -->
                <div v-else class="text-center py-8">
                    <p class="text-gray-500">
                        No se pudo obtener la información del pago.
                    </p>
                </div>
            </div>

            <!-- Información de Contacto -->
            <div
                v-if="showContactInfo"
                class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8"
            >
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg
                            class="h-6 w-6 text-blue-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-blue-800">
                            ¿Necesitas Ayuda?
                        </h3>
                        <div class="mt-2 text-blue-700">
                            <p class="text-sm">
                                Si se te descontó dinero de tu cuenta pero el
                                pago no se procesó correctamente, contacta a
                                nuestro equipo de atención al cliente para
                                verificar el estado de tu transacción.
                            </p>
                            <div class="mt-4 space-y-2">
                                <p class="text-sm">
                                    <strong>Email:</strong>
                                    <a
                                        href="mailto:contacto@latitud90.cl"
                                        class="underline hover:text-blue-800"
                                    >
                                        contacto@latitud90.cl
                                    </a>
                                </p>
                                <p class="text-sm">
                                    <strong>WhatsApp:</strong>
                                    <a
                                        href="https://wa.me/56912345678"
                                        target="_blank"
                                        class="underline hover:text-blue-800"
                                    >
                                        +56 9 1234 5678
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button
                    @click="goToHome"
                    class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-300"
                >
                    <svg
                        class="w-5 h-5 mr-2"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                        ></path>
                    </svg>
                    Volver al Inicio
                </button>
            </div>
        </div>

        <!-- Footer -->
        <Footer class="rounded-lg"></Footer>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { router } from "@inertiajs/vue3";
import Header from "@/Components/Ecommerce/Header.vue";
import Footer from "@/Components/Ecommerce/Footer.vue";

const props = defineProps({
    paymentData: {
        type: Object,
        default: null,
    },
    errorMessage: {
        type: String,
        default: "El pago no pudo ser procesado correctamente.",
    },
    rut: {
        type: String,
        default: "",
    },
});

// Obtener parámetros de la URL para determinar el tipo de error
const urlParams = new URLSearchParams(window.location.search);
const errorStatus = urlParams.get("status");

// Computed properties para manejar diferentes estados de error
const failureTitle = computed(() => {
    switch (errorStatus) {
        case "pending_validation":
            return "Pago Pendiente de Validación";
        case "max_attempts":
            return "Máximo de Intentos Alcanzado";
        case "rejected":
            return "Pago Rechazado";
        case "canceled":
            return "Pago Cancelado";
        case "error":
            return "Error Técnico";
        default:
            return "Error en el Pago";
    }
});

const failureMessage = computed(() => {
    switch (errorStatus) {
        case "pending_validation":
            return "No se pudo confirmar tu pago en este momento. Durante el día nuestro sistema continuará verificando el estado de tu transacción y te notificaremos cuando se confirme el pago.";
        case "max_attempts":
            return "No se pudo confirmar tu pago después de varios intentos. Durante el día nuestro sistema continuará verificando el estado de tu transacción automáticamente.";
        case "rejected":
            return "Tu pago fue rechazado por el banco o la pasarela de pago. Por favor, verifica los datos de tu tarjeta o intenta con otro método de pago.";
        case "canceled":
            return "El pago fue cancelado. Puedes intentar nuevamente cuando lo desees.";
        case "error":
            return "Ocurrió un error técnico durante el procesamiento del pago. Por favor, intenta nuevamente.";
        default:
            return props.errorMessage;
    }
});

const showContactInfo = computed(() => {
    return ["pending_validation", "max_attempts", "error"].includes(
        errorStatus
    );
});

// Funciones de formato
const formatCurrency = (amount) => {
    if (!amount) return "0";
    return new Intl.NumberFormat("es-CL").format(amount);
};

const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    const date = new Date(dateString);
    const day = date.getDate();
    const month = date.getMonth() + 1;
    const year = date.getFullYear();
    return `${day} de ${getMonthName(month)} ${year}`;
};

const getMonthName = (month) => {
    const months = [
        "Enero",
        "Febrero",
        "Marzo",
        "Abril",
        "Mayo",
        "Junio",
        "Julio",
        "Agosto",
        "Septiembre",
        "Octubre",
        "Noviembre",
        "Diciembre",
    ];
    return months[month - 1];
};

const getPaymentMethodName = (method) => {
    const methods = {
        transbank: "Tarjeta de Crédito/Débito",
        khipu: "Transferencia Khipu",
        debit: "Tarjeta de Débito",
        credit: "Tarjeta de Crédito",
    };
    return methods[method] || method || "N/A";
};

const getStatusText = (status) => {
    const statuses = {
        pending: "Pendiente",
        failed: "Fallido",
        rejected: "Rechazado",
        cancelled: "Cancelado",
        canceled: "Cancelado",
        error: "Error",
        rechazado: "Rechazado",
        cancelado: "Cancelado",
        processing: "Procesando",
        overdue: "Vencido"
    };
    return statuses[status] || status || "Fallido";
};

// Funciones de navegación
const goToHome = () => {
    const homeUrl = props.rut ? `/` : "/";
    router.visit(homeUrl);
};

// Auto-scroll to top al montar
import { onMounted } from "vue";

onMounted(() => {
    window.scrollTo(0, 0);
    recordPaymentFailed();
});

const recordPaymentFailed = () => {
    // Obtener session_id desde localStorage
    const sessionId = localStorage.getItem('analytics_session_id');
    
    if (!sessionId) {
        console.warn('No se encontró session_id en localStorage');
        return;
    }
    
    // Obtener datos del pago desde localStorage
    const storedData = localStorage.getItem('selectedPaymentData');
    let paymentData = {};
    if (storedData) {
        try {
            paymentData = JSON.parse(storedData);
        } catch (e) {
            console.warn('Error parsing stored payment data:', e);
        }
    }
    
    // Obtener parámetros de la URL para determinar el tipo de error
    const urlParams = new URLSearchParams(window.location.search);
    const errorStatus = urlParams.get("status");
    
    // Enviar datos de pago fallido al backend
    fetch('/api/analytics/payment-failed', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
            session_id: sessionId,
            program_id: props.paymentData?.program?.id || paymentData.programId,
            participant_rut: props.paymentData?.order_detail?.document_number || props.rut,
            error_message: props.errorMessage || `Error: ${errorStatus}`,
        })
    }).catch(error => {
        console.error('Error recording payment failed:', error);
    });
};
</script>
