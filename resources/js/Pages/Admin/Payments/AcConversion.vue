<template>
    <AdminLayout>
        <div class="p-6 max-w-7xl mx-auto">

            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Conversión AC → Boleta</h1>
                <p class="text-gray-500 text-sm mt-1">
                    Pagos registrados como anticipo de cliente que ahora deben generar boleta BSale.
                </p>
            </div>

            <!-- Banner explicativo -->
            <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4 flex gap-3">
                <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm text-blue-800">
                    <p class="font-semibold mb-1">¿Por qué aparecen estos pagos?</p>
                    <p>Estos abonos fueron registrados como anticipos (AC) antes del año de ejecución del programa. En su momento BSale no generó boleta porque el programa aún no había salido. Ahora que el año de salida ya llegó, corresponde emitir la boleta oficial.</p>
                </div>
            </div>

            <!-- Sin pagos pendientes -->
            <div v-if="payments.length === 0" class="bg-white rounded-lg border border-gray-200 p-12 text-center">
                <svg class="w-12 h-12 text-green-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-gray-600 font-medium">No hay pagos pendientes de conversión.</p>
                <p class="text-gray-400 text-sm mt-1">Todos los anticipos han sido convertidos a boleta.</p>
            </div>

            <template v-else>
                <!-- Filtros -->
                <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6 flex flex-wrap gap-4 items-center">
                    <div class="relative">
                        <input
                            v-model="filterParticipant"
                            type="text"
                            placeholder="Buscar por RUT o Apellido"
                            class="h-[42px] w-[260px] bg-white rounded-[50px] border border-gray-200 px-4 py-2 text-sm outline-none pr-10"
                        />
                        <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.486-4.494M19 10.5a8.5 8.5 0 11-17 0 8.5 8.5 0 0117 0z"/>
                        </svg>
                    </div>
                    <div class="relative">
                        <input
                            v-model="filterProgram"
                            type="text"
                            placeholder="Código de Programa"
                            class="h-[42px] w-[200px] bg-white rounded-[50px] border border-gray-200 px-4 py-2 text-sm outline-none"
                        />
                    </div>
                    <button
                        v-if="filterParticipant || filterProgram"
                        @click="filterParticipant = ''; filterProgram = ''"
                        class="h-[42px] px-4 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-[50px] border border-gray-300 text-sm flex items-center gap-2 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Limpiar
                    </button>
                    <span v-if="filterParticipant || filterProgram" class="text-xs text-gray-500">
                        {{ filteredPayments.length }} resultado{{ filteredPayments.length !== 1 ? 's' : '' }}
                    </span>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <p class="text-xs text-gray-500 uppercase font-medium">Pagos pendientes</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ payments.length }}</p>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <p class="text-xs text-gray-500 uppercase font-medium">Seleccionados</p>
                        <p class="text-2xl font-bold text-orange-600 mt-1">{{ selectedIds.length }}</p>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <p class="text-xs text-gray-500 uppercase font-medium">Monto total pendiente</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">${{ formatPrice(total_amount) }}</p>
                    </div>
                </div>

                <!-- Resultados de conversión (post-ejecución) -->
                <div v-if="conversionResults.length > 0" class="mb-6 bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                        <h3 class="font-semibold text-gray-900 text-sm">Resultado de la conversión</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="result in conversionResults"
                            :key="result.payment_id"
                            class="px-4 py-3 flex items-center gap-3"
                        >
                            <svg v-if="result.success" class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <svg v-else class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-medium" :class="result.success ? 'text-green-800' : 'text-red-800'">
                                    Pago #{{ result.payment_id }} — {{ result.message }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-6">
                    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center gap-3">
                        <input
                            type="checkbox"
                            :checked="allSelected"
                            :indeterminate="someSelected && !allSelected"
                            @change="toggleAll"
                            class="h-4 w-4 text-orange-500 border-gray-300 rounded focus:ring-orange-500"
                        />
                        <span class="text-sm font-medium text-gray-700">Seleccionar todos ({{ filteredPayments.length }})</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 bg-gray-50">
                                    <th class="px-4 py-3 text-left w-10"></th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Participante</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Programa</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Monto</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Fecha pago</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Tipo actual</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="payment in filteredPayments"
                                    :key="payment.id"
                                    class="hover:bg-gray-50 transition-colors"
                                    :class="selectedIds.includes(payment.id) ? 'bg-orange-50' : ''"
                                >
                                    <td class="px-4 py-3">
                                        <input
                                            type="checkbox"
                                            :value="payment.id"
                                            v-model="selectedIds"
                                            class="h-4 w-4 text-orange-500 border-gray-300 rounded focus:ring-orange-500"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900">{{ payment.participant.full_name }}</p>
                                        <p class="text-gray-400 text-xs">{{ payment.participant.document_type }}: {{ payment.participant.document_number }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900">{{ payment.program.code }}</p>
                                        <p class="text-gray-500 text-xs">{{ payment.program.name }}</p>
                                        <p class="text-gray-400 text-xs">Salida: {{ payment.program.departure_year }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900">
                                        ${{ formatPrice(payment.amount) }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ formatDate(payment.transaction_date) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">AC</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Botón confirmar -->
                <div class="flex justify-end">
                    <button
                        @click="openConfirmModal"
                        :disabled="selectedIds.length === 0"
                        class="px-6 py-3 rounded-lg font-semibold text-sm transition-colors"
                        :class="selectedIds.length > 0
                            ? 'bg-orange-500 hover:bg-orange-600 text-white'
                            : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                    >
                        Generar boletas para {{ selectedIds.length }} pago{{ selectedIds.length !== 1 ? 's' : '' }} seleccionado{{ selectedIds.length !== 1 ? 's' : '' }}
                    </button>
                </div>
            </template>

            <!-- Modal de confirmación -->
            <div v-if="showConfirmModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-lg max-w-lg w-full shadow-xl">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-bold text-gray-900">Confirmar generación de boletas</h3>
                        <p class="text-sm text-gray-500 mt-1">Esta acción generará boletas BSale para los siguientes pagos:</p>
                    </div>
                    <div class="p-6 max-h-64 overflow-y-auto">
                        <div
                            v-for="payment in selectedPayments"
                            :key="payment.id"
                            class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0"
                        >
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ payment.participant.full_name }}</p>
                                <p class="text-xs text-gray-500">{{ payment.program.code }} · Pago #{{ payment.id }}</p>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">${{ formatPrice(payment.amount) }}</span>
                        </div>
                    </div>
                    <div class="p-6 border-t border-gray-200 bg-gray-50 rounded-b-lg flex justify-between items-center">
                        <div>
                            <p class="text-xs text-gray-500">Total seleccionado</p>
                            <p class="font-bold text-gray-900">${{ formatPrice(selectedTotal) }}</p>
                        </div>
                        <div class="flex gap-3">
                            <button
                                @click="showConfirmModal = false"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                            >
                                Cancelar
                            </button>
                            <button
                                @click="executeConversion"
                                :disabled="processing"
                                class="px-4 py-2 bg-orange-500 hover:bg-orange-600 disabled:bg-orange-300 text-white rounded-lg text-sm font-semibold transition-colors"
                            >
                                {{ processing ? 'Procesando...' : 'Confirmar y generar' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    payments: Array,
    total_amount: Number,
});

const page = usePage();
const selectedIds = ref([]);
const showConfirmModal = ref(false);
const processing = ref(false);
const currentYear = new Date().getFullYear();
const filterParticipant = ref('');
const filterProgram = ref('');

const conversionResults = computed(() => {
    return page.props.flash?.conversion_results ?? [];
});

const filteredPayments = computed(() => {
    let list = props.payments;
    if (filterParticipant.value) {
        const q = filterParticipant.value.toLowerCase();
        list = list.filter(p =>
            p.participant.full_name.toLowerCase().includes(q) ||
            p.participant.document_number?.toLowerCase().includes(q)
        );
    }
    if (filterProgram.value) {
        const q = filterProgram.value.toLowerCase();
        list = list.filter(p =>
            p.program.code.toLowerCase().includes(q) ||
            p.program.name.toLowerCase().includes(q)
        );
    }
    return list;
});

const allSelected = computed(() => selectedIds.value.length === filteredPayments.value.length && filteredPayments.value.length > 0);
const someSelected = computed(() => selectedIds.value.length > 0);

const selectedPayments = computed(() => props.payments.filter(p => selectedIds.value.includes(p.id)));
const selectedTotal = computed(() => selectedPayments.value.reduce((sum, p) => sum + p.amount, 0));

const toggleAll = () => {
    if (allSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = filteredPayments.value.map(p => p.id);
    }
};

const openConfirmModal = () => {
    if (selectedIds.value.length > 0) showConfirmModal.value = true;
};

const executeConversion = () => {
    processing.value = true;
    router.post(route('admin.payments.ac-conversion.execute'), {
        payment_ids: selectedIds.value,
    }, {
        onSuccess: () => {
            processing.value = false;
            showConfirmModal.value = false;
            selectedIds.value = [];
        },
        onError: () => {
            processing.value = false;
        },
    });
};

const formatPrice = (amount) => {
    if (!amount) return '0';
    return new Intl.NumberFormat('es-CL').format(Math.abs(amount));
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('es-CL', { day: '2-digit', month: '2-digit', year: 'numeric' });
};
</script>
