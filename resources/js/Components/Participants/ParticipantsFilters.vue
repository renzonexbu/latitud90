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
                    placeholder="Buscar usuario"
                    class="w-full h-[45.79px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-[17px] py-2 text-[#434343] text-left font-nexa-bold text-[12px] leading-[18px] font-bold pr-12 outline-none"
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
        <div class="flex flex-row gap-[15px] items-center justify-start w-[1144px] relative">
            <!-- Programa de viaje -->
            <div class="relative flex-1">
                <select
                    v-model="filters.program"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                    @change="performSearch"
                >
                    <option value="">Programa de viaje</option>
                    <option value="ruta-lagos">Ruta de lagos</option>
                    <option value="patagonia">Patagonia</option>
                    <option value="norte-grande">Norte Grande</option>
                </select>
            </div>

            <!-- Institución -->
            <div class="relative flex-shrink-0 w-[129px]">
                <select
                    v-model="filters.institution"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                    @change="performSearch"
                >
                    <option value="">Institución</option>
                    <option value="pedro-valdivia">Pedro de Valdivia</option>
                    <option value="colegio-maria">Colegio María</option>
                    <option value="liceo-jose">Liceo José</option>
                </select>
            </div>

            <!-- Nivel de educación -->
            <div class="relative flex-shrink-0 w-[134px]">
                <select
                    v-model="filters.level"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                    @change="performSearch"
                >
                    <option value="">Nivel de educación</option>
                    <option value="preescolar">Preescolar</option>
                    <option value="primaria">Primaria</option>
                    <option value="secundaria">Secundaria</option>
                    <option value="universitaria">Universitaria</option>
                </select>
            </div>

            <!-- Grado -->
            <div class="relative flex-shrink-0 w-[110px]">
                <select
                    v-model="filters.grade"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                    @change="performSearch"
                >
                    <option value="">Grado</option>
                    <option value="1">1°</option>
                    <option value="2">2°</option>
                    <option value="3">3°</option>
                    <option value="4">4°</option>
                    <option value="5">5°</option>
                    <option value="6">6°</option>
                </select>
            </div>

            <!-- Turno -->
            <div class="relative flex-shrink-0 w-[110px]">
                <select
                    v-model="filters.turno"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                    @change="performSearch"
                >
                    <option value="">Turno</option>
                    <option value="mañana">Mañana</option>
                    <option value="tarde">Tarde</option>
                    <option value="noche">Noche</option>
                </select>
            </div>

            <!-- Estado de pago -->
            <div class="relative flex-shrink-0 w-[134px]">
                <select
                    v-model="filters.paymentStatus"
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] border-[1px] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none pr-8"
                    @change="performSearch"
                >
                    <option value="">Estado de pago</option>
                    <option value="pending_payment">Pendiente</option>
                    <option value="confirmed">Completado</option>
                    <option value="cancelled">Liberado</option>
                </select>
            </div>
        </div>
    </div>
</template>

<script>
import _ from "lodash";

export default {
    name: "ParticipantsFilters",
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
                program: this.initialFilters.program || "",
                institution: this.initialFilters.institution || "",
                level: this.initialFilters.level || "",
                grade: this.initialFilters.grade || "",
                turno: this.initialFilters.turno || "",
                paymentStatus: this.initialFilters.paymentStatus || "",
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