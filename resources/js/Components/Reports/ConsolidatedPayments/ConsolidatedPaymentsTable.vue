<template>
    <div class="bg-white rounded-[20px] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-[#1c4f4a]">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Código (Programa)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">RUT Alumno</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Nombre del Alumno</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-white uppercase tracking-wider">Pago o Devolución $</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Documentos N° Boleta o NC</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Tipo de Documento</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">N° Reserva</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Forma de Pago</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">N° Cuotas Pagadas</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Fecha de Pago</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-white uppercase tracking-wider">Aporte o becas</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">Liberado</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-white uppercase tracking-wider">Valor total prog.</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                        v-for="(row, index) in rows"
                        :key="row.id || index"
                        :class="[
                            'hover:bg-gray-50 transition-colors',
                            index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                        ]"
                    >
                        <td class="px-4 py-4 whitespace-nowrap">{{ row.program_code || row.program_id || 'N/A' }}</td>
                        <td class="px-4 py-4 whitespace-nowrap">{{ formatRut(row.participant_rut || row.participant_document) }}</td>
                        <td class="px-4 py-4 whitespace-nowrap">{{ row.participant_name || row.participant_full_name || 'N/A' }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-right" :class="row.is_refund ? 'text-red-600 font-semibold' : 'text-green-700 font-semibold'">${{ formatCurrency(row.payment_amount) }}</td>
                        <td class="px-4 py-4 whitespace-nowrap">{{ row.invoice_number || row.document_number || 'N/A' }}</td>
                        <td class="px-4 py-4 whitespace-nowrap">{{ row.document_type || row.document_type_code || 'N/A' }}</td>
                        <td class="px-4 py-4 whitespace-nowrap">{{ row.reservation_number || row.buy_order || 'N/A' }}</td>
                        <td class="px-4 py-4 whitespace-nowrap">{{ row.payment_method_name || row.payment_method_code || 'N/A' }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-center">{{ row.paid_installments_display || formatInstallments(row.paid_installments, row.total_installments) }}</td>
                        <td class="px-4 py-4 whitespace-nowrap">{{ formatDate(row.payment_date) }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-right">${{ formatCurrency(row.external_contribution || row.scholarship_amount) }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-center">
                            <span :class="row.released ? 'text-green-600' : 'text-gray-500'">{{ row.released ? 'Sí' : 'No' }}</span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-right">${{ formatCurrency(row.total_program_value || row.program_total_value) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="rows.length === 0" class="p-8 text-center">
            <div class="text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
                <p class="text-lg font-medium">No hay registros para mostrar</p>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    rows: {
        type: Array,
        default: () => []
    }
});

const formatCurrency = (value) => {
    const numeric = Math.round(Number(value) || 0);
    return numeric.toLocaleString('es-CL');
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('es-CL');
};

const formatRut = (rut) => {
    if (!rut) return 'N/A';
    if (rut.includes('.')) return rut;
    let clean = rut.toString().replace(/\./g, '').replace(/-/g, '');
    if (clean.length >= 7 && clean.length <= 9 && /^\d{7,8}[\dK]$/.test(clean)) {
        const dv = clean.slice(-1);
        const num = clean.slice(0, -1);
        const withDots = num.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return `${withDots}-${dv.toUpperCase()}`;
    }
    return rut;
};

const formatInstallments = (paid, total) => {
    const p = Number(paid) || 0;
    const t = Number(total) || 0;
    if (t <= 0) return `${p}`;
    return `${p} de ${t}`;
};
</script>


