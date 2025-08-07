<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <Header />

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Error Alert -->
            <div v-if="$page.props.flash.error" class="mb-6">
                <div class="bg-red-50 border border-red-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                Error en el pago
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p>{{ $page.props.flash.error }}</p>
                            </div>
                            <div class="mt-4">
                                <button
                                    @click="retryPayment"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                >
                                    Intentar nuevamente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Process Steps -->
            <ProcessSteps :current-step="4" />

            <!-- Cards Container -->
            <div class="flex gap-6 my-8">
                <!-- Left Card - Payment Panel -->
                <div class="w-1/2 bg-transparent rounded-lg">
                    <div class="p-6">
                        <PaymentPanel
                            :final-payment-date="
                                confirmationData.program.final_payment_date
                            "
                            :program-id="programId"
                            :program="confirmationData.program"
                            :is-confirmation="true"
                            :show-header="false"
                            :show-warning="false"
                            :show-remaining-amount="false"
                            :show-payment-button="false"
                            @terms-accepted-updated="handleTermsAcceptedUpdate"
                        />
                    </div>
                </div>

                <!-- Right Card -->
                <div class="w-1/2">
                    <div class="p-6">
                        <ConfirmationCard
                            :program="confirmationData.program"
                            :form-data="confirmationData.form_data"
                        />
                    </div>
                </div>
            </div>

            <!-- Botón Volver a Completar Datos -->
            <div class="mx-[120px] mt-8">
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

    mounted() {
        // Auto-scroll to top
        window.scrollTo(0, 0);

        // Debug: Verificar datos del programa
        console.log("Confirmation data:", this.confirmationData);
        console.log("Program data:", this.confirmationData.program);
        console.log("Payment settings:", {
            enable_total_payment:
                this.confirmationData.program?.enable_total_payment,
            enable_lat90_payment:
                this.confirmationData.program?.enable_lat90_payment,
            total_payment_method_id:
                this.confirmationData.program?.total_payment_method_id,
            lat90_payment_method_id:
                this.confirmationData.program?.lat90_payment_method_id,
        });
    },
    methods: {
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
        retryPayment() {
            // Simular un nuevo intento de pago
            // Esto podría redirigir al usuario a procesar el pago nuevamente
            console.log('Reintentando pago...');
            // Por ahora, solo recargar la página
            window.location.reload();
        },
    },
};
</script>
