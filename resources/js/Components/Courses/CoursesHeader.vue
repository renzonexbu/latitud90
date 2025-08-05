<template>
    <div class="py-5 flex flex-row items-center justify-between relative">
        <!-- Left side - Breadcrumb and title -->
        <div class="flex flex-row gap-[14.37px] items-center justify-start flex-shrink-0 relative">
            <div class="text-[24px] leading-[28px] font-bold text-turquesa font-nexa-bold">
                Cursos
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
                <!-- Plus icon positioned above the button -->
                <div class="absolute top-[0.8rem] -right-52 z-10 flex-shrink-0 w-[21.289px] h-[21.289px] relative overflow-visible">
                    <svg 
                        xmlns="http://www.w3.org/2000/svg" 
                        width="21.289" 
                        height="21.289" 
                        viewBox="0 0 22 22" 
                        fill="none"
                        class="flex-shrink-0"
                    >
                        <circle cx="11.3553" cy="10.6447" r="10.6447" fill="#FBBD51"/>
                        <path d="M4.58154 9.49167H9.74326V4.36182H12.2923V9.49167H17.5177V11.9451H12.2923V17.2024H9.74326V11.9451H4.58154V9.49167Z" fill="#F9F9F9"/>
                    </svg>
                </div>
                
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
                        Agregar nuevo curso
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