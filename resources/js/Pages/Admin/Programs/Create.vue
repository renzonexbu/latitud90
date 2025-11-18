<template>
    <AdminLayout>
        <Head title="Crear Plantilla de Programa" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Componente reutilizado de ProgramDescription -->
                <ProgramDescription
                    v-model="programData"
                    @update:images="updateImages"
                    :errors="errors"
                    mode="create-template"
                />

                <!-- Botón de guardar centrado debajo -->
                <div class="flex justify-center mt-8">
                    <button
                        @click="submit"
                        :disabled="form.processing"
                        class="bg-[#007e93] hover:bg-[#006b7a] disabled:opacity-50 disabled:cursor-not-allowed rounded-full py-4 px-8 text-white font-bold text-lg transition-colors duration-200"
                    >
                        {{
                            form.processing
                                ? "Guardando..."
                                : "Crear Plantilla"
                        }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import ProgramDescription from "@/Components/Ecommerce/CreateProgramComponents/ProgramDescription.vue";

// Props
const props = defineProps({
    errors: {
        type: Object,
        default: () => ({}),
    },
});

// Formulario simplificado - solo campos de plantilla
const form = useForm({
    name: "",
    destination: "",
    pilar_1: "Aventura",  // Valor fijo
    pilar_2: "Entretenimiento",  // Valor fijo
    pilar_3: "Educación",  // Valor fijo
    pilar_4: "Seguridad",  // Valor fijo
    itinerary_file: null,
    coverage_file: null,
    equipment_file: null,
    images: [],
    active: true,
});

// Datos del programa que se sincronizan con el componente
const programData = ref({
    name: "",
    destination: "",
    pilar_1: "Aventura",  // Valor fijo
    pilar_2: "Entretenimiento",  // Valor fijo
    pilar_3: "Educación",  // Valor fijo
    pilar_4: "Seguridad",  // Valor fijo
    itinerary_file: null,
    coverage_file: null,
    equipment_file: null,
});

// Estado para las imágenes
const selectedImages = ref([]);

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
watch(selectedImages, (newImages) => {
    const imageFiles = newImages.map((img) => img.file).filter(Boolean);
    form.images = imageFiles;
});

// Función para actualizar imágenes
const updateImages = (images) => {
    selectedImages.value = images;
};

// Enviar formulario
const submit = () => {
    form.post(route("admin.programs.store"), {
        onSuccess: () => {
            console.log("✅ Plantilla creada exitosamente");
        },
        onError: (errors) => {
            console.error("❌ Error al crear plantilla:", errors);
        },
    });
};
</script>

<style scoped>
/* Estilos personalizados si son necesarios */
</style>
