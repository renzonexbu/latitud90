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
                    placeholder="Buscar por nombre, email o documento..."
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

        <!-- Filtros Row -->
        <div class="flex flex-wrap gap-[15px] items-center justify-start w-full relative">
            <!-- Estado -->
            <div class="relative flex-shrink-0 w-[150px]">
                <select
                    v-model="filters.status"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                    @change="performSearch"
                >
                    <option value="">Estado</option>
                    <option value="active">Activo</option>
                    <option value="pending_payment">Pago Pendiente</option>
                    <option value="suspended">Suspendido</option>
                </select>
            </div>

            <!-- Email verificado -->
            <div class="relative flex-shrink-0 w-[160px]">
                <select
                    v-model="filters.email_verified"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                    @change="performSearch"
                >
                    <option value="">Email verificado</option>
                    <option value="verified">Verificado</option>
                    <option value="unverified">Sin verificar</option>
                </select>
            </div>

            <!-- Fecha desde -->
            <div class="relative flex-shrink-0 w-[134px]">
                <input
                    v-model="filters.date_from"
                    type="date"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none"
                    @change="performSearch"
                />
            </div>

            <!-- Fecha hasta -->
            <div class="relative flex-shrink-0 w-[134px]">
                <input
                    v-model="filters.date_to"
                    type="date"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none"
                    @change="performSearch"
                />
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
    name: "GuardianUsersFilters",
    props: {
        initialFilters: {
            type: Object,
            default: () => ({})
        }
    },
    data() {
        return {
            filters: {
                search: this.initialFilters.search || "",
                status: this.initialFilters.status || "",
                email_verified: this.initialFilters.email_verified || "",
                date_from: this.initialFilters.date_from || "",
                date_to: this.initialFilters.date_to || "",
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
                email_verified: "",
                date_from: "",
                date_to: "",
            };
            this.performSearch();
        }
    }
};
</script>
