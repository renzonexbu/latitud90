<template>
    <div class="bg-white rounded-[20px] h-[255px] w-[750px] flex-shrink-0 flex justify-center items-center relative overflow-hidden">
        <div class="flex flex-col gap-6 w-[627px] absolute left-9 top-8">
        <!-- Top Section -->
        <div class="flex justify-between items-center">
            <!-- Institution Info -->
            <div class="flex flex-col gap-0.5">
                <div class="text-[#c7c7c7] text-[14px] leading-[18px] font-nexa-regular">
                    Nombre de institución
                </div>
                <div class="text-turquesa text-[20px] leading-[28px] font-nexa-bold">
                    {{ course.institution?.name || 'Sin institución' }}
                </div>
            </div>
            
            <!-- Edit Button -->
            <button 
                @click="$emit('edit-course')"
                class="bg-[#f9f9f9] border border-[#d3d3d3] rounded-[112.89px] px-3 py-[11.29px] flex items-center gap-[14px] h-[40px] hover:bg-gray-100 transition-colors"
            >
                <svg class="w-[16.42px] h-[16.93px] text-[#c7c7c7]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                <span class="text-[#c7c7c7] text-[14px] leading-[13px] font-nexa-xbold">
                    Editar Curso
                </span>
            </button>
        </div>
        
        <!-- Course Attributes -->
        <div class="flex gap-[22px]">
            <!-- Año -->
            <div class="flex flex-col gap-2 w-[110px]">
                <div class="text-[#5b5b5b] text-[10px] leading-[14px] font-nexa-regular">
                    Año
                </div>
                <div class="bg-white border border-[#f0f0f0] rounded-[50px] px-4 py-2 flex items-center justify-between w-[110px] shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)]">
                    <div class="text-turquesa text-[14px] leading-[22px] font-nexa-bold text-center w-[48px]">
                        {{ course.year }}
                    </div>
                    <svg class="w-[12.62px] h-[25.21px] text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>
            
            <!-- Curso -->
            <div class="flex flex-col gap-2 w-[180px]">
                <div class="text-[#5b5b5b] text-[10px] leading-[14px] font-nexa-regular">
                    Curso
                </div>
                <div class="bg-white border border-[#f0f0f0] rounded-[50px] px-4 py-2 flex items-center justify-between w-full shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)]">
                    <div class="text-turquesa text-[14px] leading-[22px] font-nexa-bold text-left w-full">
                        {{ formatCourseDisplay(course) }}
                    </div>
                    <svg class="w-[12.62px] h-[25.21px] text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>
            
            <!-- Nivel de educación -->
            <div class="flex flex-col gap-2 w-[210px]">
                <div class="text-[#5b5b5b] text-[10px] leading-[14px] font-nexa-regular">
                    Nivel de educación
                </div>
                <div class="bg-white border border-[#f0f0f0] rounded-[50px] px-4 py-2 flex items-center justify-between w-[210px] shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)]">
                    <div class="text-turquesa text-[14px] leading-[22px] font-nexa-bold text-left w-[90px]">
                        {{ capitalizeWords(course.education_level) }}
                    </div>
                    <svg class="w-[12.62px] h-[25.21px] text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <!-- Separator Line -->
        <svg xmlns="http://www.w3.org/2000/svg" width="627" height="2" viewBox="0 0 627 2" fill="none" class="w-full">
            <path d="M0 1L627 1.00005" stroke="#C7C7C7" stroke-width="1"/>
        </svg>
        
        <!-- Bottom Section -->
        <div class="flex justify-between items-center">
            <!-- Contact Info -->
            <div class="flex gap-[22px]">
                <!-- Email -->
                <div class="flex items-center gap-[5px]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
                        <rect width="18" height="18" fill="white"/>
                        <path d="M2.25 5.25006C2.25 4.85224 2.40804 4.47071 2.68934 4.1894C2.97064 3.9081 3.35218 3.75006 3.75 3.75006H14.25C14.6478 3.75006 15.0294 3.9081 15.3107 4.1894C15.592 4.47071 15.75 4.85224 15.75 5.25006V12.7501C15.75 13.1479 15.592 13.5294 15.3107 13.8107C15.0294 14.092 14.6478 14.2501 14.25 14.2501H3.75C3.35218 14.2501 2.97064 14.092 2.68934 13.8107C2.40804 13.5294 2.25 13.1479 2.25 12.7501V5.25006Z" stroke="#1C4F4A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2.25 5.25006L9 9.75006L15.75 5.25006" stroke="#1C4F4A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <div class="text-verde-oscuro text-[12px] leading-[13px] font-nexa-bold w-[94px]">
                        {{ course.contact_email || 'N/A' }}
                    </div>
                </div>
                
                <!-- Phone -->
                <div class="flex items-center gap-[5px] ml-[65px]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
                        <rect width="18" height="18" fill="white"/>
                        <path d="M11.6999 10.8916C9.90365 12.7816 5.3219 8.24109 7.1249 6.34359C8.2259 5.18484 6.9824 3.86109 6.2939 2.88684C5.00165 1.06059 2.1659 3.58209 2.2514 5.18634C2.52365 10.2458 7.9964 16.2413 13.2959 15.7178C14.9534 15.5543 16.8584 12.5603 14.9572 11.4661C14.0062 10.9186 12.7004 9.83859 11.6999 10.8908M10.4999 2.25009C11.8923 2.25009 13.2276 2.80321 14.2122 3.78777C15.1968 4.77234 15.7499 6.1077 15.7499 7.50009M10.4999 5.25009C11.0966 5.25009 11.6689 5.48714 12.0909 5.90909C12.5129 6.33105 12.7499 6.90335 12.7499 7.50009" stroke="#1C4F4A" stroke-width="1.125" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <div class="text-verde-oscuro text-[12px] leading-[13px] font-nexa-bold w-[94px]">
                        {{ course.contact_phone || 'N/A' }}
                    </div>
                </div>
            </div>
            
            <!-- Students Count -->
            <div class="flex items-center gap-[5px]">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="18" viewBox="0 0 20 18" fill="none">
                    <rect width="20" height="18" fill="white"/>
                    <path d="M17.5 14.8126C17.5 13.2451 16.1083 11.0116 14.1667 10.5173M12.5 14.8126C12.5 12.8243 10.2617 10.3126 7.5 10.3126C4.73833 10.3126 2.5 12.8243 2.5 14.8126M12.5 7.68756C13.163 7.68756 13.7989 7.45051 14.2678 7.02855C14.7366 6.60659 15 6.0343 15 5.43756C15 4.84082 14.7366 4.26853 14.2678 3.84657C13.7989 3.42461 13.163 3.18756 12.5 3.18756M10 5.43756C10 6.0343 9.73661 6.60659 9.26777 7.02855C8.79893 7.45051 8.16304 7.68756 7.5 7.68756C6.83696 7.68756 6.20107 7.45051 5.73223 7.02855C5.26339 6.60659 5 6.0343 5 5.43756C5 4.84082 5.26339 4.26853 5.73223 3.84657C6.20107 3.42461 6.83696 3.18756 7.5 3.18756C8.16304 3.18756 8.79893 3.42461 9.26777 3.84657C9.73661 4.26853 10 4.84082 10 5.43756Z" stroke="#1C4F4A" stroke-width="1.125" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <div class="text-verde-oscuro text-[12px] leading-[13px] font-nexa-bold w-[122px]">
                    Total de alumnos: {{ course.total_students || 0 }}
                </div>
            </div>
        </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "CourseInfoCard",
    props: {
        course: {
            type: Object,
            required: true,
        },
    },
    methods: {
        capitalizeWords(string) {
            if (!string) return '';
            return string.split(' ').map(word => 
                word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
            ).join(' ');
        },
        formatCourseDisplay(course) {
            if (!course) return 'N/A';
            const level = (course.education_level || '').toString();
            const num = course.course_number ? `${course.course_number}°` : '';
            if (level === 'preescolar') {
                return course.course_name || 'Kínder';
            }
            if (level === 'basica') {
                return num ? `${num} Básico` : 'Básica';
            }
            if (level === 'media') {
                return num ? `${num} Medio` : 'Media';
            }
            if (level === 'universitaria') {
                return course.course_name || 'Universitaria';
            }
            return course.course_name || 'N/A';
        }
    },
};
</script>

<style scoped>
.font-nexa-regular {
    font-family: 'Nexa-Regular', sans-serif;
    font-weight: 400;
}

.font-nexa-bold {
    font-family: 'Nexa-Bold', sans-serif;
    font-weight: 700;
}

.font-nexa-xbold {
    font-family: 'Nexa-XBold', sans-serif;
    font-weight: 400;
}

.text-turquesa {
    color: #007e93;
}

.text-verde-oscuro {
    color: #1c4f4a;
}
</style> 