<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <Header />

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
};
</script>
