<template>
    <AdminLayout>
        <Head title="Editar Plantilla de Programa" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Componente reutilizado de ProgramDescription -->
                <ProgramDescription
                    v-model="programData"
                    mode="template-edit"
                    :existing-images="program.images || []"
                    :existing-files="{
                        itinerary_file: program.itinerary_file_url,
                        coverage_file: program.travel_assistance_coverage_url,
                        equipment_file: program.equipment_list_url,
                    }"
                    :is-active="form.active"
                    :errors="errors"
                    @update:images="updateImages"
                    @remove:existingImage="markImageForDeletion"
                    @remove:existingFile="markFileForDeletion"
                    @toggle-status="toggleStatus"
                />

                <!-- Botones de acción -->
                <div class="flex justify-center gap-4 mt-8">
                    <Link
                        :href="route('admin.programs.index')"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-full font-medium transition-colors inline-flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Volver
                    </Link>
                    <button
                        @click="submit"
                        :disabled="form.processing"
                        class="bg-[#007e93] hover:bg-[#006b7a] disabled:opacity-50 disabled:cursor-not-allowed rounded-full py-4 px-8 text-white font-bold text-lg transition-colors duration-200"
                    >
                        {{ form.processing ? "Guardando..." : "Actualizar Plantilla" }}
                    </button>
                    <button
                        @click="checkAndDelete"
                        :disabled="isDeleting"
                        class="bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-full py-4 px-8 text-white font-bold text-lg transition-colors duration-200 inline-flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        {{ isDeleting ? "Verificando..." : "Eliminar Plantilla" }}
                    </button>
                </div>

                <!-- Modal de confirmación de eliminación -->
                <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Confirmar eliminación</h3>
                        </div>
                        <p class="text-gray-600 mb-6">
                            ¿Estás seguro de que deseas eliminar la plantilla <strong>"{{ props.program.name }}"</strong>?
                            Esta acción eliminará también todas las imágenes y archivos asociados y no se puede deshacer.
                        </p>
                        <div class="flex justify-end gap-3">
                            <button
                                @click="showDeleteModal = false"
                                class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors"
                            >
                                Cancelar
                            </button>
                            <button
                                @click="confirmDelete"
                                :disabled="isDeleting"
                                class="px-4 py-2 text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 rounded-lg font-medium transition-colors"
                            >
                                {{ isDeleting ? "Eliminando..." : "Sí, eliminar" }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal de error (no se puede eliminar) -->
                <div v-if="showErrorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">No se puede eliminar</h3>
                        </div>
                        <p class="text-gray-600 mb-6">{{ deleteErrorMessage }}</p>
                        <div class="flex justify-end">
                            <button
                                @click="showErrorModal = false"
                                class="px-4 py-2 text-white bg-[#007e93] hover:bg-[#006b7a] rounded-lg font-medium transition-colors"
                            >
                                Entendido
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, useForm, Link, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import ProgramDescription from "@/Components/Ecommerce/CreateProgramComponents/ProgramDescription.vue";

// Props
const props = defineProps({
    program: {
        type: Object,
        required: true,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

// Parsear pilares del programa
const pillars = props.program.pillars ? props.program.pillars.split(', ') : [];

// Formulario simplificado - solo campos de plantilla
const form = useForm({
    _method: 'put',
    name: props.program.name || "",
    destination: props.program.destination || "",
    description: props.program.trip_description || "",
    pilar_1: pillars[0] || "Aventura",
    pilar_2: pillars[1] || "Entretenimiento",
    pilar_3: pillars[2] || "Educación",
    pilar_4: pillars[3] || "Seguridad",
    itinerary: props.program.itinerary_description || "",
    itinerary_file: null,
    coverage_file: null,
    equipment_file: null,
    images: [],
    imagesToDelete: [],
    filesToDelete: [],
    active: props.program.active ?? true,
});

// Datos del programa que se sincronizan con el componente
const programData = ref({
    name: props.program.name || "",
    destination: props.program.destination || "",
    description: props.program.trip_description || "",
    pilar_1: pillars[0] || "Aventura",
    pilar_2: pillars[1] || "Entretenimiento",
    pilar_3: pillars[2] || "Educación",
    pilar_4: pillars[3] || "Seguridad",
    itinerary: props.program.itinerary_description || "",
    itinerary_file: null,
    coverage_file: null,
    equipment_file: null,
});

// Estado para nuevas imágenes
const newImages = ref([]);

// Estado para eliminación
const showDeleteModal = ref(false);
const showErrorModal = ref(false);
const deleteErrorMessage = ref('');
const isDeleting = ref(false);

// Watcher para sincronizar programData con el formulario
watch(
    programData,
    (newValue) => {
        Object.keys(newValue).forEach((key) => {
            form[key] = newValue[key];
        });
    },
    { deep: true }
);

// Watcher para sincronizar las imágenes con el formulario
watch(newImages, (newImages) => {
    const imageFiles = newImages.map((img) => img.file).filter(Boolean);
    form.images = imageFiles;
}, { deep: true });

// Función para actualizar imágenes
const updateImages = (images) => {
    newImages.value = images;
};

// Marcar imagen existente para eliminación
const markImageForDeletion = (index) => {
    if (!form.imagesToDelete.includes(index)) {
        form.imagesToDelete.push(index);
    } else {
        form.imagesToDelete = form.imagesToDelete.filter(i => i !== index);
    }
};

// Marcar archivo para eliminación
const markFileForDeletion = (fileType) => {
    if (!form.filesToDelete.includes(fileType)) {
        form.filesToDelete.push(fileType);
    } else {
        form.filesToDelete = form.filesToDelete.filter(f => f !== fileType);
    }
};

// Toggle status - actualiza la base de datos inmediatamente
const toggleStatus = () => {
    // Guardar el estado actual antes de la petición
    const currentStatus = form.active;

    // Usar router.patch para solo actualizar el estado, no todo el formulario
    router.patch(route('admin.programs.toggle-status', props.program.id), {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            // Cambiar el estado local solo si la petición fue exitosa
            form.active = !currentStatus;
            console.log('✅ Estado de la plantilla actualizado:', form.active ? 'Activo' : 'Inactivo');
        },
        onError: (errors) => {
            console.error('❌ Error al cambiar estado:', errors);
        }
    });
};

// Enviar formulario
const submit = () => {
    form.post(route("admin.programs.update", props.program.id), {
        forceFormData: true,
        onSuccess: () => {
            console.log("✅ Plantilla actualizada exitosamente");
        },
        onError: (errors) => {
            console.error("❌ Error al actualizar plantilla:", errors);
        },
    });
};

// Verificar si se puede eliminar y mostrar modal correspondiente
const checkAndDelete = async () => {
    isDeleting.value = true;
    try {
        const response = await fetch(route('admin.programs.can-delete', props.program.id));
        const result = await response.json();

        if (result.can_delete) {
            showDeleteModal.value = true;
        } else {
            deleteErrorMessage.value = result.message;
            showErrorModal.value = true;
        }
    } catch (error) {
        console.error('Error al verificar eliminación:', error);
        deleteErrorMessage.value = 'Error al verificar si la plantilla puede ser eliminada.';
        showErrorModal.value = true;
    } finally {
        isDeleting.value = false;
    }
};

// Confirmar y ejecutar eliminación
const confirmDelete = () => {
    isDeleting.value = true;
    router.delete(route('admin.programs.destroy', props.program.id), {
        onSuccess: () => {
            console.log('✅ Plantilla eliminada exitosamente');
            showDeleteModal.value = false;
        },
        onError: (errors) => {
            console.error('❌ Error al eliminar plantilla:', errors);
            showDeleteModal.value = false;
            deleteErrorMessage.value = errors.error || 'Error al eliminar la plantilla.';
            showErrorModal.value = true;
        },
        onFinish: () => {
            isDeleting.value = false;
        }
    });
};
</script>

<style scoped>
/* Estilos personalizados si son necesarios */
</style>
