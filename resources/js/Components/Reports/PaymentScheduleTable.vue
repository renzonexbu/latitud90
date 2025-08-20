<template>
    <div class="bg-white rounded-[20px] overflow-hidden">
        <!-- Table Container -->
        <div class="flex flex-col gap-0">
            <!-- Table Header -->
            <div
                class="bg-turquesa rounded-t-[20px] px-5 py-[11px] flex items-center justify-between h-[61.51px]"
            >
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]"
                >
                    Participante
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]"
                >
                    Programa
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[100px]"
                >
                    N° Cuota
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]"
                >
                    Fecha Vencimiento
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Monto Cuota
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[100px]"
                >
                    Estado
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Días Vencimiento
                </div>
                <!-- Columna de acciones -->
                <div class="w-[80px]"></div>
            </div>

            <!-- Table Body -->
            <div class="flex flex-col">
                <div
                    v-for="(schedule, index) in schedules"
                    :key="schedule.id"
                    :class="[
                        'px-5 py-[14px] flex items-center justify-between',
                        index % 2 === 0 ? 'bg-white' : 'bg-[#f9f9f9]',
                    ]"
                >
                    <!-- Participante -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]"
                    >
                        <div class="text-center">
                            <div class="font-semibold">{{ schedule.participant_name }}</div>
                            <div class="text-xs text-gray-500">{{ schedule.participant_email }}</div>
                            <div class="text-xs text-gray-400">{{ schedule.participant_document }}</div>
                        </div>
                    </div>

                    <!-- Programa -->
                    <div
                        class="text-[#1c4f4a] font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]"
                    >
                        <div class="text-center">
                            <div class="font-semibold">{{ schedule.program_name }}</div>
                            <div class="text-xs text-gray-500">{{ formatDate(schedule.program_departure_date) }}</div>
                        </div>
                    </div>

                    <!-- N° Cuota -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[100px]"
                    >
                        {{ schedule.installment_number }}
                    </div>

                    <!-- Fecha Vencimiento -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]"
                    >
                        {{ formatDate(schedule.due_date) }}
                    </div>

                    <!-- Monto Cuota -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        <div class="text-center">
                            <div class="font-semibold">${{ formatPrice(schedule.amount) }}</div>
                            <div v-if="schedule.discount_amount > 0" class="text-xs text-red-500">
                                -${{ formatPrice(schedule.discount_amount) }}
                            </div>
                        </div>
                    </div>

                    <!-- Estado -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[100px]"
                    >
                        <span
                            :class="getStatusClass(schedule.status)"
                            class="px-2 py-1 text-xs font-semibold rounded-full"
                        >
                            {{ getStatusLabel(schedule.status) }}
                        </span>
                    </div>

                    <!-- Días Vencimiento -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        <span :class="getDaysClass(schedule.days_overdue)">
                            {{ schedule.days_overdue || 0 }} días
                        </span>
                    </div>

                    <!-- Acciones -->
                    <div class="w-[80px] flex justify-center">
                        <button
                            @click="$emit('view-details', schedule)"
                            class="bg-[#1c4f4a] hover:bg-[#0f3a36] text-white px-3 py-1 rounded-lg text-xs font-medium transition-colors"
                        >
                            Ver
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensaje cuando no hay datos -->
        <div v-if="schedules.length === 0" class="p-8 text-center">
            <div class="text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-lg font-medium">No hay cuotas para mostrar</p>
                <p class="text-sm">Intenta ajustar los filtros o verifica que existan cuotas en el sistema</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    schedules: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['view-details']);

const formatPrice = (price) => {
    if (!price) return '0';
    // Convertir a número, redondear y formatear sin decimales
    const numericPrice = Math.round(Number(price) || 0);
    return numericPrice.toLocaleString('es-CL');
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('es-CL');
};

const getStatusClass = (status) => {
    const classes = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'overdue': 'bg-red-100 text-red-800',
        'paid': 'bg-green-100 text-green-800',
        'upcoming': 'bg-blue-100 text-blue-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const labels = {
        'pending': 'Pendiente',
        'overdue': 'Vencida',
        'paid': 'Pagada',
        'upcoming': 'Próxima',
    };
    return labels[status] || status;
};

const getDaysClass = (days) => {
    if (!days || days <= 0) return 'text-gray-500';
    if (days <= 7) return 'text-yellow-600';
    if (days <= 30) return 'text-orange-600';
    return 'text-red-600';
};
</script>
