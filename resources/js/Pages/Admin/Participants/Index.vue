<template>
  <AdminLayout>
        <Head title="Gestión de Participantes" />

    <div class="py-12">
      <div class="max-w-full mx-auto sm:px-6 lg:px-8">
                <!-- Success Message -->
                <div v-if="$page.props.flash.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    <span class="block sm:inline">{{ $page.props.flash.success }}</span>
                </div>

                <!-- Header -->
                <ParticipantsHeader
                    subtitle="Visualización de participantes"
                    :show-create-button="true"
                    @create-participant="openCreateModal"
                />

                <!-- Filtros -->
                <div class="bg-white overflow-hidden shadow-sm rounded-[20px] mb-6 p-6">
                    <ParticipantsFilters
                        :initial-filters="localFilters"
                        :participants="flattenedParticipantsData"
                        @filters-changed="handleFiltersChanged"
                    />
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
            :errors="errors"
            @close="closeCreateModal" 
        />
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
import { PersonsIcon } from "@/Components/Icons";
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
        PersonsIcon,
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
        errors: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            showCreateModal: false,
            currentPage: 1,
            localFilters: {
                search: "",
                program: "",
                institution: "",
                level: "",
                course_number: "",
                paymentStatus: "",
                active: null,
            }
        };
    },
    computed: {
        allParticipantsData() {
            return this.allParticipants || [];
        },
        // Lista plana de participantes repetidos por cada programa (inscripción)
        flattenedParticipantsData() {
            console.log('🔍 Index.vue - Props recibidos:', {
                participants: this.participants,
                enrollments: this.enrollments,
                allParticipants: this.allParticipants
            });
            
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
            
            console.log('🔍 Index.vue - Enrollments procesados:', enrollments);
            
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
            }));
            
            console.log('🔍 Index.vue - Resultado final mapeado:', result);
            return result;
        },
        filteredParticipants() {
            let filtered = this.flattenedParticipantsData;

            // Filtro de búsqueda
            if (this.localFilters.search) {
                const searchTerm = this.localFilters.search.toLowerCase();
                filtered = filtered.filter(participant => 
                    participant.first_name?.toLowerCase().includes(searchTerm) ||
                    participant.second_name?.toLowerCase().includes(searchTerm) ||
                    participant.first_last_name?.toLowerCase().includes(searchTerm) ||
                    participant.second_last_name?.toLowerCase().includes(searchTerm) ||
                    participant.document_number?.toLowerCase().includes(searchTerm) ||
                    this.getFirstCourseInfo(participant, 'institution', 'name')?.toLowerCase().includes(searchTerm)
                );
            }

            // Filtro por programa
            if (this.localFilters.program) {
                filtered = filtered.filter(participant => 
                    this.getFirstCourseInfo(participant, 'program', 'name') === this.localFilters.program
                );
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

            // (turno eliminado del esquema)

            // Filtro por estado de pago
            if (this.localFilters.paymentStatus) {
                filtered = filtered.filter(participant => 
                    this.getFirstCoursePivotStatus(participant) === this.localFilters.paymentStatus
                );
            }

            // Filtro por estado activo/inactivo
            if (this.localFilters.active !== null) {
                filtered = filtered.filter(participant => 
                    participant.is_active === this.localFilters.active
                );
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
            // No hacemos router.get aquí para mantener todo interno
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