<template>
    <div class="bg-white rounded-[20px] border border-[#D3D3D3] p-[40px_30px] shadow-[0px_4px_59.3px_0px_rgba(229,229,229,0.25)] w-full h-fit mx-5">
        <div class="flex flex-col gap-[24px]">
            <!-- Header Section -->
            <div class="flex flex-col gap-[14px]">
                <h2 class="text-[#007E93] font-outfit-semibold text-[20px] leading-[61.43px] font-semibold">
                    Selecione la formas de pago
                </h2>
                <p class="text-[#5B5B5B] font-nexa text-sm leading-[18px]">
                    ¡Selecciona la forma de pago que mejor se adapte a ti, total o en cuotas! Para cualquier consulta, no dudes en escribirnos por
                    <span class="underline">WhatsApp</span>
                </p>
            </div>

            <!-- Payment Options Section -->
            <div class="flex flex-col gap-[18px]">
                <!-- Pago Total Option -->
                <PaymentOption
                    :is-selected="paymentType === 'total'"
                    :is-accordion-open="accordionOpen === 'total'"
                    title="Pago total"
                    @select="selectPaymentType('total')"
                >
                    <template #accordion-content>
                        <div class="flex flex-col gap-[9px]">
                            <PaymentSubOption
                                v-for="option in totalPaymentOptions"
                                :key="option.value"
                                :option="option"
                                :is-selected="paymentType === 'total' && totalPaymentOption === option.value"
                                @select="selectTotalPaymentOption(option.value)"
                            />
                        </div>
                    </template>
                </PaymentOption>

                <!-- Mensual Option -->
                <PaymentOption
                    :is-selected="paymentType === 'monthly'"
                    :is-accordion-open="accordionOpen === 'monthly'"
                    title="Mensual | Cuota Lat 90"
                    @select="selectPaymentType('monthly')"
                >
                    <template #accordion-content>
                        <div class="flex flex-col gap-[9px]">
                            <!-- Warning Message -->
                            <MonthlyWarningMessage />
                            
                            <!-- Monthly Payment Options -->
                            <PaymentSubOption
                                v-for="option in monthlyPaymentOptions"
                                :key="option.value"
                                :option="option"
                                :is-selected="paymentType === 'monthly' && monthlyPaymentOption === option.value"
                                @select="selectMonthlyPaymentOption(option.value)"
                            />

                            <!-- Installment Selection Section -->
                            <div class="flex flex-col gap-[12px] mt-[16px] ml-4">
                                <!-- Title -->
                                <p class="text-[#5B5B5B] font-nexa text-[14px] leading-[18px] font-normal">
                                    Elige la cantidad de cuotas para fraccionar
                                </p>

                                <!-- Installment Selector and Date -->
                                <div class="flex flex-row items-center gap-[16px]">
                                    <!-- Installment Selector -->
                                    <div class="relative">
                                        <select 
                                            v-model="selectedInstallments"
                                            class="appearance-none bg-white border border-[#D3D3D3] rounded-lg px-[12px] py-[8px] text-[#434343] font-nexa text-[14px] leading-[18px] pr-[32px] shadow-sm"
                                        >
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                            <option value="5">5</option>
                                            <option value="6">6</option>
                                            <option value="7">7</option>
                                            <option value="8">8</option>
                                            <option value="9">9</option>
                                            <option value="10">10</option>
                                            <option value="11">11</option>
                                            <option value="12">12</option>
                                        </select>
                                    </div>

                                    <!-- End Date -->
                                    <div class="flex flex-row items-center gap-[8px]">
                                        <span class="text-[#007E93] font-nexa text-[12px] leading-[13px] font-normal">
                                            Fecha de finalización
                                        </span>
                                        <span class="text-[#007E93] font-nexa text-[12px] leading-[13px] font-normal">
                                            {{ formatEndDate(finalPaymentDate) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </PaymentOption>
            </div>

            <!-- Payment Summary Section -->
            <div class="border-t border-[#D3D3D3] pt-[14px]">
                <div class="flex flex-row items-center justify-between">
                    <span class="text-[#434343] font-nexa text-[20px] leading-[28px] font-bold">
                        Faltan pagar
                    </span>
                    <span class="text-[#434343] font-nexa text-[30px] leading-[36px] font-bold">
                        $1800
                    </span>
                </div>
            </div>

            <!-- Payment Button -->
            <button 
                class="rounded-[35px] p-[11px_20px] h-[55.94px] w-full transition-colors duration-300"
                :class="{
                    'bg-[#FBBD51] cursor-pointer': paymentType !== null,
                    'bg-[#C7C7C7] cursor-not-allowed': paymentType === null
                }"
                :disabled="paymentType === null"
            >
                <span class="text-white font-urbanist-semibold text-[18px] leading-[18px] font-semibold">
                    Iniciar pago
                </span>
            </button>
        </div>
    </div>
</template>

<script>
import PaymentOption from './PaymentOption.vue'
import PaymentSubOption from './PaymentSubOption.vue'
import MonthlyWarningMessage from './MonthlyWarningMessage.vue'

export default {
    name: "PaymentPanel",
    components: {
        PaymentOption,
        PaymentSubOption,
        MonthlyWarningMessage
    },
    props: {
        finalPaymentDate: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            paymentType: null,
            totalPaymentOption: null,
            monthlyPaymentOption: null,
            accordionOpen: null,
            selectedInstallments: 12,
            totalPaymentOptions: [
                {
                    value: 'debit',
                    label: 'Tarjeta de Debito',
                    description: null
                },
                {
                    value: 'credit',
                    label: 'Tarjeta de Credito',
                    description: '3, 6 o 12 cuotas sin interés, con cualquier promo bancaria'
                },
                {
                    value: 'khipu',
                    label: 'Transferencia Khipu',
                    description: null
                }
            ],
            monthlyPaymentOptions: [
                {
                    value: 'debit',
                    label: 'Tarjeta de Debito',
                    description: null
                },
                {
                    value: 'credit',
                    label: 'Tarjeta de Credito',
                    description: null,
                    warning: 'Solo se efectuará 1 cuota'
                },
                {
                    value: 'khipu',
                    label: 'Transferencia Khipu',
                    description: null
                }
            ]
        };
    },
    methods: {
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
                    if (!this.monthlyPaymentOption) {
                        this.monthlyPaymentOption = "debit";
                    }
                } else if (type === "total") {
                    this.monthlyPaymentOption = null;
                    if (!this.totalPaymentOption) {
                        this.totalPaymentOption = "debit";
                    }
                }
            }
        },
        selectTotalPaymentOption(option) {
            this.paymentType = "total";
            this.accordionOpen = "total";
            this.totalPaymentOption = option;
        },
        selectMonthlyPaymentOption(option) {
            this.paymentType = "monthly";
            this.accordionOpen = "monthly";
            this.monthlyPaymentOption = option;
        },
        formatEndDate(date) {
            if (!date) return 'No especificada';
            const dateObj = new Date(date);
            const day = dateObj.getDate();
            const month = dateObj.getMonth() + 1;
            const year = dateObj.getFullYear();
            return `${day.toString().padStart(2, '0')}/${month.toString().padStart(2, '0')}/${year.toString().slice(-2)}`;
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
