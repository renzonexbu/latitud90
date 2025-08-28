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
                            <div class="font-semibold">
                                {{ schedule.participant_name }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ schedule.participant_email }}
                            </div>
                            <div class="text-xs text-gray-400">
                                {{ formatRut(schedule.participant_document) }}
                            </div>
                        </div>
                    </div>

                    <!-- Programa -->
                    <div
                        class="text-[#1c4f4a] font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]"
                    >
                        <div class="text-center">
                            <div class="font-semibold">
                                {{ schedule.program_name }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{
                                    formatDate(schedule.program_departure_date)
                                }}
                            </div>
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
                            <div class="font-semibold">
                                ${{ formatPrice(schedule.amount) }}
                            </div>
                            <div
                                v-if="schedule.discount_amount > 0"
                                class="text-xs text-red-500"
                            >
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
                            class="w-[30px] h-[30px] hover:opacity-75 transition-opacity flex items-center justify-center"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="16"
                                viewBox="0 0 24 16"
                                fill="none"
                                class="w-[30px] h-[20px]"
                            >
                                <path
                                    d="M12 2C6 2 2 8 2 8C2 8 6 14 12 14C18 14 22 8 22 8C22 8 18 2 12 2Z"
                                    stroke="#C7C7C7"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="M12 10.5C13.3807 10.5 14.5 9.38071 14.5 8C14.5 6.61929 13.3807 5.5 12 5.5C10.6193 5.5 9.5 6.61929 9.5 8C9.5 9.38071 10.6193 10.5 12 10.5Z"
                                    stroke="#C7C7C7"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensaje cuando no hay datos -->
        <div v-if="schedules.length === 0" class="p-8 text-center">
            <div class="text-gray-500">
                <svg
                    class="mx-auto h-12 w-12 text-gray-400 mb-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    ></path>
                </svg>
                <p class="text-lg font-medium">No hay cuotas para mostrar</p>
                <p class="text-sm">
                    Intenta ajustar los filtros o verifica que existan cuotas en
                    el sistema
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    schedules: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["view-details"]);

const formatPrice = (price) => {
    if (!price) return "0";
    // Convertir a número, redondear y formatear sin decimales
    const numericPrice = Math.round(Number(price) || 0);
    return numericPrice.toLocaleString("es-CL");
};

const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("es-CL");
};

const formatRut = (rut) => {
    if (!rut) return "N/A";
    
    // Si ya viene formateado del backend (con puntos), devolverlo tal como está
    if (rut.includes('.')) {
        return rut;
    }
    
    // Limpiar el RUT de puntos y guiones
    let rutLimpio = rut.toString().replace(/\./g, "").replace(/-/g, "");
    
    // Verificar si es un RUT (7-8 dígitos + dígito verificador)
    if (rutLimpio.length >= 7 && rutLimpio.length <= 9 && /^\d{7,8}[\dK]$/.test(rutLimpio)) {
        // Es un RUT, formatearlo
        let dv = rutLimpio.slice(-1);
        let numero = rutLimpio.slice(0, -1);
        
        // Formatear número con puntos
        let numeroFormateado = numero.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        
        // Retornar RUT formateado
        return `${numeroFormateado}-${dv.toUpperCase()}`;
    } else {
        // No es un RUT, devolver tal como está
        return rut;
    }
};

const getStatusClass = (status) => {
    const classes = {
        pending: "bg-yellow-100 text-yellow-800",
        overdue: "bg-red-100 text-red-800",
        paid: "bg-green-100 text-green-800",
        upcoming: "bg-blue-100 text-blue-800",
    };
    return classes[status] || "bg-gray-100 text-gray-800";
};

const getStatusLabel = (status) => {
    const labels = {
        pending: "Pendiente",
        overdue: "Vencida",
        paid: "Pagada",
        upcoming: "Próxima",
    };
    return labels[status] || status;
};

const getDaysClass = (days) => {
    if (!days || days <= 0) return "text-gray-500";
    if (days <= 7) return "text-yellow-600";
    if (days <= 30) return "text-orange-600";
    return "text-red-600";
};
</script>
