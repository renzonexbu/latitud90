<template>
    <div class="flex flex-col gap-[18px] items-start justify-start relative">
        <!-- Header Row con título y búsqueda -->
        <div class="flex flex-row items-center justify-between w-full relative gap-[510px]">
            <!-- Título -->
            <div class="text-[20px] leading-7 font-normal text-[#1c4f4a] font-nexa-regular">
                Filtros de búsqueda
            </div>
            
            <!-- Rango de fechas -->
            <div class="flex gap-4 items-center">
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
            <!-- Programas -->
            <div class="relative flex-1">
                <select
                    v-model="filters.programId"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                    @change="onProgramChange"
                >
                    <option value="">Todos los programas</option>
                    <option v-for="program in programs" :key="program.id" :value="program.id">
                        {{ program.code }} - {{ program.name }} - {{ program.destination }}
                    </option>
                </select>
            </div>

            <!-- Ejecutivo Comercial -->
            <div class="relative flex-shrink-0 w-[200px]">
                <select
                    v-model="filters.salesExecutiveId"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                    @change="performSearch"
                >
                    <option value="">Ejecutivo comercial</option>
                    <option v-for="executive in salesExecutives" :key="executive.id" :value="executive.id">
                        {{ executive.name }}
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
        }
    },
    data() {
        return {
            filters: {
                programId: this.initialFilters.programId || "",
                salesExecutiveId: this.initialFilters.salesExecutiveId || "",
                dateFrom: this.initialFilters.dateFrom || "",
                dateTo: this.initialFilters.dateTo || "",
            }
        };
    },
    mounted() {
        // Establecer fecha por defecto (último mes) si no hay fechas
        if (!this.filters.dateFrom && !this.filters.dateTo) {
            const today = new Date();
            const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
            this.filters.dateFrom = lastMonth.toISOString().split('T')[0];
            this.filters.dateTo = today.toISOString().split('T')[0];
            this.performSearch();
        }
    },
    methods: {
        performSearch: _.debounce(function () {
            console.log('🔍 ExecutivesConsolidatedFilters - Filtros enviados:', {
                filters: this.filters
            });
            this.$emit('filters-changed', this.filters);
        }, 300),
        
        onProgramChange() {
            console.log('🔍 ExecutivesConsolidatedFilters - Programa seleccionado:', this.filters.programId);
            this.performSearch();
        },
        
        clearFilters() {
            console.log('🔍 ExecutivesConsolidatedFilters - Limpiando filtros');
            const today = new Date();
            const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
            
            this.filters = {
                programId: "",
                salesExecutiveId: "",
                dateFrom: lastMonth.toISOString().split('T')[0],
                dateTo: today.toISOString().split('T')[0],
            };
            console.log('🔍 ExecutivesConsolidatedFilters - Filtros después de limpiar:', this.filters);
            this.performSearch();
        }
    }
};
</script>
