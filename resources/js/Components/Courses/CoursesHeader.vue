<template>
    <div class="py-5 flex flex-row items-center justify-between relative">
        <!-- Left side - Breadcrumb and title -->
        <div class="flex flex-row gap-[14.37px] items-center justify-start flex-shrink-0 relative">
            <div class="text-[24px] leading-[28px] font-bold text-turquesa font-nexa-bold">
                Programas - Cursos
            </div>
            <div class="w-0 h-[29.59px] relative overflow-visible">
                <!-- Vector separator -->
                <div class="w-px h-full bg-gray-300"></div>
            </div>
            <div class="text-[16px] leading-[22px] font-normal text-[#5b5b5b] font-nexa-regular">
                {{ subtitle }}
            </div>
            
            <!-- Course info separator and details (only show if courseInfo is provided) -->
            <template v-if="courseInfo">
                <div class="w-0 h-[29.59px] relative overflow-visible">
                    <!-- Vector separator -->
                    <div class="w-px h-full bg-gray-300"></div>
                </div>
                <div class="text-[16px] leading-[22px] font-normal text-turquesa font-nexa-regular">
                    {{ courseInfo }}
                </div>
            </template>
        </div>

        <!-- Right side - Action buttons -->
        <div v-if="showCreateButton" class="flex flex-row items-center justify-end flex-shrink-0 relative gap-3">
            <!-- Management Buttons -->
            <Link
                :href="route('admin.executives.index')"
                class="bg-gray-600 hover:bg-gray-700 rounded-[112.89px] px-[18px] py-[14px] flex flex-row gap-[11.29px] items-center justify-center transition-colors"
                title="Gestionar Ejecutivos Comerciales"
            >
                <svg class="w-5 h-5" fill="white" viewBox="0 0 24 24">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
                <span class="text-white text-left font-nexa-bold text-sm leading-[22px] font-bold">
                    Ejecutivos
                </span>
            </Link>

            <Link
                :href="route('admin.institutions.index')"
                class="bg-gray-600 hover:bg-gray-700 rounded-[112.89px] px-[18px] py-[14px] flex flex-row gap-[11.29px] items-center justify-center transition-colors"
                title="Gestionar Instituciones"
            >
                <svg class="w-5 h-5" fill="white" viewBox="0 0 24 24">
                    <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
                </svg>
                <span class="text-white text-left font-nexa-bold text-sm leading-[22px] font-bold">
                    Instituciones
                </span>
            </Link>

            <!-- Create Course Button Container -->
            <div class="relative">
                <!-- Create Course Button -->
                <button
                    @click="handleCreateCourse"
                    class="bg-turquesa rounded-[112.89px] px-[18px] py-[14px] flex flex-row gap-[11.29px] items-center justify-center hover:bg-turquesa-dark transition-colors"
                >
                    <div class="flex-shrink-0 w-[21.52px] h-[21.52px] relative overflow-hidden aspect-square">
                        <CourseIcon 
                            class="w-full h-full"
                            stroke-color="white"
                        />
                    </div>
                    <div class="text-white text-left font-nexa-bold text-base leading-[22px] font-bold relative flex items-end justify-start">
                        Agregar nuevo programa - curso
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { Link } from "@inertiajs/vue3";
import { CourseIcon } from "@/Components/Icons";

export default {
    name: "CoursesHeader",
    components: {
        Link,
        CourseIcon,
    },
    props: {
        subtitle: {
            type: String,
            default: "Visualización de cursos"
        },

        showCreateButton: {
            type: Boolean,
            default: false
        },

        courseInfo: {
            type: String,
            default: null
        }
    },
    methods: {
        handleCreateCourse() {
            this.$emit('create-course');
        }
    }
};
</script>