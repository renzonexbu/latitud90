<template>
    <div class="flex flex-col gap-[27px] items-start justify-start relative">
        <!-- Header Row -->
        <div class="flex flex-row items-start justify-between flex-shrink-0 w-full relative">
            <div class="text-[20px] leading-7 font-normal text-[#1c4f4a] font-nexa-regular">
                Filtros de Pagos
            </div>
        </div>

        <!-- Filters Row -->
        <div class="flex flex-wrap gap-5 items-center justify-start w-full relative">
            <!-- Search Input - Nombre del Participante -->
            <div class="relative w-[297.28px]">
                <input
                    v-model="filters.participant_name"
                    type="text"
                    placeholder="Buscar participante"
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

            <!-- Estado del Pago Dropdown -->
            <div class="relative w-[180px]">
                <select
                    v-model="filters.payment_status"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-12"
                    @change="performSearch"
                >
                    <option value="all">Todos los Estados</option>
                    <option value="pending">Pendiente</option>
                    <option value="completed">Completado</option>
                    <option value="failed">Fallido</option>
                    <option value="cancelled">Cancelado</option>
                    <option value="refunded">Reembolsado</option>
                </select>
            </div>

            <!-- Código de Programa Dropdown -->
            <div class="relative w-[180px]">
                <select
                    v-model="filters.program_id"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-12"
                    @change="performSearch"
                >
                    <option value="">Código de Programa</option>
                    <option v-for="program in programs" :key="program.id" :value="program.id">
                        {{ program.code || program.name }}
                    </option>
                </select>
            </div>

            <!-- Método de Pago Dropdown -->
            <div class="relative w-[180px]">
                <select
                    v-model="filters.payment_method"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-12"
                    @change="performSearch"
                >
                    <option value="all">Todos los Métodos</option>
                    <option value="transbank">Webpay (Tarjeta)</option>
                    <option value="khipu">Transferencia Khipu</option>
                    <option value="presencial">Pago Presencial</option>
                    <option value="virtualpos">VirtualPos (Suscripciones)</option>
                    <option value="refund">Devoluciones</option>
                </select>
            </div>

            <!-- Rango de Fechas -->
            <div class="flex gap-2">
                <div class="relative w-[140px]">
                    <input
                        v-model="filters.date_from"
                        type="date"
                        class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none"
                        @change="performSearch"
                    />
                </div>
                <div class="relative w-[140px]">
                    <input
                        v-model="filters.date_to"
                        type="date"
                        class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none"
                        @change="performSearch"
                    />
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
                Limpiar Filtros
            </button>
        </div>
    </div>
</template>

<script>
import _ from "lodash";

export default {
    name: "PaymentsFilters",
    props: {
        initialFilters: {
            type: Object,
            default: () => ({})
        },
        programs: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            filters: {
                participant_name: this.initialFilters.participant_name || "",
                payment_status: this.initialFilters.payment_status || "all",
                program_id: this.initialFilters.program_id || "",
                payment_method: this.initialFilters.payment_method || "all",
                date_from: this.initialFilters.date_from || "",
                date_to: this.initialFilters.date_to || "",
            }
        };
    },

    watch: {
        initialFilters: {
            handler(newFilters) {
                this.filters = {
                    participant_name: newFilters.participant_name || "",
                    payment_status: newFilters.payment_status || "all",
                    program_id: newFilters.program_id || "",
                    payment_method: newFilters.payment_method || "all",
                    date_from: newFilters.date_from || "",
                    date_to: newFilters.date_to || "",
                };
            },
            deep: true,
            immediate: true
        }
    },
    methods: {
        performSearch: _.debounce(function () {
            this.$emit('filters-changed', this.filters);
        }, 300),
        
        clearFilters() {
            this.filters = {
                participant_name: "",
                payment_status: "all",
                program_id: "",
                payment_method: "all",
                date_from: "",
                date_to: "",
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

<style scoped>
.bg-turquesa {
    background-color: #007e93;
}

.font-nexa-regular {
    font-family: "Nexa-Regular", sans-serif;
    font-weight: 400;
}

.font-nexa-bold {
    font-family: "Nexa-Bold", sans-serif;
    font-weight: 700;
}
</style>