<template>
    <div class="flex flex-col gap-[18px] items-start justify-start relative">
        <!-- Header Row con título y búsqueda -->
        <div class="flex flex-row items-center justify-between w-full relative gap-4 flex-wrap">
            <!-- Título -->
            <div class="text-[20px] leading-7 font-normal text-[#1c4f4a] font-nexa-regular">
                Filtros de búsqueda
            </div>

            <!-- Rango de fechas (condicional) -->
            <div v-if="showDateFilters" class="flex gap-4 items-center">
                <div class="relative">
                    <input
                        v-model="filters.dateFrom"
                        type="date"
                        class="w-[180px] h-[45.79px] bg-white rounded-[50px] border border-[#f0f0f0] px-[17px] py-2 text-[#434343] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none"
                        @change="performSearch"
                    />
                </div>
                <span class="text-gray-500">hasta</span>
                <div class="relative">
                    <input
                        v-model="filters.dateTo"
                        type="date"
                        class="w-[180px] h-[45.79px] bg-white rounded-[50px] border border-[#f0f0f0] px-[17px] py-2 text-[#434343] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none"
                        @change="performSearch"
                    />
                </div>
            </div>
        </div>

        <!-- Filtros Row -->
        <div class="flex flex-wrap gap-[15px] items-center justify-start w-full relative">
            <!-- Buscador por RUT/Documento -->
            <div class="relative min-w-[200px]">
                <input
                    v-model="filters.documentSearch"
                    type="text"
                    placeholder="Buscar por RUT..."
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none pr-10"
                    @input="onDocumentSearchInput"
                    @keydown.enter.prevent="performSearch"
                />
                <!-- Icono de búsqueda o X para limpiar -->
                <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                    <button
                        v-if="filters.documentSearch"
                        type="button"
                        @click="clearDocumentSearch"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>

            <!-- Buscador de Programas con Autocompletado -->
            <div class="relative flex-1 min-w-[300px]" data-program-search>
                <div class="relative">
                    <input
                        v-model="programSearchQuery"
                        type="text"
                        placeholder="Buscar por código de programa..."
                        class="w-full h-[46px] bg-white rounded-[50px] border px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none pr-10"
                        :class="{
                            'border-green-500': selectedProgram,
                            'border-[#f0f0f0]': !selectedProgram
                        }"
                        @input="onSearchInput"
                        @focus="showProgramDropdown = true"
                        @keydown.escape="showProgramDropdown = false"
                        @keydown.down.prevent="navigateDropdown(1)"
                        @keydown.up.prevent="navigateDropdown(-1)"
                        @keydown.enter.prevent="selectHighlighted"
                    />
                    <!-- Icono de búsqueda o X para limpiar -->
                    <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                        <button
                            v-if="selectedProgram"
                            type="button"
                            @click="clearProgramSelection"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Dropdown de resultados -->
                <div
                    v-if="showProgramDropdown && filteredPrograms.length > 0"
                    class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-80 overflow-y-auto"
                >
                    <!-- Opción "Todos los programas" -->
                    <div
                        @click="selectAllPrograms"
                        @mouseenter="highlightedIndex = -1"
                        class="px-4 py-3 cursor-pointer border-b border-gray-100 transition-colors font-medium"
                        :class="{
                            'bg-[#007e93] text-white': highlightedIndex === -1,
                            'hover:bg-gray-50 text-gray-700': highlightedIndex !== -1
                        }"
                    >
                        Todos los programas
                    </div>
                    <div
                        v-for="(program, index) in filteredPrograms"
                        :key="program.id"
                        @click="selectProgram(program)"
                        @mouseenter="highlightedIndex = index"
                        class="px-4 py-3 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors"
                        :class="{
                            'bg-[#007e93] text-white': highlightedIndex === index,
                            'hover:bg-gray-50': highlightedIndex !== index
                        }"
                    >
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="font-semibold" :class="{ 'text-white': highlightedIndex === index, 'text-[#007e93]': highlightedIndex !== index }">
                                    {{ program.code }}
                                </span>
                                <span :class="{ 'text-gray-200': highlightedIndex === index, 'text-gray-600': highlightedIndex !== index }">
                                    - {{ program.name }}
                                </span>
                            </div>
                            <span
                                v-if="program.program?.destination"
                                class="text-xs"
                                :class="{ 'text-gray-300': highlightedIndex === index, 'text-gray-500': highlightedIndex !== index }"
                            >
                                {{ program.program.destination }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Mensaje si no hay resultados -->
                <div
                    v-if="showProgramDropdown && programSearchQuery.length >= 1 && filteredPrograms.length === 0"
                    class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg p-4 text-center text-gray-500"
                >
                    No se encontraron programas
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
    name: "ExecutivesConsolidatedFilters",
    props: {
        initialFilters: {
            type: Object,
            default: () => ({})
        },
        programs: {
            type: Array,
            default: () => []
        },
        salesExecutives: {
            type: Array,
            default: () => []
        },
        showDateFilters: {
            type: Boolean,
            default: true
        }
    },
    data() {
        return {
            filters: {
                programId: this.initialFilters.programId || "",
                dateFrom: this.initialFilters.dateFrom || "",
                dateTo: this.initialFilters.dateTo || "",
                documentSearch: this.initialFilters.documentSearch || "",
            },
            programSearchQuery: "",
            showProgramDropdown: false,
            highlightedIndex: -1,
            selectedProgram: null,
        };
    },
    computed: {
        filteredPrograms() {
            // Ordenar programas por código
            const sortedPrograms = [...this.programs].sort((a, b) => {
                const codeA = (a.code || '').toLowerCase();
                const codeB = (b.code || '').toLowerCase();
                return codeA.localeCompare(codeB);
            });

            if (!this.programSearchQuery) {
                // Mostrar todos los programas cuando no hay búsqueda
                return sortedPrograms;
            }

            const query = this.programSearchQuery.toLowerCase();
            return sortedPrograms.filter(program => {
                const code = (program.code || '').toLowerCase();
                const name = (program.name || '').toLowerCase();
                const destination = (program.program?.destination || '').toLowerCase();
                return code.includes(query) || name.includes(query) || destination.includes(query);
            });
        }
    },
    mounted() {
        // Establecer fecha "hasta" por defecto (hoy), "desde" vacío para mostrar todo el histórico
        if (!this.filters.dateTo) {
            const today = new Date();
            this.filters.dateTo = today.toISOString().split('T')[0];
            this.performSearch();
        }

        // Si hay un programId inicial, buscar y seleccionar el programa
        if (this.filters.programId) {
            const program = this.programs.find(p => p.id == this.filters.programId);
            if (program) {
                this.selectedProgram = program;
                this.programSearchQuery = `${program.code} - ${program.name}`;
            }
        }

        // Listener para cerrar dropdown al hacer clic fuera
        document.addEventListener('click', this.handleClickOutside);
    },
    beforeUnmount() {
        document.removeEventListener('click', this.handleClickOutside);
    },
    methods: {
        performSearch: _.debounce(function () {
            this.$emit('filters-changed', this.filters);
        }, 300),

        onSearchInput() {
            // Si el usuario está escribiendo, limpiar la selección
            if (this.selectedProgram) {
                this.selectedProgram = null;
                this.filters.programId = "";
            }
            this.showProgramDropdown = true;
            this.highlightedIndex = -1;
        },

        navigateDropdown(direction) {
            const maxIndex = this.filteredPrograms.length - 1;

            this.highlightedIndex += direction;

            // -1 es "Todos los programas", después vienen los programas (0 a maxIndex)
            if (this.highlightedIndex < -1) {
                this.highlightedIndex = maxIndex;
            } else if (this.highlightedIndex > maxIndex) {
                this.highlightedIndex = -1;
            }
        },

        selectHighlighted() {
            if (this.highlightedIndex === -1) {
                this.selectAllPrograms();
            } else if (this.filteredPrograms.length > 0 && this.highlightedIndex >= 0) {
                this.selectProgram(this.filteredPrograms[this.highlightedIndex]);
            }
        },

        selectProgram(program) {
            this.selectedProgram = program;
            this.filters.programId = program.id;
            this.programSearchQuery = `${program.code} - ${program.name}`;
            this.showProgramDropdown = false;
            this.performSearch();
        },

        selectAllPrograms() {
            this.selectedProgram = null;
            this.filters.programId = "";
            this.programSearchQuery = "";
            this.showProgramDropdown = false;
            this.performSearch();
        },

        clearProgramSelection() {
            this.selectedProgram = null;
            this.filters.programId = "";
            this.programSearchQuery = "";
            this.performSearch();
        },

        onDocumentSearchInput() {
            // Limpiar el RUT de puntos y guiones para búsqueda
            // y convertir a mayúsculas para normalizar
            this.filters.documentSearch = this.filters.documentSearch
                .replace(/[.\-\s]/g, '')
                .toUpperCase();
            this.performSearch();
        },

        clearDocumentSearch() {
            this.filters.documentSearch = "";
            this.performSearch();
        },

        handleClickOutside(event) {
            const searchContainer = event.target.closest('[data-program-search]');
            if (!searchContainer) {
                this.showProgramDropdown = false;
            }
        },

        clearFilters() {
            const today = new Date();

            this.filters = {
                programId: "",
                dateFrom: "",
                dateTo: today.toISOString().split('T')[0],
                documentSearch: "",
            };
            this.selectedProgram = null;
            this.programSearchQuery = "";
            this.performSearch();
        }
    }
};
</script>
