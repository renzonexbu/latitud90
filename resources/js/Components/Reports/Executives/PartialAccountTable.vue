<template>
    <div>
        <div v-if="!items || !items.data || items.data.length === 0" class="text-gray-500 text-sm">
            No hay datos para mostrar.
        </div>
        <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RUT Apoderado</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre Apoderado</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Participante</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Programa</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Último Pago</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="(row, idx) in items.data" :key="idx">
                        <td class="px-4 py-2 text-sm text-gray-900">{{ row.guardian_rut || 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ row.guardian_name || 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ row.participant_name || 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ row.program_name || 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-right">${{ (row.balance || 0).toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</td>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ row.last_payment_date || '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    items: { type: Object, required: true },
});
</script>


