<template>
    <AdminLayout>
        <Head title="Gestión de Participantes" />

        <!-- Sistema de Alertas -->
        <AlertWrapper ref="alertWrapper" />

        <div class="py-12">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8">

                <!-- Header -->
                <ParticipantsHeader
                    subtitle="Visualización de participantes"
                    :show-create-button="true"
                    @create-participant="openCreateModal"
                />

                <!-- Filtros y Botón Exportar -->
                <div class="bg-white overflow-hidden shadow-sm rounded-[20px] mb-6 p-6">
                    <div class="flex justify-between items-start gap-4">
                        <div class="flex-1">
                            <ParticipantsFilters
                                :initial-filters="localFilters"
                                :participants="flattenedParticipantsData"
                                @filters-changed="handleFiltersChanged"
                            />
                        </div>
                        <button
                            @click="showExportModal = true"
                            class="flex-shrink-0 inline-flex items-center px-6 py-3 bg-[#007e93] hover:bg-[#006b7a] text-white text-sm font-bold rounded-lg transition-colors shadow-sm"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Exportar
                        </button>
                    </div>
              </div>

                <!-- Participants List -->
                <div v-if="filteredParticipants.data.length === 0" 
                     class="bg-white overflow-hidden shadow-sm rounded-[20px] p-6">
                    <div class="text-center py-12">
                        <div class="text-gray-400 mb-4">
                            <PersonsIcon class="w-16 h-16 mx-auto" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            No hay participantes disponibles
                        </h3>
                        <p class="text-gray-600 mb-6">
                            Comienza agregando participantes para mostrar aquí.
                        </p>
                        <button
                            @click="openCreateModal"
                            class="bg-turquesa hover:bg-turquesa-dark text-white px-6 py-3 rounded-lg font-medium transition-colors inline-flex items-center gap-2"
                        >
                            <PersonsIcon stroke-color="white" class="w-5 h-5" />
                            Agregar nuevo participante
                        </button>
                      </div>
            </div>

                <!-- Participants Table -->
                <div v-else class="space-y-6">
                    <div class="bg-white rounded-[20px] p-0 overflow-hidden">
                        <ParticipantsTable 
                            :participants="filteredParticipants.data" 
                            @edit-participant="handleEditParticipant"
                        />
                        
                        <!-- Pagination -->
                        <div class="p-4">
                            <ParticipantsPagination
                                :current-page="filteredParticipants.current_page || 1"
                                :total-participants="filteredParticipants.total || filteredParticipants.length"
                                :participants-per-page="filteredParticipants.per_page || 10"
                                @page-changed="handlePageChanged"
                            />
            </div>
          </div>
        </div>
                
      </div>
    </div>

        <!-- Create Participant Modal -->
        <CreateParticipantModal
            :show="showCreateModal"
            :courses="courses"
            :institutions="institutions"
            :programs="programs"
            :document-types="documentTypes"
            :errors="errors"
            @close="closeCreateModal"
        />

        <!-- Export Modal -->
        <Modal :show="showExportModal" @close="closeExportModal">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    Exportar Participantes
                </h2>

                <p class="text-gray-600 mb-6">
                    Selecciona el formato y el filtro de participantes que deseas exportar:
                </p>

                <!-- Export Options Grid -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <!-- Excel Column -->
                    <div class="space-y-3">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Excel (.xlsx)
                        </h3>
                        <a
                            :href="route('admin.participants.export.excel', { status: 'all' })"
                            class="block w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors text-center"
                        >
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                            </svg>
                            Todos los Participantes
                        </a>
                        <a
                            :href="route('admin.participants.export.excel', { status: 'active' })"
                            class="block w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors text-center"
                        >
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                            </svg>
                            Solo Activos
                        </a>
                        <a
                            :href="route('admin.participants.export.excel', { status: 'inactive' })"
                            class="block w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors text-center"
                        >
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                            </svg>
                            Solo Inactivos
                        </a>
                    </div>

                    <!-- CSV Column -->
                    <div class="space-y-3">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            CSV (.csv)
                        </h3>
                        <a
                            :href="route('admin.participants.export.csv', { status: 'all' })"
                            class="block w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors text-center"
                        >
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                            </svg>
                            Todos los Participantes
                        </a>
                        <a
                            :href="route('admin.participants.export.csv', { status: 'active' })"
                            class="block w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors text-center"
                        >
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                            </svg>
                            Solo Activos
                        </a>
                        <a
                            :href="route('admin.participants.export.csv', { status: 'inactive' })"
                            class="block w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors text-center"
                        >
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3" />
                            </svg>
                            Solo Inactivos
                        </a>
                    </div>
                </div>

                <!-- Close Button -->
                <div class="flex justify-end">
                    <button
                        type="button"
                        @click="closeExportModal"
                        class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#007e93]"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </Modal>
  </AdminLayout>
</template>

<script>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
  import AdminLayout from "@/Layouts/AdminLayout.vue";
import ParticipantsHeader from "@/Components/Participants/ParticipantsHeader.vue";
import ParticipantsFilters from "@/Components/Participants/ParticipantsFilters.vue";
import ParticipantsPagination from "@/Components/Participants/ParticipantsPagination.vue";
import ParticipantsTable from "@/Components/Participants/ParticipantsTable.vue";
import CreateParticipantModal from "./Create.vue";
import Modal from "@/Components/Modal.vue";
import { PersonsIcon } from "@/Components/Icons";
import AlertWrapper from "@/Components/Admin/AlertWrapper.vue";
import _ from "lodash";

export default {
    name: "ParticipantsIndex",
    setup() {
        const page = usePage();
        return { page };
    },
    components: {
        Head,
        Link,
        AdminLayout,
        ParticipantsHeader,
        ParticipantsFilters,
        ParticipantsTable,
        ParticipantsPagination,
        CreateParticipantModal,
        Modal,
        PersonsIcon,
        AlertWrapper,
    },
    props: {
        participants: {
            type: Object,
            default: () => ({ data: [] }),
        },
        enrollments: {
            type: [Array, Object],
            default: () => [],
        },
        allParticipants: {
            type: Array,
            default: () => [],
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
        courses: {
            type: Array,
            default: () => [],
        },
        institutions: {
            type: Array,
            default: () => [],
        },
        programs: {
            type: Array,
            default: () => [],
        },
        documentTypes: {
            type: Array,
            default: () => [],
        },
        errors: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            showCreateModal: false,
            showExportModal: false,
            currentPage: 1,
            localFilters: {
                search: "",
                institution: "",
                level: "",
                course_number: "",
                paymentStatus: "",
                active: "",
            }
        };
    },
    computed: {
        allParticipantsData() {
            return this.allParticipants || [];
        },
        // Lista plana de participantes repetidos por cada programa (inscripción)
        flattenedParticipantsData() {
            // Debug logging removed for production
            
            // Error logging for enrollment issues
            if (this.enrollments && this.enrollments.length > 0 && typeof this.enrollments[0].is_active === 'undefined') {
                console.error('❌ Enrollment is_active is undefined for enrollment:', this.enrollments[0].id);
            }
            
            // Preferir inscripciones del backend si están presentes (array o paginado con data)
            let enrollments = [];
            if (Array.isArray(this.enrollments) && this.enrollments.length > 0) {
                enrollments = this.enrollments;
            } else if (this.enrollments && Array.isArray(this.enrollments.data)) {
                enrollments = this.enrollments.data;
            } else {
                // Derivar desde allParticipants.courses
                // Sin dataset desde backend, mantener listado vacío para no inventar datos inconsistentes
                enrollments = [];
            }
            
            // Debug logging removed for production
            
            // Mapear al formato que la tabla espera: un objeto de participante por fila
            const result = enrollments.map((enr) => ({
                id: enr.participant_id,
                first_last_name: enr.participant?.first_last_name ?? enr.first_last_name ?? '',
                second_last_name: enr.participant?.second_last_name ?? enr.second_last_name ?? '',
                first_name: enr.participant?.first_name ?? enr.first_name ?? '',
                second_name: enr.participant?.second_name ?? enr.second_name ?? '',
                document_number: enr.participant?.document_number ?? enr.document_number ?? '',
                document_type: enr.participant?.document_type ?? enr.document_type ?? 'RUT',
                phone: enr.participant?.phone,
                code_phone: enr.participant?.code_phone,
                is_active: enr.is_active, // Ahora viene directamente del backend
                courses: [
                    {
                        institution: { name: enr.institution_name || null },
                        education_level: enr.education_level || null,
                        course_number: enr.course_number || null,
                        program: enr.program_id ? { id: enr.program_id, code: enr.program_code, name: enr.program_name, destination: enr.program_destination, year: enr.program_year } : null,
                        pivot: {
                            individual_price: enr.total_due ?? 0,
                            price_adjustments: 0,
                            status: enr.status || 'pending_payment',
                        },
                    },
                ],
                __paid_amount: enr.paid_amount ?? 0,
                __total_due: enr.total_due ?? 0,
                __discounts: enr.discounts ?? 0,
                __contribution: enr.contribution ?? 0,
                __released_amount: enr.released_amount ?? 0,
                __program_code: enr.program_code ?? '',
                __enrollment_code: enr.enrollment_code ?? '',
            }));
            
            return result;
        },
        filteredParticipants() {
            let filtered = this.flattenedParticipantsData;

            // Filtro de búsqueda
            if (this.localFilters.search) {
                const searchTerm = this.localFilters.search.toLowerCase();
                // Normalizar el término de búsqueda para RUT (eliminar puntos y guiones)
                const normalizedSearchTerm = searchTerm.replace(/[.\-]/g, '');

                filtered = filtered.filter(participant => {
                    // Normalizar el document_number del participante para comparación de RUT
                    const normalizedDocNumber = (participant.document_number || '').toLowerCase().replace(/[.\-]/g, '');
                    // Obtener código de inscripción y código del programa
                    const enrollmentCode = (participant.__enrollment_code || '').toLowerCase();
                    const programCode = (participant.__program_code || '').toLowerCase();

                    return (
                        participant.first_name?.toLowerCase().includes(searchTerm) ||
                        participant.second_name?.toLowerCase().includes(searchTerm) ||
                        participant.first_last_name?.toLowerCase().includes(searchTerm) ||
                        participant.second_last_name?.toLowerCase().includes(searchTerm) ||
                        // Buscar por document_number: tanto con formato como sin formato
                        participant.document_number?.toLowerCase().includes(searchTerm) ||
                        normalizedDocNumber.includes(normalizedSearchTerm) ||
                        this.getFirstCourseInfo(participant, 'institution', 'name')?.toLowerCase().includes(searchTerm) ||
                        // Buscar por código de inscripción (contiene)
                        enrollmentCode.includes(searchTerm) ||
                        // Buscar por código de programa (contiene)
                        programCode.includes(searchTerm)
                    );
                });
            }

            // Filtro por institución
            if (this.localFilters.institution) {
                filtered = filtered.filter(participant => 
                    this.getFirstCourseInfo(participant, 'institution', 'name') === this.localFilters.institution
                );
            }

            // Filtro por nivel educativo
            if (this.localFilters.level) {
                filtered = filtered.filter(participant => {
                    const firstCourse = this.getFirstCourse(participant);
                    return firstCourse?.education_level === this.localFilters.level;
                });
            }

            // Filtro por curso (course_number)
            if (this.localFilters.course_number) {
                filtered = filtered.filter(participant => {
                    const firstCourse = this.getFirstCourse(participant);
                    return String(firstCourse?.course_number || '') === String(this.localFilters.course_number);
                });
            }

            // Filtro por estado de pago
            if (this.localFilters.paymentStatus) {
                filtered = filtered.filter(participant => 
                    this.getFirstCoursePivotStatus(participant) === this.localFilters.paymentStatus
                );
            }

            // Filtro por estado activo/inactivo
            if (this.localFilters.active !== '') {
                filtered = filtered.filter(participant => {
                    // Convertir el valor del participante a booleano
                    const participantActive = Boolean(participant.is_active);
                    
                    // Convertir el valor del filtro a booleano
                    let filterActive;
                    if (this.localFilters.active === true || this.localFilters.active === 'true') {
                        filterActive = true;
                    } else if (this.localFilters.active === false || this.localFilters.active === 'false') {
                        filterActive = false;
                    } else {
                        filterActive = null; // No debería llegar aquí
                    }
                    
                    const matches = participantActive === filterActive;
                    
                    return matches;
                });
            }
            
            // Paginación
            const perPage = 10;
            const startIndex = (this.currentPage - 1) * perPage;
            const endIndex = startIndex + perPage;
            const paginatedData = filtered.slice(startIndex, endIndex);

            return {
                data: paginatedData,
                current_page: this.currentPage,
                total: filtered.length,
                per_page: perPage,
                last_page: Math.ceil(filtered.length / perPage)
            };
        }
    },
    mounted() {
        // Abrir modal de creación si viene desde acceso rápido del header
        try {
            const search = typeof window !== 'undefined' ? window.location.search : '';
            const params = new URLSearchParams(search);
            if (params.get('openCreate') === '1') {
                this.openCreateModal();
            }
        } catch (_) {}
        
        // Inicializar filtros desde el backend
        if (this.filters.active !== undefined) {
            this.localFilters.active = this.filters.active;
        }
    },
    methods: {
        handleFiltersChanged(newFilters) {
            this.localFilters = newFilters;
            this.currentPage = 1; // Resetear a la primera página cuando se cambian los filtros
        },
        handlePageChanged(page) {
            this.currentPage = page;
        },
        handleEditParticipant(participantId) {
            router.visit(route("admin.participants.edit", participantId));
        },
        openCreateModal() {
            this.showCreateModal = true;
        },
        closeCreateModal() {
            this.showCreateModal = false;
        },
        closeExportModal() {
            this.showExportModal = false;
        },
        
        // Métodos auxiliares para manejar la nueva estructura de cursos
        getFirstCourse(participant) {
            if (!participant.courses || participant.courses.length === 0) {
                return null;
            }
            return participant.courses[0];
        },
        
        getFirstCourseInfo(participant, relation, field) {
            const firstCourse = this.getFirstCourse(participant);
            if (!firstCourse || !firstCourse[relation]) {
                return null;
            }
            return firstCourse[relation][field];
        },
        
        getFirstCoursePivotStatus(participant) {
            const firstCourse = this.getFirstCourse(participant);
            if (!firstCourse || !firstCourse.pivot) {
                return 'pendiente_pago';
            }
            return firstCourse.pivot.status || 'pendiente_pago';
        },
    },
};
</script>

<style scoped>
.bg-turquesa {
    background-color: #007e93;
}

.bg-turquesa-dark {
    background-color: #006b7d;
}
</style>