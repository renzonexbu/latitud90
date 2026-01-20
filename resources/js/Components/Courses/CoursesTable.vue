<template>
    <div class="rounded-[20px] border border-gray-300 overflow-hidden">
        <!-- Table Header -->
        <div class="bg-turquesa rounded-t-[20px] px-4 py-4 flex items-center gap-2 h-[75px]">
            <div class="text-white font-nexa-bold text-xs flex-1 min-w-[140px]">
                Institución
            </div>
            <div class="text-white font-nexa-bold text-xs text-center w-[80px]">
                Nivel
            </div>
            <div class="text-white font-nexa-bold text-xs text-center w-[70px]">
                Curso
            </div>
            <div class="text-white font-nexa-bold text-xs text-center w-[50px]">
                Año
            </div>
            <div class="text-white font-nexa-bold text-xs flex-1 min-w-[120px]">
                Plantilla
            </div>
            <div class="text-white font-nexa-bold text-xs flex-1 min-w-[100px]">
                Destino
            </div>
            <div class="text-white font-nexa-bold text-xs text-center w-[60px]">
                Alumnos
            </div>
            <div class="text-white font-nexa-bold text-xs text-center w-[90px]">
                % Pago
            </div>
            <div class="text-white font-nexa-bold text-xs text-center w-[160px]">
                Recaudado / Total
            </div>
            <div class="w-[40px] flex-shrink-0">
                <!-- Empty space for actions column -->
            </div>
        </div>

        <!-- Table Body -->
        <div class="flex flex-col">
            <div
                v-for="(course, index) in courses"
                :key="course.id"
                :class="[
                    'px-4 py-3 flex items-center gap-2',
                    index % 2 === 0 ? 'bg-white' : 'bg-gray-50'
                ]"
            >
                <div class="text-verde-oscuro font-nexa-bold text-xs flex-1 min-w-[140px] truncate">
                    {{ capitalizeWords(course.institution?.name || 'Sin institución') }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-xs text-center w-[80px] truncate">
                    {{ capitalizeWords(course.education_level) }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-xs text-center w-[70px]">
                    {{ course.course_display }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-xs text-center w-[50px]">
                    {{ course.year }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-xs flex-1 min-w-[120px] truncate">
                    {{ capitalizeWords(getProgramName(course)) }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-xs flex-1 min-w-[100px] truncate">
                    {{ capitalizeWords(getProgramDestination(course)) }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-xs text-center w-[60px]">
                    {{ course.total_students || 0 }}
                </div>
                <div class="w-[90px] flex justify-center">
                    <div
                        :class="[
                            'rounded-xl px-2 py-1 flex items-center justify-center w-full',
                            getStatusChipClass(course)
                        ]"
                    >
                        <span
                            :class="[
                                'font-nexa-xbold text-xs text-center',
                                getStatusTextClass(course)
                            ]"
                        >
                            {{ getCoursePaymentPercentage(course) }}
                        </span>
                    </div>
                </div>
                <div class="text-verde-oscuro font-nexa-xbold text-xs text-center w-[160px]">
                    {{ formatCurrency(course.course_paid_amount || 0) }} / {{ formatCurrency(course.course_total_amount || 0) }}
                </div>
                <div class="w-[40px] flex-shrink-0 flex justify-center">
                    <button
                        @click="editCourse(course.id)"
                        class="p-1 hover:bg-gray-100 rounded transition-colors"
                        :title="`Editar ${course.institution?.name || 'curso'}`"
                    >
                        <EditPencilIcon fill-color="#C7C7C7" class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { EditPencilIcon } from "@/Components/Icons";

export default {
    name: "CoursesTable",
    components: {
        EditPencilIcon,
    },
    props: {
        courses: {
            type: Array,
            default: () => []
        }
    },
    methods: {
        editCourse(courseId) {
            // Emit event to parent component or navigate to edit page
            this.$emit('edit-course', courseId);
        },
        getProgramName(course) {
            // Laravel serializa las relaciones en snake_case
            // Obtener el nombre de la plantilla (Program.name)
            const programCourse = course.program_courses?.[0] || course.programCourses?.[0];
            return programCourse?.program?.name || 'Sin Plantilla';
        },
        getProgramDestination(course) {
            // Laravel serializa las relaciones en snake_case
            // El destino ahora está en ProgramCourse, no en Program
            const programCourse = course.program_courses?.[0] || course.programCourses?.[0];
            return programCourse?.destination || programCourse?.program?.destination || 'N/a';
        },
        getStatusChipClass(course) {
            const percentage = course.payment_percentage;
            
            if (percentage === null) {
                return 'bg-gray-300'; // Gris para ---
            } else if (percentage >= 100) {
                return 'bg-[#1a4b75]'; // Azul oscuro para 100%
            } else if (percentage >= 75) {
                return 'bg-[#4b8d7f]'; // Verde para 75%+
            } else if (percentage >= 50) {
                return 'bg-yellow-500'; // Amarillo para 50%+
            } else if (percentage >= 25) {
                return 'bg-orange-500'; // Naranja para 25%+
            } else {
                return 'bg-[#d54a42]'; // Rojo para menos de 25%
            }
        },
        
        getStatusTextClass(course) {
            return 'text-white';
        },
        
        getPaymentPercentage(course) {
            return course.payment_percentage_text || '---';
        },

        getCoursePaymentPercentage(course) {
            const pct = course.course_payment_percentage;
            if (pct === null || pct === undefined) return '---';
            return `${pct}%`;
        },
        
        formatCurrency(amount) {
            if (!amount) return '$0';
            return new Intl.NumberFormat('es-CL', {
                style: 'currency',
                currency: 'CLP',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount);
        },
        
        capitalizeWords(string) {
            if (!string) return '';
            return string.split(' ').map(word => 
                word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
            ).join(' ');
        }
    }
};
</script>

<style scoped>
/* Custom font classes - add these to your Tailwind config or use existing ones */
.font-nexa-bold {
    font-family: 'Nexa-Bold', sans-serif;
    font-weight: 700;
}

.font-nexa-xbold {
    font-family: 'Nexa-XBold', sans-serif;
    font-weight: 400;
}

.text-verde-oscuro {
    color: #1c4f4a;
}

.bg-turquesa {
    background-color: #007e93;
}
</style>