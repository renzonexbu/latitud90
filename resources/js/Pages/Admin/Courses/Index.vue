<template>
    <AdminLayout>
        <Head title="Gestión de Cursos" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <CoursesHeader
                    subtitle="Visualización de cursos"
                    :show-create-button="true"
                    @create-course="openCreateModal"
                />

                <!-- Filters -->
                <div
                    class="bg-white overflow-hidden shadow-sm rounded-[20px] mb-6 p-6"
                >
                    <CoursesFilters
                        :initial-filters="filters"
                        @filters-changed="handleFiltersChanged"
                    />
                </div>

                <!-- Courses List -->
                <div v-if="courses.length === 0 && sampleCourses.length === 0" class="bg-white overflow-hidden shadow-sm rounded-[20px] p-6">
                    <div class="text-center py-12">
                        <div class="text-gray-400 mb-4">
                            <BackpackIcon class="w-16 h-16 mx-auto" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            No hay cursos disponibles
                        </h3>
                        <p class="text-gray-600 mb-6">
                            Comienza creando tu primer curso para mostrar aquí.
                        </p>
                        <button
                            @click="openCreateModal"
                            class="bg-turquesa hover:bg-turquesa-dark text-white px-6 py-3 rounded-lg font-medium transition-colors inline-flex items-center gap-2"
                        >
                            <CourseIcon stroke-color="white" class="w-5 h-5" />
                            Agregar nuevo curso
                        </button>
                    </div>
                </div>

                <!-- Courses Table -->
                <div v-else class="space-y-6">
                    <CoursesTable 
                        :courses="sampleCourses" 
                        @edit-course="handleEditCourse"
                    />
                    
                    <!-- Pagination -->
                    <CoursesPagination
                        :current-page="1"
                        :total-courses="sampleCourses.length"
                        :courses-per-page="6"
                        @page-changed="handlePageChanged"
                    />
                </div>
            </div>
        </div>

        <!-- Create Course Modal -->
        <CreateCourseModal 
            :show="showCreateModal" 
            @close="closeCreateModal" 
        />
    </AdminLayout>
</template>

<script>
import { Head, Link, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import CoursesHeader from "@/Components/Courses/CoursesHeader.vue";
import CoursesFilters from "@/Components/Courses/CoursesFilters.vue";
import CoursesPagination from "@/Components/Courses/CoursesPagination.vue";
import CoursesTable from "@/Components/Courses/CoursesTable.vue";
import CreateCourseModal from "./Create.vue";
import { BackpackIcon, CourseIcon } from "@/Components/Icons";
import _ from "lodash";

export default {
    name: "CoursesIndex",
    components: {
        Head,
        Link,
        AdminLayout,
        CoursesHeader,
        CoursesFilters,
        CoursesPagination,
        CoursesTable,
        CreateCourseModal,
        BackpackIcon,
        CourseIcon,
    },
    props: {
        courses: {
            type: Array,
            default: () => [],
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            showCreateModal: false
        };
    },
    computed: {
        sampleCourses() {
            // Datos de ejemplo basados en el Figma
            return [
                {
                    id: 1,
                    institution: 'Pedro de Valdivia Providencia',
                    level: 'Preescolar',
                    grade: '5',
                    shift: 'Tarde',
                    year: '2025',
                    program: 'Ruta de lagos',
                    destination: 'Bariloche Arg- CL',
                    students: '20',
                    status: 'active',
                    percentage: 20,
                    totalCollected: '4.000 USD'
                },
                {
                    id: 2,
                    institution: 'Pedro de Valdivia Providencia',
                    level: 'Primaria',
                    grade: '6',
                    shift: 'Mañana',
                    year: '2025',
                    program: 'Ruta de lagos',
                    destination: 'Bariloche Arg- CL',
                    students: '20',
                    status: 'active',
                    percentage: 90,
                    totalCollected: '20.000 USD'
                },
                {
                    id: 3,
                    institution: 'Pedro de Valdivia Providencia',
                    level: 'Secundaria',
                    grade: '1',
                    shift: 'Mañana',
                    year: '2023',
                    program: 'Ruta de lagos',
                    destination: 'Bariloche Arg- CL',
                    students: '20',
                    status: 'completed',
                    percentage: 100,
                    totalCollected: '20.000 USD'
                },
                {
                    id: 4,
                    institution: 'Pedro de Valdivia Providencia',
                    level: 'Primaria',
                    grade: '5',
                    shift: 'Tarde',
                    year: '2025',
                    program: 'Ruta de lagos',
                    destination: 'Bariloche Arg- CL',
                    students: '20',
                    status: 'active',
                    percentage: 80,
                    totalCollected: '18.000 USD'
                },
                {
                    id: 5,
                    institution: 'Pedro de Valdivia Providencia',
                    level: 'Preescolar',
                    grade: '4',
                    shift: 'Tarde',
                    year: '2025',
                    program: 'Ruta de lagos',
                    destination: 'Bariloche Arg- CL',
                    students: '20',
                    status: 'active',
                    percentage: 20,
                    totalCollected: '4.000 USD'
                },
                {
                    id: 6,
                    institution: 'Pedro de Valdivia Providencia',
                    level: 'Preescolar',
                    grade: '2',
                    shift: 'Tarde',
                    year: '2025',
                    program: 'Ruta de lagos',
                    destination: 'Bariloche Arg- CL',
                    students: '20',
                    status: 'active',
                    percentage: 100,
                    totalCollected: '24.000 USD'
                },
                {
                    id: 7,
                    institution: 'Pedro de Valdivia Providencia',
                    level: 'Preescolar',
                    grade: '1',
                    shift: 'Mañana',
                    year: '2025',
                    program: 'Ruta de lagos',
                    destination: 'Bariloche Arg- CL',
                    students: '20',
                    status: 'active',
                    percentage: 0,
                    totalCollected: '4.000 USD'
                }
            ];
        }
    },
    methods: {
        handleFiltersChanged(newFilters) {
            router.get(route("admin.courses.index"), newFilters, {
                preserveState: true,
                replace: true,
            });
        },
        handlePageChanged(page) {
            router.get(route("admin.courses.index"), { 
                ...this.filters, 
                page 
            }, {
                preserveState: true,
                replace: true,
            });
        },
        handleEditCourse(courseId) {
            // Navigate to edit course page
            router.visit(route("admin.courses.edit", courseId));
        },
        openCreateModal() {
            this.showCreateModal = true;
        },
        closeCreateModal() {
            this.showCreateModal = false;
        },
    },
};
</script>
