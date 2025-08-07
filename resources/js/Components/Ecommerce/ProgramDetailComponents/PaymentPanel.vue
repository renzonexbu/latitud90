<template>
    <div
        class="bg-white rounded-[20px] border border-[#D3D3D3] p-[40px_30px] shadow-[0px_4px_59.3px_0px_rgba(229,229,229,0.25)] w-full h-fit mx-5"
    >
        <div class="flex flex-col gap-[24px]">
            <!-- Header Section -->
            <div v-if="showHeader" class="flex flex-col gap-[14px]">
                <h2
                    class="text-[#007E93] font-outfit-semibold text-[20px] leading-[61.43px] font-semibold"
                >
                    Selecione la formas de pago
                </h2>
                <p class="text-[#5B5B5B] font-nexa text-sm leading-[18px]">
                    ¡Selecciona la forma de pago que mejor se adapte a ti, total
                    o en cuotas! Para cualquier consulta, no dudes en
                    escribirnos por
                    <span class="underline">WhatsApp</span>
                </p>
            </div>

            <!-- Payment Options Section -->
            <div class="flex flex-col gap-[18px]">
                <!-- Pago Total Option -->
                <PaymentOption
                    v-if="program.enable_total_payment"
                    :is-selected="paymentType === 'total'"
                    :is-accordion-open="accordionOpen === 'total'"
                    title="Pago total"
                    @select="selectPaymentType('total')"
                >
                    <template #accordion-content>
                        <div class="flex flex-col gap-[9px]">
                            <PaymentSubOption
                                v-for="option in getTotalPaymentOptions()"
                                :key="option.value"
                                :option="option"
                                :is-selected="
                                    paymentType === 'total' &&
                                    totalPaymentOption === option.value
                                "
                                @select="selectTotalPaymentOption(option.value)"
                            />
                        </div>
                    </template>
                </PaymentOption>

                <!-- Mensual Option -->
                <PaymentOption
                    v-if="program.enable_lat90_payment"
                    :is-selected="paymentType === 'monthly'"
                    :is-accordion-open="accordionOpen === 'monthly'"
                    title="Mensual | Cuota Lat 90"
                    @select="selectPaymentType('monthly')"
                >
                    <template #accordion-content>
                        <div class="flex flex-col gap-[9px]">
                            <!-- Warning Message -->
                            <MonthlyWarningMessage v-if="showWarning" />

                            <!-- Monthly Payment Options -->
                            <PaymentSubOption
                                v-for="option in getMonthlyPaymentOptions()"
                                :key="option.value"
                                :option="option"
                                :is-selected="
                                    paymentType === 'monthly' &&
                                    monthlyPaymentOption === option.value
                                "
                                @select="
                                    selectMonthlyPaymentOption(option.value)
                                "
                            />

                            <!-- Installment Selection Section -->
                            <div
                                class="flex flex-col gap-[12px] mt-[16px] ml-4"
                            >
                                <!-- Title -->
                                <p
                                    class="text-[#5B5B5B] font-nexa text-[14px] leading-[18px] font-normal"
                                >
                                    Elige la cantidad de cuotas para fraccionar
                                </p>

                                <!-- Installment Selector and Date -->
                                <div
                                    class="flex flex-row items-center gap-[16px]"
                                >
                                    <!-- Installment Selector -->
                                    <div class="relative">
                                        <select
                                            v-model="selectedInstallments"
                                            class="appearance-none bg-white border border-[#D3D3D3] rounded-lg px-[12px] py-[8px] text-[#434343] font-nexa text-[14px] leading-[18px] pr-[32px] shadow-sm"
                                        >
                                            <option 
                                                v-for="installment in getAvailableInstallments()" 
                                                :key="installment" 
                                                :value="installment"
                                            >
                                                {{ installment }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- End Date -->
                                    <div
                                        class="flex flex-row items-center gap-[8px]"
                                    >
                                        <span
                                            class="text-[#007E93] font-nexa text-[12px] leading-[13px] font-normal"
                                        >
                                            Fecha de finalización
                                        </span>
                                        <span
                                            class="text-[#007E93] font-nexa text-[12px] leading-[13px] font-normal"
                                        >
                                            {{
                                                formatEndDate(finalPaymentDate)
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </PaymentOption>
            </div>

            <!-- Payment Summary Section -->
            <div v-if="showRemainingAmount" class="border-t border-[#D3D3D3] pt-[14px]">
                <div class="flex flex-row items-center justify-between">
                    <span
                        class="text-[#434343] font-nexa text-[20px] leading-[28px] font-bold"
                    >
                        Faltan pagar
                    </span>
                    <span
                        class="text-[#434343] font-nexa text-[30px] leading-[36px] font-bold"
                    >
                        $1800
                    </span>
                </div>
            </div>

            <!-- Payment Button -->
            <button
                v-if="showPaymentButton"
                class="rounded-[35px] p-[11px_20px] h-[55.94px] w-full transition-colors duration-300"
                :class="{
                    'bg-[#FBBD51] cursor-pointer': paymentType !== null,
                    'bg-[#C7C7C7] cursor-not-allowed': paymentType === null,
                }"
                :disabled="paymentType === null"
                @click="initiatePayment"
            >
                <span
                    class="text-white font-urbanist-semibold text-[18px] leading-[18px] font-semibold"
                >
                    Iniciar pago
                </span>
            </button>
        </div>
    </div>
</template>

<script>
import PaymentOption from "./PaymentOption.vue";
import PaymentSubOption from "./PaymentSubOption.vue";
import MonthlyWarningMessage from "./MonthlyWarningMessage.vue";
import { router } from "@inertiajs/vue3";

export default {
    name: "PaymentPanel",
    components: {
        PaymentOption,
        PaymentSubOption,
        MonthlyWarningMessage,
    },
    props: {
        finalPaymentDate: {
            type: String,
            required: true,
        },
        programId: {
            type: [String, Number],
            required: true,
        },
        program: {
            type: Object,
            required: true,
        },
        isConfirmation: {
            type: Boolean,
            default: false
        },
        showHeader: {
            type: Boolean,
            default: true
        },
        showWarning: {
            type: Boolean,
            default: true
        },
        showRemainingAmount: {
            type: Boolean,
            default: true
        },
        showPaymentButton: {
            type: Boolean,
            default: true
        },
        showSeparator: {
            type: Boolean,
            default: true
        },
    },
    watch: {
        selectedInstallments(newValue) {
            // Guardar en localStorage cuando cambie el número de cuotas
            if (this.paymentType === 'monthly') {
                this.savePaymentDataToLocalStorage();
            }
        }
    },
    mounted() {
        // Debug: Log de la configuración del programa
        console.log('Configuración de pago del programa:', {
            enable_total_payment: this.program.enable_total_payment,
            total_payment_method_id: this.program.total_payment_method_id,
            total_payment_method: this.program.total_payment_method,
            enable_lat90_payment: this.program.enable_lat90_payment,
            lat90_payment_method_id: this.program.lat90_payment_method_id,
            lat90_payment_method: this.program.lat90_payment_method,
            lat90_max_installments: this.program.lat90_max_installments
        });
        
        // Si está en modo confirmación, cargar datos desde localStorage
        if (this.isConfirmation) {
            this.loadPaymentDataFromLocalStorage();
        }
    },
    data() {
        return {
            paymentType: null,
            totalPaymentOption: null,
            monthlyPaymentOption: null,
            accordionOpen: null,
            selectedInstallments: 12,
            // Opciones base de pago
            allPaymentOptions: [
                {
                    value: "debit",
                    label: "Tarjeta de Debito",
                    description: null,
                },
                {
                    value: "credit",
                    label: "Tarjeta de Credito",
                    description: "3, 6 o 12 cuotas sin interés, con cualquier promo bancaria",
                },
                {
                    value: "khipu",
                    label: "Transferencia Khipu",
                    description: null,
                },
            ],
        };
    },
    methods: {
        // Obtener opciones de pago total según la configuración del programa
        getTotalPaymentOptions() {
            if (!this.program.enable_total_payment || !this.program.total_payment_method_id) {
                return [];
            }

            const methodId = this.program.total_payment_method_id;
            return this.filterPaymentOptionsByMethod(methodId);
        },

        // Obtener opciones de pago mensual según la configuración del programa
        getMonthlyPaymentOptions() {
            if (!this.program.enable_lat90_payment || !this.program.lat90_payment_method_id) {
                return [];
            }

            const methodId = this.program.lat90_payment_method_id;
            return this.filterPaymentOptionsByMethod(methodId);
        },

        // Filtrar opciones según el método de pago configurado
        filterPaymentOptionsByMethod(methodId) {
            switch (methodId) {
                case 1: // Todos los medios (Débito/Crédito/Transferencia)
                    return this.allPaymentOptions;
                
                case 2: // Solo pago con Tarjeta (Débito/Crédito)
                    return this.allPaymentOptions.filter(option => 
                        option.value === 'debit' || option.value === 'credit'
                    );
                
                case 3: // Solo pago transferencia
                    return this.allPaymentOptions.filter(option => 
                        option.value === 'khipu'
                    );
                
                case 4: // Solo pago contado (Débito/Transferencia)
                    return this.allPaymentOptions.filter(option => 
                        option.value === 'debit' || option.value === 'khipu'
                    );
                
                default:
                    return this.allPaymentOptions;
            }
        },

        // Obtener cuotas disponibles según la configuración del programa
        getAvailableInstallments() {
            const maxInstallments = this.program.lat90_max_installments || 12;
            const installments = [];
            
            for (let i = 1; i <= Math.min(maxInstallments, 12); i++) {
                installments.push(i);
            }
            
            return installments;
        },

        selectPaymentType(type) {
            if (this.paymentType === type) {
                this.paymentType = null;
                this.accordionOpen = null;
                this.totalPaymentOption = null;
                this.monthlyPaymentOption = null;
            } else {
                this.paymentType = type;
                this.accordionOpen = type;

                if (type === "monthly") {
                    this.totalPaymentOption = null;
                    // Seleccionar la primera opción disponible por defecto
                    const availableOptions = this.getMonthlyPaymentOptions();
                    if (availableOptions.length > 0 && !this.monthlyPaymentOption) {
                        this.monthlyPaymentOption = availableOptions[0].value;
                    }
                } else if (type === "total") {
                    this.monthlyPaymentOption = null;
                    // Seleccionar la primera opción disponible por defecto
                    const availableOptions = this.getTotalPaymentOptions();
                    if (availableOptions.length > 0 && !this.totalPaymentOption) {
                        this.totalPaymentOption = availableOptions[0].value;
                    }
                }
            }
            
            // Guardar en localStorage en tiempo real
            this.savePaymentDataToLocalStorage();
        },
        selectTotalPaymentOption(option) {
            this.paymentType = "total";
            this.accordionOpen = "total";
            this.totalPaymentOption = option;
            
            // Guardar en localStorage en tiempo real
            this.savePaymentDataToLocalStorage();
        },
        selectMonthlyPaymentOption(option) {
            this.paymentType = "monthly";
            this.accordionOpen = "monthly";
            this.monthlyPaymentOption = option;
            
            // Guardar en localStorage en tiempo real
            this.savePaymentDataToLocalStorage();
        },
        formatEndDate(date) {
            if (!date) return "No especificada";
            const dateObj = new Date(date);
            const day = dateObj.getDate();
            const month = dateObj.getMonth() + 1;
            const year = dateObj.getFullYear();
            return `${day.toString().padStart(2, "0")}/${month
                .toString()
                .padStart(2, "0")}/${year.toString().slice(-2)}`;
        },
        
        savePaymentDataToLocalStorage() {
            if (this.paymentType === null) {
                return;
            }

            // Obtener datos existentes para preservar términos aceptados
            const existingData = localStorage.getItem("selectedPaymentData");
            let existingTermsAccepted = false;
            
            if (existingData) {
                try {
                    const parsed = JSON.parse(existingData);
                    existingTermsAccepted = parsed.termsAccepted || false;
                } catch (error) {
                    console.error("Error parsing existing payment data:", error);
                }
            }

            const paymentData = {
                paymentType: this.paymentType,
                paymentMethod:
                    this.paymentType === "total"
                        ? this.totalPaymentOption
                        : this.monthlyPaymentOption,
                installments:
                    this.paymentType === "monthly"
                        ? this.selectedInstallments
                        : 1,
                termsAccepted: existingTermsAccepted, // Preservar términos aceptados
            };

            // Guardar en localStorage
            localStorage.setItem(
                "selectedPaymentData",
                JSON.stringify(paymentData)
            );
            
            console.log('Payment data saved to localStorage:', paymentData);
        },
        
        loadPaymentDataFromLocalStorage() {
            const savedPaymentData = localStorage.getItem("selectedPaymentData");
            if (savedPaymentData) {
                try {
                    const paymentData = JSON.parse(savedPaymentData);
                    
                    // Cargar los datos de pago
                    this.paymentType = paymentData.paymentType;
                    this.selectedInstallments = paymentData.installments || 12;
                    
                    // Configurar las opciones según el tipo de pago
                    if (paymentData.paymentType === 'total') {
                        this.totalPaymentOption = paymentData.paymentMethod;
                        this.accordionOpen = 'total';
                    } else if (paymentData.paymentType === 'monthly') {
                        this.monthlyPaymentOption = paymentData.paymentMethod;
                        this.accordionOpen = 'monthly';
                    }
                    
                    // Emitir evento para actualizar términos aceptados en el componente padre
                    if (paymentData.termsAccepted !== undefined) {
                        this.$emit('terms-accepted-updated', paymentData.termsAccepted);
                    }
                    
                    console.log('Payment data loaded from localStorage:', paymentData);
                } catch (error) {
                    console.error("Error parsing payment data:", error);
                }
            } else {
                console.log("No payment data found in localStorage");
            }
        },
        initiatePayment() {
            if (this.paymentType === null) {
                return;
            }

            console.log("Iniciando pago con:", {
                paymentType: this.paymentType,
                paymentMethod:
                    this.paymentType === "total"
                        ? this.totalPaymentOption
                        : this.monthlyPaymentOption,
                installments:
                    this.paymentType === "monthly"
                        ? this.selectedInstallments
                        : 1,
            });

            // Guardar los datos de pago en la sesión o localStorage
            const paymentData = {
                paymentType: this.paymentType,
                paymentMethod:
                    this.paymentType === "total"
                        ? this.totalPaymentOption
                        : this.monthlyPaymentOption,
                installments:
                    this.paymentType === "monthly"
                        ? this.selectedInstallments
                        : 1,
            };

            // Guardar en localStorage para que esté disponible en la siguiente vista
            localStorage.setItem(
                "selectedPaymentData",
                JSON.stringify(paymentData)
            );

            // Redirigir a la vista de detalles de pago usando URL directa con RUT
            router.visit(`/programs/${this.programId}/payment`, {
                data: { rut: this.$page.props.rut || this.$page.props.participant?.rut }
            });
        },
    },
};
</script>

<style scoped>
/* Transiciones de acordeón */
.accordion-slide-enter-active,
.accordion-slide-leave-active {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    transform-origin: top;
    overflow: visible;
}

.accordion-slide-enter-from {
    opacity: 0;
    max-height: 0;
    transform: translateY(-20px) scaleY(0.8);
    padding-top: 0;
    padding-bottom: 0;
}

.accordion-slide-enter-to {
    opacity: 1;
    max-height: 5000px;
    transform: translateY(0) scaleY(1);
    padding-top: 20px;
    padding-bottom: 30px;
}

.accordion-slide-leave-from {
    opacity: 1;
    max-height: 5000px;
    transform: translateY(0) scaleY(1);
    padding-top: 20px;
    padding-bottom: 30px;
}

.accordion-slide-leave-to {
    opacity: 0;
    max-height: 0;
    transform: translateY(-20px) scaleY(0.8);
    padding-top: 0;
    padding-bottom: 0;
}
</style>
