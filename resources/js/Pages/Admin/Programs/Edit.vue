<template>
    <AdminLayout>
        <Head title="Editar Programa" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="custom-grid gap-8">
                    <!-- Componente de Descripción del Programa -->
                    <ProgramDescription
                        v-model="programData"
                        mode="edit"
                        :existing-images="existingImages"
                        :existing-files="existingFiles"
                        @update:images="updateImages"
                        @remove:existingImage="markImageForDeletion"
                        @remove:existingFile="markFileForDeletion"
                    />

                    <!-- Componente de Detalle Administrativo -->
                    <PaymentDetails
                        v-model="paymentData"
                        mode="edit"
                        :payment-status="paymentStatus"
                    />
                </div>

                <!-- Botón de actualizar centrado debajo de ambos cards -->
                <div class="flex justify-center mt-8">
                    <button
                        @click="submit"
                        :disabled="form.processing"
                        class="bg-[#007e93] hover:bg-[#006b7a] disabled:opacity-50 disabled:cursor-not-allowed rounded-full py-4 px-8 text-white font-bold text-lg transition-colors duration-200"
                    >
                        {{
                            form.processing
                                ? "Actualizando..."
                                : "Actualizar Programa"
                        }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import ProgramDescription from "@/Components/Ecommerce/CreateProgramComponents/ProgramDescription.vue";
import PaymentDetails from "@/Components/Ecommerce/CreateProgramComponents/PaymentDetails.vue";

const props = defineProps({
    program: Object,
});

const form = useForm({
    // Campos del programa
    name: props.program.name || "",
    destination: props.program.destination || "",
    departure_date: props.program.departure_date || "",
    description: props.program.description || "",
    pilar_aventura: props.program.pilar_aventura || "",
    pilar_entretenimiento: props.program.pilar_entretenimiento || "",
    pilar_educacion: props.program.pilar_educacion || "",
    pilar_seguridad: props.program.pilar_seguridad || "",
    itinerary: props.program.itinerary || "",
    itinerary_file: null,
    coverage_file: null,
    equipment_file: null,
    images: [],
    // Campos del detalle administrativo
    total_price: props.program.total_price || "",
    final_payment_date: props.program.final_payment_date || "",
    sales_person: props.program.sales_person || "",
    institution_name: props.program.institution_name || "",
    education_level: props.program.education_level || "",
    shift: props.program.shift || "",
    grade: props.program.grade || "",
    students_file: null,
    group_benefit: props.program.group_benefit || "",
    payment_option: props.program.payment_option || "",
    full_payment_method: props.program.full_payment_method || "",
    installments_payment_method:
        props.program.installments_payment_method || "",
    max_installments: props.program.max_installments || "",
    active: props.program.active || true,
    // Control de archivos e imágenes existentes
    imagesToDelete: [],
    filesToDelete: [],
});

// Datos del programa que se sincronizan con el componente
const programData = ref({
    name: props.program.name || "",
    destination: props.program.destination || "",
    departure_date: props.program.departure_date || "",
    description: props.program.description || "",
    pilar_aventura: props.program.pilar_aventura || "",
    pilar_entretenimiento: props.program.pilar_entretenimiento || "",
    pilar_educacion: props.program.pilar_educacion || "",
    pilar_seguridad: props.program.pilar_seguridad || "",
    itinerary: props.program.itinerary || "",
    itinerary_file: null,
    coverage_file: null,
    equipment_file: null,
});

// Datos del detalle administrativo que se sincronizan con el componente
const paymentData = ref({
    total_price: props.program.total_price || "",
    final_payment_date: props.program.final_payment_date || "",
    sales_person: props.program.sales_person || "",
    institution_name: props.program.institution_name || "",
    education_level: props.program.education_level || "",
    shift: props.program.shift || "",
    grade: props.program.grade || "",
    students_file: null,
    group_benefit: props.program.group_benefit || "",
    payment_option: props.program.payment_option || "",
    full_payment_method: props.program.full_payment_method || "",
    installments_payment_method:
        props.program.installments_payment_method || "",
    max_installments: props.program.max_installments || "",
});

// Imágenes existentes (desde la base de datos)
const existingImages = ref(props.program.images || []);

// Archivos existentes (desde la base de datos)
const existingFiles = ref({
    itinerary_file: props.program.itinerary_file || null,
    coverage_file: props.program.coverage_file || null,
    equipment_file: props.program.equipment_file || null,
});

// Estado de pago (datos de ejemplo - en el futuro vendrán de la BD)
const paymentStatus = ref({
    paymentPercentage: props.program.id === 1 ? 75 : 50, // 75% para programa 1, 50% para programa 2
    paidAmount: props.program.id === 1 ? 337500 : 190000,
    totalAmount: props.program.id === 1 ? 450000 : 380000,
    remainingAmount: props.program.id === 1 ? 112500 : 190000,
});

// Estado para las nuevas imágenes
const selectedImages = ref([]);

// Función para actualizar las imágenes desde el componente
const updateImages = (images) => {
    selectedImages.value = images;
};

// Función para marcar imagen existente para eliminar
const markImageForDeletion = (imageId) => {
    if (!form.imagesToDelete.includes(imageId)) {
        form.imagesToDelete.push(imageId);
    }
};

// Función para marcar archivo existente para eliminar
const markFileForDeletion = (fileType) => {
    if (!form.filesToDelete.includes(fileType)) {
        form.filesToDelete.push(fileType);
    }
};

const submit = () => {
    // Sincronizar los datos del programa con el formulario
    Object.keys(programData.value).forEach((key) => {
        if (
            key !== "itinerary_file" &&
            key !== "coverage_file" &&
            key !== "equipment_file"
        ) {
            form[key] = programData.value[key];
        }
    });

    // Sincronizar los datos del detalle administrativo con el formulario
    Object.keys(paymentData.value).forEach((key) => {
        if (key !== "students_file") {
            form[key] = paymentData.value[key];
        }
    });

    // Agregar las nuevas imágenes al formulario
    const imageFiles = selectedImages.value
        .filter((img) => !img.isExisting)
        .map((img) => img.file);
    form.images = imageFiles;

    // Agregar archivos nuevos si existen
    if (programData.value.itinerary_file) {
        form.itinerary_file = programData.value.itinerary_file;
    }
    if (programData.value.coverage_file) {
        form.coverage_file = programData.value.coverage_file;
    }
    if (programData.value.equipment_file) {
        form.equipment_file = programData.value.equipment_file;
    }
    if (paymentData.value.students_file) {
        form.students_file = paymentData.value.students_file;
    }

    form.put(route("admin.programs.update", props.program.id));
};
</script>

<style scoped>
.custom-grid {
    display: grid;
    grid-template-columns: 1fr;
}

@media (min-width: 1024px) {
    .custom-grid {
        grid-template-columns: 1.2fr 1fr;
    }
}
</style>
