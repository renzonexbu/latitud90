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
                        :errors="errors"
                    />

                    <!-- Componente de Detalle Administrativo -->
                    <PaymentDetails 
                        v-model="paymentData" 
                        :errors="errors"
                        :institutions="localInstitutions"
                        @create-institution="openCreateInstitutionModal"
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
    </AdminLayout>
</template>

<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import ProgramDescription from "@/Components/Ecommerce/CreateProgramComponents/ProgramDescription.vue";
import PaymentDetails from "@/Components/Ecommerce/CreateProgramComponents/PaymentDetails.vue";
import CreateInstitutionModal from "@/Components/Institutions/CreateInstitutionModal.vue";

// Props
const props = defineProps({
    errors: {
        type: Object,
        default: () => ({})
    },
    institutions: {
        type: Array,
        default: () => []
    }
});

const form = useForm({
    // Campos del programa
    name: "",
    destination: "",
    departure_date: "",
    description: "",
    pilar_aventura: "",
    pilar_entretenimiento: "",
    pilar_educacion: "",
    pilar_seguridad: "",
    itinerary: "",
    itinerary_file: null,
    coverage_file: null,
    equipment_file: null,
    images: [],
    // Campos del detalle administrativo
    total_price: "",
    final_payment_date: "",
    sales_person: "",
    institution_name: "",
    institution_id: "",
    education_level: "",
    shift: "",
    grade: "",
    students_file: null,
    group_benefit: "",
    payment_option: "", // "full_payment" o "installments"
    full_payment_method: "",
    installments_payment_method: "",
    max_installments: "",
    active: true,
});

// Datos del programa que se sincronizan con el componente
const programData = ref({
    name: "",
    destination: "",
    departure_date: "",
    description: "",
    pilar_aventura: "",
    pilar_entretenimiento: "",
    pilar_educacion: "",
    pilar_seguridad: "",
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
    shift: "",
    grade: "",
    students_file: null,
    group_benefit: "",
    payment_option: "", // "full_payment" o "installments"
    full_payment_method: "",
    installments_payment_method: "",
    max_installments: "",
});

// Estado para las imágenes
const selectedImages = ref([]);

// Estado para el modal de creación de institución
const showCreateInstitutionModal = ref(false);

// Estado local para las instituciones (para poder modificarlas)
const localInstitutions = ref([...props.institutions]);

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

// Función para manejar la creación de una nueva institución
const handleInstitutionCreated = (newInstitution) => {
    // Agregar la nueva institución a la lista local
    localInstitutions.value.push(newInstitution);
    // Actualizar el ID de la institución en el formulario
    paymentData.value.institution_id = newInstitution.id;
    // Cerrar el modal
    closeCreateInstitutionModal();
};

const submit = () => {
    // Sincronizar los datos del programa con el formulario
    Object.keys(programData.value).forEach((key) => {
        form[key] = programData.value[key];
    });

    // Sincronizar los datos del detalle administrativo con el formulario
    Object.keys(paymentData.value).forEach((key) => {
        form[key] = paymentData.value[key];
    });

    // Agregar las imágenes al formulario
    const imageFiles = selectedImages.value.map((img) => img.file);
    form.images = imageFiles;

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
