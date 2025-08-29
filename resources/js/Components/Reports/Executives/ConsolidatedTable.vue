<template>
    <div class="bg-white rounded-[20px] overflow-hidden w-full">
        <div class="overflow-x-auto w-full">
            <table class="w-full min-w-full">
                <!-- Table Header -->
                <thead class="bg-[#1c4f4a]">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider w-24">
                            Nro. Programa
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider w-32">
                            N° de Identificación
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider w-48">
                            Nombres y Apellidos
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider w-24">
                            Estado
                        </th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-white uppercase tracking-wider w-28">
                            Pago y/o Dev.
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider w-32">
                            Nro. Documento
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider w-28">
                            Tipo de Documento
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider w-32">
                            Forma Pago
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider w-28">
                            Fecha de Pago
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider w-32">
                            Contacto Pagador
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider w-40">
                            Email Contacto Pagador
                        </th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-white uppercase tracking-wider w-28">
                            Aporte o Beca
                        </th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-white uppercase tracking-wider w-28">
                            Liberado
                        </th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-white uppercase tracking-wider w-28">
                            Precio
                        </th>
                    </tr>
                </thead>

                <!-- Table Body -->
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                        v-for="(item, index) in items.data"
                        :key="item.id"
                        :class="[
                            'hover:bg-gray-50 transition-colors',
                            index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                        ]"
                    >
                        <!-- Nro. Programa -->
                        <td class="px-3 py-4 whitespace-nowrap w-24">
                            <div class="text-sm font-medium text-gray-900">
                                {{ item.program_number || 'N/A' }}
                            </div>
                        </td>

                        <!-- N° de Identificación -->
                        <td class="px-3 py-4 whitespace-nowrap w-32">
                            <div class="text-sm text-gray-900">
                                {{ formatRut(item.identification_number) }}
                            </div>
                        </td>

                        <!-- Nombres y Apellidos -->
                        <td class="px-3 py-4 w-48">
                            <div class="text-sm text-[#1c4f4a] font-medium">
                                {{ item.full_name || 'N/A' }}
                            </div>
                        </td>

                        <!-- Estado -->
                        <td class="px-3 py-4 whitespace-nowrap w-24">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" :class="getStatusClass(item.status)">
                                {{ getStatusLabel(item.status) }}
                            </span>
                        </td>

                        <!-- Pago y/o Dev. -->
                        <td class="px-3 py-4 whitespace-nowrap text-right w-28">
                            <div class="text-sm font-bold" :class="getPaymentAmountClass(item.payment_or_refund)">
                                ${{ formatPrice(item.payment_or_refund) }}
                            </div>
                        </td>

                        <!-- Nro. Documento -->
                        <td class="px-3 py-4 whitespace-nowrap w-32">
                            <div class="text-sm text-gray-900">
                                {{ item.document_number || 'N/A' }}
                            </div>
                        </td>

                        <!-- Tipo de Documento -->
                        <td class="px-3 py-4 whitespace-nowrap w-28">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                {{ item.document_type || 'N/A' }}
                            </span>
                        </td>

                        <!-- Forma Pago -->
                        <td class="px-3 py-4 whitespace-nowrap w-32">
                            <div class="text-sm text-gray-900">
                                {{ item.payment_form || 'N/A' }}
                            </div>
                        </td>

                        <!-- Fecha de Pago -->
                        <td class="px-3 py-4 whitespace-nowrap w-28">
                            <div class="text-sm text-gray-900">
                                {{ item.payment_date || 'N/A' }}
                            </div>
                        </td>

                        <!-- Contacto Pagador -->
                        <td class="px-3 py-4 whitespace-nowrap w-32">
                            <div class="text-sm text-gray-900">
                                {{ item.payer_contact || 'N/A' }}
                            </div>
                        </td>

                        <!-- Email Contacto Pagador -->
                        <td class="px-3 py-4 whitespace-nowrap w-40">
                            <div class="text-sm text-gray-900">
                                {{ item.payer_email || 'N/A' }}
                            </div>
                        </td>

                        <!-- Aporte o Beca -->
                        <td class="px-3 py-4 whitespace-nowrap text-right w-28">
                            <div class="text-sm font-bold text-blue-600">
                                ${{ formatPrice(item.scholarship_or_grant) }}
                            </div>
                        </td>

                        <!-- Liberado -->
                        <td class="px-3 py-4 whitespace-nowrap text-right w-28">
                            <div class="text-sm font-bold text-green-600">
                                ${{ formatPrice(item.liberated) }}
                            </div>
                        </td>

                        <!-- Precio -->
                        <td class="px-3 py-4 whitespace-nowrap text-right w-28">
                            <div class="text-sm font-bold text-gray-900">
                                ${{ formatPrice(item.price) }}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mensaje cuando no hay datos -->
        <div v-if="!items.data || items.data.length === 0" class="p-8 text-center">
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
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                    ></path>
                </svg>
                <p class="text-lg font-medium">No hay datos para mostrar</p>
                <p class="text-sm">
                    Intenta ajustar los filtros o verifica que existan participantes en el rango de fechas seleccionado
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    items: {
        type: Object,
        required: true,
    },
});

const formatPrice = (price) => {
    if (!price) return "0";
    const numericPrice = Math.round(Number(price) || 0);
    return numericPrice.toLocaleString("es-CL");
};

const formatRut = (rut) => {
    if (!rut) return "N/A";
    
    if (rut.includes('.')) {
        return rut;
    }
    
    let rutLimpio = rut.toString().replace(/\./g, "").replace(/-/g, "");
    
    if (rutLimpio.length >= 7 && rutLimpio.length <= 9 && /^\d{7,8}[\dK]$/.test(rutLimpio)) {
        let dv = rutLimpio.slice(-1);
        let numero = rutLimpio.slice(0, -1);
        let numeroFormateado = numero.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        return `${numeroFormateado}-${dv.toUpperCase()}`;
    } else {
        return rut;
    }
};

const getStatusClass = (status) => {
    const statusLower = status?.toLowerCase() || '';
    const classes = {
        'activo': 'bg-green-100 text-green-800',
        'inactivo': 'bg-red-100 text-red-800',
        'pending': 'bg-yellow-100 text-yellow-800',
        'completed': 'bg-blue-100 text-blue-800',
    };
    return classes[statusLower] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const statusLower = status?.toLowerCase() || '';
    const labels = {
        'activo': 'Activo',
        'inactivo': 'Inactivo',
        'pending': 'Pendiente',
        'completed': 'Completado',
    };
    return labels[statusLower] || status || 'N/A';
};

const getPaymentAmountClass = (amount) => {
    const numericAmount = Number(amount) || 0;
    if (numericAmount > 0) return 'text-green-600';
    if (numericAmount < 0) return 'text-red-600';
    return 'text-gray-600';
};

const getLiberatedClass = (liberated) => {
    const liberatedLower = liberated?.toLowerCase() || '';
    if (liberatedLower === 'sí' || liberatedLower === 'si') return 'bg-green-100 text-green-800';
    return 'bg-red-100 text-red-800';
};
</script>


