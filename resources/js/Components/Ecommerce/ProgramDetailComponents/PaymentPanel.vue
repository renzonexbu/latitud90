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
                <div class="flex flex-row items-center justify-between">
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
                { value: 'debit',  label: 'Tarjeta de Débito',    description: null },
                { value: 'credit', label: 'Tarjeta de Crédito',   description: 'Cuotas según configuración del programa' },
            ],
        };
    },
    methods: {
        formatPrice(amount) {
            const safe = Number(amount ?? 0);
            return new Intl.NumberFormat("es-CL", {
                style: "currency",
                currency: "CLP",
                maximumFractionDigits: 0,
            })
                .format(safe)
                .replace("CLP", "")
                .trim();
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
                const pushUnique = (value, label, description = null) => {
                    if (!result.some(o => o.value === value)) {
                        result.push({ value, label, description });
                    }
                };
                codes.forEach(code => {
                    if (code.includes('khipu')) {
                        pushUnique('khipu', 'Pagar con Transferencia Khipu');
                    } else if (code.includes('debit')) {
                        pushUnique('debit', 'Pagar con Tarjeta de Débito (Webpay)');
                    } else if (code.includes('credit')) {
                        const match = code.match(/(\d+)(?!.*\d)/);
                        if (match) {
                            const n = parseInt(match[1], 10);
                            if (!isNaN(n) && n > 0) {
                                pushUnique(`credit_${n}`, `Pagar con Tarjeta de Crédito ${n} cuotas sin interés (Webpay)`);
                            } else {
                                pushUnique('credit_0', 'Pagar con Tarjeta de Crédito sin cuotas (Webpay)');
                            }
                        } else {
                            pushUnique('credit_0', 'Pagar con Tarjeta de Crédito sin cuotas (Webpay)');
                        }
                    }
                });
                return result;
            }
            // Legacy fallback
            if (!this.program.enable_total_payment || !this.program.total_payment_method_id) return [];
            return this.filterPaymentOptionsByMethod(this.program.total_payment_method_id);
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
                if (codes.some(c => c.includes('khipu'))) pushUnique('khipu', 'Pagar con Transferencia Khipu');
                if (codes.some(c => c.includes('debit'))) pushUnique('debit', 'Pagar con Tarjeta de Débito (Webpay)');
                if (codes.some(c => c.includes('credit'))) pushUnique('credit', 'Pagar con Tarjeta de Crédito sin cuotas (Webpay)', null, 'Solo se efectuará 1 cuota');
                return result;
            }
            // Legacy fallback
            if (!this.program.enable_lat90_payment || !this.program.lat90_payment_method_id) return [];
            const base = this.filterPaymentOptionsByMethod(this.program.lat90_payment_method_id);
            return base.map(opt => ({ ...opt, description: opt.value === 'credit' ? null : opt.description }));
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
            if (option === 'credit') {
                // Si no contiene la cuota actual, seleccionar la mínima disponible
                if (!allowed.includes(this.selectedInstallments)) {
                    this.selectedInstallments = allowed[0] || 1;
                }
            } else {
                // Débito/Khipu: 1 cuota
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
            return Math.round(((Number(base) || 0) / installments) * 100) / 100;
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
