<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <Header />

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Process Steps -->
            <ProcessSteps :current-step="3" />

            <!-- Content Section -->
            <div class="mt-8 flex justify-center">
                <!-- Form -->
                <div class="flex flex-col gap-8">
                    <div class="flex flex-row gap-[54px]">
                        <!-- Left Column - Personal Information -->
                        <div class="flex flex-col gap-[18px] w-[364px]">
                            <!-- Nombre completo -->
                            <div class="flex flex-col gap-[12px]">
                                <label
                                    class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal"
                                >
                                    Nombre completo *
                                </label>
                                <input
                                    type="text"
                                    placeholder="Nombre y apellido"
                                    class="w-full h-[46px] bg-white rounded-lg border border-[#5B5B5B] px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                    v-model="formData.fullName"
                                />
                            </div>

                            <!-- RUT/Pasaporte -->
                            <div class="flex flex-col gap-[12px]">
                                <label
                                    class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal"
                                >
                                    RUT/Pasaporte *
                                </label>
                                <input
                                    type="text"
                                    placeholder="Ingresa su RUT*"
                                    class="w-full h-[46px] bg-white rounded-lg border border-[#5B5B5B] px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                    v-model="formData.rut"
                                />
                            </div>

                            <!-- Correo electrónico -->
                            <div class="flex flex-col gap-[12px]">
                                <label
                                    class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal"
                                >
                                    Correo electrónico *
                                </label>
                                <input
                                    type="email"
                                    placeholder="Escriba su correo electronico"
                                    class="w-full h-[46px] bg-white rounded-lg border border-[#5B5B5B] px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                    v-model="formData.email"
                                />
                            </div>

                            <!-- Número de celular -->
                            <div class="flex flex-col gap-[12px]">
                                <label
                                    class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal"
                                >
                                    Número de celular *
                                </label>
                                <div class="flex">
                                    <select
                                        v-model="formData.code_phone"
                                        class="w-[70px] h-[46px] bg-white border border-[#5B5B5B] rounded-l border-r-0 flex items-center justify-center gap-2 px-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none"
                                    >
                                        <option value="+56">🇨🇱</option>
                                        <option value="+54">🇦🇷</option>
                                        <option value="+51">🇵🇪</option>
                                        <option value="+598">🇺🇾</option>
                                    </select>
                                    <input
                                        type="tel"
                                        placeholder="9-- --- ---"
                                        class="flex-1 h-[46px] bg-white border rounded-r px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                        v-model="formData.phone"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Location and Agreements -->
                        <div class="flex flex-col gap-[21px] w-[366px]">
                            <!-- Location Information -->
                            <div class="flex flex-col gap-[21px]">
                                <!-- País -->
                                <div class="flex flex-col gap-[12px]">
                                    <label
                                        class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal"
                                    >
                                        País *
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="Selecciona su pais"
                                        class="w-full h-[46px] bg-white rounded-lg border border-[#5B5B5B] px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                        v-model="formData.country"
                                    />
                                </div>

                                <div class="flex flex-col gap-[12px]">
                                    <label
                                        class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal"
                                    >
                                        Región
                                    </label>
                                    <select
                                        v-model="formData.region"
                                        class="w-full h-[46px] bg-white rounded-lg border border-[#5B5B5B] px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none"
                                    >
                                        <option value="">
                                            Selecciona su región
                                        </option>
                                        <option
                                            v-for="region in regions"
                                            :key="region.id"
                                            :value="region.id"
                                        >
                                            {{ region.name }}
                                        </option>
                                    </select>
                                </div>

                                <div class="flex flex-col gap-[12px]">
                                    <label
                                        class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal"
                                    >
                                        Comuna
                                    </label>
                                    <select
                                        v-model="formData.city"
                                        class="w-full h-[46px] bg-white rounded-lg border border-[#5B5B5B] px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none"
                                        :disabled="!formData.region"
                                    >
                                        <option value="">
                                            {{
                                                formData.region
                                                    ? "Selecciona su comuna"
                                                    : "Primero selecciona una región"
                                            }}
                                        </option>
                                        <option
                                            v-for="comune in filteredComunes"
                                            :key="comune.id"
                                            :value="comune.id"
                                        >
                                            {{ comune.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Agreements - Moved to align with phone input -->
                            <div class="flex flex-col gap-[7px] mt-[18px]">
                                <!-- Terms and Conditions -->
                                <div class="flex flex-col gap-[8px]">
                                    <div class="flex items-center gap-[8px]">
                                        <input
                                            type="checkbox"
                                            class="w-[12px] h-[12px] rounded-[1.5px]"
                                            v-model="formData.termsAccepted"
                                        />
                                        <div
                                            class="text-[#434343] font-nexa text-[10px] leading-[16px] font-normal"
                                        >
                                            <span class="font-nexa"
                                                >Acepto todos los</span
                                            >
                                            <a
                                                href="/terminos-y-condiciones"
                                                target="_blank"
                                                class="font-nexa-bold font-bold underline hover:text-[#007E93] transition-colors"
                                            >
                                                términos y condiciones*
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Marketing Agreement -->
                                <div class="flex flex-col gap-[8px]">
                                    <div class="flex items-center gap-[8px]">
                                        <input
                                            type="checkbox"
                                            class="w-[12px] h-[12px] rounded-[1.5px]"
                                            v-model="formData.marketingAccepted"
                                        />
                                        <div
                                            class="text-[#434343] font-nexa text-[10px] leading-[16px] font-normal"
                                        >
                                            Acepto recibir información sobre
                                            programas educativos, viajes y
                                            ofertas
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between items-center w-full gap-8 mt-12">
                <!-- Back Button -->
                <BackToHomeButton
                    :rut="rut"
                    variant="programs"
                    @click="goBackToProgram"
                />

                <!-- Continue Button -->
                <button
                    class="rounded-[59px] bg-[#C7C7C7] flex px-[20px] py-[11px] justify-center items-center gap-[12px] transition-colors duration-300"
                    :class="{
                        'bg-[#FBBD51] cursor-pointer': isFormValid,
                        'bg-[#C7C7C7] cursor-not-allowed': !isFormValid,
                    }"
                    :disabled="!isFormValid"
                    @click="continueToPayment"
                >
                    <span class="text-white font-medium">Continuar</span>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path
                            d="M2.25 12C2.25 17.385 6.615 21.75 12 21.75C17.385 21.75 21.75 17.385 21.75 12C21.75 6.615 17.385 2.25 12 2.25C6.615 2.25 2.25 6.615 2.25 12ZM12.47 7.97C12.6106 7.82955 12.8012 7.75066 13 7.75066C13.1988 7.75066 13.3894 7.82955 13.53 7.97L17.03 11.47C17.1705 11.6106 17.2493 11.8012 17.2493 12C17.2493 12.1988 17.1705 12.3894 17.03 12.53L13.53 16.03C13.4613 16.1037 13.3785 16.1628 13.2865 16.2038C13.1945 16.2448 13.0952 16.2668 12.9945 16.2686C12.8938 16.2704 12.7938 16.2518 12.7004 16.2141C12.607 16.1764 12.5222 16.1203 12.451 16.049C12.3797 15.9778 12.3236 15.893 12.2859 15.7996C12.2482 15.7062 12.2296 15.6062 12.2314 15.5055C12.2332 15.4048 12.2552 15.3055 12.2962 15.2135C12.3372 15.1215 12.3963 15.0387 12.47 14.97L14.69 12.75H7.5C7.30109 12.75 7.11032 12.671 6.96967 12.5303C6.82902 12.3897 6.75 12.1989 6.75 12C6.75 11.8011 6.82902 11.6103 6.96967 11.4697C7.11032 11.329 7.30109 11.25 7.5 11.25H14.69L12.47 9.03C12.3295 8.88937 12.2507 8.69875 12.2507 8.5C12.2507 8.30125 12.3295 8.11063 12.47 7.97Z"
                            fill="white"
                        />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Footer -->
        <Footer class="mt-16" />
    </div>
</template>

<script>
import { Head } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";
import Header from "@/Components/Ecommerce/Header.vue";
import Footer from "@/Components/Ecommerce/Footer.vue";
import ProcessSteps from "@/Components/Ecommerce/ProcessSteps.vue";
import BackToHomeButton from "@/Components/Ecommerce/BackToHomeButton.vue";

export default {
    name: "PaymentDetails",
    components: {
        Header,
        Footer,
        ProcessSteps,
        BackToHomeButton,
    },
    props: {
        paymentData: {
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
        regions: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            selectedPaymentType: "total",
            selectedPaymentMethod: "debit",
            formData: {
                fullName: "",
                rut: "",
                email: "",
                phone: "",
                code_phone: "+56",
                country: "",
                region: "",
                city: "",
                termsAccepted: false,
                marketingAccepted: false,
            },
            isFormValid: false,
        };
    },
    computed: {
        filteredComunes() {
            if (!this.formData.region) return [];
            const selectedRegion = this.regions.find(
                (r) => r.id == this.formData.region
            );
            return selectedRegion ? selectedRegion.comunes : [];
        },
    },
    mounted() {
        // Leer los datos de pago del localStorage
        const savedPaymentData = localStorage.getItem("selectedPaymentData");
        if (savedPaymentData) {
            try {
                const paymentData = JSON.parse(savedPaymentData);
                this.selectedPaymentType = paymentData.paymentType;
                this.selectedPaymentMethod = paymentData.paymentMethod;
                console.log("Payment data loaded:", paymentData);
            } catch (error) {
                console.error("Error parsing payment data:", error);
            }
        } else {
            console.log("No payment data found in localStorage");
        }
    },
    watch: {
        formData: {
            deep: true,
            handler() {
                this.validateForm();
            },
        },
        "formData.region"() {
            // Limpiar la comuna cuando cambie la región
            this.formData.city = "";
        },
    },
    methods: {
        validateForm() {
            this.isFormValid =
                this.formData.fullName.trim() !== "" &&
                this.formData.rut.trim() !== "" &&
                this.formData.email.trim() !== "" &&
                this.formData.phone.trim() !== "" &&
                this.formData.country.trim() !== "" &&
                this.formData.termsAccepted;
        },
        goBackToProgram() {
            // Usar el router directamente para ir a program detail
            router.visit(`/programs/${this.programId}`);
        },
        continueToPayment() {
            if (!this.isFormValid) return;

            // Aquí procesarías el formulario y continuarías al pago
            console.log("Form data:", this.formData);

            // Guardar datos del formulario en localStorage
            localStorage.setItem("formData", JSON.stringify(this.formData));

            // Continuar al gateway de pago
            router.visit(
                `/payment/gateway?programId=${this.programId}&paymentType=${this.selectedPaymentType}&paymentMethod=${this.selectedPaymentMethod}`
            );
        },
    },
};
</script>
