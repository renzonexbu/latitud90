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
                         :errors="page.props?.errors || {}"
                        :is-active="isActive"
                        @toggle-status="toggleProgramStatus"
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
		:sales-executives="localSalesExecutives"
                         :errors="page.props?.errors || {}"
                        :has-participants="program.course && program.course.participants && program.course.participants.length > 0"
                        :has-existing-course="hasExistingCourse"
                        @create-executive="openCreateExecutiveModal"
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
            <!-- Create Executive Modal -->
            <CreateExecutiveModal 
                :show="showCreateExecutiveModal" 
                :errors="page.props?.errors || {}"
                @close="closeCreateExecutiveModal"
                @executive-created="handleExecutiveCreated"
            />
    </AdminLayout>
</template>

<script setup>
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import ProgramDescription from "@/Components/Ecommerce/CreateProgramComponents/ProgramDescription.vue";
import PaymentDetails from "@/Components/Ecommerce/CreateProgramComponents/PaymentDetails.vue";
import CreateExecutiveModal from "@/Components/Sales/CreateExecutiveModal.vue";

const props = defineProps({
    program: Object,
    institutions: {
        type: Array,
        default: () => []
    },
    salesExecutives: {
        type: Array,
        default: () => []
    }
});

// No necesitamos inicializar router, ya viene importado
const page = usePage();
// Estado para modal y lista local de ejecutivos
const showCreateExecutiveModal = ref(false);
const localSalesExecutives = ref([...(props.salesExecutives || [])]);

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

// Mapeo genérico a las llaves usadas por el select del admin (todos_medios, solo_tarjeta, etc.)
const mapGenericPaymentKeyFromName = (name) => {
    const byName = {
        'Todos los medios (Débito/Crédito/Transferencia)': 'todos_medios',
        'Solo pago con Tarjeta (Débito/Crédito)': 'solo_tarjeta',
        'Solo pago con Transferencia': 'solo_transferencia',
        'Solo pago Contado (Débito/Transferencia)': 'solo_contado',
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
    code: props.program.code || "",
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
    // Ejecutivo comercial
    sales_executive_id: props.program.sales_executive_id ? String(props.program.sales_executive_id) : "",
    // Campos del detalle administrativo
    total_price: props.program.trip_price || "",
    final_payment_date: props.program.final_payment_date ? new Date(props.program.final_payment_date).toISOString().split('T')[0] : "",
    sales_person: props.program.seller_name || "",
    institution_id: props.program.course?.institution_id || "",
    institution_name: props.program.course?.institution?.name || "",
    education_level: mapEducationLevel(props.program.course?.education_level) || "",
    grade: props.program.course?.grade || "",
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
    // Nuevos arrays de opciones específicas (se envían siempre durante update)
    full_payment_options: (props.program.full_payment_options || []),
    lat90_payment_options: (props.program.lat90_payment_options || []),
    full_payment_method: (props.program && props.program.total_payment_method_id) ? mapFullMethodFromId(props.program.total_payment_method_id) : '',
    installments_payment_method: (() => {
        if (props.program && props.program.lat90_payment_method_id) {
            return mapFullMethodFromId(props.program.lat90_payment_method_id);
        }
        if (props.program && props.program.lat90_payment_method && props.program.lat90_payment_method.name) {
            return mapGenericPaymentKeyFromName(props.program.lat90_payment_method.name);
        }
        return '';
    })(),
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
    code: props.program.code || "",
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
    sales_executive_id: props.program.sales_executive_id ? String(props.program.sales_executive_id) : "",
    institution_id: props.program.course?.institution_id || "",
    institution_name: props.program.course?.institution?.name || "",
    education_level: mapEducationLevel(props.program.course?.education_level) || "",
    grade: props.program.course?.grade || "",
    course_number: props.program.course?.course_number || "",
    students_file: null,
    group_benefit: props.program.group_benefit || "",
    discount_type: props.program.discount_type || "",
    discount_amount: props.program.discount_amount ? props.program.discount_amount.toString() : "",
    payment_options: [
        ...((props.program && props.program.enable_total_payment) ? ['full_payment'] : []),
        ...((props.program && props.program.enable_lat90_payment) ? ['installments'] : []),
    ],
    // Nuevas opciones por checkbox (precarga desde pivote si la tienes en props)
    full_payment_options: (props.program.full_payment_options || []),
    lat90_payment_options: (props.program.lat90_payment_options || []),
    full_payment_method: (props.program && props.program.total_payment_method_id) ? mapFullMethodFromId(props.program.total_payment_method_id) : '',
    installments_payment_method: (() => {
        if (props.program && props.program.lat90_payment_method_id) {
            return mapFullMethodFromId(props.program.lat90_payment_method_id);
        }
        if (props.program && props.program.lat90_payment_method && props.program.lat90_payment_method.name) {
            return mapGenericPaymentKeyFromName(props.program.lat90_payment_method.name);
        }
        return '';
    })(),
    max_installments: (props.program && props.program.lat90_max_installments) ? props.program.lat90_max_installments.toString() : "",
});

// Imágenes existentes (desde la base de datos)
const existingImages = ref([]);

// Cargar imágenes existentes desde la carpeta del programa
if (props.program.images && props.program.images.length > 0) {
    // Orden estable por filename para empatar con backend
    const ordered = [...props.program.images].sort((a, b) => (a.filename || '').localeCompare(b.filename || ''));
    existingImages.value = ordered.map((image, index) => ({
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
    grade_loaded: paymentData.value.grade,
    discount_type: props.program.discount_type
});

// Debug específico para el campo grade
console.log('Debug del campo grade:', {
    grade_from_props: props.program.course?.grade,
    grade_from_paymentData: paymentData.value.grade,
    grade_in_form: form.grade,
    course_exists: !!props.program.course,
    course_id: props.program.course?.id
});

// Debug adicional para verificar la inicialización de paymentData
console.log('paymentData inicializado:', {
    grade: paymentData.value.grade,
    grade_type: typeof paymentData.value.grade,
    grade_length: paymentData.value.grade ? paymentData.value.grade.length : 0
});

// Estado de pago (valores reales agregados del curso)
const paymentStatus = ref({
    paymentPercentage: props.program.course_payment_percentage ?? 0,
    paidAmount: props.program.course_paid_amount ?? 0,
    totalAmount: props.program.course_total_amount ?? (props.program.trip_price || 0),
    remainingAmount: Math.max(
        (props.program.course_total_amount ?? (props.program.trip_price || 0)) - (props.program.course_paid_amount ?? 0),
        0
    ),
});

// Estado para las nuevas imágenes
const selectedImages = ref([]);

// Verificar si ya existe un curso (para deshabilitar campos)
const hasExistingCourse = computed(() => {
    return props.program.course !== null && props.program.course !== undefined;
});

// Estado activo del programa
const isActive = computed(() => Boolean(props.program.active));

// Alternar estado activo
const toggleProgramStatus = () => {
    const confirmMsg = isActive.value
        ? '¿Seguro que desea desactivar este programa?'
        : '¿Seguro que desea activar este programa?';
    if (!confirm(confirmMsg)) return;
    router.patch(route('admin.programs.toggle-status', props.program.id), {}, {
        replace: true,
        preserveScroll: true,
    });
};

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
const markImageForDeletion = (imageIndex) => {
    if (!form.imagesToDelete.includes(imageIndex)) {
        form.imagesToDelete.push(imageIndex);
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

// Abrir/Cerrar modal de nuevo Ejecutivo
const openCreateExecutiveModal = () => { showCreateExecutiveModal.value = true; };
const closeCreateExecutiveModal = () => { showCreateExecutiveModal.value = false; };

// Al crear ejecutivo: agregar a la lista y seleccionarlo
const handleExecutiveCreated = (newExecutive) => {
    try {
        if (newExecutive && newExecutive.id) {
            localSalesExecutives.value.push(newExecutive);
            paymentData.value.sales_executive_id = String(newExecutive.id);
        }
    } finally {
        closeCreateExecutiveModal();
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
    if (shouldSendField(programData.value.code, props.program.code, 'code')) {
        form.code = programData.value.code;
    }
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
    // Campo grade: siempre enviar el valor correcto para mantener la sincronización
    if (paymentData.value.grade !== undefined && paymentData.value.grade !== null && paymentData.value.grade !== '') {
        form.grade = paymentData.value.grade;
        console.log('Campo grade enviado desde paymentData:', form.grade);
    } else if (props.program.course?.grade) {
        // Si no hay valor nuevo pero hay uno existente, mantenerlo
        form.grade = props.program.course.grade;
        console.log('Campo grade mantenido del valor existente:', form.grade);
    } else {
        // Si no hay valor, enviar cadena vacía para que el backend lo maneje
        form.grade = '';
        console.log('Campo grade enviado como cadena vacía');
    }
    
    // Forzar el envío del campo grade para asegurar que se mantenga sincronizado
    console.log('Campo grade final en el formulario:', form.grade);
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
    // Enviar siempre los arrays de opciones específicas (para sincronizar pivote)
    form.full_payment_options = Array.isArray(paymentData.value.full_payment_options) ? paymentData.value.full_payment_options : [];
    form.lat90_payment_options = Array.isArray(paymentData.value.lat90_payment_options) ? paymentData.value.lat90_payment_options : [];
    // Forzar payment_options en base a los arrays (para habilitar/deshabilitar secciones)
    const derivedPaymentOptions = [];
    if (form.full_payment_options.length > 0) derivedPaymentOptions.push('full_payment');
    if (form.lat90_payment_options.length > 0) derivedPaymentOptions.push('installments');
    form.payment_options = derivedPaymentOptions;
    if (shouldSendField(paymentData.value.full_payment_method, (props.program.total_payment_method_id ? 'todos_medios' : ''), 'full_payment_method')) {
        form.full_payment_method = paymentData.value.full_payment_method;
    }
    if (shouldSendField(
        paymentData.value.installments_payment_method,
        (props.program.lat90_payment_method_id
            ? mapFullMethodFromId(props.program.lat90_payment_method_id)
            : (props.program.lat90_payment_method ? mapGenericPaymentKeyFromName(props.program.lat90_payment_method.name) : '')
        ),
        'installments_payment_method'
    )) {
        form.installments_payment_method = paymentData.value.installments_payment_method;
    }
    if (shouldSendField(paymentData.value.max_installments, props.program.lat90_max_installments, 'max_installments')) {
        form.max_installments = formatValue(paymentData.value.max_installments, 'max_installments');
    }

    // Enviar siempre Ejecutivo Comercial si hay valor (garantiza actualización)
    if (
        paymentData.value.sales_executive_id !== undefined &&
        paymentData.value.sales_executive_id !== null &&
        paymentData.value.sales_executive_id !== ''
    ) {
        form.sales_executive_id = paymentData.value.sales_executive_id;
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
    
    // Verificar que el campo grade esté en el formulario antes del envío
    if (form.grade !== undefined) {
        console.log('✅ Campo grade confirmado en el formulario antes del envío:', form.grade);
    } else {
        console.log('❌ Campo grade NO está en el formulario antes del envío');
        // Forzar el campo grade si no está presente
        if (props.program.course?.grade) {
            form.grade = props.program.course.grade;
            console.log('🔧 Campo grade forzado desde props:', form.grade);
        }
    }

    // Log de depuración en cliente (solo para ver que se construye el payload)
    console.log('Payload del formulario:', JSON.parse(JSON.stringify(form.data())));
    console.log('Form keys que se envían:', Object.keys(form.data()));
    console.log('Form values:', Object.values(form.data()));
    console.log('Ruta del formulario:', route("admin.programs.update", props.program.id));
    console.log('¿Formulario vacío?', Object.keys(form.data()).length === 0);
    
    // Debug específico para el campo grade en el formulario
    console.log('Campo grade en el formulario antes del envío:', {
        grade_in_form: form.grade,
        grade_in_form_data: form.data().grade,
        grade_type: typeof form.grade
    });
    
    // Verificar que el campo grade esté en el formulario
    if (form.grade !== undefined) {
        console.log('✅ Campo grade está en el formulario:', form.grade);
    } else {
        console.log('❌ Campo grade NO está en el formulario');
    }
    
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
    
    // Verificar específicamente que el campo grade se esté enviando
    const gradeValue = form.data().grade;
    if (gradeValue !== undefined && gradeValue !== null) {
        console.log('✅ Campo grade incluido en FormData:', gradeValue);
    } else {
        console.log('❌ Campo grade NO incluido en FormData');
    }
    
    // Agregar _method para PUT request
    formData.append('_method', 'PUT');
    
    // Debug del FormData antes del envío
    console.log('FormData antes del envío:');
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }
    
    // Verificar específicamente si el campo grade está en el FormData
    const gradeInFormData = formData.get('grade');
    console.log('Campo grade en FormData:', {
        grade: gradeInFormData,
        grade_type: typeof gradeInFormData,
        grade_exists: gradeInFormData !== null
    });
    
    // Verificar que el campo grade se esté enviando correctamente
    if (gradeInFormData !== null) {
        console.log('✅ Campo grade confirmado en FormData:', gradeInFormData);
    } else {
        console.log('❌ Campo grade NO está en FormData');
        // Intentar agregar el campo grade manualmente si no está presente
        if (form.grade !== undefined && form.grade !== null) {
            formData.append('grade', form.grade);
            console.log('🔧 Campo grade agregado manualmente al FormData:', form.grade);
        }
    }
    
    // Verificar todos los campos del FormData para debug
    console.log('Todos los campos del FormData:');
    const formDataEntries = [];
    for (let [key, value] of formData.entries()) {
        formDataEntries.push({ key, value });
    }
    console.table(formDataEntries);
    
    // Verificar específicamente el campo grade en el FormData final
    const finalGradeInFormData = formData.get('grade');
    console.log('Campo grade en FormData final:', {
        grade: finalGradeInFormData,
        grade_type: typeof finalGradeInFormData,
        grade_exists: finalGradeInFormData !== null,
        grade_in_form: form.grade
    });
    
    // Enviar usando router.post con _method: 'PUT'. Dejar que el backend redirija con Inertia (evita doble navegación/flicker)
    router.post(route("admin.programs.update", props.program.id), formData, {
        replace: true,
        onSuccess: () => {},
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
