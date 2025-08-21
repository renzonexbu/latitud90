<template>
    <div class="bg-white rounded-[20px] overflow-hidden">
        <!-- Table Container -->
        <div class="flex flex-col gap-0">
            <!-- Table Header -->
            <div
                class="bg-turquesa rounded-t-[20px] px-5 py-[11px] flex items-center justify-between h-[61.51px]"
            >
                <div
                    class="text-white font-bold text-[14px] leading-[18px] text-center w-[140px]"
                >
                    Participante
                </div>
                <div
                    class="text-white font-bold text-[14px] leading-[18px] text-center w-[180px]"
                >
                    Programa
                </div>
                <div
                    class="text-white font-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    N° Orden
                </div>
                <div
                    class="text-white font-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Fecha Pago
                </div>
                <div
                    class="text-white font-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Monto Pagado
                </div>
                <div
                    class="text-white font-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Método Pago
                </div>
                <div
                    class="text-white font-bold text-[14px] leading-[18px] text-center w-[100px]"
                >
                    Estado
                </div>
                <!-- Columna de acciones -->
                <div class="w-[80px]"></div>
            </div>

            <!-- Table Body -->
            <div class="flex flex-col">
                <div
                    v-for="(payment, index) in payments"
                    :key="payment.id"
                    :class="[
                        'px-5 py-[14px] flex items-center justify-between',
                        index % 2 === 0 ? 'bg-white' : 'bg-[#f9f9f9]',
                    ]"
                >
                    <!-- Participante -->
                    <div
                        class="text-[#5b5b5b] font-bold text-[14px] leading-[18px] text-center w-[140px]"
                    >
                        <div class="text-center">
                            <div class="font-semibold">
                                {{ payment.participant_name }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ payment.participant_email }}
                            </div>
                            <div class="text-xs text-gray-400">
                                {{ formatRut(payment.participant_document) }}
                            </div>
                        </div>
                    </div>

                    <!-- Programa -->
                    <div
                        class="text-[#1c4f4a] font-bold text-[14px] leading-[18px] text-center w-[180px]"
                    >
                        <div class="text-center">
                            <div class="font-semibold">
                                {{ payment.program_name }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ payment.program_destination }}
                            </div>
                            <div class="text-xs text-gray-400">
                                {{ formatDate(payment.program_departure_date) }}
                            </div>
                        </div>
                    </div>

                    <!-- N° Orden -->
                    <div
                        class="text-[#5b5b5b] font-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        {{ payment.order_number }}
                    </div>

                    <!-- Fecha Pago -->
                    <div
                        class="text-[#5b5b5b] font-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        {{ formatDate(payment.payment_date) }}
                    </div>

                    <!-- Monto Pagado -->
                    <div
                        class="text-[#5b5b5b] font-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        <div class="text-center">
                            <div class="font-semibold text-green-600">
                                ${{ formatPrice(payment.payment_amount) }}
                            </div>
                            <div class="text-xs text-gray-500">
                                Cuota {{ payment.installment_number }}
                            </div>
                        </div>
                    </div>

                    <!-- Método Pago -->
                    <div
                        class="text-[#5b5b5b] font-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        <div class="text-center">
                            <div class="font-semibold">
                                {{ payment.payment_method_name }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ payment.financing_type_label }}
                            </div>
                        </div>
                    </div>

                    <!-- Estado -->
                    <div
                        class="text-[#5b5b5b] font-bold text-[14px] leading-[18px] text-center w-[100px]"
                    >
                        <span
                            :class="getStatusClass(payment.payment_status)"
                            class="px-3 py-1 text-xs font-semibold rounded-full inline-block"
                        >
                            {{ getStatusLabel(payment.payment_status) }}
                        </span>
                    </div>

                    <!-- Acciones -->
                    <div class="w-[80px] flex justify-center">
                        <button
                            @click="$emit('view-details', payment)"
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
        <div v-if="payments.length === 0" class="p-8 text-center">
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
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"
                    ></path>
                </svg>
                <p class="text-lg font-medium">No hay pagos para mostrar</p>
                <p class="text-sm">
                    Intenta ajustar los filtros o verifica que existan pagos en
                    el sistema
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    payments: {
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
    // Normalizar el status a minúsculas para evitar problemas de case
    const normalizedStatus = (status || '').toLowerCase();
    
    const classes = {
        pending: "bg-yellow-100 text-yellow-800 border border-yellow-200",
        completed: "bg-green-100 text-green-800 border border-green-200",
        failed: "bg-red-100 text-red-800 border border-red-200",
        authorized: "bg-blue-100 text-blue-800 border border-blue-200",
        processing: "bg-blue-100 text-blue-800 border border-blue-200",
        success: "bg-green-100 text-green-800 border border-green-200",
        paid: "bg-green-100 text-green-800 border border-green-200",
        approved: "bg-green-100 text-green-800 border border-green-200",
        rejected: "bg-red-100 text-red-800 border border-red-200",
        cancelled: "bg-gray-100 text-gray-800 border border-gray-200",
    };
    return classes[normalizedStatus] || "bg-gray-100 text-gray-800 border border-gray-200";
};

const getStatusLabel = (status) => {
    // Normalizar el status a minúsculas para evitar problemas de case
    const normalizedStatus = (status || '').toLowerCase();
    
    const labels = {
        pending: "Pendiente",
        completed: "Completado",
        failed: "Fallido",
        authorized: "Autorizado",
        processing: "Procesando",
        success: "Exitoso",
        paid: "Pagado",
        approved: "Aprobado",
        rejected: "Rechazado",
        cancelled: "Cancelado",
    };
    return labels[normalizedStatus] || (status || "Desconocido");
};
</script>
