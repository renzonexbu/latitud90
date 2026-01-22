<template>
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="overflow-x-auto">
            <table class="w-full text-xs" style="min-width: 1400px;">
                <!-- Table Header -->
                <thead class="bg-[#007e93] sticky top-0 z-10">
                    <tr>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[140px]">
                            Ejecutivo Comercial
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[100px]">
                            Código
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[220px]">
                            Programa
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            N° Documento
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            F. Pago
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            <div class="flex items-center justify-center">
                                <span>Tipo Dcto</span>
                                <button
                                    @mouseenter="showTooltip = true"
                                    @mouseleave="showTooltip = false"
                                    class="ml-1 w-3 h-3 text-white/70 hover:text-white cursor-help relative"
                                >
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div
                                        v-if="showTooltip"
                                        class="absolute top-full left-1/2 transform -translate-x-1/2 mt-2 w-48 p-2 bg-gray-900 text-white text-xs rounded-lg shadow-lg z-50"
                                        style="margin-left: -96px;"
                                    >
                                        <div class="space-y-1">
                                            <div><strong>B2:</strong> Boleta</div>
                                            <div><strong>BC:</strong> Nota de Crédito</div>
                                            <div><strong>FF:</strong> Factura</div>
                                            <div><strong>AC:</strong> Reserva</div>
                                        </div>
                                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-b-gray-900" style="margin-left: -8px;"></div>
                                    </div>
                                </button>
                            </div>
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Cuotas Pagadas
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Cuotas Pend.
                        </th>
                        <th class="px-2 py-2 text-right text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Monto Pagado
                        </th>
                        <th class="px-2 py-2 text-right text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Saldo Pend.
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
                        <!-- Ejecutivo Comercial -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs font-medium text-gray-900">
                                {{ payment.sales_executive_name || 'N/A' }}
                            </div>
                        </td>

                        <!-- Código -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs text-gray-900">
                                {{ payment.program_code || 'N/A' }}
                            </div>
                        </td>

                        <!-- Programa -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs text-[#1c4f4a] font-medium">
                                {{ payment.program_name || 'N/A' }}
                            </div>
                            <div class="text-[10px] text-gray-500">
                                {{ payment.program_destination || '' }}
                            </div>
                        </td>

                        <!-- N° Documento -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs text-gray-900">
                                {{ payment.transaction_document_number || 'N/A' }}
                            </div>
                        </td>

                        <!-- Forma de Pago -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <span class="inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ payment.payment_form_code || 'N/A' }}
                            </span>
                        </td>

                        <!-- Tipo de Dcto -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <span class="inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-gray-100 text-gray-800">
                                {{ payment.document_type_code || 'N/A' }}
                            </span>
                        </td>

                        <!-- N° Cuotas Pagadas -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs font-bold text-green-600">
                                {{ payment.paid_installments_display || '0' }}
                            </div>
                        </td>

                        <!-- N° Cuotas No Pagadas -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs font-bold text-red-600">
                                {{ payment.overdue_installments_display || '0' }}
                            </div>
                        </td>

                        <!-- Monto Total Pagado -->
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

const showTooltip = ref(false);

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
