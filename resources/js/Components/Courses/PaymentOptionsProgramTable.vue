<template>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-white uppercase bg-[#1c4f4a]">
                <tr>
                    <th scope="col" class="px-4 py-3 whitespace-nowrap">Código</th>
                    <th scope="col" class="px-4 py-3 whitespace-nowrap">Nombre del Programa</th>
                    <th scope="col" class="px-4 py-3 whitespace-nowrap text-center">Fecha Inicio</th>
                    <th scope="col" class="px-4 py-3 whitespace-nowrap text-center">Valor Unitario</th>
                    <th scope="col" class="px-4 py-3 whitespace-nowrap text-center">Ejecutivo</th>
                    <th scope="col" class="px-4 py-3 whitespace-nowrap text-center">Participantes</th>
                    <th scope="col" class="px-4 py-3 whitespace-nowrap text-center">% de Pago</th>
                    <th scope="col" class="px-4 py-3 whitespace-nowrap text-right">Recaudado / Total</th>
                    <th scope="col" class="px-4 py-3">Pago Total</th>
                    <th scope="col" class="px-4 py-3">Suscripciones</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="program in programs"
                    :key="program.id"
                    class="border-b hover:bg-gray-50"
                    :class="{ 'bg-gray-50': !program.active }"
                >
                    <td class="px-4 py-3 font-medium text-[#007e93]">
                        {{ program.code || 'N/A' }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-xs">{{ program.name || 'N/A' }}</div>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600 whitespace-nowrap">
                        {{ formatDate(program.departure_date) }}
                    </td>
                    <td class="px-4 py-3 text-center text-gray-900 whitespace-nowrap font-medium">
                        ${{ formatPrice(program.trip_price) }}
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600 text-xs">
                        {{ program.sales_executive_name || 'Sin asignar' }}
                    </td>
                    <td class="px-4 py-3 text-center text-gray-900">
                        {{ program.participants_count || 0 }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span
                            class="px-2 py-1 text-xs font-bold rounded-full"
                            :class="getPercentageClass(program.payment_percentage)"
                        >
                            {{ program.payment_percentage || 0 }}%
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <span class="font-medium text-gray-900">${{ formatPrice(program.paid_amount) }}</span>
                        <span class="text-gray-500"> / ${{ formatPrice(program.total_amount) }}</span>
                    </td>
                    <!-- Columna Pago Total -->
                    <td class="px-4 py-3">
                        <div class="flex flex-col gap-1">
                            <template v-for="option in getFullPaymentOptions(program.payment_options)" :key="option.id">
                                <div class="flex items-center gap-1">
                                    <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-xs text-gray-700">{{ cleanLabel(option.label) }}</span>
                                </div>
                            </template>
                            <span v-if="getFullPaymentOptions(program.payment_options).length === 0" class="text-xs text-gray-400">-</span>
                        </div>
                    </td>
                    <!-- Columna Suscripción -->
                    <td class="px-4 py-3">
                        <div class="flex flex-col gap-1">
                            <template v-for="option in getSubscriptionOptions(program.payment_options)" :key="option.id">
                                <div class="flex items-center gap-1">
                                    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-xs text-gray-700">{{ cleanLabel(option.label) }}</span>
                                </div>
                            </template>
                            <span v-if="getSubscriptionOptions(program.payment_options).length === 0" class="text-xs text-gray-400">-</span>
                        </div>
                    </td>
                </tr>
                <tr v-if="programs.length === 0">
                    <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                        No se encontraron programas con los filtros seleccionados
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
export default {
    name: "PaymentOptionsProgramTable",
    props: {
        programs: {
            type: Array,
            default: () => []
        }
    },
    methods: {
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            // Extraer solo YYYY-MM-DD para evitar conversión UTC → Chile que resta un día
            const dateOnly = String(dateString).split('T')[0].split(' ')[0];
            const [year, month, day] = dateOnly.split('-');
            if (!year || !month || !day) return 'N/A';
            return `${day}-${month}-${year}`;
        },
        formatPrice(price) {
            if (!price) return '0';
            const n = Math.round(Number(price) || 0);
            return n.toLocaleString('es-CL');
        },
        cleanLabel(label) {
            if (!label) return '';
            return label
                .replace(/\s*\(Webpay\)\s*$/i, '')
                .replace(/\s*\(Khipu\)\s*$/i, '')
                .replace(/\s*\(VirtualPos\)\s*$/i, '')
                .trim();
        },
        getFullPaymentOptions(options) {
            return options.filter(opt => opt.code?.startsWith('full_'));
        },
        getSubscriptionOptions(options) {
            return options.filter(opt => opt.code?.startsWith('subscription_'));
        },
        getPercentageClass(percentage) {
            if (percentage >= 100) return 'bg-green-500 text-white';
            if (percentage >= 75) return 'bg-blue-500 text-white';
            if (percentage >= 51) return 'bg-yellow-500 text-white';
            if (percentage > 0) return 'bg-red-500 text-white';
            return 'bg-gray-300 text-gray-700';
        }
    }
};
</script>
