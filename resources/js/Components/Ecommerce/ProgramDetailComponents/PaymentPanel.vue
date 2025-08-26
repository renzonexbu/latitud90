<template>
    <div :class="containerClass">
        <div class="flex flex-col gap-[24px] w-full md:max-w-none mx-auto">
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
                <!-- Si hay cuota activa (orden mensual existente), no permitir cambiar tipo; mostrar solo subopciones -->
                <template v-if="program.active_installment && program.payment_plan_locked">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[#434343] font-nexa text-[16px] leading-[22px] font-bold">
                            Cuota {{ program.active_installment.number }} de {{ program.active_installment.total }}
                        </span>
                        <span class="text-[#434343] font-nexa text-[20px] leading-[28px] font-bold">
                            {{ formatPrice(program.active_installment.amount) }}
                        </span>
                    </div>
                    <div class="flex flex-col gap-[9px]">
                        <PaymentSubOption
                            v-for="option in getMonthlyPaymentOptions()"
                            :key="option.value"
                            :option="option"
                            :is-selected="monthlyPaymentOption === option.value"
                            @select="selectMonthlyPaymentOption(option.value)"
                        />
                    </div>
                </template>
                <template v-else>
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
                                class="flex flex-col gap-[12px] mt-[16px] ml-0 md:ml-4"
                            >
                                <!-- Title -->
                                <p
                                    class="text-[#5B5B5B] font-nexa text-[14px] leading-[18px] font-normal"
                                >
                                    Elige la cantidad de cuotas para fraccionar
                                </p>

                                <!-- Installment Selector and Date -->
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center gap-[16px]"
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
                </template>
            </div>

            <!-- Payment Summary Section -->
            <div v-if="showRemainingAmount" class="border-t border-[#D3D3D3] pt-[14px]">
                <!-- Mensaje de pago completo -->
                <div v-if="isPaymentComplete" class="mb-4">
                    <div class="bg-green-50 border border-green-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">
                                    Pago Completo
                                </h3>
                                <div class="mt-2 text-sm text-green-700">
                                    <p>Ya has pagado el monto total del programa. No hay pagos pendientes.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensaje de monto excedido -->
                <div v-else-if="isAmountExceeded" class="mb-4">
                    <div class="bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">
                                    Monto Excedido
                                </h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p>El monto a pagar excede el saldo pendiente. Por favor, selecciona un número menor de cuotas.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resumen de pago normal -->
                <div v-else class="flex flex-row items-center justify-between">
                    <span
                        class="text-[#434343] font-nexa text-[20px] leading-[28px] font-bold"
                    >
                        Pagarás
                    </span>
                    <span
                        class="text-[#434343] font-nexa text-[30px] leading-[36px] font-bold"
                    >
                        {{ formatPrice(displayRemainingAmount) }}
                    </span>
                </div>
            </div>

            <!-- Payment Button -->
            <button
                v-if="showPaymentButton"
                class="rounded-[35px] p-[11px_20px] h-[55.94px] w-full transition-colors duration-300"
                :class="{
                    'bg-[#FBBD51] cursor-pointer': canProceedWithPayment,
                    'bg-[#C7C7C7] cursor-not-allowed': !canProceedWithPayment,
                }"
                :disabled="!canProceedWithPayment"
                @click="initiatePayment"
            >
                <span
                    class="text-white font-urbanist-semibold text-[18px] leading-[18px] font-semibold"
                >
                    {{ getPaymentButtonText() }}
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
import { getFirstInstallmentAmount, formatPrice, splitAmountInInstallments } from "@/utils/paymentUtils";

export default {
    name: "PaymentPanel",
    emits: ['terms-accepted-updated', 'payment-selection-updated'],
    components: {
        PaymentOption,
        PaymentSubOption,
        MonthlyWarningMessage,
    },
    props: {
        isMobileOverlay: {
            type: Boolean,
            default: false
        },
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
                this.emitSelection();
            }
        }
    },
    mounted() {
        // Si está en modo confirmación, cargar datos desde localStorage
        if (this.isConfirmation) {
            this.loadPaymentDataFromLocalStorage();
            this.$nextTick(() => this.reconcileSelection());
        } else {
            // Si hay cuota activa, configurar automáticamente para esa cuota
            if (this.program.active_installment && this.program.payment_plan_locked) {
                this.paymentType = 'monthly';
                this.accordionOpen = 'monthly';
                this.selectedInstallments = this.program.active_installment.total;
                const availableOptions = this.getMonthlyPaymentOptions();
                if (availableOptions.length > 0) {
                    this.monthlyPaymentOption = availableOptions[0].value;
                }
                this.savePaymentDataToLocalStorage();
                this.emitSelection();
                this.$nextTick(() => this.reconcileSelection());
            } else {
                // Estado base por defecto: mensual con mínima cuota permitida si está habilitado
                if (this.program.enable_lat90_payment && !this.paymentType) {
                    this.paymentType = 'monthly';
                    this.accordionOpen = 'monthly';
                    const allowedInst = this.getAvailableInstallments();
                    this.selectedInstallments = allowedInst.length > 0 ? allowedInst[0] : 1;
                    const availableOptions = this.getMonthlyPaymentOptions();
                    if (availableOptions.length > 0) {
                        this.monthlyPaymentOption = availableOptions[0].value;
                    }
                    this.savePaymentDataToLocalStorage();
                    this.emitSelection();
                    this.$nextTick(() => this.reconcileSelection());
                }
            }
        }
    },
    data() {
        return {
            paymentType: null,
            totalPaymentOption: null,
            monthlyPaymentOption: null,
            accordionOpen: null,
            selectedInstallments: 1,
            // Opciones base (visualización); se filtrarán por lo habilitado en el programa
            allPaymentOptions: [
                { value: 'khipu',  label: 'Transferencia Khipu', description: null },
                { value: 'debit_credit_0',  label: 'Débito y Crédito sin cuotas (Webpay)',    description: null },
                { value: 'international',  label: 'Pago Internacional (Webpay)',    description: null },
            ],
        };
    },
    methods: {
        formatPrice(amount) {
            return formatPrice(amount);
        },
        containerBaseClasses() {
            return "w-full h-fit";
        },
        overlayContainerClasses() {
            // Sin fondo/borde propios; el contenedor externo los aporta
            return `${this.containerBaseClasses()} bg-transparent border-0 p-0 shadow-none mx-0`;
        },
        defaultContainerClasses() {
            return `${this.containerBaseClasses()} bg-white rounded-[20px] border border-[#D3D3D3] p-[20px_15px] md:p-[40px_30px] shadow-[0px_4px_59.3px_0px_rgba(229,229,229,0.25)]`;
        },
        
        // Helpers para nuevas estructuras de opciones
        getFullOptionCodes() {
            // full_payment_options puede venir como array de códigos
            return Array.isArray(this.program.full_payment_options) ? this.program.full_payment_options : [];
        },
        getLat90OptionCodes() {
            // Puede venir como array de strings o de objetos {code,label}
            if (!Array.isArray(this.program.lat90_payment_options)) return [];
            return this.program.lat90_payment_options.map(o => typeof o === 'string' ? o : (o.code || ''));
        },
        hasCodeLike(codes, substr) {
            return codes.some(code => String(code).toLowerCase().includes(substr));
        },
        // Obtener opciones de pago total según la configuración del programa (nueva estructura)
        getTotalPaymentOptions() {
            // Nueva estructura basada en códigos
            if (Array.isArray(this.program.full_payment_options)) {
                const codes = this.getFullOptionCodes().map(c => String(c).toLowerCase());
                const result = [];
                const pushUnique = (value, label, description = null, warning = null, order = 999) => {
                    if (!result.some(o => o.value === value)) {
                        const obj = { value, label, order };
                        if (description) obj.description = description;
                        if (warning) obj.warning = warning;
                        result.push(obj);
                    }
                };
                
                codes.forEach(code => {
                    if (code.includes('khipu')) {
                        pushUnique('khipu', 'Pagar con Transferencia Khipu', null, '⚠️ Importante: la primera transferencia a una cuenta nueva tiene un límite bancario de $250.000. Si el monto supera este valor, escríbanos a pagos@latitud90.com para recibir un link de pago.', 1);
                    } else if (code.includes('international')) {
                        pushUnique('international', 'Pagar con Pago Internacional (Webpay)', null, 'Pago con tarjetas internacionales', 999);
                    } else if (code.includes('debit_credit')) {
                        const match = code.match(/(\d+)(?!.*\d)/);
                        if (match) {
                            const n = parseInt(match[1], 10);
                            if (!isNaN(n) && n > 0) {
                                // Orden específico: 3=3, 6=4, 9=5, 12=6
                                let order = 2;
                                if (n === 3) order = 3;
                                else if (n === 6) order = 4;
                                else if (n === 9) order = 5;
                                else if (n === 12) order = 6;
                                else order = 7 + n; // Otros números después
                                pushUnique(`debit_credit_${n}`, `Pagar con Débito y Crédito hasta ${n} cuotas sin interés (Webpay)`, null, null, order);
                            } else {
                                pushUnique('debit_credit_0', 'Pagar con Débito y Crédito sin cuotas (Webpay)', null, null, 2);
                            }
                        } else {
                            pushUnique('debit_credit_0', 'Pagar con Débito y Crédito sin cuotas (Webpay)', null, null, 2);
                        }
                    }
                });
                
                // Ordenar por el campo order y luego remover el campo order
                return result.sort((a, b) => a.order - b.order).map(({ order, ...rest }) => rest);
            }
            // Legacy fallback
            if (!this.program.enable_total_payment || !this.program.total_payment_method_id) return [];
            const baseOptions = this.filterPaymentOptionsByMethod(this.program.total_payment_method_id);
            return baseOptions.map(opt => {
                if (opt.value === 'khipu') {
                    return { ...opt, warning: '⚠️ Importante: la primera transferencia a una cuenta nueva tiene un límite bancario de $250.000. Si el monto supera este valor, escríbanos a pagos@latitud90.com para recibir un link de pago.' };
                }
                return opt;
            });
        },

        // Obtener opciones de pago mensual (Latitud 90), basadas en códigos (nueva estructura)
        getMonthlyPaymentOptions() {
            // Nueva estructura
            if (Array.isArray(this.program.lat90_payment_options)) {
                const codes = this.getLat90OptionCodes().map(c => String(c).toLowerCase());
                const result = [];
                const pushUnique = (value, label, description = null, warning = null) => {
                    if (!result.some(o => o.value === value)) {
                        const obj = { value, label };
                        if (description) obj.description = description;
                        if (warning) obj.warning = warning;
                        result.push(obj);
                    }
                };
                if (codes.some(c => c.includes('khipu'))) {
                    pushUnique('khipu', 'Pagar con Transferencia Khipu', null, '⚠️ Importante: la primera transferencia a una cuenta nueva tiene un límite bancario de $250.000. Si el monto supera este valor, escríbanos a pagos@latitud90.com para recibir un link de pago.');
                }
                if (codes.some(c => c.includes('debit_credit'))) {
                    pushUnique('debit_credit_0', 'Pagar con Débito y Crédito sin cuotas (Webpay)', null, 'Solo se efectuará 1 cuota');
                }
                if (codes.some(c => c.includes('international'))) {
                    pushUnique('international', 'Pagar con Pago Internacional (Webpay)', null, 'Pago con tarjetas internacionales');
                }
                return result;
            }
            // Legacy fallback
            if (!this.program.enable_lat90_payment || !this.program.lat90_payment_method_id) return [];
            const base = this.filterPaymentOptionsByMethod(this.program.lat90_payment_method_id);
            return base.map(opt => {
                if (opt.value === 'khipu') {
                    return { ...opt, warning: '⚠️ Importante: la primera transferencia a una cuenta nueva tiene un límite bancario de $250.000. Si el monto supera este valor, escríbanos a pagos@latitud90.com para recibir un link de pago.' };
                } else if (opt.value === 'credit') {
                    return { ...opt, description: null };
                }
                return opt;
            });
        },

        // Filtrar opciones según el método de pago configurado
        filterPaymentOptionsByMethod(methodId) {
            switch (methodId) {
                case 1: // Todos los medios (Débito/Crédito/Transferencia/Internacional)
                    return [
                        { value: 'khipu',  label: 'Pagar con Transferencia Khipu', description: null },
                        { value: 'debit_credit_0',  label: 'Pagar con Débito y Crédito sin cuotas (Webpay)',    description: null },
                        { value: 'international',  label: 'Pagar con Pago Internacional (Webpay)',    description: null },
                    ];
                
                case 2: // Solo pago con Tarjeta (Débito/Crédito/Internacional)
                    return [
                        { value: 'debit_credit_0',  label: 'Pagar con Débito y Crédito sin cuotas (Webpay)',    description: null },
                        { value: 'international',  label: 'Pagar con Pago Internacional (Webpay)',    description: null },
                    ];
                
                case 3: // Solo pago transferencia
                    return [
                        { value: 'khipu',  label: 'Pagar con Transferencia Khipu', description: null },
                    ];
                
                case 4: // Solo pago contado (Débito/Transferencia)
                    return [
                        { value: 'khipu',  label: 'Pagar con Transferencia Khipu', description: null },
                        { value: 'debit_credit_0',  label: 'Pagar con Débito y Crédito sin cuotas (Webpay)',    description: null },
                    ];
                
                default:
                    return [
                        { value: 'khipu',  label: 'Pagar con Transferencia Khipu', description: null },
                        { value: 'debit_credit_0',  label: 'Pagar con Débito y Crédito sin cuotas (Webpay)',    description: null },
                        { value: 'international',  label: 'Pagar con Pago Internacional (Webpay)',    description: null },
                    ];
            }
        },

        // Obtener cuotas disponibles según la configuración del programa
        getAvailableInstallments() {
            // Nueva estructura: derivar SOLO desde lat90_installments_X de program.lat90_payment_options
            if (Array.isArray(this.program.lat90_payment_options)) {
                const codes = this.getLat90OptionCodes().map(c => String(c).toLowerCase());
                const installments = new Set();
                codes.forEach(code => {
                    // Aceptar formatos: lat90_installments_3, lat90-installments-6
                    const m = code.match(/lat90[_-]?installments[_-]?(\d+)/);
                    if (m) {
                        const n = parseInt(m[1], 10);
                        if (!isNaN(n) && n > 0) {
                            installments.add(n);
                        }
                    }
                });
                // Si no hay lat90_installments configurados, dejar vacío para no confundir con 1 cuota
                return Array.from(installments).sort((a,b)=>a-b);
            }
            // Legacy fallback: 1..max
            const maxInstallments = this.program.lat90_max_installments || 12;
            const list = [];
            for (let i = 1; i <= Math.min(maxInstallments, 12); i++) list.push(i);
            return list;
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
                    this.selectedInstallments = 1;
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
            this.emitSelection();
        },
        selectTotalPaymentOption(option) {
            this.paymentType = "total";
            this.accordionOpen = "total";
            this.totalPaymentOption = option;
            
            // Guardar en localStorage en tiempo real
            this.savePaymentDataToLocalStorage();
            this.emitSelection();
        },
        selectMonthlyPaymentOption(option) {
            this.paymentType = "monthly";
            this.accordionOpen = "monthly";
            this.monthlyPaymentOption = option;
            // Ajustar cuotas disponibles al cambiar el método
            const allowed = this.getAvailableInstallments();
            if (option === 'debit_credit_0') {
                // Si no contiene la cuota actual, seleccionar la mínima disponible
                if (!allowed.includes(this.selectedInstallments)) {
                    this.selectedInstallments = allowed[0] || 1;
                }
            } else {
                // Khipu: 1 cuota
                this.selectedInstallments = 1;
            }
            // Guardar en localStorage en tiempo real
            this.savePaymentDataToLocalStorage();
            this.emitSelection();
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
        },
        emitSelection() {
            if (this.paymentType === null) return;
            this.$emit('payment-selection-updated', {
                paymentType: this.paymentType,
                paymentMethod: this.paymentType === 'total' ? this.totalPaymentOption : this.monthlyPaymentOption,
                installments: this.paymentType === 'monthly' ? this.selectedInstallments : 1,
            });
        },
        
        loadPaymentDataFromLocalStorage() {
            const savedPaymentData = localStorage.getItem("selectedPaymentData");
            if (savedPaymentData) {
                try {
                    const paymentData = JSON.parse(savedPaymentData);
                    
                    // Cargar los datos de pago
                    this.paymentType = paymentData.paymentType;
                    this.selectedInstallments = paymentData.installments || 1;
                    
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
                    // Emitir selección cargada
                    this.$emit('payment-selection-updated', {
                        paymentType: this.paymentType,
                        paymentMethod: this.paymentType === 'total' ? this.totalPaymentOption : this.monthlyPaymentOption,
                        installments: this.selectedInstallments,
                    });
                } catch (error) {
                    // noop
                }
            } else {
                // noop
            }
        },
        // Asegurar que la opción guardada exista entre las opciones disponibles; si no, tomar la primera
        reconcileSelection() {
            if (this.paymentType === 'total') {
                const options = this.getTotalPaymentOptions();
                const values = options.map(o => o.value);
                if (!values.includes(this.totalPaymentOption) && values.length > 0) {
                    this.totalPaymentOption = values[0];
                }
            } else if (this.paymentType === 'monthly') {
                const options = this.getMonthlyPaymentOptions();
                const values = options.map(o => o.value);
                if (!values.includes(this.monthlyPaymentOption) && values.length > 0) {
                    this.monthlyPaymentOption = values[0];
                }
                // Validar cuotas
                const allowed = this.getAvailableInstallments();
                if (!allowed.includes(this.selectedInstallments)) {
                    this.selectedInstallments = allowed[0] || 1;
                }
            }
        },
        initiatePayment() {
            if (this.paymentType === null) {
                return;
            }

            // Validaciones adicionales
            if (this.isPaymentComplete) {
                alert('Ya has pagado el monto total del programa. No hay pagos pendientes.');
                return;
            }

            if (this.isAmountExceeded) {
                alert('El monto a pagar excede el saldo pendiente. Por favor, selecciona un número menor de cuotas.');
                return;
            }

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

            // Registrar selección de método de pago en analytics
            this.recordPaymentSelection(paymentData);

            // Obtener los parámetros de documento desde las props de la página
            const document = this.$page.props.document || this.$page.props.participant?.document_number || '';
            const documentType = this.$page.props.document_type || 'RUT';
            
            console.log('PaymentPanel - initiatePayment params:', {
                document,
                documentType,
                programId: this.programId
            });
            
            // Construir URL con query parameters (solo document y document_type)
            const params = new URLSearchParams({
                document: document,
                document_type: documentType
            });
            
            const url = `/programs/${this.programId}/payment?${params.toString()}`;
            console.log('PaymentPanel - Generated URL:', url);
            
            // Usar window.location.href para navegación completa
            window.location.href = url;
        },
        
        recordPaymentSelection(paymentData) {
            // Obtener session_id desde localStorage
            const sessionId = localStorage.getItem('analytics_session_id');
            
            if (!sessionId) {
                console.warn('No se encontró session_id en localStorage');
                return;
            }
            
            // Obtener términos aceptados desde localStorage
            const storedData = localStorage.getItem('selectedPaymentData');
            let termsAccepted = false;
            if (storedData) {
                try {
                    const parsed = JSON.parse(storedData);
                    termsAccepted = parsed.termsAccepted || false;
                } catch (e) {
                    console.warn('Error parsing stored payment data:', e);
                }
            }
            
            // Enviar datos de selección de método de pago al backend
            fetch('/api/analytics/payment-selection', {
                method: 'POST',
                credentials: 'same-origin', // Incluir cookies de sesión
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    session_id: sessionId,
                    program_id: this.programId,
                    payment_type: paymentData.paymentType,
                    payment_method: paymentData.paymentMethod,
                    installments: paymentData.installments,
                    amount: this.displayRemainingAmount,
                    terms_accepted: termsAccepted,
                })
            }).catch(error => {
                console.error('Error recording payment selection:', error);
            });
        },
        getPaymentButtonText() {
            if (this.isPaymentComplete) {
                return 'Pago Completo';
            }
            if (this.isAmountExceeded) {
                return 'Monto Excedido';
            }
            if (this.paymentType === null) {
                return 'Selecciona método de pago';
            }
            return 'Iniciar pago';
        },
    },
    computed: {
        containerClass() {
            return this.isMobileOverlay
                ? this.overlayContainerClasses()
                : this.defaultContainerClasses();
        },
        displayRemainingAmount() {
            // Si hay cuota activa bloqueada, mostrar el monto de esa cuota, no dividir el saldo
            if (this.program.active_installment && this.program.payment_plan_locked) {
                return Number(this.program.active_installment.amount) || 0;
            }
            const base = this.program.participant_balance ?? this.program.participant_total_due ?? this.program.trip_price;
            if (!this.paymentType || this.paymentType === 'total') {
                return Number(base) || 0;
            }
            const installments = Math.max(1, Number(this.selectedInstallments || 1));
            
            // Usar el método estandarizado de redondeo
            const result = getFirstInstallmentAmount(Number(base) || 0, installments);
            
            // Debug log
            console.log('=== DEBUG displayRemainingAmount ===');
            console.log('base:', base);
            console.log('participant_balance:', this.program.participant_balance);
            console.log('participant_total_due:', this.program.participant_total_due);
            console.log('trip_price:', this.program.trip_price);
            console.log('paymentType:', this.paymentType);
            console.log('selectedInstallments:', this.selectedInstallments);
            console.log('installments:', installments);
            console.log('result:', result);
            console.log('====================================');
            
            return result;
        },
        isPaymentComplete() {
            const balance = Number(this.program.participant_balance ?? 0);
            return balance <= 0;
        },
        isAmountExceeded() {
            if (this.isPaymentComplete) return false;
            
            const balance = Number(this.program.participant_balance ?? 0);
            
            // Para pagos totales, verificar que no exceda el balance
            if (this.paymentType === 'total') {
                const amountToPay = Number(this.displayRemainingAmount ?? 0);
                return amountToPay > balance;
            }
            
            // Para pagos mensuales, verificar que el total de las cuotas no exceda el balance
            if (this.paymentType === 'monthly') {
                const totalInstallments = Number(this.selectedInstallments || 1);
                const amounts = splitAmountInInstallments(balance, totalInstallments);
                const totalToPay = amounts.reduce((sum, amount) => sum + amount, 0);
                
                // Usar una tolerancia pequeña para manejar errores de redondeo (1 peso)
                const tolerance = 1;
                const isExceeded = (totalToPay - balance) > tolerance;
                
                // Debug log
                console.log('=== DEBUG isAmountExceeded ===');
                console.log('balance:', balance);
                console.log('totalInstallments:', totalInstallments);
                console.log('amounts:', amounts);
                console.log('totalToPay:', totalToPay);
                console.log('difference:', totalToPay - balance);
                console.log('tolerance:', tolerance);
                console.log('isExceeded:', isExceeded);
                console.log('==============================');
                
                return isExceeded;
            }
            
            return false;
        },
        canProceedWithPayment() {
            return this.paymentType !== null && 
                   !this.isPaymentComplete && 
                   !this.isAmountExceeded &&
                   this.displayRemainingAmount > 0;
        }
    }
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
