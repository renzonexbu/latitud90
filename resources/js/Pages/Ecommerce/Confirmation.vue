<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <Header />

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Error Alert -->
            <div v-if="showPaymentError" class="mb-6">
                <div class="bg-red-50 border border-red-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                {{ paymentErrorTitle }}
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p>{{ paymentErrorMessage }}</p>
                            </div>
                            <div v-if="showSuggestions" class="mt-4 space-y-2">
                                <p class="text-xs text-red-600">
                                    <strong>Sugerencias:</strong>
                                    <ul class="mt-1 ml-4 list-disc">
                                        <li>Verifica que los datos de tu tarjeta sean correctos</li>
                                        <li>Confirma que tienes fondos suficientes</li>
                                        <li>Intenta con otra tarjeta o método de pago</li>
                                        <li>Contacta a tu banco si el problema persiste</li>
                                    </ul>
                                </p>
                            </div>
                            
                            <div v-if="showActionButtons" class="mt-4">
                                <div class="flex flex-col sm:flex-row gap-2">
                                    <button
                                        @click="retryPayment"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        Intentar con otro método de pago
                                    </button>
                                    <button
                                        @click="goToHome"
                                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                        </svg>
                                        Volver al inicio
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Process Steps -->
            <ProcessSteps :current-step="4" />

            <!-- Cards Container -->
            <div class="flex flex-col md:flex-row items-center md:items-start justify-center gap-6 my-8">
                <!-- Left Card - Payment Panel -->
                <div class="w-full md:w-1/2 bg-transparent rounded-lg">
                    <div class="w-full max-w-[480px] mx-auto md:mx-0 p-4 sm:p-6">
                        <PaymentPanel
                            :final-payment-date="
                                confirmationData.program.final_payment_date
                            "
                            :program-id="programId"
                            :program="confirmationData.program"
                            :is-confirmation="true"
                            :show-header="false"
                            :show-warning="true"
                            :show-remaining-amount="false"
                            :show-payment-button="false"
                            @terms-accepted-updated="handleTermsAcceptedUpdate"
                            @payment-selection-updated="handlePaymentSelection"
                        />
                    </div>
                </div>

                <!-- Right Card -->
                <div class="w-full md:w-1/2">
                    <div class="w-full max-w-[480px] mx-auto md:mx-0 p-4 sm:p-6">
                        <!-- Aviso de fecha límite si hay cuota activa -->
                        <div v-if="confirmationData.program && confirmationData.program.active_installment && confirmationData.program.active_installment.due_date"
                             class="bg-[#FFF7E6] border border-[#F5C26B] text-[#7A5E10] rounded-md p-3 mb-3">
                            <span class="font-nexa text-[12px]">Fecha límite de pago de la próxima cuota:</span>
                            <span class="font-nexa-bold text-[12px] ml-1">{{ formatDueDate(confirmationData.program.active_installment.due_date) }}</span>
                        </div>
                        <ConfirmationCard
                            :program="confirmationData.program"
                            :form-data="confirmationData.form_data"
                            ref="confirmCard"
                        />
                    </div>
                </div>
            </div>

            <!-- Botón Volver a Completar Datos -->
            <div class="mx-4 md:mx-[120px] mt-8">
                <BackToHomeButton
                    :rut="rut"
                    variant="programs"
                    text="Volver a completar datos"
                    :route="`/programs/${programId}/payment?from=confirmation&rut=${rut}`"
                />
            </div>
        </div>

        <!-- Footer -->
        <Footer class="mt-16" />
    </div>
</template>

<script>
import Header from "@/Components/Ecommerce/Header.vue";
import Footer from "@/Components/Ecommerce/Footer.vue";
import ProcessSteps from "@/Components/Ecommerce/ProcessSteps.vue";
import BackToHomeButton from "@/Components/Ecommerce/BackToHomeButton.vue";
import PaymentPanel from "@/Components/Ecommerce/ProgramDetailComponents/PaymentPanel.vue";
import ConfirmationCard from "@/Components/Ecommerce/ConfirmationCard.vue";

export default {
    name: "Confirmation",
    components: {
        Header,
        Footer,
        ProcessSteps,
        BackToHomeButton,
        PaymentPanel,
        ConfirmationCard,
    },
    props: {
        confirmationData: {
            type: Object,
            required: true,
        },
        programId: {
            type: [String, Number],
            required: true,
        },
        rut: {
            type: String,
            default: "",
        },
    },

    computed: {
        showPaymentError() {
            return this.$page.props.flash.error || this.paymentErrorStatus;
        },
        
        paymentErrorTitle() {
            if (this.paymentErrorStatus === 'rejected') {
                return 'Pago Rechazado';
            } else if (this.paymentErrorStatus === 'canceled') {
                return 'Pago Cancelado';
            } else if (this.paymentErrorStatus === 'error') {
                return 'Ha Ocurrido un Error';
            }
            return 'Error en el Pago';
        },
        
        paymentErrorMessage() {
            if (this.paymentErrorStatus === 'rejected') {
                return 'Tu pago fue rechazado por el banco o la pasarela de pago.';
            } else if (this.paymentErrorStatus === 'canceled') {
                return 'El pago fue cancelado.';
            } else if (this.paymentErrorStatus === 'error') {
                return 'Ha ocurrido un error durante el procesamiento del pago.';
            }
            return this.$page.props.flash.error || 'El pago no pudo ser procesado correctamente.';
        },
        
                     showSuggestions() {
                 return false; // No mostrar sugerencias para ningún tipo de error
             },
             
             showActionButtons() {
                 return false; // No mostrar botones de acción para ningún tipo de error
             }
    },

    data() {
        return {
            paymentErrorStatus: null,
            paymentErrorMessage: null
        };
    },

    mounted() {
        // Auto-scroll to top
        window.scrollTo(0, 0);
        
        // Verificar si hay parámetros de error en la URL
        this.checkPaymentError();
    },
    methods: {
        formatDueDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('es-CL', { day: '2-digit', month: '2-digit', year: 'numeric' });
        },
        handlePaymentSelection(selection) {
            const card = this.$refs.confirmCard;
            if (card && selection) {
                card.currentInstallments = selection.installments || 1;
            }
        },
        handleTermsAcceptedUpdate(termsAccepted) {
            // Actualizar el estado de términos aceptados en localStorage
            const paymentData = localStorage.getItem("selectedPaymentData");
            if (paymentData) {
                try {
                    const parsedData = JSON.parse(paymentData);
                    parsedData.termsAccepted = termsAccepted;
                    localStorage.setItem("selectedPaymentData", JSON.stringify(parsedData));
                } catch (error) {
                    console.error("Error updating payment data:", error);
                }
            }
        },
        checkPaymentError() {
            // Obtener parámetros de la URL
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            const message = urlParams.get('message');
            
            if (status) {
                this.paymentErrorStatus = status;
                if (message) {
                    this.paymentErrorMessage = decodeURIComponent(message);
                }
                
                // Limpiar la URL para evitar mostrar el error en recargas
                const newUrl = new URL(window.location);
                newUrl.searchParams.delete('status');
                newUrl.searchParams.delete('message');
                window.history.replaceState({}, '', newUrl);
            }
        },
        
        retryPayment() {
            // Redirigir a la página de pago para intentar nuevamente
            const paymentUrl = `/programs/${this.programId}/payment?from=confirmation&rut=${this.rut}`;
            window.location.href = paymentUrl;
        },
        
        goToHome() {
            // Redirigir al inicio
            const homeUrl = this.rut ? `/?rut=${encodeURIComponent(this.rut)}` : '/';
            window.location.href = homeUrl;
        },
    },
};
</script>
