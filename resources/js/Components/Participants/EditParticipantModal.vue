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
                        <!-- Nombre -->
                        <div>
                            <label
                                class="block text-[14px] font-nexa-bold text-gray-700 mb-2"
                            >
                                Nombre *
                            </label>
                            <input
                                v-model="form.first_name"
                                type="text"
                                placeholder="Nombre"
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

                        <!-- Apellido -->
                        <div>
                            <label
                                class="block text-[14px] font-nexa-bold text-gray-700 mb-2"
                            >
                                Apellido *
                            </label>
                            <input
                                v-model="form.last_name"
                                type="text"
                                placeholder="Apellido"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                :class="{ 'border-red-500': errors?.last_name }"
                            />
                            <div
                                v-if="errors?.last_name"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ errors.last_name }}
                            </div>
                        </div>

                        <!-- RUT (bloqueado y formateado) -->
                        <div>
                            <label
                                class="block text-[14px] font-nexa-bold text-gray-700 mb-2"
                            >
                                RUT *
                            </label>
                            <input
                                :value="formattedRut"
                                type="text"
                                placeholder="000000000"
                                disabled
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed"
                            />
                            <p class="text-gray-500 text-sm mt-1">
                                El RUT no se puede modificar
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

                        <!-- Ajuste (+/-): Izquierda -->
                        <div>
                            <label class="block text-[14px] font-nexa-bold text-gray-700 mb-2">
                                Ajuste (+/-)
                            </label>
                            <input
                                v-model="form.price_adjustments"
                                type="number"
                                step="1"
                                placeholder="0"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                            />
                        </div>

                        <!-- Motivo: Derecha -->
                        <div>
                            <label class="block text-[14px] font-nexa-bold text-gray-700 mb-2">
                                Motivo Del Ajuste
                            </label>
                            <textarea
                                v-model="form.adjustment_reason"
                                rows="2"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                :class="{ 'border-red-500': showAdjustmentReasonError }"
                                placeholder="Beca, liberado de pago, descuento personal, etc."
                            />
                            <div v-if="showAdjustmentReasonError" class="text-red-500 text-sm mt-1">
                                Debes ingresar el motivo del ajuste.
                            </div>
                        </div>
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
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(["close"]);

const isSubmitting = ref(false);
const showAdjustmentReasonError = ref(false);

const form = ref({
    first_name: "",
    last_name: "",
    document_number: "",
    birth_date: "",
    email: "",
    code_phone: "+56",
    phone: "",
    pivot_course_id: "",
    individual_price: "",
    price_adjustments: "",
    adjustment_reason: "",
});

const formatDateForInput = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    return date.toISOString().split("T")[0];
};

// Cargar datos del participante cuando se abre el modal
watch(
    () => props.participant,
    (newParticipant) => {
        if (newParticipant && Object.keys(newParticipant).length > 0) {
            form.value = {
                first_name: newParticipant.first_name || "",
                last_name: newParticipant.last_name || "",
                document_number: newParticipant.document_number || "",
                birth_date: formatDateForInput(newParticipant.birth_date),
                email: newParticipant.email || "",
                code_phone: newParticipant.code_phone || "+56",
                phone: newParticipant.phone || "",
                pivot_course_id:
                    (newParticipant.courses && newParticipant.courses[0]?.id) ||
                    "",
                individual_price: newParticipant.individual_price ?? "",
                price_adjustments: newParticipant.price_adjustments ?? "",
                adjustment_reason: newParticipant.adjustment_reason ?? "",
            };
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
    },
    { immediate: true }
);

const updateParticipant = () => {
    isSubmitting.value = true;

    const formData = new FormData();
    formData.append("first_name", form.value.first_name);
    formData.append("last_name", form.value.last_name);
    formData.append("document_number", form.value.document_number);
    formData.append("birth_date", form.value.birth_date);
    formData.append("email", form.value.email);
    formData.append("code_phone", form.value.code_phone);
    formData.append("phone", form.value.phone);
    if (form.value.pivot_course_id)
        formData.append("pivot_course_id", form.value.pivot_course_id);
    if (form.value.individual_price !== "")
        formData.append("individual_price", form.value.individual_price);
    // Validación de motivo de ajuste en cliente
    showAdjustmentReasonError.value = false;
    if (form.value.price_adjustments !== "") {
        formData.append("price_adjustments", form.value.price_adjustments);
        if (!form.value.adjustment_reason || form.value.adjustment_reason.trim() === "") {
            showAdjustmentReasonError.value = true;
            isSubmitting.value = false;
            return;
        }
    }
    if (form.value.adjustment_reason !== "") {
        formData.append("adjustment_reason", form.value.adjustment_reason);
    }
    formData.append("_method", "PUT");

    router.post(
        route("admin.participants.update", props.participant.id),
        formData,
        {
            onSuccess: () => {
                emit("close");
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
// Formatear RUT para visualización
const formattedRut = computed(() => {
    const rut = form.value.document_number || "";
    const clean = rut.replace(/\./g, "").replace(/-/g, "");
    if (clean.length < 2) return rut;
    const body = clean.slice(0, -1);
    const dv = clean.slice(-1);
    const withDots = body.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    return `${withDots}-${dv}`;
});
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
