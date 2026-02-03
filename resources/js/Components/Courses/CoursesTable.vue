<template>
    <div class="bg-white rounded-lg border border-gray-200">
        <!-- Scroll Container -->
        <div class="overflow-x-auto">
            <table class="w-full text-xs" style="min-width: 900px;">
                <!-- Table Header -->
                <thead class="bg-[#007e93] sticky top-0 z-10">
                    <tr>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[120px]">
                            Código
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[200px]">
                            Institución
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[150px]">
                            Destino
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[150px]">
                            Ejecutivo Comercial
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[100px]">
                            Fecha de Inicio
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Estado
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Participantes
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            % de Pago
                        </th>
                        <th class="px-2 py-2 text-right text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[180px]">
                            Recaudado / Total
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap w-[50px]">

                        </th>
                    </tr>
                </thead>

                <!-- Table Body -->
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                        v-for="(course, index) in courses"
                        :key="course.id"
                        :class="[
                            'hover:bg-gray-50 transition-colors',
                            index % 2 === 0 ? 'bg-white' : 'bg-gray-50'
                        ]"
                    >
                        <!-- Código -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs font-medium text-[#1c4f4a]">
                                {{ getProgramCode(course) }}
                            </div>
                        </td>
                        <!-- Institución -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs text-gray-900">
                                {{ capitalizeWords(course.institution?.name || 'Sin institución') }}
                            </div>
                        </td>
                        <!-- Destino -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs font-medium text-[#1c4f4a]">
                                {{ capitalizeWords(getProgramDestination(course)) }}
                            </div>
                        </td>
                        <!-- Ejecutivo Comercial -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs text-gray-900">
                                {{ capitalizeWords(getSalesExecutiveName(course)) }}
                            </div>
                        </td>
                        <!-- Fecha de Inicio -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="flex flex-col items-center">
                                <span class="text-xs text-gray-900">
                                    {{ getDepartureDateFormatted(course) }}
                                </span>
                                <span
                                    v-if="getDepartureDate(course)"
                                    :class="[
                                        'text-[9px] font-medium',
                                        isPastDate(getDepartureDate(course)) ? 'text-gray-400' : 'text-green-600'
                                    ]"
                                >
                                    {{ isPastDate(getDepartureDate(course)) ? 'Ejecutado' : 'Próximo' }}
                                </span>
                            </div>
                        </td>
                        <!-- Estado (Activo/Inactivo) -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <span
                                :class="[
                                    'inline-flex px-2 py-0.5 text-[10px] font-semibold rounded-full',
                                    isProgramActive(course) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                ]"
                            >
                                {{ isProgramActive(course) ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <!-- Participantes -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs text-gray-900">
                                {{ course.total_students || 0 }}
                            </div>
                        </td>
                        <!-- % de Pago -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <span
                                :class="[
                                    'inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded-full text-white',
                                    getStatusChipClass(course)
                                ]"
                            >
                                {{ getCoursePaymentPercentage(course) }}
                            </span>
                        </td>
                        <!-- Recaudado / Total -->
                        <td class="px-2 py-2 whitespace-nowrap text-right">
                            <div class="text-xs font-bold text-gray-900">
                                {{ formatCurrency(course.course_paid_amount || 0) }} / {{ formatCurrency(course.course_total_amount || 0) }}
                            </div>
                        </td>
                        <!-- Acciones -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <button
                                @click="editCourse(course.id)"
                                class="w-[18px] h-[19px] hover:opacity-75 transition-opacity"
                                :title="`Editar ${course.institution?.name || 'curso'}`"
                            >
                                <EditPencilIcon fill-color="#C7C7C7" />
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
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
        getProgramCourseName(course) {
            // Obtener el nombre completo del ProgramCourse (como lo ve el cliente)
            const programCourse = course.program_courses?.[0] || course.programCourses?.[0];
            return programCourse?.name || 'Sin nombre';
        },
        getProgramDestination(course) {
            // Laravel serializa las relaciones en snake_case
            // El destino ahora está en ProgramCourse, no en Program
            const programCourse = course.program_courses?.[0] || course.programCourses?.[0];
            return programCourse?.destination || programCourse?.program?.destination || 'N/a';
        },
        getProgramCode(course) {
            // Obtener el código del ProgramCourse
            const programCourse = course.program_courses?.[0] || course.programCourses?.[0];
            return programCourse?.code || 'N/A';
        },
        isProgramActive(course) {
            // Verificar si el programa está activo
            const programCourse = course.program_courses?.[0] || course.programCourses?.[0];
            return programCourse?.active === true || programCourse?.active === 1;
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
        },
        getSalesExecutiveName(course) {
            // Obtener el nombre del ejecutivo comercial desde el ProgramCourse
            const programCourse = course.program_courses?.[0] || course.programCourses?.[0];
            return programCourse?.sales_executive?.name || programCourse?.salesExecutive?.name || '—';
        },
        getDepartureDate(course) {
            // Obtener la fecha de salida del ProgramCourse
            const programCourse = course.program_courses?.[0] || course.programCourses?.[0];
            return programCourse?.departure_date || null;
        },
        getDepartureDateFormatted(course) {
            const date = this.getDepartureDate(course);
            if (!date) return '—';
            try {
                return new Date(date).toLocaleDateString('es-CL', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
            } catch (e) {
                return '—';
            }
        },
        isPastDate(dateStr) {
            if (!dateStr) return false;
            const date = new Date(dateStr);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            return date < today;
        }
    }
};
</script>

<style scoped>
/* Custom font classes - add these to your Tailwind config or use existing ones */
.font-nexa-bold {
    font-family: "Nexa-Bold", sans-serif;
    font-weight: 700;
}

.font-nexa-xbold {
    font-family: "Nexa-XBold", sans-serif;
    font-weight: 400;
}

.bg-turquesa {
    background-color: #007e93;
}
</style>