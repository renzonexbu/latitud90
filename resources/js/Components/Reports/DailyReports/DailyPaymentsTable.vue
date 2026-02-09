<template>
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="overflow-x-auto">
            <table class="w-full text-xs" style="min-width: 1400px;">
                <!-- Table Header -->
                <thead class="bg-[#007e93] sticky top-0 z-10">
                    <tr>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[100px]">
                            Cód. Inscripción
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[160px]">
                            Participante
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Forma de Pago
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Tipo Documento
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[110px]">
                            N° Documento
                        </th>
                        <th class="px-2 py-2 text-right text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Monto Pagado
                        </th>
                        <th class="px-2 py-2 text-right text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Saldo Pendiente
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[140px]">
                            Ejecutivo Comercial
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap w-[50px]">

                        </th>
                    </tr>
                </thead>

                <!-- Table Body -->
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                        v-for="(payment, index) in payments"
                        :key="payment.id"
                        :class="[
                            'hover:bg-gray-50 cursor-pointer transition-colors',
                            index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                        ]"
                        @click="$emit('view-details', payment)"
                    >
                        <!-- Código de Inscripción -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs font-medium text-[#1c4f4a]">
                                {{ payment.enrollment_code || 'N/A' }}
                            </div>
                        </td>

                        <!-- Participante -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs font-medium text-gray-900">
                                {{ payment.participant_name || 'N/A' }}
                            </div>
                        </td>

                        <!-- Forma de Pago -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <span class="inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ payment.payment_form_code || 'N/A' }}
                            </span>
                        </td>

                        <!-- Tipo de Documento (B2, BC, FF, AC) -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <span class="inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-gray-100 text-gray-800">
                                {{ payment.document_type_code || 'N/A' }}
                            </span>
                        </td>

                        <!-- N° de Documento (boleta/transacción) -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs text-gray-900">
                                {{ payment.transaction_document_number || 'N/A' }}
                            </div>
                        </td>

                        <!-- Monto Pagado -->
                        <td class="px-2 py-2 whitespace-nowrap text-right">
                            <div class="text-xs font-bold text-green-600">
                                ${{ formatPrice(payment.total_paid_amount) }}
                            </div>
                        </td>

                        <!-- Saldo Pendiente -->
                        <td class="px-2 py-2 whitespace-nowrap text-right">
                            <div class="text-xs font-bold" :class="getBalanceClass(payment.total_pending_amount)">
                                ${{ formatPrice(payment.total_pending_amount) }}
                            </div>
                        </td>

                        <!-- Ejecutivo Comercial -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs text-gray-900">
                                {{ payment.sales_executive_name || 'N/A' }}
                            </div>
                        </td>

                        <!-- Acciones -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <button
                                @click.stop="$emit('view-details', payment)"
                                class="text-[#1c4f4a] hover:text-[#0f2e29] transition-colors"
                                title="Ver detalles"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
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
                    Intenta ajustar los filtros o verifica que existan pagos en el rango de fechas seleccionado
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from "vue";

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

const getBalanceClass = (balance) => {
    const amount = Number(balance) || 0;
    if (amount <= 0) return "text-green-600";
    if (amount <= 100000) return "text-yellow-600";
    return "text-red-600";
};
</script>
