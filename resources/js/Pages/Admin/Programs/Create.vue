<template>
    <AdminLayout>
        <Head title="Crear Programa" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="custom-grid gap-8">
                    <!-- Componente de Descripción del Programa -->
                    <ProgramDescription
                        v-model="programData"
                        @update:images="updateImages"
                        :errors="{ ...errors, ...localErrors }"
                    />

                    <!-- Componente de Detalle Administrativo -->
                    <PaymentDetails 
                        v-model="paymentData" 
                        :errors="{ ...errors, ...localErrors }"
                        :institutions="localInstitutions"
                        :sales-executives="localSalesExecutives"
                        :has-participants="false"
                        @create-institution="openCreateInstitutionModal"
                        @create-executive="openCreateExecutiveModal"
                    />
                </div>

                <!-- Botón de guardar centrado debajo de ambos cards -->
                <div class="flex justify-center mt-8">
                    <button
                        @click="submit"
                        :disabled="form.processing"
                        class="bg-[#007e93] hover:bg-[#006b7a] disabled:opacity-50 disabled:cursor-not-allowed rounded-full py-4 px-8 text-white font-bold text-lg transition-colors duration-200"
                    >
                        {{
                            form.processing
                                ? "Guardando..."
                                : "Guardar Programa"
                        }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Create Institution Modal -->
        <CreateInstitutionModal 
            :show="showCreateInstitutionModal" 
            :errors="errors"
            @close="closeCreateInstitutionModal"
            @institution-created="handleInstitutionCreated"
        />
        <!-- Create Executive Modal -->
        <CreateExecutiveModal 
            :show="showCreateExecutiveModal" 
            :errors="errors"
            @close="closeCreateExecutiveModal"
            @executive-created="handleExecutiveCreated"
        />
    </AdminLayout>
</template>

<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import ProgramDescription from "@/Components/Ecommerce/CreateProgramComponents/ProgramDescription.vue";
import PaymentDetails from "@/Components/Ecommerce/CreateProgramComponents/PaymentDetails.vue";
import CreateInstitutionModal from "@/Components/Institutions/CreateInstitutionModal.vue";
import CreateExecutiveModal from "@/Components/Sales/CreateExecutiveModal.vue";

// Props
const props = defineProps({
    errors: {
        type: Object,
        default: () => ({})
    },
    institutions: {
        type: Array,
        default: () => []
    },
    salesExecutives: {
        type: Array,
        default: () => []
    }
});

const form = useForm({
    // Campos del programa
    code: "",
    name: "",
    destination: "",
    departure_date: "",
    description: "",
    pilar_1: "",
    pilar_2: "",
    pilar_3: "",
    pilar_4: "",
    itinerary: "",
    itinerary_file: null,
    coverage_file: null,
    equipment_file: null,
    images: [],
    // Campos del detalle administrativo
    total_price: "",
    final_payment_date: "",
    sales_person: "",
    sales_executive_id: "",
    institution_name: "",
    institution_id: "",
    education_level: "",
    course_number: "",
    students_file: null,
    group_benefit: "",
    discount_type: "",
    discount_amount: "",
    payment_options: [], // Array para múltiples opciones de pago
    full_payment_options: [],
    lat90_payment_options: [],
    full_payment_method: "",
    installments_payment_method: "",
    max_installments: "",
    created_by: null, // Se establecerá en el backend
    active: true,
});

// Datos del programa que se sincronizan con el componente
const programData = ref({
    code: "",
    name: "",
    destination: "",
    departure_date: "",
    description: "",
    pilar_1: "",
    pilar_2: "",
    pilar_3: "",
    pilar_4: "",
    itinerary: "",
    itinerary_file: null,
    coverage_file: null,
    equipment_file: null,
});

// Datos del detalle administrativo que se sincronizan con el componente
const paymentData = ref({
    total_price: "",
    final_payment_date: "",
    sales_person: "",
    institution_name: "",
    institution_id: "",
    education_level: "",
    course_number: "",
    students_file: null,
    group_benefit: "",
    discount_type: "",
    discount_amount: "",
    payment_options: [],
    full_payment_method: "",
    installments_payment_method: "",
    max_installments: "",
    sales_executive_id: "",
});

// Estado para las imágenes
const selectedImages = ref([]);

// Estado para el modal de creación de institución
const showCreateInstitutionModal = ref(false);
// Estado para el modal de creación de ejecutivo
const showCreateExecutiveModal = ref(false);

// Estado local para las instituciones (para poder modificarlas)
const localInstitutions = ref([...props.institutions]);
const localSalesExecutives = ref([...props.salesExecutives]);

// Estado para errores de validación local
const localErrors = ref({});

// Watcher para manejar errores del backend
watch(() => props.errors, (newErrors) => {
    if (newErrors && Object.keys(newErrors).length > 0) {
        // Si hay errores del backend, hacer scroll al primer error
        scrollToFirstError();
    }
}, { immediate: true });

// Watcher para sincronizar programData con el formulario
watch(programData, (newValue) => {
    // Sincronizar todos los campos del programa con el formulario
    Object.keys(newValue).forEach((key) => {
        form[key] = newValue[key];
    });
    // Asegurar que el código quede sincronizado
    form.code = newValue.code || form.code;
}, { deep: true });

// Watcher para sincronizar paymentData con el formulario
watch(paymentData, (newValue) => {
    // Sincronizar todos los campos del detalle administrativo con el formulario
    Object.keys(newValue).forEach((key) => {
        form[key] = newValue[key];
    });
    // Forzar sincronización del ejecutivo comercial
    form.sales_executive_id = newValue.sales_executive_id || form.sales_executive_id;
    // Forzar sincronización de institución (requerido en backend)
    form.institution_id = newValue.institution_id || form.institution_id;
    
    // Debug: Log de los datos de pago que se están sincronizando
    console.log('Datos de pago sincronizados:', {
        payment_options: newValue.payment_options,
        full_payment_method: newValue.full_payment_method,
        installments_payment_method: newValue.installments_payment_method,
        max_installments: newValue.max_installments
    });
    
    // Debug: Verificar que payment_options se esté asignando correctamente
    console.log('Form payment_options después de sincronizar:', form.payment_options);
}, { deep: true });

// Watcher específico para discount_type y discount_amount
watch(() => paymentData.value.discount_type, (newValue) => {
    form.discount_type = newValue;
});

watch(() => paymentData.value.discount_amount, (newValue) => {
    form.discount_amount = newValue;
});



// Asegurar que created_by se establezca
watch(() => form.created_by, (newValue) => {
    if (!newValue) {
        form.created_by = 1; // ID del usuario autenticado
    }
}, { immediate: true });

// Watcher específico para discount_type y discount_amount
watch(() => paymentData.value.discount_type, (newValue) => {
    form.discount_type = newValue;
    console.log('discount_type actualizado:', newValue);
});

watch(() => paymentData.value.discount_amount, (newValue) => {
    form.discount_amount = newValue;
    console.log('discount_amount actualizado:', newValue);
});

// Watcher para sincronizar las imágenes con el formulario
watch(selectedImages, (newImages) => {
    const imageFiles = newImages.map((img) => img.file).filter(Boolean);
    form.images = imageFiles;
}, { deep: true });

// Función para limpiar errores locales
const clearLocalErrors = () => {
    localErrors.value = {};
};

// Función para validar el formulario
const validateForm = () => {
    clearLocalErrors();
    const errors = {};
    
    // El nombre del programa ya no es obligatorio
    
    // Validar que el código del programa sea obligatorio
    if (!programData.value.code || programData.value.code.trim() === '') {
        errors.code = 'El código del programa es obligatorio';
    }
    
    // Validar que el destino sea obligatorio
    if (!programData.value.destination || programData.value.destination.trim() === '') {
        errors.destination = 'El destino es obligatorio';
    }
    
    // Validar que la fecha de salida sea obligatoria
    if (!programData.value.departure_date) {
        errors.departure_date = 'La fecha de salida es obligatoria';
    }
    
    // Validar que al menos una imagen sea obligatoria
    if (!selectedImages.value || selectedImages.value.length === 0) {
        errors.images = 'Debe seleccionar al menos una imagen para el programa';
    }
    
    // Asignar errores locales
    localErrors.value = errors;
    
    return Object.keys(errors).length === 0;
};

// Función para hacer scroll al primer campo con error
const scrollToFirstError = () => {
    // Esperar un tick para que los errores se rendericen en el DOM
    setTimeout(() => {
        let firstErrorElement = null;
        
        // Primero, intentar encontrar campos específicos con errores por nombre
        const allErrors = { ...props.errors, ...localErrors.value };
        const errorFieldNames = Object.keys(allErrors);
        
        if (errorFieldNames.length > 0) {
            // Mapeo de nombres de campos a selectores específicos
            const fieldSelectors = {
                'code': 'input[placeholder="1234"]',
                'name': 'input[placeholder="Nombre"]',
                'destination': 'input[placeholder*="Santiago"]',
                'departure_date': 'input[type="date"]',
                'description': 'textarea[placeholder*="descripción"]',
                'images': '.image-upload-area',
                'total_price': 'input[placeholder="--------"]',
                'final_payment_date': 'input[type="date"]',
                'sales_person': 'input[placeholder="Nombre"]',
                'institution_id': 'select[id*="institution"]',
                'education_level': 'select:has(option[value="inicial"])',
                'shift': 'select:has(option[value="mañana"])',
                'grade': 'select:has(option[value="1"])',
                'students_file': 'input[type="file"]'
            };
            
            // Buscar el primer campo con error usando los selectores específicos
            for (const fieldName of errorFieldNames) {
                if (fieldSelectors[fieldName]) {
                    const element = document.querySelector(fieldSelectors[fieldName]);
                    if (element) {
                        firstErrorElement = element;
                        break;
                    }
                }
            }
        }
        
        // Si no encontramos por nombre específico, usar selectores generales
        if (!firstErrorElement) {
            const errorSelectors = [
                // Errores en campos de texto
                'input.border-red-500',
                'textarea.border-red-500', 
                'select.border-red-500',
                // Mensajes de error
                '.text-red-500',
                // Elementos con clases de error
                '.error-message',
                '[class*="error"]'
            ];
            
            // Buscar el primer elemento con error
            for (const selector of errorSelectors) {
                const elements = document.querySelectorAll(selector);
                if (elements.length > 0) {
                    firstErrorElement = elements[0];
                    break;
                }
            }
        }
        
        // Si encontramos un elemento con error, hacer scroll
        if (firstErrorElement) {
            // Buscar el contenedor padre más apropiado para el scroll
            const fieldWrapper = firstErrorElement.closest('.field-wrapper') || 
                                firstErrorElement.closest('.field-container') || 
                                firstErrorElement.closest('.accordion-content') ||
                                firstErrorElement;
            
            // Primero expandir el acordeón si está colapsado
            const accordionHeader = fieldWrapper.closest('.accordion-section')?.querySelector('.accordion-header');
            if (accordionHeader) {
                const accordionContent = accordionHeader.nextElementSibling?.querySelector('.accordion-content');
                if (accordionContent && !accordionContent.offsetParent) {
                    // El acordeón está colapsado, hacer click para expandir
                    accordionHeader.click();
                    // Esperar un poco más para que se expanda
                    setTimeout(() => {
                        fieldWrapper.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'center',
                            inline: 'nearest'
                        });
                    }, 300);
                } else {
                    fieldWrapper.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center',
                        inline: 'nearest'
                    });
                }
            } else {
                fieldWrapper.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center',
                    inline: 'nearest'
                });
            }
            
            // También intentar hacer focus en el input si es posible
            if (firstErrorElement.tagName === 'INPUT' || 
                firstErrorElement.tagName === 'TEXTAREA' || 
                firstErrorElement.tagName === 'SELECT') {
                setTimeout(() => {
                    firstErrorElement.focus();
                }, 500);
            }
        }
    }, 100);
};

// Función para actualizar las imágenes desde el componente
const updateImages = (images) => {
    selectedImages.value = images;
};

// Función para abrir el modal de creación de institución
const openCreateInstitutionModal = () => {
    showCreateInstitutionModal.value = true;
};

// Función para cerrar el modal de creación de institución
const closeCreateInstitutionModal = () => {
    showCreateInstitutionModal.value = false;
};

const openCreateExecutiveModal = () => {
    showCreateExecutiveModal.value = true;
};

const closeCreateExecutiveModal = () => {
    showCreateExecutiveModal.value = false;
};

// Función para manejar la creación de una nueva institución
const handleInstitutionCreated = (newInstitution) => {
    // Agregar la nueva institución a la lista local
    localInstitutions.value.push(newInstitution);
    // Actualizar el ID de la institución en el formulario
    paymentData.value.institution_id = newInstitution.id;
    // Cerrar el modal
    closeCreateInstitutionModal();
};

const handleExecutiveCreated = (newExecutive) => {
    localSalesExecutives.value.push(newExecutive);
    paymentData.value.sales_executive_id = newExecutive.id;
    closeCreateExecutiveModal();
};

const submit = () => {
    // Validar el formulario
    if (!validateForm()) {
        // Hacer scroll al primer error
        scrollToFirstError();
        return;
    }

    // Convertir sales_executive_id a entero si no está vacío
    if (form.sales_executive_id && form.sales_executive_id !== '') {
        form.sales_executive_id = parseInt(form.sales_executive_id);
    } else {
        form.sales_executive_id = null;
    }

    // Debug: Log de los datos que se van a enviar
    console.log('Datos que se van a enviar al backend:', {
        payment_options: form.payment_options,
        full_payment_options: form.full_payment_options,
        lat90_payment_options: form.lat90_payment_options,
        sales_executive_id: form.sales_executive_id,
        form_data: form.data()
    });

    // Establecer created_by
    form.created_by = null; // Se establecerá en el backend con auth()->id()

    // Pilares fijos siempre
    form.pilar_1 = 'Aventura';
    form.pilar_2 = 'Entretenimiento';
    form.pilar_3 = 'Educación';
    form.pilar_4 = 'Seguridad';

    form.post(route("admin.programs.store"));
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
