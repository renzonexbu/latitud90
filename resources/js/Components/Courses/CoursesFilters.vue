<template>
    <div class="flex flex-col gap-[27px] items-start justify-start relative">
        <!-- Header Row -->
        <div class="flex flex-row items-start justify-between flex-shrink-0 w-full relative">
            <div class="text-[20px] leading-7 font-normal text-[#1c4f4a] font-nexa-regular">
                Filtros de busqueda
            </div>
        </div>

        <!-- Filters Row -->
        <div class="flex flex-wrap gap-5 items-center justify-start w-full relative">
            <!-- Search Input -->
            <div class="relative w-[297.28px]">
                <input
                    v-model="filters.search"
                    type="text"
                    placeholder="Buscar institución"
                    class="w-full h-[45.79px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-[17px] py-2 text-[#434343] text-left font-nexa-bold text-[12px] leading-[18px] font-bold pr-12 outline-none"
                    @input="performSearch"
                />
                <button 
                    class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-turquesa rounded-[41.67px] w-[30px] h-[30px] flex items-center justify-center shadow-[0px_0.83px_3.33px_0px_rgba(25,33,61,0.08)]"
                    @click="performSearch"
                >
                    <svg class="w-[14.79px] h-[14.79px]" viewBox="0 0 24 24" fill="none">
                        <path d="M21 21L16.514 16.506L21 21ZM19 10.5C19 15.194 15.194 19 10.5 19C5.806 19 2 15.194 2 10.5C2 5.806 5.806 2 10.5 2C15.194 2 19 5.806 19 10.5Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>

            <!-- Institution Dropdown -->
            <div class="relative w-[200px]">
                <select
                    v-model="filters.institution"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-12"
                    @change="performSearch"
                >
                    <option value="">Institución</option>
                    <option v-for="institution in uniqueInstitutions" :key="institution" :value="institution">
                        {{ capitalizeWords(institution) }}
                    </option>
                </select>
            </div>

            <!-- Education Level Dropdown -->
            <div class="relative w-[180px]">
                <select
                    v-model="filters.level"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-12"
                    @change="performSearch"
                >
                    <option value="">Nivel educación</option>
                    <option v-for="level in uniqueLevels" :key="level" :value="level">
                        {{ capitalizeWords(level) }}
                    </option>
                </select>
            </div>

            <!-- Curso Dropdown -->
            <div class="relative w-[120px]">
                <select
                    v-model="filters.course_number"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-12"
                    @change="performSearch"
                >
                    <option value="">Curso</option>
                    <option v-for="num in uniqueCourseNumbers" :key="num" :value="num">
                        {{ num }}
                    </option>
                </select>
            </div>

            <!-- Year Dropdown -->
            <div class="relative w-[120px]">
                <select
                    v-model="filters.year"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-12"
                    @change="performSearch"
                >
                    <option value="">Año</option>
                    <option v-for="year in uniqueYears" :key="year" :value="year">
                        {{ year }}
                    </option>
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
    name: "CoursesFilters",
    props: {
        initialFilters: {
            type: Object,
            default: () => ({})
        },
        courses: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            filters: {
                search: this.initialFilters.search || "",
                institution: this.initialFilters.institution || "",
                level: this.initialFilters.level || "",
                course_number: this.initialFilters.course_number || "",
                year: this.initialFilters.year || "",
            }
        };
    },
    computed: {
        // Obtener instituciones únicas de los cursos
        uniqueInstitutions() {
            const institutions = this.courses
                .map(course => course.institution?.name)
                .filter(institution => institution && institution.trim() !== '');
            return [...new Set(institutions)].sort();
        },
        
        // Obtener niveles educativos únicos
        uniqueLevels() {
            const levels = this.courses
                .map(course => course.education_level)
                .filter(level => level && level.trim() !== '');
            return [...new Set(levels)].sort();
        },
        
        // Obtener cursos únicos (course_number)
        uniqueCourseNumbers() {
            const numbers = this.courses
                .map(course => course.course_number)
                .filter(num => num !== null && num !== undefined && num.toString().trim() !== '');
            return [...new Set(numbers)].sort((a, b) => Number(a) - Number(b));
        },
        
        // Obtener años únicos
        uniqueYears() {
            const years = this.courses
                .map(course => course.year)
                .filter(year => year && year.toString().trim() !== '');
            return [...new Set(years)].sort();
        }
    },
    methods: {
        performSearch: _.debounce(function () {
            this.$emit('filters-changed', this.filters);
        }, 300),
        
        clearFilters() {
            this.filters = {
                search: "",
                institution: "",
                level: "",
                course_number: "",
                year: "",
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