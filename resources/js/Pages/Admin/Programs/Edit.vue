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
                        :has-participants="program.course && program.course.participants && program.course.participants.length > 0"
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
import { Head, useForm, router } from "@inertiajs/vue3";
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

// No necesitamos inicializar router, ya viene importado

// Función para mapear nivel de educación desde BD al frontend
const mapEducationLevel = (level) => {
    const mapping = {
        'preescolar': 'preescolar',
        'basica': 'basica',
        'media': 'media',
        'universitaria': 'universitaria'
    };
    return mapping[level] || level;
};

// Mapeos para el nuevo esquema Lat90
const mapLat90PaymentKeyFromName = (name) => {
    const byName = {
        'Transferencia bancaria (Khipu)': 'khipu',
        'Débito y crédito sin cuotas (Webpay)': 'webpay_1',
        'Débito y crédito 3 cuotas sin interés (Webpay)': 'webpay_3',
        'Débito y crédito 6 cuotas sin interés (Webpay)': 'webpay_6',
        'Débito y crédito 12 cuotas sin interés (Webpay)': 'webpay_12',
    };
    return byName[name] || '';
};

const mapPaymentOption = (program) => {
    if (!program) return '';
    if (program.enable_lat90_payment) return 'installments';
    if (program.enable_total_payment) return 'full_payment';
    return '';
};

const mapFullMethodFromId = (id) => {
    const mapping = {
        1: 'todos_medios',
        2: 'solo_tarjeta',
        3: 'solo_transferencia',
        4: 'solo_contado',
    };
    return mapping[id] || '';
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
    course_number: props.program.course?.course_number || "",
    students_file: null,
    group_benefit: props.program.group_benefit || "",
    discount_type: props.program.discount_type || "",
    discount_amount: props.program.discount_amount ? props.program.discount_amount.toString() : "",
    payment_option: mapPaymentOption(props.program),
    payment_options: [
        ...((props.program && props.program.enable_total_payment) ? ['full_payment'] : []),
        ...((props.program && props.program.enable_lat90_payment) ? ['installments'] : []),
    ],
    full_payment_method: (props.program && props.program.total_payment_method_id) ? mapFullMethodFromId(props.program.total_payment_method_id) : '',
    installments_payment_method: (props.program && props.program.lat90_payment_method) ? mapLat90PaymentKeyFromName(props.program.lat90_payment_method.name) : '',
    max_installments: (props.program && props.program.lat90_max_installments) ? props.program.lat90_max_installments.toString() : "",
    active: Boolean(props.program.active),
    // Control de archivos e imágenes existentes
    imagesToDelete: [],
    filesToDelete: [],
});

// Evitar bucles: no observar 'form' con watchers que reescriban sus propios campos,
// y cuando comparemos cambios, hacerlo una sola vez por submit sin mutar props.

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
    // Mostrar precio por participante en edición
    total_price: (() => {
        const course = props.program.course;
        if (course && course.participants && course.participants.length > 0) {
            const first = course.participants[0];
            const pivot = first.pivot || {};
            if (pivot.individual_price != null) return pivot.individual_price;
        }
        return "";
    })(),
    final_payment_date: props.program.final_payment_date ? new Date(props.program.final_payment_date).toISOString().split('T')[0] : "",
    sales_person: props.program.seller_name || "",
    institution_id: props.program.course?.institution_id || "",
    institution_name: props.program.course?.institution?.name || "",
    education_level: mapEducationLevel(props.program.course?.education_level) || "",
    course_number: props.program.course?.course_number || "",
    students_file: null,
    group_benefit: props.program.group_benefit || "",
    discount_type: props.program.discount_type || "",
    discount_amount: props.program.discount_amount ? props.program.discount_amount.toString() : "",
    payment_options: [
        ...((props.program && props.program.enable_total_payment) ? ['full_payment'] : []),
        ...((props.program && props.program.enable_lat90_payment) ? ['installments'] : []),
    ],
    full_payment_method: (props.program && props.program.total_payment_method_id) ? mapFullMethodFromId(props.program.total_payment_method_id) : '',
    installments_payment_method: (props.program && props.program.lat90_payment_method) ? mapLat90PaymentKeyFromName(props.program.lat90_payment_method.name) : '',
    max_installments: (props.program && props.program.lat90_max_installments) ? props.program.lat90_max_installments.toString() : "",
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
    payment_option_mapped: mapPaymentOption(props.program),
    enable_total_payment: props.program.enable_total_payment,
    total_payment_method_id: props.program.total_payment_method_id,
    enable_lat90_payment: props.program.enable_lat90_payment,
    lat90_payment_method: props.program.lat90_payment_method?.name,
    lat90_max_installments: props.program.lat90_max_installments,
    grade: props.program.course?.grade,
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
    if (!pillarsString || typeof pillarsString !== 'string') {
        return { pilar_1: "", pilar_2: "", pilar_3: "", pilar_4: "" };
    }
    // Normalizar separadores y espacios
    const normalized = pillarsString
        .replace(/\s*,\s*/g, ',')
        .replace(/\s+\|\s+/g, ',')
        .trim();
    const parts = normalized.split(',');
    return {
        pilar_1: (parts[0] || '').trim(),
        pilar_2: (parts[1] || '').trim(),
        pilar_3: (parts[2] || '').trim(),
        pilar_4: (parts[3] || '').trim(),
    };
};

// Procesar los pilares desde la base de datos
const pillarsData = processPillars(props.program.pillars);
programData.value = {
    ...programData.value,
    pilar_1: pillarsData.pilar_1,
    pilar_2: pillarsData.pilar_2,
    pilar_3: pillarsData.pilar_3,
    pilar_4: pillarsData.pilar_4,
};

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
    // Pilares: forzar los cuatro valores fijos
    form.pilar_1 = 'Aventura';
    form.pilar_2 = 'Entretenimiento';
    form.pilar_3 = 'Educación';
    form.pilar_4 = 'Seguridad';

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
    if (shouldSendField(paymentData.value.payment_option, mapPaymentOption(props.program), 'payment_option')) {
        form.payment_option = paymentData.value.payment_option;
    }
    // Incluir payment_options (array) si cambió
    const originalPaymentOptions = [
        ...(props.program.enable_total_payment ? ['full_payment'] : []),
        ...(props.program.enable_lat90_payment ? ['installments'] : []),
    ];
    const newPaymentOptions = paymentData.value.payment_options || [];
    if (JSON.stringify(newPaymentOptions.sort()) !== JSON.stringify(originalPaymentOptions.sort())) {
        form.payment_options = newPaymentOptions;
    }
    if (shouldSendField(paymentData.value.full_payment_method, (props.program.total_payment_method_id ? 'todos_medios' : ''), 'full_payment_method')) {
        form.full_payment_method = paymentData.value.full_payment_method;
    }
    if (shouldSendField(paymentData.value.installments_payment_method, (props.program.lat90_payment_method ? mapLat90PaymentKeyFromName(props.program.lat90_payment_method.name) : ''), 'installments_payment_method')) {
        form.installments_payment_method = paymentData.value.installments_payment_method;
    }
    if (shouldSendField(paymentData.value.max_installments, props.program.lat90_max_installments, 'max_installments')) {
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

    // Forzar el envío de al menos un campo para probar (temporal)
    form.test_field = 'test_value';

    // Log de depuración en cliente (solo para ver que se construye el payload)
    console.log('Payload del formulario:', JSON.parse(JSON.stringify(form.data())));
    console.log('Form keys que se envían:', Object.keys(form.data()));
    console.log('Form values:', Object.values(form.data()));
    console.log('Ruta del formulario:', route("admin.programs.update", props.program.id));
    console.log('¿Formulario vacío?', Object.keys(form.data()).length === 0);
    
    // Crear FormData y agregar _method para PUT request (como hacen los cursos)
    const formData = new FormData();
    
    // Agregar todos los campos del formulario
    Object.keys(form.data()).forEach(key => {
        const value = form.data()[key];
        if (value !== null && value !== undefined) {
            if (Array.isArray(value)) {
                // Para arrays, agregar cada elemento
                value.forEach(item => {
                    formData.append(key + '[]', item);
                });
            } else if (typeof value === 'boolean') {
                // Normalizar booleanos a 1/0 para que pasen la regla boolean de Laravel
                formData.append(key, value ? 1 : 0);
            } else {
                formData.append(key, value);
            }
        }
    });
    
    // Agregar _method para PUT request
    formData.append('_method', 'PUT');
    
    // Enviar usando router.post con _method: 'PUT' (como hacen los cursos)
    router.post(route("admin.programs.update", props.program.id), formData, {
        onSuccess: () => {
            // Redirigir a la lista de programas
            window.location.href = route('admin.programs.index');
        },
        onError: (errors) => {
            console.error('Errores del formulario:', errors);
        }
    });
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
