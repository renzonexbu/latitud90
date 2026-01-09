<template>
    <AdminLayout>
        <Head title="Gestión de Programas - Cursos" />

        <!-- Sistema de Alertas -->
        <AlertWrapper ref="alertWrapper" />

        <div class="py-12">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8">

                <!-- Header -->
                <CoursesHeader
                    subtitle="Visualización de programas - cursos"
                    :show-create-button="true"
                    @create-course="openCreateModal"
                />

                <!-- Filters -->
                <div
                    class="bg-white overflow-hidden shadow-sm rounded-[20px] mb-6 p-6"
                >
                    <CoursesFilters
                        :initial-filters="localFilters"
                        :courses="allCourses"
                        @filters-changed="handleFiltersChanged"
                    />
                </div>

                <!-- Courses List -->
                <div v-if="(!filteredCourses.data && filteredCourses.length === 0) || (filteredCourses.data && filteredCourses.data.length === 0)" class="bg-white overflow-hidden shadow-sm rounded-[20px] p-6">
                    <div class="text-center py-12">
                        <div class="text-gray-400 mb-4">
                            <BackpackIcon class="w-16 h-16 mx-auto" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            No hay programas - cursos disponibles
                        </h3>
                        <p class="text-gray-600 mb-6">
                            Comienza creando tu primer programa - curso para mostrar aquí.
                        </p>
                        <button
                            @click="openCreateModal"
                            class="bg-turquesa hover:bg-turquesa-dark text-white px-6 py-3 rounded-lg font-medium transition-colors inline-flex items-center gap-2"
                        >
                            <CourseIcon stroke-color="white" class="w-5 h-5" />
                            Agregar nuevo programa - curso
                        </button>
                    </div>
                </div>

                <!-- Courses Table -->
                <div v-else class="space-y-6">
                    <CoursesTable 
                        :courses="filteredCourses.data || (Array.isArray(filteredCourses) ? filteredCourses : [])" 
                        @edit-course="handleEditCourse"
                    />
                    
                    <!-- Pagination -->
                    <CoursesPagination
                        :current-page="filteredCourses.current_page || 1"
                        :total-courses="filteredCourses.total || filteredCourses.length"
                        :courses-per-page="filteredCourses.per_page || 10"
                        @page-changed="handlePageChanged"
                    />
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

        <!-- Success Message -->
        <div v-if="page.props.flash.success" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                {{ page.props.flash.success }}
                <button @click="closeSuccessMessage" class="ml-2 hover:bg-green-600 rounded p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import CoursesHeader from "@/Components/Courses/CoursesHeader.vue";
import CoursesFilters from "@/Components/Courses/CoursesFilters.vue";
import CoursesPagination from "@/Components/Courses/CoursesPagination.vue";
import CoursesTable from "@/Components/Courses/CoursesTable.vue";
import CreateInstitutionModal from "@/Components/Institutions/CreateInstitutionModal.vue";
import { BackpackIcon, CourseIcon } from "@/Components/Icons";
import AlertWrapper from "@/Components/Admin/AlertWrapper.vue";
import _ from "lodash";

export default {
    name: "CoursesIndex",
    setup() {
        const page = usePage();
        return { page };
    },
    components: {
        Head,
        Link,
        AdminLayout,
        CoursesHeader,
        CoursesFilters,
        CoursesPagination,
        CoursesTable,
        CreateInstitutionModal,
        BackpackIcon,
        CourseIcon,
        AlertWrapper,
    },
    props: {
        courses: {
            type: [Array, Object],
            default: () => [],
        },
        allCourses: {
            type: [Array, Object],
            default: () => [],
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
        programs: {
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
            showCreateInstitutionModal: false,
            currentPage: 1,
            localFilters: {
                search: "",
                institution: "",
                level: "",
                course_number: "",
                year: "",
            },
        };
    },
    computed: {
        // Cursos filtrados
        filteredCourses() {
            let filtered = [...this.allCourses];
            
            // Filtro por búsqueda
            if (this.localFilters.search) {
                const searchTerm = this.localFilters.search.toLowerCase();
                filtered = filtered.filter(course => 
                    course.institution?.name?.toLowerCase().includes(searchTerm) ||
                    course.education_level?.toLowerCase().includes(searchTerm) ||
                    course.course_display?.toLowerCase().includes(searchTerm)
                );
            }
            
            // Filtro por institución
            if (this.localFilters.institution) {
                filtered = filtered.filter(course => 
                    course.institution?.name === this.localFilters.institution
                );
            }
            
            // Filtro por nivel educativo
            if (this.localFilters.level) {
                filtered = filtered.filter(course => 
                    course.education_level === this.localFilters.level
                );
            }
            
            // Filtro por curso (course_number)
            if (this.localFilters.course_number) {
                filtered = filtered.filter(course => 
                    String(course.course_number || '') === String(this.localFilters.course_number)
                );
            }
            
            // Filtro por año
            if (this.localFilters.year) {
                filtered = filtered.filter(course => 
                    course.year?.toString() === this.localFilters.year
                );
            }
            
            // Paginación
            const itemsPerPage = 10;
            const startIndex = (this.currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            
            return {
                data: filtered.slice(startIndex, endIndex),
                current_page: this.currentPage,
                total: filtered.length,
                per_page: itemsPerPage,
                last_page: Math.ceil(filtered.length / itemsPerPage),
            };
        },
    },
    mounted() {
        // Auto-redirect to create page if openCreate parameter is present
        try {
            const search = typeof window !== 'undefined' ? window.location.search : '';
            const params = new URLSearchParams(search);
            const shouldOpen = params.has('openCreate') && (params.get('openCreate') === '1' || params.get('openCreate') === 'true' || params.get('openCreate') === 'yes' || params.get('openCreate') === 'on' || params.get('openCreate') === '');
            if (shouldOpen) this.$nextTick(() => this.openCreateModal());
        } catch (_) {}
    },

    methods: {
        handleFiltersChanged(newFilters) {
            this.localFilters = newFilters;
            this.currentPage = 1; // Resetear a la primera página cuando se cambian los filtros
            // No hacemos router.get aquí para mantener todo interno
        },
        handlePageChanged(page) {
            this.currentPage = page;
            // No hacemos router.get aquí para mantener todo interno
        },
        handleEditCourse(courseId) {
            // Navigate to edit course page
            router.visit(route("admin.courses.edit", courseId));
        },
        openCreateModal() {
            // Navigate to create course page
            router.visit(route("admin.courses.create"));
        },
        closeSuccessMessage() {
            // Clear the flash message
            this.page.props.flash.success = null;
        },
        openCreateInstitutionModal() {
            this.showCreateInstitutionModal = true;
        },
        closeCreateInstitutionModal() {
            this.showCreateInstitutionModal = false;
        },
        handleInstitutionCreated(newInstitution) {
            // Add the new institution to the list
            this.institutions.push(newInstitution);
            // Close the modal
            this.closeCreateInstitutionModal();
            // Show success message
            this.page.props.flash.success = 'Institución creada exitosamente.';
        },
    },
};
</script>
