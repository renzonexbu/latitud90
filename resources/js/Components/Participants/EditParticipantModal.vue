<template>
    <div
        v-if="show"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
        <div
            class="bg-white rounded-[20px] w-[800px] max-h-[90vh] overflow-y-auto"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between p-6 border-b border-gray-200"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="w-8 h-8 bg-turquesa rounded-full flex items-center justify-center"
                    >
                        <svg
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z"
                                fill="white"
                            />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-[24px] font-nexa-bold text-turquesa">
                            Editar Participante
                        </h2>
                        <p class="text-[14px] text-gray-600">
                            Modificar datos personales del participante
                        </p>
                    </div>
                </div>
                <button
                    @click="$emit('close')"
                    class="text-gray-400 hover:text-gray-600"
                >
                    <svg
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z"
                            fill="currentColor"
                        />
                    </svg>
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="updateParticipant" class="p-6">
                <!-- Datos del participante -->
                <div class="mb-6">
                    <h3 class="text-[18px] font-nexa-bold text-turquesa mb-4">
                        Datos Del Participante*
                    </h3>

                    <div class="grid grid-cols-2 gap-6">
                        <!-- Primer Apellido -->
                        <div>
                            <label
                                class="block text-[14px] font-nexa-bold text-gray-700 mb-2"
                            >
                                Primer Apellido *
                            </label>
                            <input
                                v-model="form.first_last_name"
                                type="text"
                                placeholder="Primer Apellido"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                :class="{
                                    'border-red-500': errors?.first_last_name,
                                }"
                            />
                            <div
                                v-if="errors?.first_last_name"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ errors.first_last_name }}
                            </div>
                        </div>

                        <!-- Segundo Apellido -->
                        <div>
                            <label
                                class="block text-[14px] font-nexa-bold text-gray-700 mb-2"
                            >
                                Segundo Apellido
                            </label>
                            <input
                                v-model="form.second_last_name"
                                type="text"
                                placeholder="Segundo Apellido (opcional)"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                :class="{ 'border-red-500': errors?.second_last_name }"
                            />
                            <div
                                v-if="errors?.second_last_name"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ errors.second_last_name }}
                            </div>
                        </div>

                        <!-- Primer Nombre -->
                        <div>
                            <label
                                class="block text-[14px] font-nexa-bold text-gray-700 mb-2"
                            >
                                Primer Nombre *
                            </label>
                            <input
                                v-model="form.first_name"
                                type="text"
                                placeholder="Primer Nombre"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                :class="{
                                    'border-red-500': errors?.first_name,
                                }"
                            />
                            <div
                                v-if="errors?.first_name"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ errors.first_name }}
                            </div>
                        </div>

                        <!-- Segundo Nombre -->
                        <div>
                            <label
                                class="block text-[14px] font-nexa-bold text-gray-700 mb-2"
                            >
                                Segundo Nombre
                            </label>
                            <input
                                v-model="form.second_name"
                                type="text"
                                placeholder="Segundo Nombre (opcional)"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                :class="{ 'border-red-500': errors?.second_name }"
                            />
                            <div
                                v-if="errors?.second_name"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ errors.second_name }}
                            </div>
                        </div>

                        <!-- RUT/PASAPORTE (bloqueado y formateado) -->
                        <div>
                            <label
                                class="block text-[14px] font-nexa-bold text-gray-700 mb-2"
                            >
                                RUT / PASAPORTE *
                            </label>
                            <input
                                :value="formattedDocument"
                                type="text"
                                placeholder="000000000"
                                disabled
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed"
                            />
                            <p class="text-gray-500 text-sm mt-1">
                                El documento no se puede modificar
                            </p>
                        </div>

                        <!-- Fecha de nacimiento -->
                        <div>
                            <label
                                class="block text-[14px] font-nexa-bold text-gray-700 mb-2"
                            >
                                Fecha De Nacimiento
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.birth_date"
                                    type="date"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent pr-10"
                                    :class="{
                                        'border-red-500': errors?.birth_date,
                                    }"
                                />
                                <div
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none"
                                >
                                    <svg
                                        width="20"
                                        height="20"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path
                                            d="M19 3H5C3.89 3 3 3.9 3 5V19C3 20.1 3.89 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3ZM19 19H5V8H19V19ZM7 10H12V15H7V10Z"
                                            fill="#6B7280"
                                        />
                                    </svg>
                                </div>
                            </div>
                            <div
                                v-if="errors?.birth_date"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ errors.birth_date }}
                            </div>
                        </div>

                        <!-- Email -->
                        <div>
                            <label
                                class="block text-[14px] font-nexa-bold text-gray-700 mb-2"
                            >
                                Email
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="Email"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                :class="{ 'border-red-500': errors?.email }"
                            />
                            <div
                                v-if="errors?.email"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ errors.email }}
                            </div>
                        </div>

                        <!-- Teléfono -->
                        <div>
                            <label
                                class="block text-[14px] font-nexa-bold text-gray-700 mb-2"
                            >
                                Teléfono
                            </label>
                            <div class="flex gap-2">
                                <div class="relative">
                                    <select
                                        v-model="form.code_phone"
                                        class="w-20 px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent bg-white appearance-none cursor-pointer pr-8"
                                    >
                                        <option value="+56">CL</option>
                                        <option value="+54">AR</option>
                                        <option value="+57">CO</option>
                                        <option value="+51">PE</option>
                                        <option value="+593">EC</option>
                                    </select>
                                    <div
                                        class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none"
                                    ></div>
                                </div>
                                <input
                                    v-model="form.phone"
                                    type="text"
                                    placeholder="9--- ---"
                                    class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                    :class="{ 'border-red-500': errors?.phone }"
                                />
                            </div>
                            <div
                                v-if="errors?.phone"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ errors.phone }}
                            </div>
                        </div>

                        <!-- Ajustes económicos (integrados al layout 2x2) -->
                        <!-- Aplicar ajuste: Izquierda -->
                        <div>
                            <label class="block text-[14px] font-nexa-bold text-gray-700 mb-2">
                                Aplicar Ajuste A Curso/Programa
                            </label>
                            <select
                                v-model="form.pivot_course_id"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                            >
                                <option value="">-- Selecciona --</option>
                                <option
                                    v-for="c in participant.courses || []"
                                    :key="c.id"
                                    :value="c.id"
                                >
                                    {{ (c.institution?.name || 'Sin institución') + ' / ' + (c.program?.name || 'Sin programa') }}
                                </option>
                            </select>
                        </div>

                        <!-- Precio individual: Derecha -->
                        <div>
                            <label class="block text-[14px] font-nexa-bold text-gray-700 mb-2">
                                Precio Individual (CLP)
                            </label>
                            <input
                                v-model="form.individual_price"
                                type="number"
                                min="0"
                                step="1"
                                placeholder="0"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed"
                                disabled
                            />
                        </div>
                    </div>
                </div>

                <!-- Sistema de Descuentos Múltiples -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-[18px] font-nexa-bold text-turquesa">
                            Descuentos Y Ajustes
                        </h3>
                        <button
                            type="button"
                            @click="addDiscount"
                            class="px-4 py-2 bg-turquesa text-white rounded-lg hover:bg-turquesa-dark transition-colors text-sm"
                        >
                            + Agregar Descuento
                        </button>
                    </div>

                    <!-- Lista de descuentos existentes -->
                    <div v-if="discounts.length > 0" class="space-y-3 mb-4">
                        <div
                            v-for="(discount, index) in discounts"
                            :key="index"
                            class="border border-gray-200 rounded-lg p-4 bg-gray-50"
                        >
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                        <!-- Descripción -->
                                        <div class="md:col-span-2">
                                            <label class="block text-[12px] font-nexa-bold text-gray-700 mb-1">
                                                Descripción Del Descuento
                                            </label>
                                            <input
                                                v-model="discount.comment"
                                                type="text"
                                                placeholder="Ej: Descuento familiar, Beca institucional, etc."
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                            />
                                        </div>

                                        <!-- Tipo de descuento -->
                                        <div>
                                            <label class="block text-[12px] font-nexa-bold text-gray-700 mb-1">
                                                Tipo
                                            </label>
                                            <select
                                                v-model="discount.type"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                            >
                                                <option value="percent">Porcentaje (%)</option>
                                                <option value="amount">Monto fijo (CLP)</option>
                                                <option value="liberado">Liberado (100%)</option>
                                            </select>
                                        </div>

                                        <!-- Valor -->
                                        <div>
                                            <label class="block text-[12px] font-nexa-bold text-gray-700 mb-1">
                                                {{ getDiscountValueLabel(discount.type) }}
                                            </label>
                                            <input
                                                v-model.number="discount.value"
                                                :type="discount.type === 'percent' ? 'number' : 'number'"
                                                :min="discount.type === 'percent' ? 0 : 0"
                                                :max="discount.type === 'percent' ? 100 : null"
                                                :step="discount.type === 'percent' ? 0.01 : 1"
                                                :disabled="discount.type === 'liberado'"
                                                :placeholder="getDiscountValuePlaceholder(discount.type)"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                                :class="{ 'bg-gray-100': discount.type === 'liberado' }"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Botón eliminar -->
                                <button
                                    type="button"
                                    @click="removeDiscount(index)"
                                    class="ml-3 p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                                    title="Eliminar descuento"
                                >
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z" fill="currentColor"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Resumen del descuento -->
                            <div class="text-sm text-gray-600 bg-white p-2 rounded border">
                                <strong>Descuento calculado:</strong> 
                                ${{ formatNumber(calculateDiscountAmount(discount)) }} 
                                <span v-if="discount.type === 'percent'">({{ discount.value || 0 }}%)</span>
                                <span v-if="discount.type === 'liberado'">(100%)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen total de descuentos -->
                    <div v-if="discounts.length > 0" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="font-semibold text-gray-700">Precio base:</span>
                                <div class="text-lg font-bold text-turquesa">${{ formatNumber(basePrice) }}</div>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-700">Total descuentos:</span>
                                <div class="text-lg font-bold text-red-600">-${{ formatNumber(totalDiscounts) }}</div>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-700">Precio final:</span>
                                <div class="text-lg font-bold text-green-600">${{ formatNumber(finalPrice) }}</div>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-700">Ahorro total:</span>
                                <div class="text-lg font-bold text-blue-600">{{ formatPercentage(totalDiscountPercentage) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Mensaje cuando no hay descuentos -->
                    <div v-else class="text-center py-8 text-gray-500 border-2 border-dashed border-gray-200 rounded-lg">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                        </svg>
                        <p class="text-sm">No hay descuentos aplicados</p>
                        <p class="text-xs mt-1">Haz clic en "Agregar Descuento" para comenzar</p>
                    </div>
                </div>

                <!-- Botones -->
                <div
                    class="flex justify-end gap-4 pt-6 border-t border-gray-200"
                >
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="isSubmitting"
                        class="px-6 py-3 bg-turquesa text-white rounded-lg hover:bg-turquesa-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ isSubmitting ? "Guardando..." : "Guardar Cambios" }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    participant: {
        type: Object,
        default: () => ({}),
    },
    participantProgramsWithDiscounts: {
        type: Array,
        default: () => [],
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(["close"]);

const isSubmitting = ref(false);
const discounts = ref([]);

const form = ref({
    first_last_name: "",
    second_last_name: "",
    first_name: "",
    second_name: "",
    document_number: "",
    birth_date: "",
    email: "",
    code_phone: "+56",
    phone: "",
    pivot_course_id: "",
    individual_price: "",
});

const formatDateForInput = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    return date.toISOString().split("T")[0];
};

// Cargar descuentos existentes desde la tabla participant_program_discounts
const loadExistingDiscounts = () => {
    const courseId = form.value.pivot_course_id;
    if (!courseId || !props.participant || !props.participant.courses) {
        discounts.value = [];
        return;
    }
    
    const course = props.participant.courses.find((c) => c.id == courseId);
    if (!course || !course.program) {
        discounts.value = [];
        return;
    }

    // Buscar el participant_program_id usando los datos del componente padre
    const participantProgram = props.participantProgramsWithDiscounts?.find(
        pp => pp.program_id === course.program.id
    );

    if (participantProgram && participantProgram.discounts) {
        discounts.value = participantProgram.discounts.map(discount => {
            // Determinar el tipo basado en los datos almacenados
            let type = "percent";
            let value = discount.percent || 0;
            
            if (discount.amount && discount.amount > 0) {
                type = "amount";
                value = discount.amount;
            } else if (discount.discount_type === 'released' || (discount.percent && discount.percent >= 100)) {
                type = "liberado";
                value = 100;
            }
            
            return {
                id: discount.id,
                type: type,
                value: value,
                comment: discount.comment,
                approved_by: discount.approved_by
            };
        });
    } else {
        discounts.value = [];
    }
};

// Cargar datos del participante cuando se abre el modal
watch(
    () => props.participant,
    (newParticipant) => {
        if (newParticipant && Object.keys(newParticipant).length > 0) {
            form.value = {
                first_last_name: newParticipant.first_last_name || "",
                second_last_name: newParticipant.second_last_name || "",
                first_name: newParticipant.first_name || "",
                second_name: newParticipant.second_name || "",
                document_number: newParticipant.document_number || "",
                birth_date: formatDateForInput(newParticipant.birth_date),
                email: newParticipant.email || "",
                code_phone: newParticipant.code_phone || "+56",
                phone: newParticipant.phone || "",
                pivot_course_id:
                    (newParticipant.courses && newParticipant.courses[0]?.id) ||
                    "",
                individual_price: newParticipant.individual_price ?? "",
            };

            // Cargar descuentos existentes si los hay
            loadExistingDiscounts();
        }
    },
    { immediate: true, deep: true }
);

// Sincronizar precio individual mostrado según el curso/programa seleccionado
watch(
    () => form.value.pivot_course_id,
    (newCourseId) => {
        if (!newCourseId || !props.participant || !props.participant.courses) {
            return;
        }
        const course = props.participant.courses.find((c) => c.id == newCourseId);
        if (course && course.pivot) {
            form.value.individual_price = course.pivot.individual_price ?? form.value.individual_price;
        }
        
        // Recargar descuentos cuando cambie el curso
        loadExistingDiscounts();
    },
    { immediate: true }
);

// Funciones para el sistema de descuentos múltiples
const addDiscount = () => {
    discounts.value.push({
        type: "percent",
        value: 0,
        comment: "",
    });
};

const removeDiscount = (index) => {
    discounts.value.splice(index, 1);
};

const getDiscountValueLabel = (type) => {
    switch (type) {
        case "percent": return "Porcentaje (%)";
        case "amount": return "Monto (CLP)";
        case "liberado": return "Liberado";
        default: return "Valor";
    }
};

const getDiscountValuePlaceholder = (type) => {
    switch (type) {
        case "percent": return "Ej: 10";
        case "amount": return "Ej: 50000";
        case "liberado": return "100%";
        default: return "";
    }
};

const calculateDiscountAmount = (discount) => {
    const basePrice = Number(form.value.individual_price || 0);
    
    if (discount.type === "liberado") {
        return basePrice;
    }
    
    if (discount.type === "percent") {
        const percentage = Math.max(0, Math.min(100, Number(discount.value || 0)));
        return (basePrice * percentage) / 100;
    }
    
    if (discount.type === "amount") {
        return Math.min(basePrice, Math.max(0, Number(discount.value || 0)));
    }
    
    return 0;
};

// Computed properties para el resumen
const basePrice = computed(() => Number(form.value.individual_price || 0));

const totalDiscounts = computed(() => {
    return discounts.value.reduce((total, discount) => {
        return total + calculateDiscountAmount(discount);
    }, 0);
});

const finalPrice = computed(() => {
    return Math.max(0, basePrice.value - totalDiscounts.value);
});

const totalDiscountPercentage = computed(() => {
    if (basePrice.value === 0) return 0;
    return (totalDiscounts.value / basePrice.value) * 100;
});

const updateParticipant = () => {
    isSubmitting.value = true;

    const formData = new FormData();
    formData.append("first_last_name", form.value.first_last_name);
    formData.append("second_last_name", form.value.second_last_name);
    formData.append("first_name", form.value.first_name);
    formData.append("second_name", form.value.second_name);
    formData.append("document_number", form.value.document_number);
    formData.append("birth_date", form.value.birth_date);
    formData.append("email", form.value.email);
    formData.append("code_phone", form.value.code_phone);
    formData.append("phone", form.value.phone);
    
    if (form.value.pivot_course_id) {
        formData.append("pivot_course_id", form.value.pivot_course_id);
    }
    
    if (form.value.individual_price !== "") {
        formData.append("individual_price", form.value.individual_price);
    }

    // Enviar los descuentos como JSON para procesarlos en el backend
    formData.append("discounts", JSON.stringify(discounts.value));
    
    formData.append("_method", "PUT");

    router.post(
        route("admin.participants.update", props.participant.id),
        formData,
        {
            onSuccess: () => {
                // Cerrar el modal primero
                emit("close");
                // Luego recargar la página
                window.location.reload();
            },
            onError: (errors) => {
                isSubmitting.value = false;
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        }
    );
};

// Formatear documento para visualización
const formattedDocument = computed(() => {
    const document = form.value.document_number || "";
    const documentType = props.participant?.document_type || 'RUT';
    
    if (documentType === 'RUT') {
        const clean = document.replace(/\./g, "").replace(/-/g, "");
        if (clean.length < 2) return document;
        const body = clean.slice(0, -1);
        const dv = clean.slice(-1);
        const withDots = body.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        return `${withDots}-${dv}`;
    } else {
        // Para pasaporte u otros documentos, mostrar en uppercase
        return document.toUpperCase();
    }
});

const formatNumber = (n) => new Intl.NumberFormat('es-CL').format(Number(n || 0));

const formatPercentage = (n) => {
    return `${Number(n || 0).toFixed(1)}%`;
};
</script>

<style scoped>
.text-turquesa {
    color: #007e93;
}

.bg-turquesa {
    background-color: #007e93;
}

.bg-turquesa-dark {
    background-color: #006b7d;
}

.focus\:ring-turquesa:focus {
    --tw-ring-color: #007e93;
}

.font-nexa-bold {
    font-family: "Nexa-Bold", sans-serif;
}
</style>
