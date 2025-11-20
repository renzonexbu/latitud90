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
            console.log('✅ Estado del programa actualizado:', form.active ? 'Activo' : 'Inactivo');
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
</script>

<style scoped>
/* Estilos personalizados si son necesarios */
</style>
