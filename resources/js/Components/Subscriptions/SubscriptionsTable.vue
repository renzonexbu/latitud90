<template>
    <div class="bg-white rounded-[20px] overflow-hidden min-h-[600px]">
        <!-- Table Container -->
        <div class="flex flex-col gap-0">
            <!-- Table Header -->
            <div
                class="bg-turquesa rounded-t-[20px] px-5 py-[11px] flex items-center justify-between h-[61.51px]"
            >
                <!-- Checkbox para seleccionar todos -->
                <div class="w-[40px] flex items-center justify-center">
                    <input
                        type="checkbox"
                        :checked="allSelected"
                        @change="toggleAll"
                        class="w-5 h-5 rounded border-white text-turquesa focus:ring-white cursor-pointer"
                    />
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[80px]"
                >
                    ID
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[150px]"
                >
                    Participante
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[200px]"
                >
                    Programa
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]"
                >
                    Institución
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[100px]"
                >
                    Plan
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Cuotas
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Monto Total
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Estado
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
                    v-for="(subscription, index) in subscriptions"
                    :key="subscription.id"
                    :class="[
                        'px-5 py-[14px] flex items-center justify-between cursor-pointer',
                        index % 2 === 0 ? 'bg-white' : 'bg-[#f9f9f9]',
                    ]"
                    @click="$emit('show-subscription-details', subscription)"
                >
                    <!-- Checkbox -->
                    <div class="w-[40px] flex items-center justify-center">
                        <input
                            type="checkbox"
                            :checked="isSelected(subscription.id)"
                            @click="toggleSubscription(subscription.id, $event)"
                            class="w-5 h-5 rounded border-gray-300 text-turquesa focus:ring-turquesa cursor-pointer"
                        />
                    </div>

                    <!-- ID -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[80px]"
                    >
                        #{{ subscription.id }}
                    </div>

                    <!-- Participante -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[150px]"
                    >
                        {{ subscription.participant.name }}
                    </div>

                    <!-- Programa -->
                    <div
                        class="text-[#1c4f4a] font-nexa-bold text-[14px] leading-[18px] text-center w-[200px]"
                    >
                        {{ subscription.program.name }}
                    </div>

                    <!-- Institución -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]"
                    >
                        {{ subscription.institution.name }}
                    </div>

                    <!-- Plan Type -->
                    <div class="flex justify-center items-center w-[100px]">
                        <div
                            v-if="subscription.plan?.is_personalized"
                            class="rounded-[12px] px-[8px] py-[4px] bg-purple-500 text-white font-nexa-xbold text-[11px] leading-[13px] text-center"
                            :title="getPlanTooltip(subscription)"
                        >
                            Personalizado
                        </div>
                        <div
                            v-else
                            class="text-[#5b5b5b] font-nexa-bold text-[11px] leading-[13px] text-center"
                        >
                            General
                        </div>
                    </div>

                    <!-- Cuotas pagadas/total -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        {{ subscription.paid_installments }}/{{ subscription.total_installments }}
                    </div>

                    <!-- Monto Total -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        ${{ formatPrice(subscription.total_amount) }}
                    </div>

                    <!-- Estado -->
                    <div class="flex justify-center items-center w-[120px]">
                        <div
                            :class="[
                                'rounded-[12px] px-[10px] py-[6px] text-white font-nexa-xbold text-[14px] leading-[13px] text-center flex items-center justify-center',
                                getStatusClass(subscription.status),
                            ]"
                        >
                            {{ getStatusLabel(subscription.status) }}
                        </div>
                    </div>

                    <!-- Fecha -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        {{ formatDate(subscription.created_at) }}
                    </div>

                    <!-- Acciones -->
                    <div
                        class="flex gap-2 items-center justify-center w-[80px]"
                    >
                        <!-- View Details Button -->
                        <button
                            @click.stop="$emit('show-subscription-details', subscription)"
                            class="w-[18px] h-[19px] hover:opacity-75 transition-opacity"
                            title="Ver detalles"
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

                        <!-- Cancel Button - Solo para suscripciones activas -->
                        <button
                            v-if="subscription.status === 'ACTIVA' || subscription.status === 'SUSCRIBIENDO'"
                            @click.stop="$emit('cancel-subscription', subscription)"
                            class="w-[18px] h-[19px] hover:opacity-75 transition-opacity text-red-500"
                            title="Cancelar suscripción"
                        >
                            <svg
                                width="18"
                                height="19"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle cx="12" cy="12" r="10" />
                                <line x1="15" y1="9" x2="9" y2="15" />
                                <line x1="9" y1="9" x2="15" y2="15" />
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
    name: "SubscriptionsTable",
    props: {
        subscriptions: {
            type: Array,
            default: () => [],
        },
        selectedSubscriptions: {
            type: Array,
            default: () => [],
        },
    },

    computed: {
        allSelected() {
            return this.subscriptions.length > 0 &&
                   this.subscriptions.every(sub => this.selectedSubscriptions.includes(sub.id));
        },
    },

    methods: {
        toggleAll() {
            if (this.allSelected) {
                this.$emit('selection-changed', []);
            } else {
                const allIds = this.subscriptions.map(sub => sub.id);
                this.$emit('selection-changed', allIds);
            }
        },

        toggleSubscription(subscriptionId, event) {
            event.stopPropagation();

            const selected = [...this.selectedSubscriptions];
            const index = selected.indexOf(subscriptionId);

            if (index > -1) {
                selected.splice(index, 1);
            } else {
                selected.push(subscriptionId);
            }

            this.$emit('selection-changed', selected);
        },

        isSelected(subscriptionId) {
            return this.selectedSubscriptions.includes(subscriptionId);
        },

        getStatusClass(status) {
            const classes = {
                'ACTIVA': "bg-[#4b8d7f]", // Verde
                'SUSCRIBIENDO': "bg-[#3b82f6]", // Azul
                'PENDIENTE': "bg-[#ffb232]", // Amarillo
                'CANCELADA': "bg-[#6b7280]", // Gris
                'RECHAZADA': "bg-[#d54b44]", // Rojo
            };
            return classes[status] || "bg-[#ffb232]";
        },

        getStatusLabel(status) {
            const labels = {
                'ACTIVA': "Activa",
                'SUSCRIBIENDO': "Suscribiendo",
                'PENDIENTE': "Pendiente",
                'CANCELADA': "Cancelada",
                'RECHAZADA': "Rechazada",
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

        getPlanTooltip(subscription) {
            if (!subscription.plan?.is_personalized) return '';

            const discountTypes = {
                'scholarship': 'Beca',
                'released': 'Liberado',
            };

            let tooltip = 'Plan personalizado';

            if (subscription.plan.discount_type) {
                const discountLabel = discountTypes[subscription.plan.discount_type] || subscription.plan.discount_type;
                tooltip += ` - ${discountLabel}`;
            }

            if (subscription.plan.discount_amount) {
                tooltip += ` ($${this.formatPrice(subscription.plan.discount_amount)} descuento)`;
            }

            if (subscription.plan.discount_reason) {
                tooltip += `\n${subscription.plan.discount_reason}`;
            }

            return tooltip;
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
