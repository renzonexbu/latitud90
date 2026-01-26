<template>
    <div class="flex flex-row items-center justify-between w-full relative gap-4">
        <!-- Search Input -->
        <div class="relative w-[300px]">
            <input
                v-model="searchTerm"
                type="text"
                placeholder="Buscar plantilla..."
                class="w-full h-[45.79px] bg-white rounded-[50px] border border-[#f0f0f0] px-[17px] py-2 text-[#434343] text-left font-nexa-bold text-[12px] leading-[18px] font-bold pr-12 outline-none"
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

        <!-- Toggle Ver plantillas activas -->
        <div class="flex flex-row gap-[15.77px] items-center justify-start flex-shrink-0 relative">
            <div class="text-[15.77px] leading-[20.27px] font-normal text-black font-nexa-regular w-[173px] h-[21px] flex items-end justify-start">
                Ver plantillas activas
            </div>
            <!-- Toggle Switch -->
            <div
                class="bg-[#f9f9f9] rounded-[45.05px] border border-turquesa flex-shrink-0 w-[39.42px] h-[16.9px] relative overflow-hidden cursor-pointer"
                @click="toggleActivePrograms"
            >
                <div
                    class="bg-turquesa rounded-[45.05px] w-[16.9px] h-[16.9px] absolute transition-all duration-300"
                    :class="showActivePrograms ? 'left-[22.52px]' : 'left-0'"
                ></div>
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
        },
        programs: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            searchTerm: this.initialFilters.search || "",
            showActivePrograms: true
        };
    },
    methods: {
        performSearch: _.debounce(function () {
            this.$emit('filters-changed', {
                search: this.searchTerm,
                active: this.showActivePrograms
            });
        }, 300),

        toggleActivePrograms() {
            this.showActivePrograms = !this.showActivePrograms;
            this.$emit('filters-changed', {
                search: this.searchTerm,
                active: this.showActivePrograms
            });
        }
    }
};
</script>
