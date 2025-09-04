<template>
    <div class="bg-white rounded-[20px] overflow-hidden w-full">
        <div class="overflow-x-auto w-full">
            <table class="w-full min-w-full">
                <thead class="bg-[#1c4f4a]">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider w-48">Alumno</th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-white uppercase tracking-wider w-24">Precio</th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-white uppercase tracking-wider w-24">Abono</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-white uppercase tracking-wider w-28">Cuotas Pagadas</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-white uppercase tracking-wider w-28">Cuotas Vencidas</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-white uppercase tracking-wider w-28">Forma de Pago</th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-white uppercase tracking-wider w-28">Aporte/Beca</th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-white uppercase tracking-wider w-28">Monto Liberado</th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-white uppercase tracking-wider w-24">Por pagar</th>
                    </tr>
                </thead>

                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="(row, index) in items.data" :key="index"
                        :class="['hover:bg-gray-50 transition-colors', index % 2 === 0 ? 'bg-white' : 'bg-gray-50']">
                        <td class="px-3 py-4 w-48">
                            <div class="text-sm text-[#1c4f4a] font-medium">{{ row.student || 'N/A' }}</div>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-right w-24">
                            <div class="text-sm font-bold text-gray-900">${{ formatPrice(row.price) }}</div>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-right w-24">
                            <div class="text-sm font-bold text-blue-600">${{ formatPrice(row.abono) }}</div>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-center w-28">
                            <div class="text-sm text-gray-900">{{ row.paid_installments }}/{{ row.total_installments }}</div>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-center w-28">
                            <div class="text-sm text-gray-900">{{ row.overdue_installments }}</div>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-center w-28">
                            <div class="text-sm text-gray-900">{{ row.payment_method || 'N/A' }}</div>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-right w-28">
                            <div class="text-sm font-bold text-blue-600">${{ formatPrice(row.scholarship) }}</div>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-right w-28">
                            <div class="text-sm font-bold text-green-600">${{ formatPrice(row.released) }}</div>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-right w-24">
                            <div class="text-sm font-bold">${{ formatPrice(row.balance) }}</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="!items.data || items.data.length === 0" class="p-8 text-center">
            <div class="text-gray-500">
                No hay datos para mostrar
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    items: { type: Object, required: true },
});

const formatPrice = (price) => {
    if (!price) return '0';
    const n = Math.round(Number(price) || 0);
    return n.toLocaleString('es-CL');
};
</script>


