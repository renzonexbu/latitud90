<template>
    <div class="flex flex-col gap-[27px] items-start justify-start relative">
        <!-- Header Row -->
        <div class="flex flex-row items-start justify-start flex-shrink-0 w-full relative">
            <div class="text-[20px] leading-7 font-normal text-[#1c4f4a] font-nexa-regular">
                Filtros de búsqueda
            </div>
        </div>

        <!-- Filters Row con exportar -->
        <div class="flex flex-wrap gap-5 items-center justify-between w-full relative">
            <!-- Filtros a la izquierda -->
            <div class="flex flex-wrap gap-5 items-center justify-start">
                <!-- Search Input -->
                <div class="relative w-[297.28px]">
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Buscar por email o nombre"
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

                <!-- Status Dropdown -->
                <div class="relative w-[150px]">
                    <select
                        v-model="filters.status"
                        class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-12"
                        @change="performSearch"
                    >
                        <option value="">Estado</option>
                        <option value="active">Activo</option>
                        <option value="inactive">Inactivo</option>
                        <option value="unsubscribed">Desuscrito</option>
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

            <!-- Botón Exportar a la derecha -->
            <button
                @click="$emit('export')"
                class="bg-turquesa hover:bg-turquesa-dark text-white rounded-[112.89px] px-[18px] py-[14px] flex flex-row gap-[11.29px] items-center justify-center transition-colors"
            >
                <div class="flex-shrink-0 w-[21.52px] h-[21.52px] relative overflow-hidden aspect-square">
                    <svg class="w-full h-full text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="text-white text-left font-nexa-bold text-base leading-[22px] font-bold relative flex items-end justify-start">
                    Exportar a Excel
                </div>
            </button>
        </div>
    </div>
</template>

<script>
import _ from "lodash";

export default {
    name: "NewsletterFilters",
    props: {
        initialFilters: {
            type: Object,
            default: () => ({})
        },
        newsletters: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            filters: {
                search: this.initialFilters.search || "",
                status: this.initialFilters.status || "",
            }
        };
    },

    methods: {
        performSearch: _.debounce(function () {
            this.$emit('filters-changed', this.filters);
        }, 300),
        
        clearFilters() {
            this.filters = {
                search: "",
                status: "",
            };
            this.performSearch();
        }
    }
};
</script>
