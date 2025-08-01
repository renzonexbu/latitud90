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
                    placeholder="Buscar orden o participante"
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
            <div class="relative w-[160px]">
                <select
                    v-model="filters.status"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-12"
                    @change="performSearch"
                >
                    <option value="">Estado de pago</option>
                    <option value="pending">Pendiente</option>
                    <option value="authorized">Autorizado</option>
                    <option value="completed">Completado</option>
                    <option value="failed">Fallido</option>
                    <option value="reversed">Reversado</option>
                    <option value="nullified">Anulado</option>
                </select>
            </div>

            <!-- Card Type Dropdown -->
            <div class="relative w-[140px]">
                <select
                    v-model="filters.card_type"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-12"
                    @change="performSearch"
                >
                    <option value="">Tipo de tarjeta</option>
                    <option value="Visa">Visa</option>
                    <option value="Mastercard">Mastercard</option>
                    <option value="American Express">American Express</option>
                    <option value="Diners">Diners</option>
                </select>
            </div>

            <!-- Amount Range Dropdown -->
            <div class="relative w-[150px]">
                <select
                    v-model="filters.amount_range"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-12"
                    @change="performSearch"
                >
                    <option value="">Rango de monto</option>
                    <option value="0-300000">$0 - $300.000</option>
                    <option value="300000-500000">$300.000 - $500.000</option>
                    <option value="500000-700000">$500.000 - $700.000</option>
                    <option value="700000+">$700.000+</option>
                </select>
            </div>

            <!-- Date Range Dropdown -->
            <div class="relative w-[140px]">
                <select
                    v-model="filters.date_range"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-12"
                    @change="performSearch"
                >
                    <option value="">Fecha</option>
                    <option value="today">Hoy</option>
                    <option value="week">Esta semana</option>
                    <option value="month">Este mes</option>
                    <option value="quarter">Este trimestre</option>
                    <option value="year">Este año</option>
                </select>
            </div>
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
        }
    },
    data() {
        return {
            filters: {
                search: this.initialFilters.search || "",
                status: this.initialFilters.status || "",
                card_type: this.initialFilters.card_type || "",
                amount_range: this.initialFilters.amount_range || "",
                date_range: this.initialFilters.date_range || "",
            }
        };
    },
    methods: {
        performSearch: _.debounce(function () {
            this.$emit('filters-changed', this.filters);
        }, 300)
    }
};
</script>