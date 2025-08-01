<template>
    <AdminLayout>
        <Head title="Gestión de Cursos" />

        <div class="py-12">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8">
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
                <div v-if="(!courses.data && courses.length === 0) || (courses.data && courses.data.length === 0)" class="bg-white overflow-hidden shadow-sm rounded-[20px] p-6">
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
                        :courses="courses.data || (Array.isArray(courses) ? courses : [])" 
                        @edit-course="handleEditCourse"
                    />
                    
                    <!-- Pagination -->
                    <CoursesPagination
                        :current-page="courses.current_page || 1"
                        :total-courses="courses.total || courses.length"
                        :courses-per-page="courses.per_page || 10"
                        @page-changed="handlePageChanged"
                    />
                </div>
            </div>
        </div>

        <!-- Create Course Modal -->
        <CreateCourseModal 
            :show="showCreateModal" 
            :programs="programs"
            :errors="errors"
            @close="closeCreateModal" 
        />

        <!-- Success Message -->
        <div v-if="$page.props.flash.success" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                {{ $page.props.flash.success }}
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
import CreateCourseModal from "./Create.vue";
import { BackpackIcon, CourseIcon } from "@/Components/Icons";
import _ from "lodash";

export default {
    name: "CoursesIndex",
    setup() {
        const $page = usePage();
        return { $page };
    },
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
        programs: {
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
            showCreateModal: false
        };
    },
    mounted() {
        console.log('Courses data:', this.courses);
        console.log('Courses type:', typeof this.courses);
        console.log('Courses length:', this.courses?.length);
        console.log('Courses data property:', this.courses?.data);
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
        closeSuccessMessage() {
            // Clear the flash message
            this.$page.props.flash.success = null;
        },
    },
};
</script>
