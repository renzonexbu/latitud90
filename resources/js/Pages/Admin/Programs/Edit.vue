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
                        :institutions="institutions"
                        :has-participants="program.participants && program.participants.length > 0"
                        @edit-group="handleEditGroup"
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
    institutions: {
        type: Array,
        default: () => []
    }
});

// Función para mapear nivel de educación desde BD al frontend
const mapEducationLevel = (level) => {
    const mapping = {
        'preescolar': 'inicial',
        'primaria': 'primario',
        'secundaria': 'secundario',
        'universitaria': 'universitario'
    };
    return mapping[level] || level;
};

// Función para mapear payment_mode_id a opciones del frontend
const mapPaymentOption = (paymentModeId) => {
    if (!paymentModeId) return "";
    
    // Según el seeder: 1 = Pago Total, 2 = Cuota Lat90
    const mapping = {
        1: 'full_payment',
        2: 'installments'
    };
    return mapping[paymentModeId] || "";
};

const form = useForm({
    // Campos del programa
    name: props.program.name || "",
    destination: props.program.destination || "",
    departure_date: props.program.departure_date ? new Date(props.program.departure_date).toISOString().split('T')[0] : "",
    description: props.program.trip_description || "", // Usar trip_description de la BD
    pilar_1: "", // Se procesará desde pillars
    pilar_2: "", // Se procesará desde pillars
    pilar_3: "", // Se procesará desde pillars
    pilar_4: "", // Se procesará desde pillars
    itinerary: props.program.itinerary_description || "", // Usar itinerary_description de la BD
    itinerary_file: null,
    coverage_file: null,
    equipment_file: null,
    images: [],
    // Campos del detalle administrativo
    total_price: props.program.trip_price || "",
    final_payment_date: props.program.final_payment_date ? new Date(props.program.final_payment_date).toISOString().split('T')[0] : "",
    sales_person: props.program.seller_name || "",
    institution_id: props.program.course?.institution_id || "",
    institution_name: props.program.course?.institution?.name || "",
    education_level: mapEducationLevel(props.program.course?.education_level) || "",
    shift: props.program.course?.shift || "",
    grade: props.program.course?.grade || "",
    students_file: null,
    group_benefit: props.program.group_benefit || "",
    discount_type: props.program.discount_type || "",
    payment_option: mapPaymentOption(props.program.payment_mode_id),
    full_payment_method: props.program.payment_method?.name || "",
    installments_payment_method: props.program.payment_method?.name || "",
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
    departure_date: props.program.departure_date ? new Date(props.program.departure_date).toISOString().split('T')[0] : "",
    description: props.program.trip_description || "", // Usar trip_description de la BD
    pilar_1: "", // Se procesará desde pillars
    pilar_2: "", // Se procesará desde pillars
    pilar_3: "", // Se procesará desde pillars
    pilar_4: "", // Se procesará desde pillars
    itinerary: props.program.itinerary_description || "", // Usar itinerary_description de la BD
    itinerary_file: null,
    coverage_file: null,
    equipment_file: null,
});

// Datos del detalle administrativo que se sincronizan con el componente
const paymentData = ref({
    total_price: props.program.trip_price || "",
    final_payment_date: props.program.final_payment_date ? new Date(props.program.final_payment_date).toISOString().split('T')[0] : "",
    sales_person: props.program.seller_name || "",
    institution_id: props.program.course?.institution_id || "",
    institution_name: props.program.course?.institution?.name || "",
    education_level: mapEducationLevel(props.program.course?.education_level) || "",
    shift: props.program.course?.shift || "",
    grade: props.program.course?.grade || "",
    students_file: null,
    group_benefit: props.program.group_benefit || "",
    discount_type: props.program.discount_type || "",
    payment_option: mapPaymentOption(props.program.payment_mode_id),
    full_payment_method: props.program.payment_method?.name || "",
    installments_payment_method: props.program.payment_method?.name || "",
    max_installments: props.program.max_installments || "",
});

// Imágenes existentes (desde la base de datos)
const existingImages = ref([]);

// Cargar imágenes existentes desde la carpeta del programa
if (props.program.images && props.program.images.length > 0) {
    existingImages.value = props.program.images.map((image, index) => ({
        id: `existing-${index}`,
        url: image.url,
        name: image.filename || `Imagen ${index + 1}`,
        isExisting: true,
        originalId: index,
    }));
}

console.log('Existing images:', existingImages.value);

// Archivos existentes (desde la base de datos)
const existingFiles = ref({
    itinerary_file: props.program.itinerary_file_url || null,
    coverage_file: props.program.travel_assistance_coverage_url || null,
    equipment_file: props.program.equipment_list_url || null,
});

// Debug para verificar datos cargados
console.log('Programa cargado:', {
    name: props.program.name,
    description: props.program.trip_description,
    itinerary: props.program.itinerary_description,
    pillars: props.program.pillars,
    images: props.program.images,
    images_folder: props.program.images_folder,
    itinerary_file: props.program.itinerary_file,
    itinerary_file_url: props.program.itinerary_file_url,
    coverage_file: props.program.travel_assistance_coverage,
    coverage_file_url: props.program.travel_assistance_coverage_url,
    equipment_file: props.program.equipment_list,
    equipment_file_url: props.program.equipment_list_url
});

console.log('Existing files:', existingFiles.value);

// Debug para verificar datos de pago
console.log('Datos de pago cargados:', {
    total_price: props.program.trip_price,
    final_payment_date: props.program.final_payment_date,
    final_payment_date_formatted: props.program.final_payment_date ? new Date(props.program.final_payment_date).toISOString().split('T')[0] : null,
    sales_person: props.program.seller_name,
    education_level_original: props.program.course?.education_level,
    education_level_mapped: mapEducationLevel(props.program.course?.education_level),
    institution_name: props.program.course?.institution?.name,
    shift: props.program.course?.shift,
    grade: props.program.course?.grade,
    payment_mode_id: props.program.payment_mode_id,
    payment_option_mapped: mapPaymentOption(props.program.payment_mode_id),
    full_payment_method: props.program.full_payment_method,
    installments_payment_method: props.program.installments_payment_method,
    max_installments: props.program.max_installments,
    discount_type: props.program.discount_type
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

// Función para procesar los pilares desde la base de datos
const processPillars = (pillarsString) => {
    if (!pillarsString) return { pilar_1: "", pilar_2: "", pilar_3: "", pilar_4: "" };
    
    const pillars = pillarsString.split(',').map(p => p.trim());
    return {
        pilar_1: pillars[0] || "",
        pilar_2: pillars[1] || "",
        pilar_3: pillars[2] || "",
        pilar_4: pillars[3] || "",
    };
};

// Procesar los pilares desde la base de datos
const pillarsData = processPillars(props.program.pillars);
programData.value.pilar_1 = pillarsData.pilar_1;
programData.value.pilar_2 = pillarsData.pilar_2;
programData.value.pilar_3 = pillarsData.pilar_3;
programData.value.pilar_4 = pillarsData.pilar_4;

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

// Función para manejar el botón Editar Grupo
const handleEditGroup = () => {
    // Aquí puedes implementar la lógica para editar el grupo
    console.log('Editar grupo clicked');
    // Por ejemplo, redirigir a una página de edición de participantes
    // window.location.href = route('admin.programs.participants', props.program.id);
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
