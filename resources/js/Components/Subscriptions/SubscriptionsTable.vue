<template>
    <div class="bg-white rounded-lg border border-gray-200">
        <!-- Table Container -->
        <div class="overflow-x-auto">
            <table class="w-full text-xs" style="min-width: 1400px;">
                <!-- Table Header -->
                <thead class="bg-[#007e93] sticky top-0 z-10">
                    <tr>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap w-[40px]">
                            <input
                                type="checkbox"
                                :checked="allSelected"
                                @change="toggleAll"
                                class="w-4 h-4 rounded border-white text-turquesa focus:ring-white cursor-pointer"
                            />
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            ID
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[160px]">
                            Participante
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[220px]">
                            Programa
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[180px]">
                            Institución
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Plan
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Cuotas
                        </th>
                        <th class="px-2 py-2 text-right text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Monto Total
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Estado
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Fecha
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap w-[60px]">

                        </th>
                    </tr>
                </thead>

                <!-- Table Body -->
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                        v-for="(subscription, index) in subscriptions"
                        :key="subscription.id"
                        :class="[
                            'hover:bg-gray-50 transition-colors cursor-pointer',
                            index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                        ]"
                        @click="$emit('show-subscription-details', subscription)"
                    >
                        <!-- Checkbox -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <input
                                type="checkbox"
                                :checked="isSelected(subscription.id)"
                                @click="toggleSubscription(subscription.id, $event)"
                                class="w-4 h-4 rounded border-gray-300 text-turquesa focus:ring-turquesa cursor-pointer"
                            />
                        </td>

                        <!-- ID -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs text-gray-900">
                                #{{ subscription.id }}
                            </div>
                        </td>

                        <!-- Participante -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs font-medium text-gray-900">
                                {{ subscription.participant.name }}
                            </div>
                        </td>

                        <!-- Programa -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs font-medium text-[#1c4f4a]">
                                {{ subscription.program.name }}
                            </div>
                        </td>

                        <!-- Institución -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs text-gray-900">
                                {{ subscription.institution.name }}
                            </div>
                        </td>

                        <!-- Plan Type -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <span
                                v-if="subscription.plan?.is_personalized"
                                class="inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-purple-500 text-white"
                                :title="getPlanTooltip(subscription)"
                            >
                                Personalizado
                            </span>
                            <span v-else class="text-xs text-gray-600">
                                General
                            </span>
                        </td>

                        <!-- Cuotas pagadas/total -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs text-gray-900">
                                {{ subscription.paid_installments }}/{{ subscription.total_installments }}
                            </div>
                        </td>

                        <!-- Monto Total -->
                        <td class="px-2 py-2 whitespace-nowrap text-right">
                            <div class="text-xs font-bold text-gray-900">
                                ${{ formatPrice(subscription.total_amount) }}
                            </div>
                        </td>

                        <!-- Estado -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <span
                                :class="[
                                    'inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded-full text-white',
                                    getStatusClass(subscription.status),
                                ]"
                            >
                                {{ getStatusLabel(subscription.status) }}
                            </span>
                        </td>

                        <!-- Fecha -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs text-gray-900">
                                {{ formatDate(subscription.created_at) }}
                            </div>
                        </td>

                        <!-- Acciones -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="flex gap-1 items-center justify-center">
                                <!-- View Details Button -->
                                <button
                                    @click.stop="$emit('show-subscription-details', subscription)"
                                    class="w-[18px] h-[18px] hover:opacity-75 transition-opacity"
                                    title="Ver detalles"
                                >
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C7C7C7" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                </button>

                                <!-- Cancel Button - Solo para suscripciones activas -->
                                <button
                                    v-if="subscription.status === 'ACTIVA' || subscription.status === 'SUSCRIBIENDO'"
                                    @click.stop="$emit('cancel-subscription', subscription)"
                                    class="w-[18px] h-[18px] hover:opacity-75 transition-opacity text-red-500"
                                    title="Cancelar suscripción"
                                >
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10" />
                                        <line x1="15" y1="9" x2="9" y2="15" />
                                        <line x1="9" y1="9" x2="15" y2="15" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
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
