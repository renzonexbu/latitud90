<template>
    <div class="bg-white rounded-[20px] overflow-hidden min-h-[600px]">
        <!-- Table Container -->
        <div class="flex flex-col gap-0">
            <!-- Table Header -->
            <div
                class="bg-turquesa rounded-t-[20px] px-5 py-[11px] flex items-center justify-between h-[61.51px]"
            >
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[80px]"
                >
                    ID
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Participante
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Pagador
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]"
                >
                    Programa
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]"
                >
                    Institución
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Monto
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Estado
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    T. Pago
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Fecha
                </div>
                <!-- Columna de acciones (vacía en header) -->
                <div class="w-[80px]"></div>
            </div>

            <!-- Table Body -->
            <div class="flex flex-col overflow-y-auto min-h-[500px] pb-4">
                <div
                    v-for="(payment, index) in payments"
                    :key="payment.id"
                    :class="[
                        'px-5 py-[14px] flex items-center justify-between cursor-pointer',
                        index % 2 === 0 ? 'bg-white' : 'bg-[#f9f9f9]',
                    ]"
                    @click="$emit('show-payment-details', payment)"
                >
                    <!-- ID -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[80px]"
                    >
                        {{ payment.id }}
                    </div>

                    <!-- Participante -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        {{ getParticipantName(payment) }}
                    </div>

                    <!-- Pagador -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        {{ getBuyerName(payment) }}
                    </div>

                    <!-- Programa -->
                    <div
                        class="text-[#1c4f4a] font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]"
                    >
                        {{ payment.order?.program_course?.name || "N/A" }}
                    </div>

                    <!-- Institución -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]"
                    >
                        {{ getInstitutionName(payment) }}
                    </div>

                    <!-- Monto -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        ${{ formatPrice(payment.amount) }}
                    </div>

                    <!-- Estado -->
                    <div class="flex justify-center items-center w-[120px]">
                        <div
                            :class="[
                                'rounded-[12px] px-[10px] py-[6px] text-white font-nexa-xbold text-[14px] leading-[13px] text-center flex items-center justify-center',
                                getStatusClass(payment.status),
                            ]"
                        >
                            {{ getStatusLabel(payment.status, payment) }}
                        </div>
                    </div>

                    <!-- Gateway -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        {{ getPaymentMethodDisplay(payment) }}
                    </div>

                    <!-- Fecha -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        {{
                            formatDate(
                                payment.transaction_date_formatted ||
                                    payment.transaction_date ||
                                    payment.created_at
                            )
                        }}
                    </div>

                    <!-- Acciones -->
                    <div
                        class="flex gap-2 items-center justify-center w-[80px]"
                    >
                        <!-- Reconfirm Button (solo para pagos pendientes con token) -->
                        <button
                            v-if="payment.status === 'pending' && payment.token"
                            @click.stop="reconfirmPayment(payment)"
                            :disabled="reconfirmingId === payment.id"
                            class="w-[22px] h-[22px] hover:opacity-75 transition-opacity disabled:opacity-50"
                            :title="reconfirmingId === payment.id ? 'Reconfirmando...' : 'Reconfirmar con VirtualPOS'"
                        >
                            <!-- Loading spinner -->
                            <svg
                                v-if="reconfirmingId === payment.id"
                                class="animate-spin"
                                width="22"
                                height="22"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <circle cx="12" cy="12" r="10" stroke="#007e93" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.4 31.4" />
                            </svg>
                            <!-- Refresh icon -->
                            <svg
                                v-else
                                width="22"
                                height="22"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="#007e93"
                                stroke-width="2"
                            >
                                <path d="M23 4v6h-6" />
                                <path d="M1 20v-6h6" />
                                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" />
                            </svg>
                        </button>

                        <!-- View Details Button -->
                        <button
                            @click.stop="$emit('show-payment-details', payment)"
                            class="w-[18px] h-[19px] hover:opacity-75 transition-opacity"
                        >
                            <svg
                                width="18"
                                height="19"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="#C7C7C7"
                                stroke-width="2"
                            >
                                <path
                                    d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"
                                />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast de notificación -->
        <div
            v-if="toastMessage"
            :class="[
                'fixed bottom-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 transition-all duration-300 max-w-md',
                toastType === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            ]"
        >
            {{ toastMessage }}
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: "PaymentsTable",
    props: {
        payments: {
            type: Array,
            default: () => [],
        },
    },

    data() {
        return {
            reconfirmingId: null,
            toastMessage: '',
            toastType: 'success',
        };
    },

    methods: {
        async reconfirmPayment(payment) {
            if (this.reconfirmingId) return;

            this.reconfirmingId = payment.id;
            this.toastMessage = '';

            try {
                const response = await axios.post(route('admin.payments.reconfirm', payment.id));

                if (response.data.success) {
                    this.showToast(response.data.message, 'success');

                    // Actualizar el estado del pago localmente
                    if (response.data.payment) {
                        Object.assign(payment, response.data.payment);
                    } else if (response.data.status) {
                        payment.status = response.data.status;
                    }

                    // Emitir evento para que el padre pueda refrescar si es necesario
                    this.$emit('payment-reconfirmed', payment, response.data);
                } else {
                    this.showToast(response.data.message || 'Error al reconfirmar el pago', 'error');
                }
            } catch (error) {
                console.error('Error reconfirmando pago:', error);
                const message = error.response?.data?.message || 'Error al reconfirmar el pago';
                this.showToast(message, 'error');
            } finally {
                this.reconfirmingId = null;
            }
        },

        showToast(message, type = 'success') {
            this.toastMessage = message;
            this.toastType = type;

            // Auto-hide after 5 seconds
            setTimeout(() => {
                this.toastMessage = '';
            }, 5000);
        },

        toCapitalCase(text) {
            if (!text) return '';
            return text
                .toLowerCase()
                .split(' ')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        },

        getParticipantName(payment) {
            // Si es un installment
            if (payment.is_installment && payment.order?.participant) {
                const p = payment.order.participant;
                const firstName = this.toCapitalCase(p.first_name || '');
                const lastName = this.toCapitalCase(p.first_last_name || '');
                return `${firstName} ${lastName}`.trim() || "N/A";
            }
            // Usar el atributo del modelo Payment que ya está construido correctamente
            return this.toCapitalCase(payment.participant_name || "N/A");
        },

        getBuyerName(payment) {
            // Si es un installment
            if (payment.is_installment && payment.order) {
                const firstName = this.toCapitalCase(payment.order.buyer_first_name || '');
                const lastName = this.toCapitalCase(payment.order.buyer_first_last_name || '');
                const fullName = `${firstName} ${lastName}`.trim();
                return fullName || "N/A";
            }
            // Los datos del comprador están en order_detail
            if (payment.order_detail?.name) {
                return this.toCapitalCase(payment.order_detail.name);
            }
            // Fallback al participante si no hay datos del comprador
            if (payment.order?.participant) {
                return this.getParticipantName(payment);
            }
            return "N/A";
        },

        getInstitutionName(payment) {
            return payment.order?.program_course?.course?.institution?.name || "N/A";
        },

        getPaymentMethodDisplay(payment) {
            // Si es un installment (cuota de suscripción)
            if (payment.is_installment) {
                return 'PAT';
            }

            // Si es una orden de suscripción (empieza con SUB-)
            if (payment.order?.order_number?.startsWith('SUB-')) {
                return 'PAT';
            }

            // Si tiene payment_option_id, mostrar el código de reporte
            if (payment.payment_option_id && payment.payment_option) {
                // Prioritize report_code over gateway_code
                if (payment.payment_option.report_code) {
                    return payment.payment_option.report_code;
                }

                // Fallback a gateway_code
                return payment.payment_option.gateway_code || "N/A";
            }

            // Si no tiene payment_option_id, es un pago presencial sin código específico
            if (!payment.payment_option_id) {
                return "Presencial";
            }

            // Fallback
            return payment.payment_gateway?.name || "N/A";
        },

        getStatusClass(status) {
            const classes = {
                pending: "bg-[#ffb232]", // Amarillo
                completed: "bg-[#4b8d7f]", // Verde
                failed: "bg-[#d54b44]", // Rojo
                authorized: "bg-[#1c4f4a]", // Verde oscuro
                approved: "bg-[#4b8d7f]", // Verde (aprobado = completado)
                cancelled: "bg-[#6b7280]", // Gris
                refunded: "bg-[#f59e0b]", // Naranja
                processing: "bg-[#3b82f6]", // Azul
            };
            return classes[status] || "bg-[#ffb232]";
        },

        getStatusLabel(status, payment = null) {
            // Estandarizar etiquetas de estado para todos los pagos
            const labels = {
                pending: "Pendiente",
                completed: "Pagado",      // Estandarizado: todo completado = "Pagado"
                failed: "Fallido",
                authorized: "Autorizado",
                approved: "Pagado",       // Aprobado también es "Pagado"
                cancelled: "Cancelado",
                refunded: "Reembolsado",
                processing: "Procesando",
            };
            return labels[status] || status;
        },

        formatPrice(amount) {
            return new Intl.NumberFormat("es-CL").format(amount || 0);
        },

        formatDate(date) {
            if (!date) return "N/A";

            try {
                const dateObj = new Date(date);
                if (isNaN(dateObj.getTime())) {
                    return "Invalid Date";
                }

                return dateObj.toLocaleDateString("es-CL", {
                    day: "2-digit",
                    month: "2-digit",
                    year: "numeric",
                });
            } catch (error) {
                return "Error Date";
            }
        },
    },
};
</script>

<style scoped>
/* Custom font classes */
.font-nexa-bold {
    font-family: "Nexa-Bold", sans-serif;
    font-weight: 700;
}

.font-nexa-xbold {
    font-family: "Nexa-XBold", sans-serif;
    font-weight: 400;
}

.bg-turquesa {
    background-color: #007e93;
}

/* Animation for loading spinner */
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
