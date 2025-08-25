<template>
    <AdminLayout>
        <Head title="Editar Pago" />

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Header -->
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold text-gray-900">Editar Pago #{{ payment.id }}</h2>
                            <Link
                                :href="route('admin.payments.index')"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200"
                            >
                                Volver
                            </Link>
                        </div>

                        <!-- Form -->
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Selección de Programa y Participante -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                                            {{ program.code }} - {{ program.name }} - {{ program.destination }}
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
                                            {{ participant.first_name }} {{ participant.last_name }} - {{ participant.document_number }}
                                        </option>
                                    </select>
                                    <span v-if="errors.participant_id" class="text-red-500 text-sm mt-1">{{ errors.participant_id }}</span>
                                </div>
                            </div>

                            <!-- Información del Pago -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
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

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Estado *
                                    </label>
                                    <select
                                        v-model="form.status"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                        :class="{ 'border-red-500': errors.status }"
                                    >
                                        <option value="">Seleccionar estado</option>
                                        <option value="pending">Pendiente</option>
                                        <option value="completed">Completado</option>
                                        <option value="failed">Fallido</option>
                                        <option value="authorized">Autorizado</option>
                                    </select>
                                    <span v-if="errors.status" class="text-red-500 text-sm mt-1">{{ errors.status }}</span>
                                </div>
                            </div>

                            <!-- Gateway y Método de Pago -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Gateway de Pago *
                                    </label>
                                    <select
                                        v-model="form.payment_gateway_id"
                                        @change="loadPaymentOptions"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                        :class="{ 'border-red-500': errors.payment_gateway_id }"
                                    >
                                        <option value="">Seleccionar gateway</option>
                                        <option
                                            v-for="gateway in paymentGateways"
                                            :key="gateway.id"
                                            :value="gateway.id"
                                        >
                                            {{ gateway.name }}
                                        </option>
                                    </select>
                                    <span v-if="errors.payment_gateway_id" class="text-red-500 text-sm mt-1">{{ errors.payment_gateway_id }}</span>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Método de Pago *
                                    </label>
                                    <select
                                        v-model="form.payment_option_id"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                        :class="{ 'border-red-500': errors.payment_option_id }"
                                        :disabled="!form.payment_gateway_id"
                                    >
                                        <option value="">Seleccionar método</option>
                                        <option
                                            v-for="option in availablePaymentOptions"
                                            :key="option.id"
                                            :value="option.id"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>
                                    <span v-if="errors.payment_option_id" class="text-red-500 text-sm mt-1">{{ errors.payment_option_id }}</span>
                                </div>
                            </div>

                            <!-- Fecha de Transacción -->
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

                            <!-- Información Adicional -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Código de Autorización
                                    </label>
                                    <input
                                        v-model="form.authorization_code"
                                        type="text"
                                        placeholder="Código de autorización"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                        :class="{ 'border-red-500': errors.authorization_code }"
                                    />
                                    <span v-if="errors.authorization_code" class="text-red-500 text-sm mt-1">{{ errors.authorization_code }}</span>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Número de Tarjeta
                                    </label>
                                    <input
                                        v-model="form.card_number"
                                        type="text"
                                        placeholder="**** **** **** ****"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                        :class="{ 'border-red-500': errors.card_number }"
                                    />
                                    <span v-if="errors.card_number" class="text-red-500 text-sm mt-1">{{ errors.card_number }}</span>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Tipo de Tarjeta
                                    </label>
                                    <select
                                        v-model="form.card_type"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                        :class="{ 'border-red-500': errors.card_type }"
                                    >
                                        <option value="">Seleccionar tipo</option>
                                        <option value="Visa">Visa</option>
                                        <option value="Mastercard">Mastercard</option>
                                        <option value="American Express">American Express</option>
                                        <option value="Diners Club">Diners Club</option>
                                        <option value="Magna">Magna</option>
                                        <option value="Cencosud">Cencosud</option>
                                        <option value="Presto">Presto</option>
                                    </select>
                                    <span v-if="errors.card_type" class="text-red-500 text-sm mt-1">{{ errors.card_type }}</span>
                                </div>
                            </div>

                            <!-- Notas -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Notas
                                </label>
                                <textarea
                                    v-model="form.notes"
                                    rows="3"
                                    placeholder="Notas adicionales sobre el pago..."
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                    :class="{ 'border-red-500': errors.notes }"
                                ></textarea>
                                <span v-if="errors.notes" class="text-red-500 text-sm mt-1">{{ errors.notes }}</span>
                            </div>

                            <!-- Botones -->
                            <div class="flex justify-end space-x-3 pt-6">
                                <Link
                                    :href="route('admin.payments.index')"
                                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200"
                                >
                                    Cancelar
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="bg-[#007e93] hover:bg-[#006b7a] disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200"
                                >
                                    {{ form.processing ? 'Actualizando...' : 'Actualizar Pago' }}
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
import { ref, reactive, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    payment: {
        type: Object,
        required: true
    },
    programs: {
        type: Array,
        default: () => []
    },
    paymentGateways: {
        type: Array,
        default: () => []
    },
    paymentOptions: {
        type: Array,
        default: () => []
    },
    errors: {
        type: Object,
        default: () => ({})
    }
});

const form = useForm({
    program_id: props.payment.order?.program?.id || '',
    participant_id: props.payment.order?.participant?.id || '',
    amount: props.payment.amount || '',
    payment_gateway_id: props.payment.payment_gateway_id || '',
    payment_option_id: props.payment.payment_option_id || '',
    status: props.payment.status || '',
    transaction_date: props.payment.transaction_date ? new Date(props.payment.transaction_date).toISOString().slice(0, 16) : new Date().toISOString().slice(0, 16),
    authorization_code: props.payment.authorization_code || '',
    card_number: props.payment.card_number || '',
    card_type: props.payment.card_type || '',
    notes: props.payment.gateway_response?.notes || ''
});

const availableParticipants = ref([]);
const availablePaymentOptions = ref([]);

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
    
    // Si el participante actual no está en la lista, agregarlo
    if (props.payment.order?.participant && !availableParticipants.value.find(p => p.id == props.payment.order.participant.id)) {
        availableParticipants.value.push(props.payment.order.participant);
    }
};

const loadPaymentOptions = () => {
    if (!form.payment_gateway_id) {
        availablePaymentOptions.value = [];
        form.payment_option_id = '';
        return;
    }

    const gateway = props.paymentGateways.find(g => g.id == form.payment_gateway_id);
    if (gateway) {
        availablePaymentOptions.value = props.paymentOptions.filter(option => 
            option.gateway_code === gateway.code
        );
    } else {
        availablePaymentOptions.value = [];
    }
    
    // Si la opción actual no está en la lista, agregarla
    if (props.payment.payment_option && !availablePaymentOptions.value.find(o => o.id == props.payment.payment_option_id)) {
        availablePaymentOptions.value.push(props.payment.payment_option);
    }
};

const submit = () => {
    form.put(route('admin.payments.update', props.payment.id));
};

onMounted(() => {
    // Cargar datos iniciales
    loadParticipants();
    loadPaymentOptions();
});
</script>
