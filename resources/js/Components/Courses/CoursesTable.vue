<template>
    <div class="rounded-[20px] border border-gray-300 overflow-hidden">
        <!-- Table Header -->
        <div class="bg-turquesa rounded-t-[20px] px-6 py-4 flex items-center justify-between h-[75px]">
            <div class="text-white font-nexa-bold text-sm w-[200px]">
                Nombre de institución
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[120px]">
                Nivel
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[80px]">
                Grado
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[120px]">
                Turno
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[60px]">
                Año
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[200px]">
                Programa
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[200px]">
                Destino
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[120px]">
                Alumnos
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[140px]">
                Estado
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[160px]">
                Total recolectado
            </div>
            <div class="w-[60px] h-[20px] flex-shrink-0">
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
                <div class="text-verde-oscuro font-nexa-bold text-sm w-[200px]">
                    {{ course.institution_name }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[120px]">
                    {{ course.education_level }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[80px]">
                    {{ course.grade }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[120px]">
                    {{ course.shift }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[60px]">
                    {{ course.year }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[200px]">
                    {{ course.program?.name || 'Sin programa' }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[200px]">
                    {{ course.program?.destination || 'N/A' }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[120px]">
                    {{ course.total_students || 0 }}
                </div>
                <div class="w-[140px] flex justify-center">
                    <div 
                        :class="[
                            'rounded-xl px-2.5 py-1.5 flex items-center justify-center w-[120px]',
                            getStatusChipClass(course.status)
                        ]"
                    >
                        <span 
                            :class="[
                                'font-nexa-xbold text-sm text-center',
                                getStatusTextClass(course.status)
                            ]"
                        >
                            {{ getStatusText(course.status) }}
                        </span>
                    </div>
                </div>
                <div class="text-verde-oscuro font-nexa-xbold text-sm text-center w-[160px]">
                    {{ formatCurrency(course.collected_amount) }}
                </div>
                <div class="w-[60px] h-[20px] flex-shrink-0">
                    <button 
                        @click="editCourse(course.id)"
                        class="p-1 hover:bg-gray-100 rounded transition-colors"
                        :title="`Editar ${course.institution_name}`"
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
        getStatusChipClass(status) {
            switch (status) {
                case 'completed':
                    return 'bg-[#1a4b75]'; // Azul oscuro para finalizado
                case 'cancelled':
                    return 'bg-[#d54a42]'; // Rojo para cancelado
                case 'active':
                default:
                    return 'bg-[#4b8d7f]'; // Verde para activo
            }
        },
        
        getStatusTextClass(status) {
            return 'text-white';
        },
        
        getStatusText(status) {
            switch (status) {
                case 'completed':
                    return 'Finalizado';
                case 'cancelled':
                    return 'Cancelado';
                case 'active':
                default:
                    return 'Activo';
            }
        },
        
        formatCurrency(amount) {
            if (!amount) return '$0';
            return new Intl.NumberFormat('es-CL', {
                style: 'currency',
                currency: 'CLP',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount);
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