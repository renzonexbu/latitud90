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
                    Comprador
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
                    Gateway
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
                        #{{ payment.id }}
                    </div>

                    <!-- Participante -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        {{ getParticipantName(payment) }}
                    </div>

                    <!-- Comprador -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        {{ getBuyerName(payment) }}
                    </div>

                    <!-- Programa -->
                    <div
                        class="text-[#1c4f4a] font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]"
                    >
                        {{ payment.order?.program?.name || "N/A" }}
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
                            {{ getStatusLabel(payment.status) }}
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
    </div>
</template>

<script>
export default {
    name: "PaymentsTable",
    props: {
        payments: {
            type: Array,
            default: () => [],
        },
    },
    


    methods: {
        getParticipantName(payment) {
            // Usar el atributo del modelo Payment que ya está construido correctamente
            return payment.participant_name || "N/A";
        },

        getBuyerName(payment) {
            // Los datos del comprador están en order_detail
            if (payment.order_detail?.name) {
                return payment.order_detail.name;
            }
            // Fallback al participante si no hay datos del comprador
            if (payment.order?.participant) {
                return this.getParticipantName(payment);
            }
            return "N/A";
        },

        getInstitutionName(payment) {
            return payment.order?.program?.course?.institution?.name || "N/A";
        },

        getPaymentMethodDisplay(payment) {
            // Si tiene payment_option_id, mostrar información del paymentOption
            if (payment.payment_option_id && payment.payment_option) {
                let displayText = payment.payment_option.gateway_code || payment.payment_option.label || "N/A";
                
                // Convertir a Title Case y manejar casos especiales
                if (displayText === 'refund') {
                    return 'Reembolso';
                }
                
                // Convertir a Title Case para todos los demás
                return displayText
                    .split('_')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                    .join(' ');
            }
            
            // Si no tiene payment_option_id, es un pago presencial
            if (!payment.payment_option_id) {
                return "Presencial";
            }
            
            // Fallback al gateway del pago
            return payment.payment_gateway?.name || "N/A";
        },

        getStatusClass(status) {
            const classes = {
                pending: "bg-[#ffb232]", // Amarillo
                completed: "bg-[#4b8d7f]", // Verde
                failed: "bg-[#d54b44]", // Rojo
                authorized: "bg-[#1c4f4a]", // Verde oscuro
            };
            return classes[status] || "bg-[#ffb232]";
        },

        getStatusLabel(status) {
            const labels = {
                pending: "Pendiente",
                completed: "Completado",
                failed: "Fallido",
                authorized: "Autorizado",
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
</style>
