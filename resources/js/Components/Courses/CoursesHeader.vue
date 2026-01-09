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
        <div v-if="showCreateButton" class="flex flex-row items-center justify-end flex-shrink-0 relative">
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
import { CourseIcon } from "@/Components/Icons";

export default {
    name: "CoursesHeader",
    components: {
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