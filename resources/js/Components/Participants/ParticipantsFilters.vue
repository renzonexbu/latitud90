<template>
    <div class="flex flex-col gap-[18px] items-start justify-start relative">
        <!-- Header Row con título y búsqueda -->
        <div class="flex flex-row items-center justify-between w-full relative gap-[510px]">
            <!-- Título -->
            <div class="text-[20px] leading-7 font-normal text-[#1c4f4a] font-nexa-regular">
                Filtros de busqueda
            </div>
            
            <!-- Campo de búsqueda -->
            <div class="relative w-[445px]">
                <input
                    v-model="filters.search"
                    type="text"
                    placeholder="Buscar usuario"
                    class="w-full h-[45.79px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-[17px] py-2 text-[#434343] text-left font-nexa-bold text-[12px] leading-[18px] font-bold pr-12 outline-none"
                    @input="performSearch"
                />
                <button 
                    class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-[#007e93] rounded-[41.67px] w-[30px] h-[30px] flex items-center justify-center shadow-[0px_0.83px_3.33px_0px_rgba(25,33,61,0.08)]"
                    @click="performSearch"
                >
                    <svg class="w-[14.79px] h-[14.79px]" viewBox="0 0 24 24" fill="none">
                        <path d="M21 21L16.514 16.506L21 21ZM19 10.5C19 15.194 15.194 19 10.5 19C5.806 19 2 15.194 2 10.5C2 5.806 5.806 2 10.5 2C15.194 2 19 5.806 19 10.5Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Filtros Row -->
        <div class="flex flex-wrap gap-[15px] items-center justify-start w-full relative">
            <!-- Programa de viaje -->
            <div class="relative flex-1">
                <select
                    v-model="filters.program"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                    @change="performSearch"
                >
                    <option value="">Programa de viaje</option>
                    <option v-for="program in uniquePrograms" :key="program" :value="program">
                        {{ capitalizeWords(program) }}
                    </option>
                </select>
            </div>

            <!-- Institución -->
            <div class="relative flex-shrink-0 w-[129px]">
                <select
                    v-model="filters.institution"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                    @change="performSearch"
                >
                    <option value="">Institución</option>
                    <option v-for="institution in uniqueInstitutions" :key="institution" :value="institution">
                        {{ capitalizeWords(institution) }}
                    </option>
                </select>
            </div>

            <!-- Nivel de educación -->
            <div class="relative flex-shrink-0 w-[134px]">
                <select
                    v-model="filters.level"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                    @change="performSearch"
                >
                    <option value="">Nivel de educación</option>
                    <option v-for="level in uniqueLevels" :key="level" :value="level">
                        {{ capitalizeWords(level) }}
                    </option>
                </select>
            </div>

            <!-- Grado -->
            <div class="relative flex-shrink-0 w-[110px]">
                <select
                    v-model="filters.grade"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                    @change="performSearch"
                >
                    <option value="">Grado</option>
                    <option v-for="grade in uniqueGrades" :key="grade" :value="grade">
                        {{ grade }}
                    </option>
                </select>
            </div>

            <!-- Turno -->
            <div class="relative flex-shrink-0 w-[110px]">
                <select
                    v-model="filters.turno"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                    @change="performSearch"
                >
                    <option value="">Turno</option>
                    <option v-for="turno in uniqueTurnos" :key="turno" :value="turno">
                        {{ capitalizeWords(turno) }}
                    </option>
                </select>
            </div>

            <!-- Estado de pago -->
            <div class="relative flex-shrink-0 w-[134px]">
                <select
                    v-model="filters.paymentStatus"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                    @change="performSearch"
                >
                    <option value="">Estado de pago</option>
                    <option value="pending_payment">Pendiente</option>
                    <option value="confirmed">Completado</option>
                    <option value="cancelled">Liberado</option>
                </select>
            </div>

            <!-- Clear Filters Button -->
            <button
                @click="clearFilters"
                class="h-[46px] px-6 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-[50px] border border-gray-300 text-left font-nexa-regular text-[12px] leading-[18px] font-normal transition-colors duration-200 flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Limpiar filtros
            </button>
        </div>
    </div>
</template>

<script>
import _ from "lodash";

export default {
    name: "ParticipantsFilters",
    props: {
        initialFilters: {
            type: Object,
            default: () => ({})
        },
        participants: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            filters: {
                search: this.initialFilters.search || "",
                program: this.initialFilters.program || "",
                institution: this.initialFilters.institution || "",
                level: this.initialFilters.level || "",
                grade: this.initialFilters.grade || "",
                turno: this.initialFilters.turno || "",
                paymentStatus: this.initialFilters.paymentStatus || "",
            }
        };
    },
    computed: {
        // Obtener programas únicos de los participantes
        uniquePrograms() {
            const programs = this.participants
                .map(participant => participant.course?.program?.name)
                .filter(program => program && program.trim() !== '');
            return [...new Set(programs)].sort();
        },
        
        // Obtener instituciones únicas
        uniqueInstitutions() {
            const institutions = this.participants
                .map(participant => participant.course?.institution?.name)
                .filter(institution => institution && institution.trim() !== '');
            return [...new Set(institutions)].sort();
        },
        
        // Obtener niveles educativos únicos
        uniqueLevels() {
            const levels = this.participants
                .map(participant => participant.course?.education_level)
                .filter(level => level && level.trim() !== '');
            return [...new Set(levels)].sort();
        },
        
        // Obtener grados únicos
        uniqueGrades() {
            const grades = this.participants
                .map(participant => participant.course?.grade)
                .filter(grade => grade && grade.toString().trim() !== '');
            return [...new Set(grades)].sort();
        },
        
        // Obtener turnos únicos
        uniqueTurnos() {
            const turnos = this.participants
                .map(participant => participant.course?.shift)
                .filter(turno => turno && turno.trim() !== '');
            return [...new Set(turnos)].sort();
        }
    },
    methods: {
        performSearch: _.debounce(function () {
            this.$emit('filters-changed', this.filters);
        }, 300),
        
        clearFilters() {
            this.filters = {
                search: "",
                program: "",
                institution: "",
                level: "",
                grade: "",
                turno: "",
                paymentStatus: "",
            };
            this.performSearch();
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