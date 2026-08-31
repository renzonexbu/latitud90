<template>
    <div class="bg-white rounded-lg border border-gray-200 w-full">
        <div class="overflow-x-auto w-full">
            <table class="w-full text-xs" style="min-width: 1100px;">
                <thead class="bg-[#007e93] sticky top-0 z-10">
                    <tr>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[200px]">Alumno</th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">Estado</th>
                        <th class="px-2 py-2 text-right text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">Precio</th>
                        <th class="px-2 py-2 text-right text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">Abono</th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">Cuotas Pagadas</th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">Forma Pago</th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">PAT</th>
                        <th class="px-2 py-2 text-right text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">Aporte/Beca</th>
                        <th class="px-2 py-2 text-right text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">Liberado</th>
                        <th class="px-2 py-2 text-right text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">Saldo</th>
                    </tr>
                </thead>

                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="(row, index) in items.data" :key="index"
                        :class="['hover:bg-gray-50 transition-colors', index % 2 === 0 ? 'bg-white' : 'bg-gray-50']">
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs text-[#1c4f4a] font-medium">{{ row.student || 'N/A' }}</div>
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <span
                                class="px-2 py-1 text-[10px] font-medium rounded-full"
                                :class="{
                                    'bg-green-100 text-green-800': row.status === 'Activo',
                                    'bg-red-100 text-red-800': row.status === 'De Baja'
                                }"
                            >
                                {{ row.status || 'Activo' }}
                            </span>
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap text-right">
                            <div class="text-xs font-bold text-gray-900">{{ formatPrice(row.price) }}</div>
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap text-right">
                            <div class="text-xs font-bold text-blue-600">{{ formatPrice(row.abono) }}</div>
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs text-gray-900">{{ row.paid_installments }}/{{ row.total_installments }}</div>
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs text-gray-900">{{ row.payment_method || 'N/A' }}</div>
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <span
                                v-if="row.pat_status && row.pat_status !== '—'"
                                class="px-2 py-1 text-[10px] font-medium rounded-full"
                                :class="getPatStatusClass(row.pat_status)"
                            >
                                {{ row.pat_status }}
                            </span>
                            <span v-else class="text-xs text-gray-400">—</span>
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap text-right">
                            <div class="text-xs font-bold text-blue-600">{{ formatPrice(row.scholarship) }}</div>
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap text-right">
                            <div class="text-xs font-bold text-green-600">{{ formatPrice(row.released) }}</div>
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap text-right">
                            <div class="text-xs font-bold" :class="getBalanceClass(row.balance)">{{ formatBalance(row.balance) }}</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="!items.data || items.data.length === 0" class="p-8 text-center">
            <div class="text-gray-500 text-sm">
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

const formatBalance = (balance) => {
    const n = Math.round(Number(balance) || 0);
    if (n < 0) return `(${Math.abs(n).toLocaleString('es-CL')})`;
    return n.toLocaleString('es-CL');
};

const getBalanceClass = (balance) => {
    const n = Number(balance) || 0;
    if (n > 0) return 'text-green-600';
    if (n < 0) return 'text-red-600';
    return 'text-gray-600';
};

// Estado de la suscripción PAT: rojo para las que se cayeron (el caso que
// interesa detectar en la revisión de morosos), verde para las vigentes.
const getPatStatusClass = (status) => {
    switch (status) {
        case 'Activa':
            return 'bg-green-100 text-green-800';
        case 'Suscribiendo':
            return 'bg-yellow-100 text-yellow-800';
        case 'Cancelada':
        case 'Fallida':
            return 'bg-red-100 text-red-800';
        case 'Finalizada':
            return 'bg-gray-100 text-gray-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};
</script>


