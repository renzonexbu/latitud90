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
                        :has-existing-course="hasExistingCourse"
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
import { ref, computed } from "vue";
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
    // Mapping según los ids reales de la tabla payment_modes
    const mapping = {
        1: 'full_payment',
        2: 'installments',
        3: 'installments', // Asumiendo que el ID 3 también es para cuotas
    };
    return mapping[paymentModeId] || "";
};

// Función para mapear payment_method_id a opciones del frontend
const mapPaymentMethod = (paymentMethodId) => {
    const mapping = {
        1: 'todos_medios',
        2: 'solo_tarjeta',
        3: 'solo_transferencia',
        4: 'solo_contado',
    };
    return mapping[paymentMethodId] || "";
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
    discount_amount: props.program.discount_amount ? props.program.discount_amount.toString() : "",
    payment_option: mapPaymentOption(props.program.payment_mode_id),
    full_payment_method: mapPaymentMethod(props.program.payment_method_id),
    installments_payment_method: mapPaymentMethod(props.program.payment_method_id),
    max_installments: props.program.max_installments ? props.program.max_installments.toString() : "",
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
    discount_amount: props.program.discount_amount ? props.program.discount_amount.toString() : "",
    payment_option: mapPaymentOption(props.program.payment_mode_id),
    full_payment_method: mapPaymentMethod(props.program.payment_method_id),
    installments_payment_method: mapPaymentMethod(props.program.payment_method_id),
    max_installments: props.program.max_installments ? props.program.max_installments.toString() : "",
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
    payment_mode_id: props.program.payment_mode_id,
    payment_method_id: props.program.payment_method_id,
    payment_option_mapped: mapPaymentOption(props.program.payment_mode_id),
    payment_method_mapped: mapPaymentMethod(props.program.payment_method_id),
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

// Verificar si ya existe un curso (para deshabilitar campos)
const hasExistingCourse = computed(() => {
    return props.program.course !== null && props.program.course !== undefined;
});

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
    // Redirigir al edit de curso con parámetro para abrir modal
    if (props.program.course) {
        window.location.href = route('admin.courses.edit', props.program.course.id) + '?openModal=true';
    } else {
        console.log('No hay curso asociado a este programa');
    }
};

const submit = () => {
    // Función para comparar valores y solo enviar si cambiaron
    const shouldSendField = (newValue, originalValue, fieldName) => {
        // Si el valor nuevo está vacío y el original también, no enviar
        if (!newValue && !originalValue) return false;
        
        // Si el valor nuevo es igual al original, no enviar
        if (newValue === originalValue) return false;
        
        // Si hay un valor nuevo, enviarlo
        return newValue !== undefined && newValue !== null;
    };

    // Función para formatear valores según el tipo esperado
    const formatValue = (value, fieldName) => {
        if (value === undefined || value === null) return null;
        
        switch (fieldName) {
            case 'max_installments':
                return value.toString();
            case 'total_price':
            case 'discount_amount':
                // Asegurar que sea string sin decimales si son .00
                let stringValue = value.toString();
                if (stringValue.includes('.00')) {
                    stringValue = stringValue.split('.')[0];
                }
                return stringValue;
            default:
                return value;
        }
    };

    // Campos del programa - solo enviar si cambiaron
    if (shouldSendField(programData.value.name, props.program.name, 'name')) {
        form.name = programData.value.name;
    }
    if (shouldSendField(programData.value.destination, props.program.destination, 'destination')) {
        form.destination = programData.value.destination;
    }
    if (shouldSendField(programData.value.departure_date, props.program.departure_date ? new Date(props.program.departure_date).toISOString().split('T')[0] : null, 'departure_date')) {
        form.departure_date = programData.value.departure_date;
    }
    if (shouldSendField(programData.value.description, props.program.trip_description, 'description')) {
        form.description = programData.value.description;
    }
    if (shouldSendField(programData.value.itinerary, props.program.itinerary_description, 'itinerary')) {
        form.itinerary = programData.value.itinerary;
    }
    if (shouldSendField(programData.value.pilar_1, props.program.pillars?.split(',')[0]?.trim() || '', 'pilar_1')) {
        form.pilar_1 = programData.value.pilar_1;
    }
    if (shouldSendField(programData.value.pilar_2, props.program.pillars?.split(',')[1]?.trim() || '', 'pilar_2')) {
        form.pilar_2 = programData.value.pilar_2;
    }
    if (shouldSendField(programData.value.pilar_3, props.program.pillars?.split(',')[2]?.trim() || '', 'pilar_3')) {
        form.pilar_3 = programData.value.pilar_3;
    }
    if (shouldSendField(programData.value.pilar_4, props.program.pillars?.split(',')[3]?.trim() || '', 'pilar_4')) {
        form.pilar_4 = programData.value.pilar_4;
    }

    // Campos del detalle administrativo - solo enviar si cambiaron
    if (shouldSendField(paymentData.value.total_price, props.program.trip_price, 'total_price')) {
        form.total_price = formatValue(paymentData.value.total_price, 'total_price');
    }
    if (shouldSendField(paymentData.value.final_payment_date, props.program.final_payment_date ? new Date(props.program.final_payment_date).toISOString().split('T')[0] : null, 'final_payment_date')) {
        form.final_payment_date = paymentData.value.final_payment_date;
    }
    if (shouldSendField(paymentData.value.sales_person, props.program.seller_name, 'sales_person')) {
        form.sales_person = paymentData.value.sales_person;
    }
    if (shouldSendField(paymentData.value.institution_name, props.program.course?.institution?.name, 'institution_name')) {
        form.institution_name = paymentData.value.institution_name;
    }
    if (shouldSendField(paymentData.value.education_level, props.program.course?.education_level, 'education_level')) {
        form.education_level = paymentData.value.education_level;
    }
    if (shouldSendField(paymentData.value.shift, props.program.course?.shift, 'shift')) {
        form.shift = paymentData.value.shift;
    }
    if (shouldSendField(paymentData.value.grade, props.program.course?.grade, 'grade')) {
        form.grade = paymentData.value.grade;
    }
    if (shouldSendField(paymentData.value.discount_type, props.program.discount_type, 'discount_type')) {
        form.discount_type = paymentData.value.discount_type;
    }
    if (shouldSendField(paymentData.value.discount_amount, props.program.discount_value, 'discount_amount')) {
        form.discount_amount = formatValue(paymentData.value.discount_amount, 'discount_amount');
    }
    if (shouldSendField(paymentData.value.payment_option, mapPaymentOption(props.program.payment_mode_id), 'payment_option')) {
        form.payment_option = paymentData.value.payment_option;
    }
    if (shouldSendField(paymentData.value.full_payment_method, mapPaymentMethod(props.program.payment_method_id), 'full_payment_method')) {
        form.full_payment_method = paymentData.value.full_payment_method;
    }
    if (shouldSendField(paymentData.value.installments_payment_method, mapPaymentMethod(props.program.payment_method_id), 'installments_payment_method')) {
        form.installments_payment_method = paymentData.value.installments_payment_method;
    }
    if (shouldSendField(paymentData.value.max_installments, props.program.max_installments, 'max_installments')) {
        form.max_installments = formatValue(paymentData.value.max_installments, 'max_installments');
    }

    // Solo enviar nuevas imágenes si hay archivos nuevos
    const imageFiles = selectedImages.value
        .filter((img) => !img.isExisting)
        .map((img) => img.file);
    if (imageFiles.length > 0) {
        form.images = imageFiles;
    }

    // Solo enviar archivos nuevos si realmente hay archivos nuevos
    if (programData.value.itinerary_file && programData.value.itinerary_file !== props.program.itinerary_file) {
        form.itinerary_file = programData.value.itinerary_file;
    }
    if (programData.value.coverage_file && programData.value.coverage_file !== props.program.travel_assistance_coverage) {
        form.coverage_file = programData.value.coverage_file;
    }
    if (programData.value.equipment_file && programData.value.equipment_file !== props.program.equipment_list) {
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
