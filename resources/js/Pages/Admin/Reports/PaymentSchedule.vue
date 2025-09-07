<template>
    <AdminLayout>
        <Head title="Cronograma de Recuperación de Cuotas" />

        <div class="py-12">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Header -->
                <ReportsHeader
                    title="Cronograma de Recuperación de Cuotas"
                    subtitle="Cuotas pactadas diariamente, vencimientos futuros y cuotas por cobrar en período determinado"
                />

                <!-- Resumen General por Ejecutivo y Programa -->
                <PaymentScheduleSummary
                    :executive-summary="executiveSummary"
                    :sales-executives="salesExecutives"
                    :programs="programs"
                    :filters="filters"
                    @update-filter="updateFilter"
                    @view-details="openMonthDetailModal"
                />




            </div>
        </div>

        <!-- Modal de Detalles -->
        <div
            v-if="showDetailModal"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        >
            <div
                class="relative top-20 mx-auto p-5 border w-11/12 max-w-6xl shadow-lg rounded-md bg-white"
            >
                <div class="mt-3">
                    <!-- Header del Modal -->
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-900">
                            Detalles de Cuota -
                            {{ selectedSchedule?.participant_name }}
                        </h3>
                        <button
                            @click="closeDetailModal"
                            class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
                        >
                            &times;
                        </button>
                    </div>

                    <div v-if="selectedSchedule" class="space-y-6">
                        <!-- Información del Participante y Programa -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div>
                                <h4
                                    class="text-lg font-semibold text-gray-900 mb-3"
                                >
                                    Información del Participante
                                </h4>
                                <div class="space-y-2">
                                    <p>
                                        <span class="font-medium">Nombre:</span>
                                        {{ selectedSchedule.participant_name }}
                                    </p>
                                    <p>
                                        <span class="font-medium"
                                            >Documento:</span
                                        >
                                        {{ selectedSchedule.participant_document }}
                                    </p>
                                </div>
                            </div>
                            <div>
                                <h4
                                    class="text-lg font-semibold text-gray-900 mb-3"
                                >
                                    Información del Apoderado
                                </h4>
                                <div class="space-y-2">
                                    <p>
                                        <span class="font-medium">Nombre:</span>
                                        {{ selectedSchedule.apoderado_name || 'N/A' }}
                                    </p>
                                    <p>
                                        <span class="font-medium">Email:</span>
                                        {{ selectedSchedule.apoderado_email || 'N/A' }}
                                    </p>
                                    <p>
                                        <span class="font-medium">Teléfono:</span>
                                        {{ selectedSchedule.apoderado_phone || 'N/A' }}
                                    </p>
                                </div>
                            </div>
                            <div>
                                <h4
                                    class="text-lg font-semibold text-gray-900 mb-3"
                                >
                                    Información de la Cuota
                                </h4>
                                <div class="space-y-2">
                                    <p>
                                        <span class="font-medium"
                                            >Programa:</span
                                        >
                                        {{ selectedSchedule.program_name }}
                                    </p>
                                    <p>
                                        <span class="font-medium"
                                            >Fecha de Salida:</span
                                        >
                                        {{
                                            formatDate(
                                                selectedSchedule.program_departure_date
                                            )
                                        }}
                                    </p>
                                    <p>
                                        <span class="font-medium"
                                            >N° Cuota:</span
                                        >
                                        {{
                                            selectedSchedule.installment_number
                                        }}
                                    </p>
                                    <p>
                                        <span class="font-medium"
                                            >Fecha Vencimiento:</span
                                        >
                                        {{
                                            formatDate(
                                                selectedSchedule.due_date
                                            )
                                        }}
                                    </p>
                                    <p>
                                        <span class="font-medium">Estado:</span>
                                        <span
                                            :class="
                                                getStatusClass(
                                                    selectedSchedule.status
                                                )
                                            "
                                            class="px-2 py-1 text-xs font-semibold rounded-full"
                                        >
                                            {{
                                                getStatusLabel(
                                                    selectedSchedule.status
                                                )
                                            }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div>
                                <h4
                                    class="text-lg font-semibold text-gray-900 mb-3"
                                >
                                    Agente Comercial
                                </h4>
                                <div class="space-y-2">
                                    <p>
                                        <span class="font-medium">Nombre:</span>
                                        {{
                                            selectedSchedule.sales_executive_name ||
                                            "No asignado"
                                        }}
                                    </p>
                                    <p
                                        v-if="
                                            selectedSchedule.sales_executive_email
                                        "
                                    >
                                        <span class="font-medium">Email:</span>
                                        {{
                                            selectedSchedule.sales_executive_email
                                        }}
                                    </p>
                                    <p
                                        v-if="
                                            selectedSchedule.sales_executive_phone
                                        "
                                    >
                                        <span class="font-medium"
                                            >Teléfono:</span
                                        >
                                        {{
                                            selectedSchedule.sales_executive_phone
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Información Financiera -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4
                                class="text-lg font-semibold text-gray-900 mb-4"
                            >
                                Información Financiera
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Monto Base
                                    </p>
                                    <p class="text-xl font-bold text-gray-900">
                                        ${{
                                            formatCurrency(
                                                selectedSchedule.base_amount ||
                                                    0
                                            )
                                        }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Descuento
                                    </p>
                                    <p class="text-xl font-bold text-red-600">
                                        -${{
                                            formatCurrency(
                                                selectedSchedule.discount_amount ||
                                                    0
                                            )
                                        }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Monto Cuota
                                    </p>
                                    <p class="text-xl font-bold text-blue-600">
                                        ${{
                                            formatCurrency(
                                                selectedSchedule.amount || 0
                                            )
                                        }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Monto Pagado
                                    </p>
                                    <p class="text-xl font-bold text-green-600">
                                        ${{
                                            formatCurrency(
                                                selectedSchedule.paid_amount ||
                                                    0
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>

                            <!-- Días de Vencimiento -->
                            <div class="mt-4 text-center">
                                <p class="text-sm text-gray-600">
                                    Días de Vencimiento
                                </p>
                                <p
                                    class="text-2xl font-bold"
                                    :class="
                                        getDaysClass(
                                            selectedSchedule.days_overdue
                                        )
                                    "
                                >
                                    {{
                                        selectedSchedule.days_overdue || 0
                                    }}
                                    días
                                </p>
                            </div>

                            <!-- Información de la Orden -->
                            <div
                                class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4"
                            >
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        N° Orden
                                    </p>
                                    <p class="text-lg font-bold text-gray-900">
                                        {{ selectedSchedule.order_number }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Monto Total Orden
                                    </p>
                                    <p class="text-lg font-bold text-gray-900">
                                        ${{
                                            formatCurrency(
                                                selectedSchedule.order_final_amount ||
                                                    0
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones del Modal -->
                    <div class="flex justify-end space-x-3 mt-6">
                        <button
                            @click="closeDetailModal"
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>



        <!-- Modal de Detalles del Mes -->
        <PaymentScheduleDetailModal
            :show="showMonthDetailModal"
            :detail-filters="monthDetailFilters"
            @close="closeMonthDetailModal"
        />
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import { Head, router, Link } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import ReportsHeader from "@/Components/Reports/ReportsHeader.vue";
import PaymentScheduleTable from "@/Components/Reports/PaymentSchedule/PaymentScheduleTable.vue";
import PaymentScheduleSummary from "@/Components/Reports/PaymentSchedule/PaymentScheduleSummary.vue";
import PaymentScheduleDetailModal from "@/Components/Reports/PaymentSchedule/PaymentScheduleDetailModal.vue";
import ReportsPagination from "@/Components/Reports/ReportsPagination.vue";

const props = defineProps({
    paymentSchedules: {
        type: Object,
        default: () => ({}),
    },
    programs: {
        type: Array,
        default: () => [],
    },
    salesExecutives: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
    executiveSummary: {
        type: Array,
        default: () => [],
    },
});

const filters = reactive({
    programId: props.filters.programId || "",
    salesExecutiveId: props.filters.salesExecutiveId || "",
    status: props.filters.status || "",
    dateFrom: props.filters.dateFrom || "",
    dateTo: props.filters.dateTo || "",
});

// Estado del modal de detalle individual
const showDetailModal = ref(false);
const selectedSchedule = ref(null);

// Estado del modal de detalles por mes
const showMonthDetailModal = ref(false);
const monthDetailFilters = ref({});

// Estado de paginación
const currentPage = ref(1);
const schedulesPerPage = ref(10);



// Establecer fechas por defecto solo si el usuario las especifica
onMounted(() => {
    // No establecer fechas por defecto automáticamente
    // Esto permite que se muestren todos los datos al inicio
});

// Obtener todos los schedules (sin paginación) para el modal
const allSchedules = computed(() => {
    const schedules = props.paymentSchedules;

    // Si es un objeto de paginación (del backend), usar data directamente
    if (schedules && schedules.data && Array.isArray(schedules.data)) {
        return schedules.data;
    }

    // Si es un array simple, retornarlo completo
    if (Array.isArray(schedules)) {
        return schedules;
    }

    // Si no es ninguno de los anteriores, retornar array vacío
    return [];
});

const updateFilter = (key, value) => {
    filters[key] = value;
    // Aplicar filtros al backend
    router.get("/admin/reports/payment-schedule", filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Función para abrir modal de detalles del mes
const openMonthDetailModal = (detailFilters) => {
    monthDetailFilters.value = detailFilters;
    showMonthDetailModal.value = true;
};

// Función para cerrar modal de detalles del mes
const closeMonthDetailModal = () => {
    showMonthDetailModal.value = false;
    monthDetailFilters.value = {};
};



const openDetailModal = (schedule) => {
    selectedSchedule.value = schedule;
    showDetailModal.value = true;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedSchedule.value = null;
};



const handlePageChange = (page) => {
    // Actualizar los filtros con la nueva página
    filters.page = page;

    // Aplicar filtros al backend con la nueva página
    router.get("/admin/reports/payment-schedule", filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("es-CL");
};

const getStatusClass = (status) => {
    const classes = {
        pending: "bg-yellow-100 text-yellow-800",
        overdue: "bg-red-100 text-red-800",
        paid: "bg-green-100 text-green-800",
        upcoming: "bg-blue-100 text-blue-800",
    };
    return classes[status] || "bg-gray-100 text-gray-800";
};

const getStatusLabel = (status) => {
    const labels = {
        pending: "Pendiente",
        overdue: "Vencida",
        paid: "Pagada",
        upcoming: "Próxima",
    };
    return labels[status] || status;
};

const getDaysClass = (days) => {
    if (!days || days <= 0) return "text-gray-500";
    if (days <= 7) return "text-yellow-600";
    if (days <= 30) return "text-orange-600";
    return "text-red-600";
};



const formatCurrency = (value) => {
    // Convertir a número y redondear para evitar decimales
    const numericValue = Math.round(Number(value) || 0);
    // Formatear solo el número sin el símbolo de moneda, ya que lo agregamos manualmente
    return numericValue.toLocaleString("es-CL");
};

const formatRut = (rut) => {
    if (!rut) return "N/A";
    
    // Limpiar el RUT de puntos y guiones
    let rutLimpio = rut.toString().replace(/\./g, "").replace(/-/g, "");
    
    if (rutLimpio.length < 2) return rut;
    
    // Separar número y dígito verificador
    let dv = rutLimpio.slice(-1);
    let numero = rutLimpio.slice(0, -1);
    
    // Formatear número con puntos
    let numeroFormateado = numero.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    
    // Retornar RUT formateado
    return `${numeroFormateado}-${dv.toUpperCase()}`;
};


</script>
