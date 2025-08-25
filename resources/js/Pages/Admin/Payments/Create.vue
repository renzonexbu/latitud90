<template>
    <AdminLayout>
        <Head title="Registrar Pago Presencial" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold text-gray-900">Registrar Pago Presencial</h2>
                            <Link
                                :href="route('admin.payments.index')"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200"
                            >
                                Volver
                            </Link>
                        </div>

                        <form @submit.prevent="submit" class="space-y-8">
                            <!-- SECCIÓN 1: DATOS DEL COMPRADOR -->
                            <div class="border-b border-gray-200 pb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-6">Datos del Comprador</h3>
                                
                                <!-- Tipo de Documento - Full Width -->
                                <div class="mb-6">
                                    <label class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal mb-3 block">
                                        Tipo de documento *
                                    </label>
                                    <div class="flex gap-6">
                                        <label class="flex items-center gap-3 cursor-pointer group">
                                            <div class="relative">
                                                <input
                                                    type="radio"
                                                    name="documentType"
                                                    :value="getDocumentTypeId('RUT')"
                                                    v-model="buyerForm.documentType"
                                                    class="sr-only peer"
                                                />
                                                <div class="w-5 h-5 border-2 border-[#5B5B5B] rounded-full peer-checked:border-[#FBBD51] peer-checked:bg-[#FBBD51] transition-all duration-200 flex items-center justify-center">
                                                    <div class="w-2 h-2 bg-white rounded-full opacity-0 peer-checked:opacity-100 transition-opacity duration-200"></div>
                                                </div>
                                            </div>
                                            <span class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal group-hover:text-[#FBBD51] transition-colors duration-200">
                                                RUT
                                            </span>
                                        </label>
                                        <label class="flex items-center gap-3 cursor-pointer group">
                                            <div class="relative">
                                                <input
                                                    type="radio"
                                                    name="documentType"
                                                    :value="getDocumentTypeId('Pasaporte')"
                                                    v-model="buyerForm.documentType"
                                                    class="sr-only peer"
                                                />
                                                <div class="w-5 h-5 border-2 border-[#5B5B5B] rounded-full peer-checked:border-[#FBBD51] peer-checked:bg-[#FBBD51] transition-all duration-200 flex items-center justify-center">
                                                    <div class="w-2 h-2 bg-white rounded-full opacity-0 peer-checked:opacity-100 transition-opacity duration-200"></div>
                                                </div>
                                            </div>
                                            <span class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal group-hover:text-[#FBBD51] transition-colors duration-200">
                                                Pasaporte
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Left Column - Document Number and Personal Information -->
                                    <div class="flex flex-col gap-[18px]">
                                        <!-- Número de Documento -->
                                        <div class="flex flex-col gap-[12px]">
                                            <label class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal">
                                                {{ getDocumentLabel() }} *
                                            </label>
                                            <input
                                                type="text"
                                                :placeholder="getDocumentPlaceholder()"
                                                :class="[
                                                    'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                    isRutDocument && rutValidation.isValid === false ? 'border-red-500' : '',
                                                    isRutDocument && rutValidation.isValid === true ? 'border-green-500' : 'border-[#5B5B5B]',
                                                ]"
                                                v-model="buyerForm.documentNumber"
                                                @input="handleDocumentInput"
                                                @blur="handleDocumentBlur"
                                            />
                                            <div
                                                v-if="isRutDocument && rutValidation.message"
                                                class="text-xs mt-1 validation-message"
                                                :class="[
                                                    rutValidation.isValid === true ? 'text-green-500' : 'text-red-500',
                                                ]"
                                            >
                                                {{ rutValidation.message }}
                                            </div>
                                        </div>

                                        <!-- Nombre completo -->
                                        <div class="flex flex-col gap-[12px]">
                                            <label class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal">
                                                Nombre completo *
                                            </label>
                                            <input
                                                type="text"
                                                placeholder="Nombre y apellido"
                                                class="w-full h-[46px] bg-white rounded-lg border border-[#5B5B5B] px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                                v-model="buyerForm.fullName"
                                            />
                                        </div>

                                        <!-- Correo electrónico -->
                                        <div class="flex flex-col gap-[12px]">
                                            <label class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal">
                                                Correo electrónico *
                                            </label>
                                            <input
                                                type="email"
                                                placeholder="Escriba su correo electronico"
                                                class="w-full h-[46px] bg-white rounded-lg border border-[#5B5B5B] px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                                v-model="buyerForm.email"
                                            />
                                        </div>

                                        <!-- Número de celular -->
                                        <div class="flex flex-col gap-[12px]">
                                            <label class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal">
                                                Número de celular *
                                            </label>
                                            <div class="flex">
                                                <select
                                                    v-model="buyerForm.code_phone"
                                                    class="w-[70px] h-[46px] bg-white border border-[#5B5B5B] rounded-l border-r-0 flex items-center justify-center gap-2 px-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none"
                                                >
                                                    <option value="+56">🇨🇱</option>
                                                    <option value="+54">🇦🇷</option>
                                                    <option value="+51">🇵🇪</option>
                                                    <option value="+598">🇺🇾</option>
                                                </select>
                                                <input
                                                    type="tel"
                                                    placeholder="9-- --- ---"
                                                    class="flex-1 h-[46px] bg-white border rounded-r px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                                    v-model="buyerForm.phone"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right Column - Location Information -->
                                    <div class="flex flex-col gap-[18px]">
                                        <!-- País -->
                                        <div class="flex flex-col gap-[12px]">
                                            <label class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal">
                                                País *
                                            </label>
                                            <SearchableSelect
                                                ref="countrySelect"
                                                :options="countries"
                                                :value="buyerForm.country"
                                                placeholder="Busca y selecciona tu país"
                                                @input="handleCountryChange"
                                                search-key="name"
                                            />
                                        </div>

                                        <div class="flex flex-col gap-[12px]">
                                            <label class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal">
                                                Región *
                                            </label>
                                            <SearchableSelect
                                                :options="regions"
                                                :value="buyerForm.region"
                                                placeholder="Busca y selecciona tu región"
                                                @input="handleRegionChange"
                                                search-key="name"
                                            />
                                        </div>

                                        <div class="flex flex-col gap-[12px]">
                                            <label class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal">
                                                Comuna *
                                            </label>
                                            <SearchableSelect
                                                :options="filteredComunes"
                                                :value="buyerForm.city"
                                                placeholder="Busca y selecciona tu comuna"
                                                :disabled="!buyerForm.region"
                                                @input="handleCityChange"
                                                search-key="name"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 2: INFORMACIÓN DEL PAGO -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-6">Información del Pago</h3>
                                
                                <!-- Selección de Programa y Participante -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Programa *
                                        </label>
                                        <select
                                            v-model="form.program_id"
                                            @change="loadParticipants"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                            :class="{ 'border-red-500': errors.program_id }"
                                        >
                                            <option value="">Seleccionar programa</option>
                                            <option
                                                v-for="program in programs"
                                                :key="program.id"
                                                :value="program.id"
                                            >
                                                {{ program.code }} - {{ program.name }}
                                            </option>
                                        </select>
                                        <span v-if="errors.program_id" class="text-red-500 text-sm mt-1">{{ errors.program_id }}</span>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Participante *
                                        </label>
                                        <select
                                            v-model="form.participant_id"
                                            @change="loadParticipantPaymentStatus"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                            :class="{ 'border-red-500': errors.participant_id }"
                                            :disabled="!form.program_id"
                                        >
                                            <option value="">Seleccionar participante</option>
                                            <option
                                                v-for="participant in availableParticipants"
                                                :key="participant.id"
                                                :value="participant.id"
                                            >
                                                {{ formatParticipantName(participant) }} - {{ formatParticipantDocument(participant) }}
                                            </option>
                                        </select>
                                        <span v-if="errors.participant_id" class="text-red-500 text-sm mt-1">{{ errors.participant_id }}</span>
                                    </div>
                                </div>

                                <!-- Información del Estado de Pagos del Participante -->
                                <div v-if="participantPaymentStatus" class="mb-6 p-4 bg-gray-50 rounded-lg">
                                    <h4 class="text-md font-semibold text-gray-800 mb-3">Estado de Pagos del Participante</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div>
                                            <span class="text-sm text-gray-600">Monto Total:</span>
                                            <p class="font-semibold text-gray-800">${{ formatPrice(participantPaymentStatus.payment_info.total_amount) }}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Monto Pagado:</span>
                                            <p class="font-semibold text-green-600">${{ formatPrice(participantPaymentStatus.payment_info.paid_amount) }}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Saldo Pendiente:</span>
                                            <p class="font-semibold text-red-600">${{ formatPrice(participantPaymentStatus.payment_info.balance) }}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Cuotas Pagadas:</span>
                                            <p class="font-semibold text-blue-600">{{ participantPaymentStatus.payment_info.installments_summary }} cuotas</p>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <span class="text-sm text-gray-600">Progreso de Pago:</span>
                                        <div class="w-full bg-gray-200 rounded-full h-2.5 mt-1">
                                            <div 
                                                class="bg-green-600 h-2.5 rounded-full transition-all duration-300"
                                                :style="{ width: participantPaymentStatus.payment_info.payment_percentage + '%' }"
                                            ></div>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1">{{ participantPaymentStatus.payment_info.payment_percentage }}% completado</p>
                                    </div>
                                    <div class="mt-3">
                                        <span class="text-sm text-gray-600">Estado:</span>
                                        <span 
                                            class="ml-2 px-2 py-1 text-xs font-medium rounded-full"
                                            :class="getPaymentStatusClass(participantPaymentStatus.payment_info.payment_status)"
                                        >
                                            {{ getPaymentStatusLabel(participantPaymentStatus.payment_info.payment_status) }}
                                        </span>
                                        <div class="mt-1 text-sm text-gray-600">
                                            {{ getDetailedPaymentStatus(participantPaymentStatus.payment_info) }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Información del Pago Presencial -->
                                <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                    <h4 class="text-md font-semibold text-blue-800 mb-3">Datos del Pago Presencial</h4>
                                    
                                                                <!-- Monto del Pago -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Monto (CLP) *
                                    </label>
                                    <input
                                        v-model="form.amount"
                                        type="number"
                                        min="0"
                                        step="1"
                                        placeholder="0"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                        :class="{ 'border-red-500': errors.amount }"
                                    />
                                    <span v-if="errors.amount" class="text-red-500 text-sm mt-1">{{ errors.amount }}</span>
                                </div>

                                    <!-- Código de Pago/Boleta/Factura -->
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Código de Pago/Boleta/Factura *
                                        </label>
                                        <input
                                            v-model="form.payment_code"
                                            type="text"
                                            placeholder="Ej: B001-2024, F2024-001, P2024-001"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                            :class="{ 'border-red-500': errors.payment_code }"
                                        />
                                        <p class="text-sm text-gray-500 mt-1">
                                            Ingrese el código de la boleta, factura o comprobante de pago presencial
                                        </p>
                                        <span v-if="errors.payment_code" class="text-red-500 text-sm mt-1">{{ errors.payment_code }}</span>
                                    </div>

                                    <!-- Fecha de Transacción y Código de Autorización -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Fecha de Transacción *
                                            </label>
                                            <input
                                                v-model="form.transaction_date"
                                                type="datetime-local"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                                :class="{ 'border-red-500': errors.transaction_date }"
                                            />
                                            <span v-if="errors.transaction_date" class="text-red-500 text-sm mt-1">{{ errors.transaction_date }}</span>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Código de Autorización
                                            </label>
                                            <input
                                                v-model="form.authorization_code"
                                                type="text"
                                                placeholder="Código de autorización (opcional)"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                                :class="{ 'border-red-500': errors.authorization_code }"
                                            />
                                            <span v-if="errors.authorization_code" class="text-red-500 text-sm mt-1">{{ errors.authorization_code }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Notas -->
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Notas
                                    </label>
                                    <textarea
                                        v-model="form.notes"
                                        rows="3"
                                        placeholder="Notas adicionales sobre el pago presencial..."
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                        :class="{ 'border-red-500': errors.notes }"
                                    ></textarea>
                                    <span v-if="errors.notes" class="text-red-500 text-sm mt-1">{{ errors.notes }}</span>
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
                                    :disabled="form.processing || !isBuyerFormValid"
                                    class="bg-[#007e93] hover:bg-[#006b7a] disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200"
                                >
                                    {{ form.processing ? 'Registrando...' : 'Registrar Pago' }}
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
import { ref, reactive, computed, onMounted, nextTick, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SearchableSelect from '@/Components/Ecommerce/SearchableSelect.vue';

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

    errors: {
        type: Object,
        default: () => ({})
    }
});

// Formulario de datos del comprador
const buyerForm = reactive({
    fullName: "",
    documentType: "",
    documentNumber: "",
    email: "",
    phone: "",
    code_phone: "+56",
    country: "",
    region: "",
    city: "",
});

// Formulario de información del pago
const form = useForm({
    program_id: '',
    participant_id: '',
    amount: '',
    payment_code: '', // Nuevo campo
    transaction_date: new Date().toISOString().slice(0, 16),
    authorization_code: '',
    notes: ''
});

const availableParticipants = ref([]);
const participantPaymentStatus = ref(null);

const rutValidation = reactive({
    isValid: null,
    message: "",
});

// Computed properties
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

const isBuyerFormValid = computed(() => {
    const validations = {
        fullName: buyerForm.fullName.trim() !== "",
        documentType: buyerForm.documentType !== "",
        documentNumber: buyerForm.documentNumber.trim() !== "",
        email: buyerForm.email.trim() !== "",
        phone: buyerForm.phone.trim() !== "",
        country: buyerForm.country !== "",
        region: buyerForm.region !== "",
        city: buyerForm.city !== "",
    };

    const basicValidation = Object.values(validations).every(
        (v) => v === true
    );
    const rutOk = isRutDocument.value
        ? rutValidation.isValid === true
        : true;
    
    // Validar también los campos del formulario de pago
    const paymentValidations = {
        program_id: form.program_id !== "",
        participant_id: form.participant_id !== "",
        amount: form.amount !== "" && parseFloat(form.amount) > 0,
        payment_code: form.payment_code.trim() !== "",
        transaction_date: form.transaction_date !== "",
    };

    const paymentValidation = Object.values(paymentValidations).every(
        (v) => v === true
    );

    return basicValidation && rutOk && paymentValidation;
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

const handleDocumentInput = () => {
    if (isRutDocument.value) {
        formatRut();
    }
};

const handleDocumentBlur = () => {
    // Validar documento si es RUT
    if (isRutDocument.value) {
        validateDocument();
    }

    // Buscar cliente frecuente si hay tipo de documento y número
    if (buyerForm.documentType && buyerForm.documentNumber.trim()) {
        searchFrequentClient();
    }
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
                console.log('Comuna establecida después de cargar:', clientData.comune_id);
            } else {
                console.log('Comunas no disponibles aún, reintentando...');
                // Reintentar si las comunas no están disponibles
                setTimeout(() => {
                    if (filteredComunes.value.length > 0) {
                        buyerForm.city = clientData.comune_id;
                        console.log('Comuna establecida en segundo intento:', clientData.comune_id);
                    }
                }, 200);
            }
        }, 300);
    });

    console.log('Formulario autocompletado con datos del cliente frecuente:', clientData);
};

const validateDocument = () => {
    if (isRutDocument.value) {
        validateRut();
    }
};

const formatRut = () => {
    // Remover todos los caracteres no numéricos excepto K
    let rut = buyerForm.documentNumber.replace(/[^0-9kK]/g, "");

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
            buyerForm.documentNumber = `${formattedBody}-${dv}`;
        } else {
            buyerForm.documentNumber = rut;
        }
    }

    // Validar el RUT después de formatearlo
    validateRut();
};

const validateRut = () => {
    const rut = buyerForm.documentNumber
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
    console.log("País seleccionado:", countryId);
    buyerForm.country = countryId;
};

const handleRegionChange = (regionId) => {
    console.log("Región seleccionada:", regionId);
    buyerForm.region = regionId;
    buyerForm.city = ""; // Limpiar comuna

    // Verificar las comunas disponibles
    if (regionId) {
        const selectedRegion = props.regions.find(
            (r) => r.id == regionId
        );
        console.log("Región encontrada:", selectedRegion);
        if (selectedRegion && selectedRegion.comunes) {
            console.log("Comunas disponibles:", selectedRegion.comunes);
        }
    }
};

const handleCityChange = (cityId) => {
    console.log("Comuna seleccionada:", cityId);
    buyerForm.city = cityId;
};

// Métodos del formulario de pago
const loadParticipants = () => {
    if (!form.program_id) {
        availableParticipants.value = [];
        form.participant_id = '';
        return;
    }

    const program = props.programs.find(p => p.id == form.program_id);
    if (program && program.course && program.course.participants) {
        availableParticipants.value = program.course.participants;
        console.log('Participantes cargados:', availableParticipants.value); // Debug
    } else {
        availableParticipants.value = [];
    }
    form.participant_id = '';
};

const loadParticipantPaymentStatus = async () => {
    if (!form.program_id || !form.participant_id) {
        participantPaymentStatus.value = null;
        return;
    }

    try {
        const response = await fetch('/admin/payments/participant-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
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
                console.log('Estado de pago del participante cargado:', participantPaymentStatus.value);
            } else {
                console.error('Error al cargar el estado de pago:', result.message);
                participantPaymentStatus.value = null;
            }
        } else {
            console.error('Error al cargar el estado de pago del participante:', response.status);
            participantPaymentStatus.value = null;
        }
    } catch (error) {
        console.error('Error en la solicitud de estado de pago del participante:', error);
        participantPaymentStatus.value = null;
    }
};

const formatPrice = (amount) => {
    return amount.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
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
    console.log('Participant data:', participant); // Debug
    
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



const submit = () => {
    // Combinar los datos del comprador con los datos del pago
    const combinedData = {
        ...form.data(),
        status: 'completed', // Siempre completado para pagos presenciales
        buyer_full_name: buyerForm.fullName,
        buyer_document_type: buyerForm.documentType,
        buyer_document_number: buyerForm.documentNumber,
        buyer_email: buyerForm.email,
        buyer_phone: buyerForm.phone,
        buyer_code_phone: buyerForm.code_phone,
        buyer_country: buyerForm.country,
        buyer_region: buyerForm.region,
        buyer_city: buyerForm.city,
    };

    // Crear un nuevo formulario con los datos combinados
    const submitForm = useForm(combinedData);
    submitForm.post(route('admin.payments.store'));
};



// Lifecycle
onMounted(() => {
    // Establecer RUT como tipo de documento por defecto
    buyerForm.documentType = getDocumentTypeId('RUT');
});
</script>

<style scoped>
/* Estilos para los radio buttons personalizados */
input[type="radio"]:checked + div {
    border-color: #FBBD51;
    background-color: #FBBD51;
}

/* Estilos para el checkbox personalizado */
.custom-checkbox {
    accent-color: #fbbd51;
}

.custom-checkbox:checked {
    background-color: #fbbd51;
    border-color: #fbbd51;
}

/* Estilos adicionales para mayor compatibilidad */
.custom-checkbox:checked::before {
    background-color: #fbbd51;
}

/* Para navegadores que no soportan accent-color */
.custom-checkbox:checked {
    background-color: #fbbd51 !important;
    border-color: #fbbd51 !important;
}
</style>
