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
            <!-- Search Input - RUT o Apellido del Participante -->
            <div class="relative w-[297.28px]">
                <input
                    v-model="filters.participant_name"
                    type="text"
                    placeholder="Buscar por RUT o Apellido"
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

            <!-- Código de Programa - Input predictivo -->
            <div class="relative w-[220px]" ref="programFilter">
                <input
                    v-model="programSearch"
                    type="text"
                    placeholder="Código de Programa"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none pr-8"
                    @input="onProgramSearchInput"
                    @focus="showProgramDropdown = true"
                    @keydown.escape="showProgramDropdown = false"
                />
                <!-- Clear button -->
                <button
                    v-if="programSearch"
                    @click="clearProgramFilter"
                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <!-- Dropdown -->
                <div
                    v-if="showProgramDropdown && filteredPrograms.length > 0"
                    class="absolute z-50 w-[300px] mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                >
                    <div
                        v-for="program in filteredPrograms"
                        :key="program.id"
                        @mousedown.prevent="selectProgram(program)"
                        class="px-4 py-2 cursor-pointer hover:bg-[#007e93] hover:text-white text-[12px] border-b border-gray-50"
                    >
                        <span class="font-bold">{{ program.code }}</span>
                        <span class="ml-1 text-[11px] opacity-75">{{ program.name }}</span>
                    </div>
                </div>
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
                    <option value="presencial">Pago Offline</option>
                    <option value="virtualpos">VirtualPos (Suscripciones)</option>
                    <option value="refund">Devoluciones</option>
                </select>
            </div>

            <!-- Estado del Pago Dropdown -->
            <div class="relative w-[160px]">
                <select
                    v-model="filters.status"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-12"
                    @change="performSearch"
                >
                    <option value="all">Todos los Estados</option>
                    <option value="completed">Pagado</option>
                    <option value="pending">Pendiente</option>
                    <option value="failed">Fallido</option>
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

            <!-- BSale Monitor Button -->
            <a
                :href="route('admin.bsale-monitor.index')"
                class="h-[46px] px-6 bg-teal-600 hover:bg-teal-700 text-white rounded-[50px] text-left font-nexa-regular text-[12px] leading-[18px] font-normal transition-colors duration-200 flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Monitor BSale
            </a>
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
                program_id: this.initialFilters.program_id || "",
                payment_method: this.initialFilters.payment_method || "all",
                status: this.initialFilters.status || "all",
                date_from: this.initialFilters.date_from || "",
                date_to: this.initialFilters.date_to || "",
            },
            programSearch: "",
            showProgramDropdown: false,
        };
    },

    computed: {
        filteredPrograms() {
            if (!this.programSearch) return this.programs;
            const search = this.programSearch.toLowerCase();
            return this.programs.filter(p =>
                (p.code && p.code.toLowerCase().includes(search)) ||
                (p.name && p.name.toLowerCase().includes(search))
            );
        }
    },

    watch: {
        initialFilters: {
            handler(newFilters) {
                this.filters = {
                    participant_name: newFilters.participant_name || "",
                    program_id: newFilters.program_id || "",
                    payment_method: newFilters.payment_method || "all",
                    status: newFilters.status || "all",
                    date_from: newFilters.date_from || "",
                    date_to: newFilters.date_to || "",
                };
                // Restaurar texto del programa seleccionado
                if (newFilters.program_id) {
                    const program = this.programs.find(p => p.id == newFilters.program_id);
                    if (program) this.programSearch = program.code || program.name;
                }
            },
            deep: true,
            immediate: true
        }
    },

    mounted() {
        // Cerrar dropdown al hacer click fuera
        document.addEventListener('click', this.handleClickOutside);
        // Restaurar texto del programa si viene con filtro inicial
        if (this.filters.program_id) {
            const program = this.programs.find(p => p.id == this.filters.program_id);
            if (program) this.programSearch = program.code || program.name;
        }
    },

    beforeUnmount() {
        document.removeEventListener('click', this.handleClickOutside);
    },

    methods: {
        performSearch: _.debounce(function () {
            this.$emit('filters-changed', this.filters);
        }, 300),

        onProgramSearchInput() {
            this.showProgramDropdown = true;
            // Si se borró el texto, limpiar el filtro
            if (!this.programSearch) {
                this.filters.program_id = "";
                this.performSearch();
            }
        },

        selectProgram(program) {
            this.programSearch = program.code || program.name;
            this.filters.program_id = program.id;
            this.showProgramDropdown = false;
            this.performSearch();
        },

        clearProgramFilter() {
            this.programSearch = "";
            this.filters.program_id = "";
            this.showProgramDropdown = false;
            this.performSearch();
        },

        handleClickOutside(event) {
            if (this.$refs.programFilter && !this.$refs.programFilter.contains(event.target)) {
                this.showProgramDropdown = false;
            }
        },

        clearFilters() {
            this.filters = {
                participant_name: "",
                program_id: "",
                payment_method: "all",
                status: "all",
                date_from: "",
                date_to: "",
            };
            this.programSearch = "";
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
