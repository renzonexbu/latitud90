<template>
    <div class="flex flex-col gap-[27px] items-start justify-start relative">
        <!-- Header Row -->
        <div class="flex flex-row items-start justify-between flex-shrink-0 w-full relative">
            <div class="text-[20px] leading-7 font-normal text-[#1c4f4a] font-nexa-regular">
                Filtros de busqueda
            </div>
            <div class="flex flex-row gap-[15.77px] items-center justify-start flex-shrink-0 relative">
                <div class="text-[15.77px] leading-[20.27px] font-normal text-black font-nexa-regular w-[173px] h-[21px] flex items-end justify-start">
                    Ver programas activos
                </div>
                <!-- Toggle Switch -->
                <div 
                    class="bg-[#f9f9f9] rounded-[45.05px] border border-turquesa border-[1.13px] flex-shrink-0 w-[39.42px] h-[16.9px] relative overflow-hidden cursor-pointer"
                    @click="toggleActivePrograms"
                >
                    <div 
                        class="bg-turquesa rounded-[45.05px] w-[16.9px] h-[16.9px] absolute transition-all duration-300"
                        :class="showActivePrograms ? 'left-[22.52px]' : 'left-0'"
                    ></div>
                </div>
            </div>
        </div>

        <!-- Filters Row -->
        <div class="flex flex-row gap-5 items-center justify-start flex-shrink-0 w-full relative">
            <!-- Search Input -->
            <div class="relative w-[297.28px]">
                <input
                    v-model="filters.search"
                    type="text"
                    placeholder="Buscar programa"
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

            <!-- Destination Dropdown -->
            <div class="relative w-[203px]">
                <select
                    v-model="filters.destination"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-12"
                    @change="performSearch"
                >
                    <option value="">Destino</option>
                    <option value="patagonia">Patagonia</option>
                    <option value="santiago">Santiago</option>
                    <option value="valparaiso">Valparaíso</option>
                    <option value="puerto-montt">Puerto Montt</option>
                </select>
            </div>

            <!-- Payment Percentage Dropdown -->
            <div class="relative w-[173.21px]">
                <select
                    v-model="filters.paymentPercentage"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-12"
                    @change="performSearch"
                >
                    <option value="">Porcentaje de pago</option>
                    <option value="25">25%</option>
                    <option value="50">50%</option>
                    <option value="75">75%</option>
                    <option value="100">100%</option>
                </select>
            </div>

            <!-- Combined Institution, Level, Grade Dropdown -->
            <div class="bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 flex flex-row items-center justify-start h-[46px] shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] min-w-[300px]">
                <!-- Institution -->
                <div class="relative flex-1">
                    <select
                        v-model="filters.institution"
                        class="w-full text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal bg-transparent border-none outline-none appearance-none pr-8"
                        @change="performSearch"
                    >
                        <option value="">Institución</option>
                        <option value="universidad-chile">Universidad de Chile</option>
                        <option value="universidad-catolica">Universidad Católica</option>
                        <option value="universidad-santiago">Universidad de Santiago</option>
                    </select>

                </div>
                
                <!-- Separator -->
                <div class="w-px h-[22px] bg-gray-300 mx-2"></div>

                <!-- Level -->
                <div class="relative w-20">
                    <select
                        v-model="filters.level"
                        class="w-full text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal bg-transparent border-none outline-none appearance-none pr-8"
                        @change="performSearch"
                    >
                        <option value="">Nivel</option>
                        <option value="basico">Básico</option>
                        <option value="intermedio">Intermedio</option>
                        <option value="avanzado">Avanzado</option>
                    </select>

                </div>

                <!-- Separator -->
                <div class="w-px h-[22px] bg-gray-300 mx-2"></div>

                <!-- Grade -->
                <div class="relative w-20">
                    <select
                        v-model="filters.grade"
                        class="w-full text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal bg-transparent border-none outline-none appearance-none pr-8"
                        @change="performSearch"
                    >
                        <option value="">Grado</option>
                        <option value="1">1°</option>
                        <option value="2">2°</option>
                        <option value="3">3°</option>
                        <option value="4">4°</option>
                    </select>

                </div>
            </div>
        </div>
    </div>
</template>

<script>
import _ from "lodash";

export default {
    name: "ProgramsFilters",
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
                destination: this.initialFilters.destination || "",
                paymentPercentage: this.initialFilters.paymentPercentage || "",
                institution: this.initialFilters.institution || "",
                level: this.initialFilters.level || "",
                grade: this.initialFilters.grade || "",
            },
            showActivePrograms: true
        };
    },
    methods: {
        performSearch: _.debounce(function () {
            this.$emit('filters-changed', {
                ...this.filters,
                active: this.showActivePrograms
            });
        }, 300),
        toggleActivePrograms() {
            this.showActivePrograms = !this.showActivePrograms;
            this.performSearch();
        }
    }
};
</script> 