<template>
    <div class="bg-white rounded-lg border border-gray-200">
        <!-- Table Container -->
        <div class="overflow-x-auto">
            <table class="w-full text-xs" style="min-width: 1200px;">
                <!-- Table Header -->
                <thead class="bg-[#007e93] sticky top-0 z-10">
                    <tr>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[180px]">
                            Participante
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[200px]">
                            Programa
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            N° Cuota
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Fecha Vencimiento
                        </th>
                        <th class="px-2 py-2 text-right text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Monto Cuota
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Estado
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Días Vencimiento
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap w-[50px]">

                        </th>
                    </tr>
                </thead>

                <!-- Table Body -->
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                        v-for="(schedule, index) in schedules"
                        :key="schedule.id"
                        :class="[
                            'hover:bg-gray-50 transition-colors',
                            index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                        ]"
                    >
                        <!-- Participante -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs font-medium text-gray-900">
                                {{ schedule.participant_name }}
                            </div>
                            <div class="text-[10px] text-gray-500">
                                {{ schedule.participant_email }}
                            </div>
                            <div class="text-[10px] text-gray-400">
                                {{ formatRut(schedule.participant_document) }}
                            </div>
                        </td>

                        <!-- Programa -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs font-medium text-[#1c4f4a]">
                                {{ schedule.program_name }}
                            </div>
                            <div class="text-[10px] text-gray-500">
                                {{ formatDate(schedule.program_departure_date) }}
                            </div>
                        </td>

                        <!-- N° Cuota -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs text-gray-900">
                                {{ schedule.installment_number }}
                            </div>
                        </td>

                        <!-- Fecha Vencimiento -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs text-gray-900">
                                {{ formatDate(schedule.due_date) }}
                            </div>
                        </td>

                        <!-- Monto Cuota -->
                        <td class="px-2 py-2 whitespace-nowrap text-right">
                            <div class="text-xs font-bold text-gray-900">
                                ${{ formatPrice(schedule.amount) }}
                            </div>
                            <div v-if="schedule.discount_amount > 0" class="text-[10px] text-red-500">
                                -${{ formatPrice(schedule.discount_amount) }}
                            </div>
                        </td>

                        <!-- Estado -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <span
                                :class="getStatusClass(schedule.status)"
                                class="inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded-full"
                            >
                                {{ getStatusLabel(schedule.status) }}
                            </span>
                        </td>

                        <!-- Días Vencimiento -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <span :class="getDaysClass(schedule.days_overdue)" class="text-xs">
                                {{ schedule.days_overdue || 0 }} días
                            </span>
                        </td>

                        <!-- Acciones -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <button
                                @click="$emit('view-details', schedule)"
                                class="w-[18px] h-[18px] hover:opacity-75 transition-opacity"
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C7C7C7" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
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
