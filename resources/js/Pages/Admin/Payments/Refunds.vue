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
                                
                                <!-- Selección de Programa y Participante -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Programa *
                                        </label>
                                        <select
                                            v-model="form.program_id"
                                            @change="loadParticipants"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e74c3c] focus:border-transparent"
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
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e74c3c] focus:border-transparent"
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
                                <div v-if="participantPaymentStatus" class="mt-6 p-4 bg-gray-50 rounded-lg">
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
                                </div>
                            </div>

                            <!-- SECCIÓN 3: DATOS FISCALES DE LA NOTA DE CRÉDITO -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-6">Datos Fiscales de la Nota de Crédito</h3>

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
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
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

// Formulario de datos del cliente
const clientForm = reactive({
    rut: "",
    name: "",
});

// Formulario de información del reembolso
const form = useForm({
    program_id: '',
    participant_id: '',
    sii_code: '',
    document_number: '',
    transaction_date: new Date().toLocaleString('sv-SE', { timeZone: 'America/Santiago' }).slice(0, 10),
    total_amount: ''
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
                'X-CSRF-TOKEN': usePage().props._csrf || '',
                'Accept': 'application/json',
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
    // Combinar los datos del cliente con los datos del reembolso
    const combinedData = {
        ...form.data(),
        client_rut: clientForm.rut,
        client_name: clientForm.name,
    };

    // Crear un nuevo formulario con los datos combinados
    const submitForm = useForm(combinedData);
    submitForm.post(route('admin.payments.refunds.store'));
};

// Lifecycle
onMounted(() => {
    // No hay configuración inicial necesaria
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
