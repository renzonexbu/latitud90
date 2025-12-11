<template>
    <div :class="containerClass">
        <div class="flex flex-col gap-[16px] md:gap-[24px] w-full md:max-w-none mx-auto">
            <!-- Header Section -->
            <div v-if="showHeader" class="flex flex-col gap-[10px] md:gap-[14px]">
                <h2
                    class="text-[#007E93] font-outfit-semibold text-[18px] md:text-[20px] leading-[24px] md:leading-[61.43px] font-semibold"
                >
                    {{ paymentFormTitle }}
                </h2>
                <p class="text-[#5B5B5B] font-nexa text-xs md:text-sm leading-[16px] md:leading-[18px]">
                    {{ paymentFormSubtitle }}
                    <a
                        :href="`https://wa.me/${effectiveWhatsappNumber}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-[#25D366] hover:text-[#128C7E] underline font-semibold transition-colors"
                    >
                        WhatsApp
                    </a>
                </p>
            </div>

            <!-- Payment Options Section -->
            <div class="flex flex-col gap-[12px] md:gap-[18px]">
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
                    :title="totalPaymentTitle"
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
                    v-if="program.enable_lat90_payment && shouldShowMonthlyOption"
                    :is-selected="paymentType === 'monthly'"
                    :is-accordion-open="accordionOpen === 'monthly'"
                    :title="subscriptionTitle"
                    @select="selectPaymentType('monthly')"
                >
                    <template #accordion-content>
                        <div class="flex flex-col gap-[9px]">
                            <!-- Warning Message -->
                            <!-- <MonthlyWarningMessage v-if="showWarning" /> -->

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
                                            :disabled="getAvailableInstallments().length === 0"
                                        >
                                            <option 
                                                v-for="installment in getAvailableInstallments()" 
                                                :key="installment" 
                                                :value="installment"
                                            >
                                                {{ installment }}
                                            </option>
                                        </select>
                                        
                                        <!-- Mensaje si no hay cuotas disponibles -->
                                        <div v-if="getAvailableInstallments().length === 0" class="text-red-500 text-xs mt-1">
                                            No hay cuotas disponibles. Verifica la configuración del programa.
                                        </div>
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

        <!-- Modal de Registro Guardian -->
        <Teleport to="body">
            <div
                v-if="showGuardianModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
                @click.self="showGuardianModal = false"
            >
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-nexa-bold text-[#1C4F4A]">
                            Registro Requerido
                        </h3>
                        <button
                            @click="showGuardianModal = false"
                            class="text-gray-400 hover:text-gray-600 transition-colors"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="mb-6">
                        <p class="text-[#434343] font-nexa text-[14px] leading-[22px] mb-4">
                            Para continuar con el pago en cuotas mensuales, necesitas estar registrado como apoderado.
                        </p>
                        <p class="text-[#434343] font-nexa text-[14px] leading-[22px] mb-4">
                            Serás redirigido al formulario de registro. Una vez completado, podrás continuar con tu compra.
                        </p>
                        <p class="text-[#434343] font-nexa text-[14px] leading-[22px]">
                            ¿Ya tienes cuenta?
                            <a
                                :href="`/guardian/login?token=${this.$page.props.token}&program_id=${this.programId}`"
                                class="text-[#1C4F4A] font-nexa-bold hover:underline"
                            >
                                Inicia sesión aquí
                            </a>
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row gap-3 justify-end">
                        <button
                            @click="showGuardianModal = false"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 font-nexa transition-colors"
                        >
                            Cancelar
                        </button>
                        <button
                            @click="proceedToGuardianRegister"
                            class="px-4 py-2 bg-[#FBBD51] text-white rounded-md hover:bg-[#e5aa3d] font-nexa-bold transition-colors"
                        >
                            Ir a registro
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script>
import PaymentOption from "./PaymentOption.vue";
import PaymentSubOption from "./PaymentSubOption.vue";
import contactInfo from "@/config/contact.js";
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
        participantDocument: {
            type: String,
            required: false,
            default: null
        },
        whatsappNumber: {
            type: String,
            default: null
        },
        paymentFormContent: {
            type: Object,
            default: () => ({})
        },
    },
    watch: {
        selectedInstallments: {
            handler(newValue, oldValue) {
                if (newValue !== oldValue && this.paymentType === 'monthly') {
                    // Validar que las cuotas seleccionadas estén disponibles
                    const allowed = this.getAvailableInstallments();
                    if (!allowed.includes(newValue)) {
                        this.selectedInstallments = allowed[0] || 1;
                        return;
                    }

                    // Guardar y emitir cambios
                    if (this.paymentType && oldValue !== undefined) {
                        this.savePaymentDataToLocalStorage();
                        this.emitSelection();
                    }
                }
            },
            immediate: false
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
                if (this.program.enable_lat90_payment && this.shouldShowMonthlyOption && !this.paymentType) {
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
                } else if (this.program.enable_total_payment && !this.shouldShowMonthlyOption && !this.paymentType) {
                    // Si solo hay 1 cuota disponible, seleccionar automáticamente pago total
                    this.paymentType = 'total';
                    this.accordionOpen = 'total';
                    const availableOptions = this.getTotalPaymentOptions();
                    if (availableOptions.length > 0) {
                        this.totalPaymentOption = availableOptions[0].value;
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
            showGuardianModal: false,
            contactInfo: contactInfo,
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
            return `${this.containerBaseClasses()} bg-white rounded-[20px] border border-[#D3D3D3] p-[16px_12px] md:p-[20px_15px] lg:p-[40px_30px] shadow-[0px_4px_59.3px_0px_rgba(229,229,229,0.25)]`;
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
                        pushUnique('khipu', 'Pagar con Transferencia Khipu', null, this.khipuWarning, 1);
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
                    return { ...opt, warning: this.khipuWarning };
                }
                return opt;
            });
        },

        // Obtener opciones de pago mensual (Latitud 90), basadas en códigos (nueva estructura)
        getMonthlyPaymentOptions() {
            // Solo retornar la opción de Suscripción VirtualPos
            return [
                {
                    value: 'subscription_virtualpos',
                    label: 'Suscripción VirtualPos',
                    description: null,
                    warning: null
                }
            ];
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

        // Obtener cuotas disponibles según fecha final y/o máximo permitido del programa
        getAvailableInstallments() {
            const options = [];
            
            // Obtener máximo configurado en el programa (nuevo campo)
            const programMax = this.program.lat90_max_installments || 12;
            
            // Calcular máximo por fecha final de pago
            let maxByDate = null;
            if (this.program.final_payment_date) {
                const now = new Date();
                const end = new Date(this.program.final_payment_date);
                
                // Calcular diferencia en meses de forma más precisa
                const yearDiff = end.getFullYear() - now.getFullYear();
                const monthDiff = end.getMonth() - now.getMonth();
                let months = yearDiff * 12 + monthDiff;
                
                // Ajustar si el día del mes actual es mayor al día de la fecha final
                if (now.getDate() > end.getDate()) {
                    months -= 1;
                }
                
                maxByDate = Math.max(0, months);
            }
            
            // Usar el máximo del programa si no hay restricción de fecha, o el menor entre ambos
            const max = maxByDate !== null ? Math.min(programMax, maxByDate) : programMax;
            
            // Generar opciones de 1 hasta el máximo
            for (let i = 1; i <= max; i++) {
                options.push(i);
            }
            return options;
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

                    // VALIDACIÓN: Verificar que el guardian logeado tenga permiso para pagar por este participante
                    this.validateGuardianPermission()
                        .then((hasPermission) => {
                            if (!hasPermission) {
                                // Guardian no tiene permiso: resetear selección y no continuar
                                this.paymentType = null;
                                this.accordionOpen = null;
                                return;
                            }

                            // Guardian tiene permiso o no hay guardian: continuar normalmente
                            // Obtener cuotas disponibles
                            const availableInstallments = this.getAvailableInstallments();
                            this.selectedInstallments = availableInstallments[0] || 1;

                            // Seleccionar la primera opción disponible por defecto
                            const availableOptions = this.getMonthlyPaymentOptions();
                            if (availableOptions.length > 0 && !this.monthlyPaymentOption) {
                                this.monthlyPaymentOption = availableOptions[0].value;
                            }

                            // Guardar en localStorage en tiempo real
                            this.savePaymentDataToLocalStorage();
                            this.emitSelection();
                        });

                    // Retornar temprano para evitar guardar datos antes de validar
                    return;

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

            // Para suscripción VirtualPos, permitir múltiples cuotas según configuración del programa
            if (option === 'subscription_virtualpos') {
                if (!allowed.includes(this.selectedInstallments)) {
                    this.selectedInstallments = allowed[0] || 1;
                }
            } else {
                // Otros métodos: usar la primera opción disponible
                this.selectedInstallments = allowed[0] || 1;
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
                }
            } else {
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

                // Validar cuotas disponibles
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

            // Si está en modo confirmación, emitir evento al padre
            if (this.isConfirmation) {
                this.$emit('payment-button-clicked', {
                    paymentType: this.paymentType,
                    paymentMethod: this.paymentType === 'total' ? this.totalPaymentOption : this.monthlyPaymentOption,
                    installments: this.paymentType === 'monthly' ? this.selectedInstallments : 1,
                });
                return;
            }

            // NUEVA VALIDACIÓN: Si es pago mensual, verificar autenticación de guardian
            if (this.paymentType === 'monthly') {
                const isGuardianLoggedIn = this.$page.props.auth?.guardian !== null;

                if (!isGuardianLoggedIn) {
                    // Guardar datos de pago antes de mostrar el modal
                    const paymentData = {
                        paymentType: this.paymentType,
                        paymentMethod: this.monthlyPaymentOption,
                        installments: this.selectedInstallments,
                    };
                    localStorage.setItem("selectedPaymentData", JSON.stringify(paymentData));

                    // Mostrar modal de registro
                    this.showGuardianModal = true;
                    return;
                }

                // VALIDACIÓN CRÍTICA: Verificar que el guardian tenga permiso para pagar por este participante
                this.validateGuardianPermission()
                    .then((hasPermission) => {
                        if (!hasPermission) {
                            // Guardian no tiene permiso: NO continuar (ya se manejó en validateGuardianPermission)
                            return;
                        }

                        // Guardian tiene permiso: continuar con el pago
                        this.processPaymentNavigation();
                    });

                return; // No continuar hasta validar permisos
            }

            // Si no es mensual o ya está autenticado, continuar con el flujo normal
            this.processPaymentNavigation();
        },

        async processPaymentNavigation() {
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

            // Si es pago total, cerrar sesión del guardian para continuar como visita
            if (this.paymentType === 'total') {
                await this.logoutGuardianForTotalPayment();
            }

            // Obtener token desde las props de la página
            const token = this.$page.props.token || '';

            if (!token) {
                console.error('No se encontró token en las props de la página');
                return;
            }

            // Construir URL con token
            const url = `/programs/${this.programId}/payment?token=${token}`;

            // Usar window.location.href para navegación completa
            window.location.href = url;
        },

        async logoutGuardianForTotalPayment() {
            // Cerrar sesión del guardian para que el pago total se procese como visita
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                await fetch('/guardian/logout', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                console.log('Sesión de guardian cerrada para pago total');
            } catch (error) {
                // Ignorar errores de logout (puede que no haya sesión activa)
                console.log('No había sesión de guardian activa');
            }
        },

        proceedToGuardianRegister() {
            // Obtener token desde las props de la página
            const token = this.$page.props.token || '';

            // Guardar pending_subscription para recuperar después del registro
            localStorage.setItem('pending_subscription', JSON.stringify({
                program_id: this.programId,
                token: token,
                return_url: `/programs/${this.programId}?token=${token}`
            }));

            // Redirigir al registro de guardian con el token y program_id
            window.location.href = `/guardian/register?token=${token}&program_id=${this.programId}`;
        },

        async initiateSubscription(document, documentType, paymentData) {
            try {
                // Mostrar indicador de carga si existe
                if (this.isLoading !== undefined) {
                    this.isLoading = true;
                }

                // Llamar al endpoint de suscripción
                const response = await fetch('/subscription/initiate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        program_id: this.programId,
                        document: document,
                        document_type: documentType,
                        installments: paymentData.installments || 1
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Redirigir a la página de detalles de suscripción
                    if (data.data?.next_step) {
                        window.location.href = data.data.next_step;
                    } else {
                        window.location.href = `/subscription/details/${this.programId}`;
                    }
                } else {
                    // Mostrar error
                    alert(data.error || 'Error al iniciar la suscripción');

                    // Si requiere login, redirigir
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                }

            } catch (error) {
                console.error('Error al iniciar suscripción:', error);
                alert('Error al procesar la suscripción. Por favor, intenta nuevamente.');
            } finally {
                if (this.isLoading !== undefined) {
                    this.isLoading = false;
                }
            }
        },

        recordPaymentSelection(paymentData) {
            // Obtener session_id desde localStorage
            const sessionId = localStorage.getItem('analytics_session_id');
            
            if (!sessionId) {
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
        async validateGuardianPermission() {
            try {
                // Obtener el RUT del participante desde props
                const participantDocument = this.participantDocument;

                if (!participantDocument) {
                    return true; // Permitir continuar si no hay documento
                }

                // Hacer petición al backend para validar permisos
                const response = await fetch('/guardian/validate-payment-permission', {
                    method: 'POST',
                    credentials: 'same-origin', // ⚠️ CRÍTICO: Enviar cookies de sesión
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify({
                        participant_document: participantDocument
                    })
                });

                const data = await response.json();

                // Si no tiene permiso, cerrar sesión y mostrar modal de login
                if (!data.has_permission) {
                    // Cerrar sesión del guardian
                    await fetch('/guardian/logout', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        }
                    });

                    // Mostrar mensaje al usuario
                    alert('Para realizar pagos de suscripción por este participante, debes iniciar sesión con la cuenta correcta.');

                    // Emitir evento para mostrar modal de login (si existe)
                    this.$emit('show-login-modal');

                    // Reload la página para refrescar el estado de auth
                    window.location.reload();

                    return false;
                }

                return true;

            } catch (error) {
                console.error('Error validando permisos de guardian:', error);
                // En caso de error, permitir continuar (fail-open para no bloquear flujo)
                return true;
            }
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
                

                
                return isExceeded;
            }
            
            return false;
        },
        canProceedWithPayment() {
            return this.paymentType !== null && 
                   !this.isPaymentComplete && 
                   !this.isAmountExceeded &&
                   this.displayRemainingAmount > 0;
        },
        shouldShowMonthlyOption() {
            const availableInstallments = this.getAvailableInstallments();
            const maxInstallments = Math.max(...availableInstallments, 0);
            return maxInstallments > 1;
        },
        effectiveWhatsappNumber() {
            return this.whatsappNumber || this.contactInfo.whatsapp.number;
        },
        // Computed properties for payment form content with fallbacks
        paymentFormTitle() {
            return this.paymentFormContent?.title?.value || 'Seleccione la forma de pago';
        },
        paymentFormSubtitle() {
            return this.paymentFormContent?.subtitle?.value || 'Selecciona la forma de pago que mejor se adapte a ti, pago con tarjeta de crédito, débito o Khipu, o pago automático con PAT. Para cualquier consulta, no dudes en escribirnos por';
        },
        totalPaymentTitle() {
            return this.paymentFormContent?.total_payment_title?.value || 'Pago total';
        },
        subscriptionTitle() {
            return this.paymentFormContent?.subscription_title?.value || 'Suscripción';
        },
        khipuWarning() {
            return this.paymentFormContent?.khipu_warning?.value || '⚠️ Importante: la primera transferencia a una cuenta nueva tiene un límite bancario de $250.000. Si el monto supera este valor, escríbanos a pagos@latitud90.com para recibir un link de pago.';
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
