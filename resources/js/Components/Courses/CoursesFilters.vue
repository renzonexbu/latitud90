<template>
    <div class="flex flex-col gap-[18px] items-start justify-start relative">
        <!-- Header Row con título y búsqueda -->
        <div class="flex flex-row items-center justify-between w-full relative gap-4">
            <!-- Título -->
            <div class="text-[20px] leading-7 font-normal text-[#1c4f4a] font-nexa-regular">
                Filtros de busqueda
            </div>

            <!-- Campo de búsqueda -->
            <div class="relative w-[350px]">
                <input
                    v-model="filters.search"
                    type="text"
                    placeholder="Buscar por código de programa..."
                    class="w-full h-[45.79px] bg-white rounded-[50px] border border-[#f0f0f0] px-[17px] py-2 text-[#434343] text-left font-nexa-bold text-[12px] leading-[18px] font-bold pr-12 outline-none"
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

        <!-- Filters Row -->
        <div class="flex flex-wrap gap-[15px] items-center justify-start w-full relative">
            <!-- Institution Autocomplete -->
            <div class="relative w-[220px]" ref="institutionDropdown">
                <input
                    v-model="institutionSearch"
                    type="text"
                    placeholder="Institución"
                    @focus="showInstitutionDropdown = true"
                    @input="showInstitutionDropdown = true"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none pr-10"
                />
                <button
                    v-if="filters.institution"
                    @click="clearInstitutionFilter"
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <span v-else class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </span>
                <!-- Dropdown opciones -->
                <div
                    v-if="showInstitutionDropdown && filteredInstitutions.length > 0"
                    class="absolute z-[100] w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                >
                    <div
                        v-for="inst in filteredInstitutions"
                        :key="inst"
                        @click="selectInstitution(inst)"
                        class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm text-gray-700"
                    >
                        {{ inst }}
                    </div>
                </div>
            </div>

            <!-- Year Dropdown -->
            <div class="relative w-[120px]">
                <select
                    v-model="filters.year"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                    @change="performSearch"
                >
                    <option value="">Año</option>
                    <option v-for="year in uniqueYears" :key="year" :value="year">
                        {{ year }}
                    </option>
                </select>
            </div>

            <!-- Estado (Activo/Inactivo) Dropdown -->
            <div class="relative w-[140px]">
                <select
                    v-model="filters.active"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                    @change="performSearch"
                >
                    <option value="">Estado</option>
                    <option value="active">Activos</option>
                    <option value="inactive">Inactivos</option>
                </select>
            </div>

            <!-- Ejecutivo Comercial Autocomplete -->
            <div class="relative w-[220px]" ref="executiveDropdown">
                <input
                    v-model="executiveSearch"
                    type="text"
                    placeholder="Ejecutivo comercial"
                    @focus="showExecutiveDropdown = true"
                    @input="showExecutiveDropdown = true"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none pr-10"
                />
                <button
                    v-if="filters.salesExecutiveId"
                    @click="clearExecutiveFilter"
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <span v-else class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </span>
                <!-- Dropdown opciones -->
                <div
                    v-if="showExecutiveDropdown && filteredExecutives.length > 0"
                    class="absolute z-[100] w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                >
                    <div
                        v-for="exec in filteredExecutives"
                        :key="exec.id"
                        @click="selectExecutive(exec)"
                        class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm text-gray-700"
                    >
                        {{ exec.name }} <span class="text-gray-400 text-xs">({{ exec.code }})</span>
                    </div>
                </div>
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
        },
        salesExecutives: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            filters: {
                search: this.initialFilters.search || "",
                institution: this.initialFilters.institution || "",
                year: this.initialFilters.year || "",
                active: this.initialFilters.active || "active", // Por defecto mostrar solo activos
                salesExecutiveId: this.initialFilters.salesExecutiveId || null,
            },
            executiveSearch: "",
            showExecutiveDropdown: false,
            institutionSearch: this.initialFilters.institution || "",
            showInstitutionDropdown: false,
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

        // Obtener años únicos
        uniqueYears() {
            const years = this.courses
                .map(course => course.year)
                .filter(year => year && year.toString().trim() !== '');
            return [...new Set(years)].sort();
        },

        // Filtrar instituciones por búsqueda
        filteredInstitutions() {
            const term = this.institutionSearch.trim().toLowerCase();
            if (!term) return this.uniqueInstitutions;
            return this.uniqueInstitutions.filter(inst =>
                inst.toLowerCase().includes(term)
            );
        },

        // Filtrar ejecutivos por búsqueda
        filteredExecutives() {
            const term = this.executiveSearch.trim().toLowerCase();
            if (!term) return this.salesExecutives;
            return this.salesExecutives.filter((exec) => {
                const name = (exec.name || "").toLowerCase();
                const code = (exec.code || "").toLowerCase();
                return name.includes(term) || code.includes(term);
            });
        }
    },
    mounted() {
        // Cerrar dropdown al hacer click fuera
        document.addEventListener('click', this.handleClickOutside);
    },
    beforeUnmount() {
        document.removeEventListener('click', this.handleClickOutside);
    },
    methods: {
        handleClickOutside(event) {
            const execDropdown = this.$refs.executiveDropdown;
            if (execDropdown && !execDropdown.contains(event.target)) {
                this.showExecutiveDropdown = false;
            }
            const instDropdown = this.$refs.institutionDropdown;
            if (instDropdown && !instDropdown.contains(event.target)) {
                this.showInstitutionDropdown = false;
            }
        },
        selectInstitution(name) {
            this.filters.institution = name;
            this.institutionSearch = name;
            this.showInstitutionDropdown = false;
            this.performSearch();
        },
        clearInstitutionFilter() {
            this.filters.institution = "";
            this.institutionSearch = "";
            this.performSearch();
        },
        selectExecutive(exec) {
            this.filters.salesExecutiveId = exec.id;
            this.executiveSearch = exec.name;
            this.showExecutiveDropdown = false;
            this.performSearch();
        },
        clearExecutiveFilter() {
            this.filters.salesExecutiveId = null;
            this.executiveSearch = "";
            this.performSearch();
        },
        performSearch: _.debounce(function () {
            this.$emit('filters-changed', this.filters);
        }, 300),

        clearFilters() {
            this.filters = {
                search: "",
                institution: "",
                year: "",
                active: "active", // Mantener filtro de activos al limpiar
                salesExecutiveId: null,
            };
            this.executiveSearch = "";
            this.institutionSearch = "";
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
