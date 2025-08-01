<template>
    <div class="rounded-[20px] border border-gray-300 overflow-hidden">
        <!-- Table Header -->
        <div class="bg-turquesa rounded-t-[20px] px-6 py-4 flex items-center justify-between h-[75px]">
            <div class="text-white font-nexa-bold text-sm w-[150px]">
                Nombre de institución
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[90px]">
                Nivel
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[53px]">
                Grado
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[90px]">
                Turno
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[39px]">
                Año
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[150px]">
                Programa
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[150px]">
                Destino
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[90px]">
                Alumnos
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[110px]">
                Estado
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[121px]">
                Total recolectado
            </div>
            <div class="w-[18px] h-[20px] flex-shrink-0">
                <!-- Empty space for actions column -->
            </div>
        </div>

        <!-- Table Body -->
        <div class="flex flex-col">
            <div 
                v-for="(course, index) in courses" 
                :key="course.id"
                :class="[
                    'px-6 py-[18px] flex items-center justify-between',
                    index % 2 === 0 ? 'bg-white' : 'bg-gray-50'
                ]"
            >
                <div class="text-verde-oscuro font-nexa-bold text-sm w-[150px]">
                    {{ course.institution }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[90px]">
                    {{ course.level }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[53px]">
                    {{ course.grade }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[90px]">
                    {{ course.shift }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[39px]">
                    {{ course.year }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[150px]">
                    {{ course.program }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[150px]">
                    {{ course.destination }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[90px]">
                    {{ course.students }}
                </div>
                <div class="w-[100px] flex justify-center">
                    <div 
                        :class="[
                            'rounded-xl px-2.5 py-1.5 flex items-center justify-center w-[100px]',
                            getStatusChipClass(course.status, course.percentage)
                        ]"
                    >
                        <span 
                            :class="[
                                'font-nexa-xbold text-sm text-center',
                                getStatusTextClass(course.status, course.percentage)
                            ]"
                        >
                            {{ getStatusText(course.status, course.percentage) }}
                        </span>
                    </div>
                </div>
                <div class="text-verde-oscuro font-nexa-xbold text-sm text-center w-[121px]">
                    {{ course.totalCollected }}
                </div>
                <div class="w-[18px] h-[20px] flex-shrink-0">
                    <button 
                        @click="editCourse(course.id)"
                        class="p-1 hover:bg-gray-100 rounded transition-colors"
                        :title="`Editar ${course.institution}`"
                    >
                        <EditPencilIcon fill-color="#C7C7C7" />
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
        getStatusChipClass(status, percentage) {
            if (status === 'completed') {
                return 'bg-[#1a4b75]'; // Azul oscuro para finalizado
            }
            
            if (percentage === 0) {
                return 'bg-[#d9d9d9]'; // Gris para 0%
            } else if (percentage <= 30) {
                return 'bg-[#ffb232]'; // Amarillo para porcentajes bajos
            } else if (percentage <= 70) {
                return 'bg-[#d54a42]'; // Rojo para porcentajes medios
            } else {
                return 'bg-[#4b8d7f]'; // Verde para porcentajes altos
            }
        },
        
        getStatusTextClass(status, percentage) {
            if (status === 'completed') {
                return 'text-[#edfcff]';
            }
            return 'text-white';
        },
        
        getStatusText(status, percentage) {
            if (status === 'completed') {
                return 'Finalizado';
            }
            return `${percentage}%`;
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