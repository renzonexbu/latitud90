¿<template>
    <div
        class="bg-white rounded-xl border border-[#D3D3D3] p-3 md:p-6 flex flex-col justify-between w-full md:h-[520px] md:w-[504px]"
    >
        <!-- Top Section -->
        <div class="flex flex-col gap-[18px]">
            <!-- Person Info -->
                <div class="flex flex-col gap-0">
                <div
                    class="text-[#5B5B5B] font-nexa text-[16px] leading-[28px] font-normal md:text-xl md:leading-7"
                >
                    Estás por pagar el viaje de
                </div>
                <div
                    class="text-[#007E93] font-nexa text-[16px] leading-[28px] font-bold md:text-xl md:leading-7"
                >
                    {{ participantName || "Usuario" }}
                </div>
            </div>

            <!-- Trip Card -->
            <div
                class="bg-[#007E93] rounded-lg p-[17px] flex flex-col gap-[15px] shadow-[0px_4px_11.6px_0px_rgba(163,163,163,0.11)]"
            >
                <div class="flex flex-col gap-[25px]">
                    <!-- Location and Date -->
                    <div class="flex justify-between items-start">
                        <div
                            class="text-white font-nexa text-[10px] font-normal leading-[33.053px] w-[171px]"
                            style="
                                font-feature-settings: 'liga' off, 'clig' off;
                            "
                        >
                            {{ program.destination }}
                        </div>
                        <div
                            class="text-white font-outfit text-[14px] font-medium leading-[33.053px]"
                            style="
                                font-feature-settings: 'liga' off, 'clig' off;
                            "
                        >
                            {{ formatDateRange(program.departure_date) }}
                        </div>
                    </div>

                    <!-- Trip Name and Change Button -->
                    <div class="flex justify-between items-center">
                        <div class="text-white font-nexa text-lg font-bold">
                            {{ program.name }}
                        </div>
                        <button
                            class="bg-[#1C4F4A] rounded-full px-[10px] py-1 hover:bg-[#0f3a35] transition-colors duration-300"
                            @click="changeProgram"
                        >
                            <div
                                class="text-white font-nexa text-[10px] leading-[14px] font-bold"
                            >
                                Cambiar programa
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Section -->
        <div class="flex flex-col gap-[10px] mt-4">
            <!-- Total Value (Total del participante o próxima cuota activa) -->
            <div class="flex justify-between items-center">
                <div class="text-[#5B5B5B] font-nexa text-xs leading-[13px] font-bold">
                    {{ program.active_installment ? `Cuota ${program.active_installment.number} de ${program.active_installment.total}` : 'Valor total' }}
                </div>
                <div class="text-[#C7C7C7] font-nexa text-base leading-[22px] font-normal">
                    ${{ formatCurrency(program.active_installment ? program.active_installment.amount : (program.participant_total_due ?? program.trip_price)) }}
                </div>
            </div>

            <!-- Payment Amount -->
            <div class="border-t border-[#D3D3D3] pb-2">
                <div class="flex justify-between items-center">
                    <div
                        class="text-[#434343] font-outfit text-xl leading-[61px] font-medium"
                    >
                        Pagarás
                    </div>
                    <div class="text-[#434343] font-nexa text-2xl leading-7 font-bold">
                        ${{ formatCurrency(program.active_installment ? program.active_installment.amount : displayPayAmount) }}
                    </div>
                </div>

                <!-- Terms Acceptance -->
                <div class="flex flex-col gap-[8px] mt-4">
                    <div class="flex items-center gap-[8px]">
                        <input
                            type="checkbox"
                            class="custom-checkbox w-[12px] h-[12px] rounded-[1.5px]"
                            v-model="termsAccepted"
                        />
                        <div
                            class="text-[#434343] font-nexa text-[10px] leading-[16px] font-normal"
                        >
                            <span class="font-nexa">Acepto los</span>
                            <a
                                href="/terminos-y-condiciones"
                                target="_blank"
                                class="font-nexa font-bold underline hover:text-[#007E93] transition-colors"
                            >
                                términos y condiciones de compra*
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Button -->
            <div class="mt-6">
                <button
                    class="flex w-full px-5 py-[11px] justify-center items-center gap-3 rounded-[49px] transition-colors duration-300"
                    :class="{
                        'bg-[#FBBD51] hover:bg-[#e6a93d] cursor-pointer': isPaymentButtonEnabled,
                        'bg-[#C7C7C7] cursor-not-allowed': !isPaymentButtonEnabled
                    }"
                    :disabled="!isPaymentButtonEnabled"
                    @click="handlePayment"
                >
                    <!-- Spinner cuando está procesando -->
                    <div v-if="isProcessing" class="flex items-center gap-2">
                        <svg
                            class="animate-spin h-5 w-5 text-white"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        <span
                            class="text-white font-urbanist text-base font-semibold leading-[18px]"
                            style="font-feature-settings: 'liga' off, 'clig' off"
                        >
                            Procesando pago...
                        </span>
                    </div>
                    
                    <!-- Contenido normal cuando no está procesando -->
                    <div v-else class="flex items-center gap-3">
                        <span class="text-white font-urbanist text-base font-semibold leading-[18px]" style="font-feature-settings: 'liga' off, 'clig' off">
                            Ir a pagar ${{ formatCurrency(displayPayAmount) }}
                        </span>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 25 24"
                            fill="none"
                            class="flex-shrink-0"
                        >
                            <path
                                d="M2.75 12C2.75 17.385 7.115 21.75 12.5 21.75C17.885 21.75 22.25 17.385 22.25 12C22.25 6.615 17.885 2.25 12.5 2.25C7.115 2.25 2.75 6.615 2.75 12ZM12.97 7.97C13.1106 7.82955 13.3012 7.75066 13.5 7.75066C13.6988 7.75066 13.8894 7.82955 14.03 7.97L17.53 11.47C17.6705 11.6106 17.7493 11.8012 17.7493 12C17.7493 12.1988 17.6705 12.3894 17.53 12.53L14.03 16.03C13.9613 16.1037 13.8785 16.1628 13.7865 16.2038C13.6945 16.2448 13.5952 16.2668 13.4945 16.2686C13.3938 16.2704 13.2938 16.2518 13.2004 16.2141C13.107 16.1764 13.0222 16.1203 12.951 16.049C12.8797 15.9778 12.8236 15.893 12.7859 15.7996C12.7482 15.7062 12.7296 15.6062 12.7314 15.5055C12.7332 15.4048 12.7552 15.3055 12.7962 15.2135C12.8372 15.1215 12.8963 15.0387 12.97 14.97L15.19 12.75H8C7.80109 12.75 7.61032 12.671 7.46967 12.5303C7.32902 12.3897 7.25 12.1989 7.25 12C7.25 11.8011 7.32902 11.6103 7.46967 11.4697C7.61032 11.329 7.80109 11.25 8 11.25H15.19L12.97 9.03C12.8295 8.88937 12.7507 8.69875 12.7507 8.5C12.7507 8.30125 12.8295 8.11063 12.97 7.97Z"
                                fill="white"
                            />
                        </svg>
                    </div>
                </button>
            </div>
        </div>
    </div>

    <!-- Error Alert -->
    <div v-if="errorMessage" class="mt-4">
        <div class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">
                        Error al procesar el pago
                    </h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p>{{ errorMessage }}</p>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button
                            @click="errorMessage = null"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                        >
                            Cerrar
                        </button>
                        <button
                            @click="reloadPage"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                        >
                            Recargar página
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


</template>

<script>
import { router } from "@inertiajs/vue3";
import { getFirstInstallmentAmount, formatPrice } from "@/utils/paymentUtils";

export default {
    name: "ConfirmationCard",
    props: {
        program: {
            type: Object,
            required: true,
        },
        formData: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            termsAccepted: false,
            isProcessing: false,
            errorMessage: null,
            currentInstallments: 1,
        };
    },
    watch: {
        termsAccepted(newValue) {
            // Guardar el estado de términos aceptados en localStorage
            const paymentData = localStorage.getItem("selectedPaymentData");
            if (paymentData) {
                try {
                    const parsedData = JSON.parse(paymentData);
                    parsedData.termsAccepted = newValue;
                    localStorage.setItem("selectedPaymentData", JSON.stringify(parsedData));
                } catch (error) {
                    console.error("Error updating payment data:", error);
                }
            }
        }
    },
    mounted() {
        // Debug: Verificar datos recibidos
        console.log("=== CONFIRMATION CARD MOUNTED ===");
        console.log("formData prop:", this.formData);
        console.log("program prop:", this.program);
        console.log("================================");
        
        // Cargar el estado de términos aceptados desde localStorage
        const paymentData = localStorage.getItem("selectedPaymentData");
        if (paymentData) {
            try {
                const parsedData = JSON.parse(paymentData);
                this.termsAccepted = parsedData.termsAccepted || false;
                this.currentInstallments = parsedData.installments || 1;
            } catch (error) {
                console.error("Error loading payment data:", error);
            }
        }
    },
    computed: {
        participantName() {
            // Usar los datos del participante desde las props
            return this.formData.name || "Usuario";
        },
        isPaymentButtonEnabled() {
            // Verificar si hay un método de pago seleccionado
            const paymentData = localStorage.getItem("selectedPaymentData");
            let hasPaymentMethod = false;
            
            if (paymentData) {
                try {
                    const parsedData = JSON.parse(paymentData);
                    hasPaymentMethod = parsedData.paymentType && parsedData.paymentMethod;
                } catch (error) {
                    console.error("Error parsing payment data:", error);
                }
            }
            
            // Verificar que no se haya pagado todo
            const isPaymentComplete = Number(this.program.participant_balance ?? 0) <= 0;
            
            // Verificar que el monto a pagar sea válido
            const hasValidAmount = this.displayPayAmount > 0;
            
            // El botón está habilitado si hay método de pago Y términos aceptados Y no está procesando Y no se pagó todo Y hay monto válido
            return hasPaymentMethod && 
                   this.termsAccepted && 
                   !this.isProcessing && 
                   !isPaymentComplete && 
                   hasValidAmount;
        },
        displayPayAmount() {
            // Si hay plan mensual activo, siempre se paga SOLO la próxima cuota
            if (this.program && this.program.active_installment) {
                return Number(this.program.active_installment.amount) || 0;
            }
            // Caso pago total (o mensual sin plan creado): dividir según selección
            const base = Number(this.program.participant_balance ?? this.program.participant_total_due ?? this.program.trip_price);
            const installments = Math.max(1, Number(this.currentInstallments || 1));
            
            // Usar el método estandarizado de redondeo
            const result = getFirstInstallmentAmount(base, installments);
            return result;
        }
    },
    methods: {
        formatCurrency(amount) {
            return formatPrice(amount);
        },
        formatDateRange(dateString) {
            if (!dateString) return "";

            const date = new Date(dateString);
            const day = date.getDate();
            const month = date.getMonth() + 1;
            const year = date.getFullYear();

            return `${day} de ${this.getMonthName(month)} ${year}`;
        },
        getMonthName(month) {
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
        },
        async handlePayment() {
            try {
                // Validaciones previas
                const isPaymentComplete = Number(this.program.participant_balance ?? 0) <= 0;
                if (isPaymentComplete) {
                    this.errorMessage = "Ya has pagado el monto total del programa. No hay pagos pendientes.";
                    return;
                }

                if (this.displayPayAmount <= 0) {
                    this.errorMessage = "El monto a pagar no es válido. Verifica tu selección de cuotas.";
                    return;
                }

                // Limpiar errores anteriores
                this.errorMessage = null;
                
                // Activar estado de procesamiento
                this.isProcessing = true;
                
                // Obtener datos del localStorage
                const paymentData = localStorage.getItem("selectedPaymentData");
                
                // Debug: Log de los datos del localStorage
                console.log("=== DEBUG LOCALSTORAGE ===");
                console.log("paymentData:", paymentData);
                console.log("formData from props:", this.formData);
                console.log("paymentData exists:", !!paymentData);
                console.log("formData from props exists:", !!this.formData);
                console.log("==========================");
                
                if (!paymentData) {
                    console.error("Datos de pago no encontrados");
                    this.isProcessing = false;
                    this.errorMessage = "Datos de pago no encontrados. Por favor, completa todos los pasos.";
                    return;
                }

                const parsedPaymentData = JSON.parse(paymentData);
                this.currentInstallments = parsedPaymentData.installments || 1;
                // Cargar datos del comprador desde localStorage (paso Detalles de Pago)
                let parsedFormData = this.formData;
                const savedBuyerData = localStorage.getItem("buyerData");
                if (savedBuyerData) {
                    try {
                        parsedFormData = JSON.parse(savedBuyerData);
                    } catch (e) {
                        console.error("Error parsing buyerData from localStorage:", e);
                        parsedFormData = this.formData;
                    }
                }

                // Obtener session_id desde localStorage
                const sessionId = localStorage.getItem('analytics_session_id');
                
                // Debug: Log de session_id
                console.log('=== SESSION ID DEBUG ===');
                console.log('sessionId from localStorage:', sessionId);
                console.log('========================');
                
                // Preparar datos para enviar al backend
                const requestData = {
                    programId: this.program.id,
                    rut: this.formData.document_number,
                    paymentData: parsedPaymentData,
                    formData: parsedFormData,
                    session_id: sessionId
                };

                console.log("Enviando datos de pago:", requestData);

                // Obtener el token CSRF de múltiples formas
                let csrfToken = null;
                
                // Método 1: Desde meta tag
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag) {
                    csrfToken = metaTag.getAttribute('content');
                }
                
                // Método 2: Desde Inertia (si está disponible)
                if (!csrfToken && window.Inertia) {
                    csrfToken = window.Inertia.page.props._csrf;
                }
                
                // Método 2.1: Desde Inertia usando router
                if (!csrfToken && window.Inertia) {
                    csrfToken = window.Inertia.page.props._csrf;
                }
                
                // Método 3: Desde el DOM (Laravel genera un input hidden)
                if (!csrfToken) {
                    const csrfInput = document.querySelector('input[name="_token"]');
                    if (csrfInput) {
                        csrfToken = csrfInput.value;
                    }
                }
                
                console.log('CSRF Token obtenido:', csrfToken ? 'SÍ' : 'NO');
                
                // Hacer la llamada al backend
                const response = await fetch('/process-payment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken })
                    },
                    body: JSON.stringify(requestData)
                });

                // Verificar si hay error de CSRF
                if (response.status === 419) {
                    console.error("Error CSRF: Token expirado o inválido");
                    this.isProcessing = false;
                    this.errorMessage = "Error de seguridad. Por favor, recarga la página e intenta nuevamente.";
                    return;
                }
                
                // Verificar si la respuesta es JSON válido
                let result;
                try {
                    result = await response.json();
                } catch (error) {
                    console.error("Error parsing JSON response:", error);
                    this.isProcessing = false;
                    this.errorMessage = "Error en la respuesta del servidor. Por favor, intenta nuevamente.";
                    return;
                }

                if (result.success) {
                    console.log("Pago procesado exitosamente:", result);
                    
                    // Redirigir a la pasarela de pago
                    if (result.gateway_url) {
                        console.log("Redirigiendo a:", result.gateway_url);
                        
                        // Siempre que venga token, usar POST con token_ws (Transbank Mall exige POST a initTransaction)
                        if (result.gateway_token) {
                            // REGISTRAR payment_initiated_at JUSTO ANTES de redirigir a la pasarela
                            this.recordPaymentInitiated(result);
                            
                            // Para Transbank, usar POST con token
                            const form = document.createElement('form');
                            form.method = 'GET';
                            form.action = result.gateway_url;
                            form.target = '_self';
                            
                            // Agregar el token como campo oculto
                            const tokenInput = document.createElement('input');
                            tokenInput.type = 'hidden';
                            tokenInput.name = 'token_ws';
                            tokenInput.value = result.gateway_token;
                            form.appendChild(tokenInput);
                            
                            // Agregar el formulario al DOM y enviarlo
                            document.body.appendChild(form);
                            form.submit();
                        } else {
                            // REGISTRAR payment_initiated_at JUSTO ANTES de redirigir a la pasarela
                            this.recordPaymentInitiated(result);
                            // Para otros gateways, usar redirección directa
                            window.location.href = result.gateway_url;
                        }
                    } else {
                        console.error("URL de pasarela no recibida");
                        this.isProcessing = false;
                        this.errorMessage = "Error: No se recibió la URL de la pasarela de pago.";
                    }
                } else {
                    console.error("Error al procesar pago:", result.error);
                    this.isProcessing = false;
                    this.errorMessage = result.error || "Error al procesar el pago. Por favor, intenta nuevamente.";
                }

            } catch (error) {
                console.error("Error al procesar pago:", error);
                this.isProcessing = false;
                this.errorMessage = "Error de conexión. Por favor, verifica tu conexión a internet e intenta nuevamente.";
            }
        },
        changeProgram() {
            // Redirigir al paso 1 (listado de programas) con el RUT del participante
            const rut = this.formData.document_number;
            if (rut) {
                router.visit(`/programs?rut=${rut}`);
            } else {
                // Si no hay RUT, ir a la página principal de programas
                router.visit("/programs");
            }
        },
        recordPaymentInitiated(result) {
            // Obtener session_id desde localStorage
            const sessionId = localStorage.getItem('analytics_session_id');
            
            if (!sessionId) {
                console.warn('No se encontró session_id en localStorage');
                return;
            }
            
            // Obtener el método de pago desde localStorage
            const paymentData = localStorage.getItem('selectedPaymentData');
            let paymentMethod = 'unknown';
            if (paymentData) {
                try {
                    const parsedData = JSON.parse(paymentData);
                    paymentMethod = parsedData.paymentMethod || 'unknown';
                } catch (error) {
                    console.error('Error parsing payment data:', error);
                }
            }
            
            // Enviar datos de inicio de pago al backend
            fetch('/api/analytics/payment-initiated', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    session_id: sessionId,
                    program_id: this.program.id,
                    participant_rut: this.formData.document_number,
                    order_id: result.order_id,
                    order_detail_id: result.order_detail_id,
                    order_number: result.order_number,
                    payment_data: {
                        payment_type: this.currentInstallments > 1 ? 'monthly' : 'total',
                        payment_method: paymentMethod,
                        amount: this.displayPayAmount,
                        installments: this.currentInstallments,
                        terms_accepted: this.termsAccepted
                    }
                })
            }).catch(error => {
                console.error('Error recording payment initiated:', error);
            });
        },
        
        reloadPage() {
            // Recargar la página para obtener un nuevo token CSRF
            window.location.reload();
        },
    },
};
</script>

<style scoped>
.custom-checkbox {
    accent-color: #fbbd51;
}

.custom-checkbox:checked {
    background-color: #fbbd51;
    border-color: #fbbd51;
}

/* Estilos adicionales para mayor compatibilidad */
.custom-checkbox:checked::before {
    background-color: #fbbd51;
}

/* Para navegadores que no soportan accent-color */
.custom-checkbox:checked {
    background-color: #fbbd51 !important;
    border-color: #fbbd51 !important;
}
</style>
