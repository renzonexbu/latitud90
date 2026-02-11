<template>
    <AdminLayout>
        <Head title="Procesar Reembolso" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold text-gray-900">Procesar Reembolso</h2>
                            <Link
                                :href="route('admin.payments.index')"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200"
                            >
                                Volver
                            </Link>
                        </div>

                        <!-- Alerta de error general -->
                        <div
                            v-if="errors.error"
                            ref="errorAlert"
                            class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg"
                        >
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg
                                        class="h-5 w-5 text-red-500"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">
                                        Error al procesar el reembolso
                                    </h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        {{ errors.error }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form @submit.prevent="submit" class="space-y-8">
                            <!-- SECCIÓN 1: DATOS DEL CLIENTE -->
                            <div class="border-b border-gray-200 pb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-6">Datos del Cliente</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- RUT del Cliente -->
                                    <div class="flex flex-col gap-[12px]">
                                        <label class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal">
                                            RUT del Cliente *
                                        </label>
                                        <input
                                            type="text"
                                            placeholder="Ej: 12.345.678-9"
                                            :class="[
                                                'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                rutValidation.isValid === false ? 'border-red-500' : '',
                                                rutValidation.isValid === true ? 'border-green-500' : 'border-[#5B5B5B]',
                                            ]"
                                            v-model="clientForm.rut"
                                            @input="handleRutInput"
                                            @blur="handleRutBlur"
                                        />
                                        <div
                                            v-if="rutValidation.message"
                                            class="text-xs mt-1 validation-message"
                                            :class="[
                                                rutValidation.isValid === true ? 'text-green-500' : 'text-red-500',
                                            ]"
                                        >
                                            {{ rutValidation.message }}
                                        </div>
                                    </div>

                                    <!-- Nombre del Cliente -->
                                    <div class="flex flex-col gap-[12px]">
                                        <label class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal">
                                            Nombre del Cliente *
                                        </label>
                                        <input
                                            type="text"
                                            placeholder="Nombre completo del cliente"
                                            class="w-full h-[46px] bg-white rounded-lg border border-[#5B5B5B] px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                            v-model="clientForm.name"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 2: NEGOCIO AFILIADO -->
                            <div class="border-b border-gray-200 pb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-6">Negocio Afiliado</h3>

                                <!-- Buscador de Participante Inscrito -->
                                <div class="mb-6" data-search-container>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Buscar Participante Inscrito *
                                    </label>
                                    <p class="text-sm text-gray-500 mb-3">
                                        Busque por RUT, nombre, apellido o código de programa
                                    </p>
                                    <div class="relative">
                                        <input
                                            type="text"
                                            v-model="searchQuery"
                                            @input="debounceSearch"
                                            @focus="showDropdown = true"
                                            @keydown.escape="showDropdown = false"
                                            @keydown.down.prevent="navigateDropdown(1)"
                                            @keydown.up.prevent="navigateDropdown(-1)"
                                            @keydown.enter.prevent="selectHighlighted"
                                            placeholder="Ej: 12.345.678-9, Juan Pérez, V0008..."
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e74c3c] focus:border-transparent"
                                            :class="{
                                                'border-red-500': errors.program_id || errors.participant_id,
                                                'border-green-500': selectedEnrollment
                                            }"
                                        />
                                        <div v-if="isSearching" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                            <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-[#e74c3c]"></div>
                                        </div>
                                        <div v-else-if="selectedEnrollment" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                            <button
                                                type="button"
                                                @click="clearSelection"
                                                class="text-gray-400 hover:text-gray-600"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Dropdown de resultados -->
                                        <div
                                            v-if="showDropdown && searchResults.length > 0"
                                            class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-80 overflow-y-auto"
                                        >
                                            <div
                                                v-for="(result, index) in searchResults"
                                                :key="`${result.participant_id}-${result.program_course_id}`"
                                                @click="selectEnrollment(result)"
                                                @mouseenter="highlightedIndex = index"
                                                class="px-4 py-3 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors"
                                                :class="{
                                                    'bg-[#e74c3c] text-white': highlightedIndex === index,
                                                    'hover:bg-gray-50': highlightedIndex !== index
                                                }"
                                            >
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <div class="font-semibold" :class="{ 'text-white': highlightedIndex === index, 'text-gray-900': highlightedIndex !== index }">
                                                            {{ result.full_name }}
                                                        </div>
                                                        <div class="text-sm" :class="{ 'text-gray-200': highlightedIndex === index, 'text-gray-600': highlightedIndex !== index }">
                                                            {{ result.document_number }}
                                                        </div>
                                                    </div>
                                                    <div class="text-right">
                                                        <div class="flex items-center justify-end gap-2">
                                                            <div class="text-sm font-medium" :class="{ 'text-gray-200': highlightedIndex === index, 'text-[#e74c3c]': highlightedIndex !== index }">
                                                                {{ result.program_code }}
                                                            </div>
                                                            <span
                                                                :class="[
                                                                    'inline-flex px-1.5 py-0.5 text-[9px] font-semibold rounded-full',
                                                                    result.program_active
                                                                        ? 'bg-green-100 text-green-800'
                                                                        : 'bg-red-100 text-red-800'
                                                                ]"
                                                            >
                                                                {{ result.program_active ? 'Activo' : 'Inactivo' }}
                                                            </span>
                                                        </div>
                                                        <div class="text-xs" :class="{ 'text-gray-300': highlightedIndex === index, 'text-gray-500': highlightedIndex !== index }">
                                                            {{ result.program_name }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Mensaje de no resultados -->
                                        <div
                                            v-if="showDropdown && searchQuery.length >= 2 && searchResults.length === 0 && !isSearching"
                                            class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg p-4 text-center text-gray-500"
                                        >
                                            No se encontraron participantes inscritos
                                        </div>
                                    </div>
                                    <span v-if="errors.program_id" class="text-red-500 text-sm mt-1 block">{{ errors.program_id }}</span>
                                    <span v-if="errors.participant_id" class="text-red-500 text-sm mt-1 block">{{ errors.participant_id }}</span>
                                </div>

                                <!-- Participante Seleccionado -->
                                <div
                                    v-if="selectedEnrollment"
                                    class="mb-6 p-4 bg-green-50 rounded-lg border border-green-200"
                                >
                                    <h4 class="text-md font-semibold text-green-800 mb-3 flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Participante Seleccionado
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <span class="text-sm text-gray-600">Nombre:</span>
                                            <p class="font-semibold text-gray-800">{{ selectedEnrollment.full_name }}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">RUT/Documento:</span>
                                            <p class="font-semibold text-gray-800">{{ selectedEnrollment.document_number }}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Programa:</span>
                                            <p class="font-semibold text-[#e74c3c] flex items-center gap-2">
                                                {{ selectedEnrollment.program_code }} - {{ selectedEnrollment.program_name }}
                                                <span
                                                    :class="[
                                                        'inline-flex px-2 py-0.5 text-[10px] font-semibold rounded-full',
                                                        selectedEnrollment.program_active
                                                            ? 'bg-green-100 text-green-800'
                                                            : 'bg-red-100 text-red-800'
                                                    ]"
                                                >
                                                    {{ selectedEnrollment.program_active ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información del Estado de Pagos del Participante -->
                                <div
                                    v-if="isLoadingParticipantStatus"
                                    class="p-4 bg-blue-50 rounded-lg border border-blue-200"
                                >
                                    <div class="flex items-center justify-center">
                                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                                        <span class="ml-2 text-blue-600">Cargando estado de pagos...</span>
                                    </div>
                                </div>

                                <div v-else-if="participantPaymentStatus" class="p-4 bg-gray-50 rounded-lg">
                                    <h4 class="text-md font-semibold text-gray-800 mb-3">Estado de Pagos del Participante</h4>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div>
                                            <span class="text-sm text-gray-600">Precio Programa:</span>
                                            <p class="font-semibold text-gray-800">${{ formatPrice(participantPaymentStatus.payment_info.price) }}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Abono:</span>
                                            <p class="font-semibold text-blue-600">${{ formatPrice(participantPaymentStatus.payment_info.abono) }}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Aportes:</span>
                                            <p class="font-semibold text-blue-600">${{ formatPrice(participantPaymentStatus.payment_info.aporte) }}</p>
                                        </div>
                                        <div v-if="participantPaymentStatus.payment_info.scholarship > 0">
                                            <span class="text-sm text-gray-600">Beca:</span>
                                            <p class="font-semibold text-purple-600">${{ formatPrice(participantPaymentStatus.payment_info.scholarship) }}</p>
                                        </div>
                                        <div v-if="participantPaymentStatus.payment_info.released > 0">
                                            <span class="text-sm text-gray-600">Liberado:</span>
                                            <p class="font-semibold text-green-600">${{ formatPrice(participantPaymentStatus.payment_info.released) }}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Saldo:</span>
                                            <p class="font-semibold" :class="participantPaymentStatus.payment_info.saldo >= 0 ? 'text-green-600' : 'text-red-600'">
                                                {{ formatBalance(participantPaymentStatus.payment_info.saldo) }}
                                            </p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Cuotas Pagadas:</span>
                                            <p class="font-semibold text-blue-600">{{ participantPaymentStatus.payment_info.installments_summary }} cuotas</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 3: TIPO DE REEMBOLSO -->
                            <div class="border-b border-gray-200 pb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-6">Tipo de Reembolso</h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div
                                        v-for="option in mainRefundOptions"
                                        :key="option.code"
                                        @click="selectRefundType(option.code)"
                                        class="relative flex items-start p-4 border rounded-lg cursor-pointer transition-all duration-200"
                                        :class="{
                                            'border-[#e74c3c] bg-red-50': selectedBaseRefundType === option.code,
                                            'border-gray-200 hover:border-gray-300 hover:bg-gray-50': selectedBaseRefundType !== option.code
                                        }"
                                    >
                                        <div class="flex items-center h-5">
                                            <input
                                                :id="option.code"
                                                type="radio"
                                                :value="option.code"
                                                v-model="selectedBaseRefundType"
                                                @change="selectRefundType(option.code)"
                                                class="h-4 w-4 text-[#e74c3c] border-gray-300 focus:ring-[#e74c3c]"
                                            />
                                        </div>
                                        <div class="ml-3">
                                            <label :for="option.code" class="font-medium text-gray-900 cursor-pointer">
                                                {{ option.label }}
                                            </label>
                                            <p class="text-sm text-gray-500">
                                                <span v-if="option.code === 'refund_credit_note'">
                                                    Devolución oficial con nota de crédito. Reduce el monto pagado del participante.
                                                </span>
                                                <span v-else-if="option.code === 'refund_admin_reversal'">
                                                    Corrección o ajuste administrativo. Aumenta la deuda pendiente del participante.
                                                </span>
                                            </p>
                                            <span class="inline-flex mt-2 px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                Código: {{ option.report_code }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sub-selector: Aplicar NC a Abonos o Aportes -->
                                <div v-if="selectedBaseRefundType === 'refund_credit_note'" class="mt-4 p-4 bg-orange-50 rounded-lg border border-orange-200">
                                    <h4 class="text-sm font-semibold text-gray-800 mb-3">Aplicar nota de crédito a:</h4>
                                    <div class="flex gap-4">
                                        <label
                                            class="flex items-center gap-2 px-4 py-2 border rounded-lg cursor-pointer transition-all duration-200"
                                            :class="{
                                                'border-[#e74c3c] bg-red-50 font-semibold': refundAppliesTo === 'abono',
                                                'border-gray-300 hover:border-gray-400': refundAppliesTo !== 'abono'
                                            }"
                                        >
                                            <input
                                                type="radio"
                                                value="abono"
                                                v-model="refundAppliesTo"
                                                @change="updateRefundType"
                                                class="h-4 w-4 text-[#e74c3c] border-gray-300 focus:ring-[#e74c3c]"
                                            />
                                            <span class="text-sm">Abonos</span>
                                        </label>
                                        <label
                                            class="flex items-center gap-2 px-4 py-2 border rounded-lg cursor-pointer transition-all duration-200"
                                            :class="{
                                                'border-[#e74c3c] bg-red-50 font-semibold': refundAppliesTo === 'aporte',
                                                'border-gray-300 hover:border-gray-400': refundAppliesTo !== 'aporte'
                                            }"
                                        >
                                            <input
                                                type="radio"
                                                value="aporte"
                                                v-model="refundAppliesTo"
                                                @change="updateRefundType"
                                                class="h-4 w-4 text-[#e74c3c] border-gray-300 focus:ring-[#e74c3c]"
                                            />
                                            <span class="text-sm">Aportes</span>
                                        </label>
                                    </div>
                                </div>

                                <span v-if="errors.refund_type" class="text-red-500 text-sm mt-2 block">{{ errors.refund_type }}</span>
                            </div>

                            <!-- SECCIÓN 4: DATOS FISCALES -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-6">
                                    {{ form.refund_type === 'refund_admin_reversal' ? 'Datos del Reverso Administrativo' : (form.refund_type === 'refund_aporte_credit_note' ? 'Datos Fiscales de la Nota de Crédito (Aporte)' : 'Datos Fiscales de la Nota de Crédito') }}
                                </h3>

                                <!-- Campos fiscales -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Código SII -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Código SII *
                                        </label>
                                        <input
                                            v-model="form.sii_code"
                                            type="text"
                                            placeholder="Ej: NC001-2024"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e74c3c] focus:border-transparent"
                                            :class="{ 'border-red-500': errors.sii_code }"
                                        />
                                        <span v-if="errors.sii_code" class="text-red-500 text-sm mt-1">{{ errors.sii_code }}</span>
                                    </div>

                                    <!-- N. Documento -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            N. Documento *
                                        </label>
                                        <input
                                            v-model="form.document_number"
                                            type="text"
                                            placeholder="Número de documento"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e74c3c] focus:border-transparent"
                                            :class="{ 'border-red-500': errors.document_number }"
                                        />
                                        <span v-if="errors.document_number" class="text-red-500 text-sm mt-1">{{ errors.document_number }}</span>
                                    </div>

                                    <!-- Fecha -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Fecha *
                                        </label>
                                        <input
                                            v-model="form.transaction_date"
                                            type="date"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e74c3c] focus:border-transparent"
                                            :class="{ 'border-red-500': errors.transaction_date }"
                                        />
                                        <span v-if="errors.transaction_date" class="text-red-500 text-sm mt-1">{{ errors.transaction_date }}</span>
                                    </div>

                                    <!-- Total -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Total (CLP) *
                                        </label>
                                        <input
                                            v-model="form.total_amount"
                                            type="number"
                                            min="0"
                                            step="1"
                                            placeholder="0"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e74c3c] focus:border-transparent"
                                            :class="{ 'border-red-500': errors.total_amount }"
                                        />
                                        <span v-if="errors.total_amount" class="text-red-500 text-sm mt-1">{{ errors.total_amount }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                                <Link
                                    :href="route('admin.payments.index')"
                                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200"
                                >
                                    Cancelar
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing || !isFormValid"
                                    class="bg-[#e74c3c] hover:bg-[#c0392b] disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200"
                                >
                                    {{ form.processing ? 'Procesando...' : 'Procesar Reembolso' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    programs: {
        type: Array,
        default: () => []
    },
    countries: {
        type: Array,
        default: () => []
    },
    regions: {
        type: Array,
        default: () => []
    },
    documentTypes: {
        type: Array,
        default: () => []
    },
    refundOptions: {
        type: Array,
        default: () => []
    },
    errors: {
        type: Object,
        default: () => ({})
    }
});

// Formulario de datos del cliente
const clientForm = reactive({
    rut: "",
    name: "",
});

// Formulario de información del reembolso
const form = useForm({
    program_id: '',
    participant_id: '',
    refund_type: 'refund_credit_note', // Default: Nota de Crédito (NC)
    sii_code: '',
    document_number: '',
    transaction_date: new Date().toLocaleString('sv-SE', { timeZone: 'America/Santiago' }).slice(0, 10),
    total_amount: ''
});

const availableParticipants = ref([]);
const participantPaymentStatus = ref(null);
const isLoadingParticipantStatus = ref(false);
const errorAlert = ref(null);

// Variables para el buscador de participantes inscritos
const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);
const showDropdown = ref(false);
const highlightedIndex = ref(0);
const selectedEnrollment = ref(null);
let searchTimeout = null;

// Variables para tipo de reembolso y sub-selector
const selectedBaseRefundType = ref('refund_credit_note');
const refundAppliesTo = ref('abono');

const rutValidation = reactive({
    isValid: null,
    message: "",
});

// Computed properties
const availableRefundOptions = computed(() => {
    // Si hay opciones desde el backend, usarlas; sino, usar valores por defecto
    if (props.refundOptions && props.refundOptions.length > 0) {
        return props.refundOptions;
    }
    // Fallback con opciones por defecto
    return [
        { code: 'refund_credit_note', label: 'Notas de crédito (devoluciones)', report_code: 'NC' },
        { code: 'refund_aporte_credit_note', label: 'Nota de crédito a Aporte', report_code: 'AP' },
        { code: 'refund_admin_reversal', label: 'Reverso Administrativo (RA)', report_code: 'RA' }
    ];
});

// Opciones principales visibles (sin la variante de aporte, que se maneja con sub-selector)
const mainRefundOptions = computed(() => {
    return availableRefundOptions.value.filter(opt => opt.code !== 'refund_aporte_credit_note');
});

const selectRefundType = (code) => {
    selectedBaseRefundType.value = code;
    if (code === 'refund_credit_note') {
        // Para NC, usar el sub-selector para determinar el tipo real
        updateRefundType();
    } else {
        // Para RA u otros, usar directamente
        form.refund_type = code;
        refundAppliesTo.value = 'abono'; // reset
    }
};

const updateRefundType = () => {
    if (refundAppliesTo.value === 'aporte') {
        form.refund_type = 'refund_aporte_credit_note';
    } else {
        form.refund_type = 'refund_credit_note';
    }
};

const filteredComunes = computed(() => {
    if (!buyerForm.region) {
        return [];
    }

    const selectedRegion = props.regions.find(
        (r) => r.id == buyerForm.region
    );

    if (!selectedRegion || !selectedRegion.comunes) {
        return [];
    }

    return selectedRegion.comunes;
});

const isRutDocument = computed(() => {
    if (!buyerForm.documentType) return false;
    const selectedDocType = props.documentTypes.find(
        (doc) => doc.id == buyerForm.documentType
    );
    return (
        selectedDocType && selectedDocType.name.toLowerCase() === "rut"
    );
});

const isFormValid = computed(() => {
    const clientValidations = {
        rut: clientForm.rut.trim() !== "",
        name: clientForm.name.trim() !== "",
    };

    const clientValidation = Object.values(clientValidations).every(
        (v) => v === true
    );
    const rutOk = rutValidation.isValid === true;
    
    // Validar también los campos del formulario de reembolso
    const refundValidations = {
        program_id: form.program_id !== "",
        participant_id: form.participant_id !== "",
        refund_type: form.refund_type !== "",
        sii_code: form.sii_code.trim() !== "",
        document_number: form.document_number.trim() !== "",
        transaction_date: form.transaction_date !== "",
        total_amount: form.total_amount !== "" && parseFloat(form.total_amount) > 0,
    };

    const refundValidation = Object.values(refundValidations).every(
        (v) => v === true
    );

    return clientValidation && rutOk && refundValidation;
});

// Methods
const getDocumentLabel = () => {
    if (!buyerForm.documentType) return "Número de documento";

    const selectedDocType = props.documentTypes.find(
        (doc) => doc.id == buyerForm.documentType
    );

    return selectedDocType
        ? selectedDocType.name
        : "Número de documento";
};

const getDocumentPlaceholder = () => {
    if (!buyerForm.documentType)
        return "Ingresa tu número de documento";

    const selectedDocType = props.documentTypes.find(
        (doc) => doc.id == buyerForm.documentType
    );

    if (!selectedDocType) return "Ingresa tu número de documento";

    switch (selectedDocType.name.toLowerCase()) {
        case "rut":
            return "Ej: 12.345.678-9";
        case "pasaporte":
            return "Ej: A12345678";
        default:
            return "Ingresa tu número de documento";
    }
};

const getDocumentTypeId = (name) => {
    const docType = props.documentTypes.find(
        (doc) => doc.name.toLowerCase() === name.toLowerCase()
    );
    return docType ? docType.id : "";
};

const handleRutInput = () => {
    formatRut();
};

const handleRutBlur = () => {
    validateRut();
};

const searchFrequentClient = async () => {
    try {
        const response = await fetch('/frequent-clients/find-by-document', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                document_id: buyerForm.documentType,
                document: buyerForm.documentNumber.trim()
            })
        });

        const result = await response.json();

        if (result.success && result.data) {
            // Autocompletar el formulario con los datos del cliente frecuente
            autocompleteForm(result.data);
        }
    } catch (error) {
        console.error('Error buscando cliente frecuente:', error);
    }
};

const autocompleteForm = (clientData) => {
    // Autocompletar todos los campos del formulario
    buyerForm.fullName = clientData.full_name;
    buyerForm.email = clientData.email;
    buyerForm.phone = clientData.phone;
    buyerForm.code_phone = clientData.phone_code;
    buyerForm.country = clientData.country_id;
    buyerForm.region = clientData.region_id;

    // Para la comuna, esperar a que se carguen las comunas después de establecer la región
    nextTick(() => {
        // Esperar un poco más para que las comunas se carguen completamente
        setTimeout(() => {
            if (filteredComunes.value.length > 0) {
                buyerForm.city = clientData.comune_id;
            } else {
                // Reintentar si las comunas no están disponibles
                setTimeout(() => {
                    if (filteredComunes.value.length > 0) {
                        buyerForm.city = clientData.comune_id;
                    }
                }, 200);
            }
        }, 300);
    });
};

const validateDocument = () => {
    if (isRutDocument.value) {
        validateRut();
    }
};

const formatRut = () => {
    // Remover todos los caracteres no numéricos excepto K
    let rut = clientForm.rut.replace(/[^0-9kK]/g, "");

    if (rut.length > 0) {
        rut = rut.toUpperCase();

        // Si tiene más de 1 carácter, separar cuerpo y dígito verificador
        if (rut.length > 1) {
            const body = rut.slice(0, -1);
            const dv = rut.slice(-1);

            // Formatear el cuerpo con puntos
            let formattedBody = "";
            for (let i = body.length - 1, j = 0; i >= 0; i--, j++) {
                if (j > 0 && j % 3 === 0) {
                    formattedBody = "." + formattedBody;
                }
                formattedBody = body[i] + formattedBody;
            }

            // Combinar cuerpo formateado con dígito verificador
            clientForm.rut = `${formattedBody}-${dv}`;
        } else {
            clientForm.rut = rut;
        }
    }

    // Validar el RUT después de formatearlo
    validateRut();
};

const validateRut = () => {
    const rut = clientForm.rut
        .replace(/\./g, "")
        .replace(/-/g, "");

    if (rut.length === 0) {
        rutValidation.isValid = null;
        rutValidation.message = "";
        return;
    }

    // Validar formato básico
    if (!/^[0-9]+[0-9kK]$/.test(rut)) {
        rutValidation.isValid = false;
        rutValidation.message = "Formato de RUT inválido";
        return;
    }

    // Separar cuerpo y dígito verificador
    const body = rut.slice(0, -1);
    const dv = rut.slice(-1).toUpperCase();

    // Validar que el cuerpo tenga al menos 7 dígitos
    if (body.length < 7) {
        rutValidation.isValid = false;
        rutValidation.message =
            "RUT debe tener al menos 7 dígitos";
        return;
    }

    // Calcular dígito verificador
    const dvCalculado = calculateDv(body);

    // Comparar dígitos verificadores
    rutValidation.isValid = dv === dvCalculado;
    rutValidation.message = rutValidation.isValid
        ? "RUT válido"
        : "RUT inválido";
};

const calculateDv = (body) => {
    let sum = 0;
    let factor = 2;
    for (let i = body.length - 1; i >= 0; i--) {
        sum += body[i] * factor;
        factor = factor === 7 ? 2 : factor + 1;
    }
    const dv = 11 - (sum % 11);
    return dv === 10 ? "K" : dv === 11 ? "0" : dv.toString();
};

const handleCountryChange = (countryId) => {
    buyerForm.country = countryId;
};

const handleRegionChange = (regionId) => {
    buyerForm.region = regionId;
    buyerForm.city = ""; // Limpiar comuna

    // Verificar las comunas disponibles
    if (regionId) {
        const selectedRegion = props.regions.find(
            (r) => r.id == regionId
        );
    }
};

const handleCityChange = (cityId) => {
    buyerForm.city = cityId;
};

// Métodos del formulario de reembolso
const loadParticipants = () => {
    if (!form.program_id) {
        availableParticipants.value = [];
        form.participant_id = '';
        return;
    }

    const program = props.programs.find(p => p.id == form.program_id);
    if (program && program.course && program.course.participants) {
        availableParticipants.value = program.course.participants;
    } else {
        availableParticipants.value = [];
    }
    form.participant_id = '';
};

// Helper function para obtener el token CSRF
const getCsrfToken = () => {
    const token = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    if (!token) {
        console.error("Token CSRF no encontrado en el documento");
    }

    return token;
};

const loadParticipantPaymentStatus = async () => {
    if (!form.program_id || !form.participant_id) {
        participantPaymentStatus.value = null;
        return;
    }

    if (isLoadingParticipantStatus.value) return;

    try {
        isLoadingParticipantStatus.value = true;
        const csrfToken = getCsrfToken();

        if (!csrfToken) {
            participantPaymentStatus.value = null;
            return;
        }

        const response = await fetch('/admin/payments/participant-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                program_id: form.program_id,
                participant_id: form.participant_id
            })
        });

        if (response.ok) {
            const result = await response.json();
            if (result.success) {
                participantPaymentStatus.value = result.data;
            } else {
                participantPaymentStatus.value = null;
            }
        } else {
            participantPaymentStatus.value = null;
        }
    } catch (error) {
        console.error('Error en la solicitud de estado de pago del participante:', error);
        participantPaymentStatus.value = null;
    } finally {
        isLoadingParticipantStatus.value = false;
    }
};

const formatPrice = (amount) => {
    const n = Math.round(Number(amount) || 0);
    return n.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
};

const formatBalance = (balance) => {
    const n = Math.round(Number(balance) || 0);
    if (n < 0) return `$(${Math.abs(n).toLocaleString('es-CL')})`;
    return `$${n.toLocaleString('es-CL')}`;
};

const getPaymentStatusClass = (status) => {
    switch (status) {
        case 'no_enrolled':
            return 'bg-gray-100 text-gray-800';
        case 'no_payments':
            return 'bg-red-100 text-red-800';
        case 'partial_payments':
            return 'bg-yellow-100 text-yellow-800';
        case 'fully_paid':
            return 'bg-green-100 text-green-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getPaymentStatusLabel = (status) => {
    switch (status) {
        case 'no_enrolled':
            return 'No inscrito';
        case 'no_payments':
            return 'Sin pagos ejecutados';
        case 'partial_payments':
            return 'Pagos parciales';
        case 'fully_paid':
            return 'Completamente pagado';
        default:
            return status;
    }
};

const getDetailedPaymentStatus = (paymentInfo) => {
    if (!paymentInfo) return '';
    
    switch (paymentInfo.payment_status) {
        case 'no_enrolled':
            return 'No inscrito en el programa';
        case 'no_payments':
            return 'Inscrito pero sin pagos ejecutados';
        case 'partial_payments':
            return `Pagos parciales (${paymentInfo.installments_summary} cuotas pagadas)`;
        case 'fully_paid':
            return `Completamente pagado (${paymentInfo.installments_summary} cuotas pagadas)`;
        default:
            return paymentInfo.payment_status;
    }
};

// Formatear nombre del participante (Primer Nombre Primer Apellido en Title Case)
const formatParticipantName = (participant) => {
    const firstName = participant.first_name ? participant.first_name.charAt(0).toUpperCase() + participant.first_name.slice(1).toLowerCase() : '';
    const lastName = participant.first_last_name ? participant.first_last_name.charAt(0).toUpperCase() + participant.first_last_name.slice(1).toLowerCase() : '';
    
    // Si no hay apellido, solo mostrar nombre
    if (!lastName) {
        return firstName;
    }
    
    return `${firstName} ${lastName}`.trim();
};

// Formatear documento del participante (RUT formateado si es RUT)
const formatParticipantDocument = (participant) => {
    if (!participant.document_number) return '';

    // Verificar si es RUT (formato chileno)
    if (/^[0-9]{7,8}[0-9kK]$/.test(participant.document_number.replace(/[.-]/g, ''))) {
        // Formatear RUT con puntos y guión
        const rut = participant.document_number.replace(/[.-]/g, '');
        const body = rut.slice(0, -1);
        const dv = rut.slice(-1).toUpperCase();

        let formattedBody = '';
        for (let i = body.length - 1, j = 0; i >= 0; i--, j++) {
            if (j > 0 && j % 3 === 0) {
                formattedBody = '.' + formattedBody;
            }
            formattedBody = body[i] + formattedBody;
        }

        return `${formattedBody}-${dv}`;
    }

    // Si no es RUT, devolver tal como está
    return participant.document_number;
};

// ========================================
// Funciones del buscador de participantes
// ========================================

const debounceSearch = () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    // Si ya hay una selección y el usuario está editando, limpiarla
    if (selectedEnrollment.value) {
        clearSelection();
    }

    searchTimeout = setTimeout(() => {
        performSearch();
    }, 300);
};

const performSearch = async () => {
    const query = searchQuery.value.trim();

    if (query.length < 2) {
        searchResults.value = [];
        showDropdown.value = false;
        return;
    }

    try {
        isSearching.value = true;
        showDropdown.value = true;
        highlightedIndex.value = 0;

        const response = await fetch(`/admin/payments/presential/search-participants?search=${encodeURIComponent(query)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            console.error('Error en la búsqueda:', response.status);
            searchResults.value = [];
            return;
        }

        const result = await response.json();

        if (result.success) {
            searchResults.value = result.data;
        } else {
            searchResults.value = [];
        }
    } catch (error) {
        console.error('Error buscando participantes:', error);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
};

const navigateDropdown = (direction) => {
    if (searchResults.value.length === 0) return;

    highlightedIndex.value += direction;

    if (highlightedIndex.value < 0) {
        highlightedIndex.value = searchResults.value.length - 1;
    } else if (highlightedIndex.value >= searchResults.value.length) {
        highlightedIndex.value = 0;
    }
};

const selectHighlighted = () => {
    if (searchResults.value.length > 0 && highlightedIndex.value >= 0) {
        selectEnrollment(searchResults.value[highlightedIndex.value]);
    }
};

const selectEnrollment = (enrollment) => {
    selectedEnrollment.value = enrollment;
    form.program_id = enrollment.program_course_id;
    form.participant_id = enrollment.participant_id;
    searchQuery.value = enrollment.label;
    showDropdown.value = false;
    searchResults.value = [];

    // Cargar estado de pagos del participante
    loadParticipantPaymentStatus();
};

const clearSelection = () => {
    selectedEnrollment.value = null;
    form.program_id = '';
    form.participant_id = '';
    searchQuery.value = '';
    searchResults.value = [];
    participantPaymentStatus.value = null;
};

// Cerrar dropdown al hacer clic fuera
const handleClickOutside = (event) => {
    const searchContainer = event.target.closest('[data-search-container]');
    if (!searchContainer) {
        showDropdown.value = false;
    }
};

const submit = () => {
    // Combinar los datos del cliente con los datos del reembolso
    const combinedData = {
        ...form.data(),
        client_rut: clientForm.rut,
        client_name: clientForm.name,
        refund_type: form.refund_type,
    };

    // Crear un nuevo formulario con los datos combinados
    const submitForm = useForm(combinedData);
    submitForm.post(route('admin.payments.refunds.store'), {
        preserveScroll: false,
        onFinish: () => {
            // Si hay errores, hacer scroll hacia el componente de error
            if (Object.keys(submitForm.errors).length > 0) {
                nextTick(() => {
                    setTimeout(() => {
                        // Hacer scroll al elemento de error si existe
                        if (errorAlert.value) {
                            errorAlert.value.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        } else {
                            // Fallback: scroll al top de la página
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        }
                    }, 150);
                });
            }
        }
    });
};

// Lifecycle
onMounted(() => {
    // Agregar listener para cerrar dropdown al hacer clic fuera
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    // Limpiar listener
    document.removeEventListener('click', handleClickOutside);

    // Limpiar timeout de búsqueda
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
});
</script>

<style scoped>
/* Estilos para los radio buttons personalizados con colores de reembolso */
input[type="radio"]:checked + div {
    border-color: #e74c3c;
    background-color: #e74c3c;
}

/* Estilos para el checkbox personalizado */
.custom-checkbox {
    accent-color: #e74c3c;
}

.custom-checkbox:checked {
    background-color: #e74c3c;
    border-color: #e74c3c;
}

/* Estilos adicionales para mayor compatibilidad */
.custom-checkbox:checked::before {
    background-color: #e74c3c;
}

/* Para navegadores que no soportan accent-color */
.custom-checkbox:checked {
    background-color: #e74c3c !important;
    border-color: #e74c3c !important;
}
</style>
