<template>
    <div class="flex flex-col gap-[18px] items-start justify-start relative">
        <!-- Header Row con título -->
        <div class="flex flex-row items-center justify-between w-full relative gap-4 flex-wrap">
            <div class="text-[20px] leading-7 font-normal text-[#1c4f4a] font-nexa-regular">
                Filtros de búsqueda
            </div>
        </div>

        <!-- Filtros Row -->
        <div class="flex flex-wrap gap-[15px] items-center justify-start w-full relative">
            <!-- Buscador por código o nombre -->
            <div class="relative flex-1 min-w-[200px] max-w-[300px]">
                <input
                    v-model="filters.search"
                    type="text"
                    placeholder="Buscar por código o nombre..."
                    class="w-full h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none"
                    @input="performSearch"
                />
            </div>

            <!-- Filtro por medio de pago -->
            <div class="relative w-[280px]" data-payment-option-search>
                <div class="relative">
                    <input
                        v-model="paymentOptionSearchQuery"
                        type="text"
                        placeholder="Filtrar por medio de pago..."
                        class="w-full h-[46px] bg-white rounded-[50px] border px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none pr-10"
                        :class="{
                            'border-green-500': selectedPaymentOption,
                            'border-[#f0f0f0]': !selectedPaymentOption
                        }"
                        @input="showPaymentOptionDropdown = true"
                        @focus="showPaymentOptionDropdown = true"
                        @keydown.escape="showPaymentOptionDropdown = false"
                        @keydown.down.prevent="navigateDropdown(1)"
                        @keydown.up.prevent="navigateDropdown(-1)"
                        @keydown.enter.prevent="selectHighlighted"
                    />
                    <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                        <button
                            v-if="selectedPaymentOption"
                            type="button"
                            @click="clearPaymentOptionSelection"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>

                <!-- Dropdown de opciones de pago -->
                <div
                    v-if="showPaymentOptionDropdown && filteredPaymentOptions.length > 0"
                    class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-64 overflow-y-auto"
                >
                    <!-- Opción "Todos los medios de pago" -->
                    <div
                        @click="selectAllPaymentOptions"
                        @mouseenter="highlightedIndex = -1"
                        class="px-3 py-2 cursor-pointer border-b border-gray-100 transition-colors text-sm"
                        :class="{
                            'bg-[#007e93] text-white': highlightedIndex === -1,
                            'hover:bg-gray-50 text-gray-700': highlightedIndex !== -1
                        }"
                    >
                        Todos los medios de pago
                    </div>
                    <div
                        v-for="(option, index) in filteredPaymentOptions"
                        :key="option.id"
                        @click="selectPaymentOption(option)"
                        @mouseenter="highlightedIndex = index"
                        class="px-3 py-2 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors text-sm"
                        :class="{
                            'bg-[#007e93] text-white': highlightedIndex === index,
                            'hover:bg-gray-50': highlightedIndex !== index
                        }"
                    >
                        {{ cleanLabel(option.label) }}
                    </div>
                </div>
            </div>

            <!-- Filtro por ejecutivo comercial -->
            <div class="relative w-[280px]" data-executive-search>
                <div class="relative">
                    <input
                        v-model="executiveSearchQuery"
                        type="text"
                        placeholder="Filtrar por ejecutivo..."
                        class="w-full h-[46px] bg-white rounded-[50px] border px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none pr-10"
                        :class="{
                            'border-green-500': selectedExecutive,
                            'border-[#f0f0f0]': !selectedExecutive
                        }"
                        @input="showExecutiveDropdown = true"
                        @focus="showExecutiveDropdown = true"
                        @keydown.escape="showExecutiveDropdown = false"
                        @keydown.down.prevent="navigateExecutiveDropdown(1)"
                        @keydown.up.prevent="navigateExecutiveDropdown(-1)"
                        @keydown.enter.prevent="selectHighlightedExecutive"
                    />
                    <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                        <button
                            v-if="selectedExecutive"
                            type="button"
                            @click="clearExecutiveSelection"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>

                <!-- Dropdown de ejecutivos -->
                <div
                    v-if="showExecutiveDropdown && filteredExecutives.length > 0"
                    class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-64 overflow-y-auto"
                >
                    <!-- Opción "Todos los ejecutivos" -->
                    <div
                        @click="selectAllExecutives"
                        @mouseenter="highlightedExecutiveIndex = -1"
                        class="px-3 py-2 cursor-pointer border-b border-gray-100 transition-colors text-sm"
                        :class="{
                            'bg-[#007e93] text-white': highlightedExecutiveIndex === -1,
                            'hover:bg-gray-50 text-gray-700': highlightedExecutiveIndex !== -1
                        }"
                    >
                        Todos los ejecutivos
                    </div>
                    <div
                        v-for="(executive, index) in filteredExecutives"
                        :key="executive.id"
                        @click="selectExecutive(executive)"
                        @mouseenter="highlightedExecutiveIndex = index"
                        class="px-3 py-2 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors text-sm"
                        :class="{
                            'bg-[#007e93] text-white': highlightedExecutiveIndex === index,
                            'hover:bg-gray-50': highlightedExecutiveIndex !== index
                        }"
                    >
                        {{ executive.name }}
                    </div>
                </div>
            </div>

            <!-- Botón limpiar filtros -->
            <button
                @click="clearFilters"
                class="h-[46px] px-6 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-[50px] border border-gray-300 text-left font-nexa-regular text-[12px] leading-[18px] font-normal transition-colors duration-200 flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Limpiar
            </button>
        </div>
    </div>
</template>

<script>
import _ from "lodash";

export default {
    name: "PaymentOptionsProgramFilters",
    props: {
        initialFilters: {
            type: Object,
            default: () => ({})
        },
        paymentOptions: {
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
                paymentOptionId: this.initialFilters.paymentOptionId || "",
                salesExecutiveId: this.initialFilters.salesExecutiveId || "",
                search: this.initialFilters.search || "",
            },
            paymentOptionSearchQuery: "",
            showPaymentOptionDropdown: false,
            highlightedIndex: -1,
            selectedPaymentOption: null,
            executiveSearchQuery: "",
            showExecutiveDropdown: false,
            highlightedExecutiveIndex: -1,
            selectedExecutive: null,
        };
    },
    computed: {
        filteredPaymentOptions() {
            const sortedOptions = [...this.paymentOptions].sort((a, b) => {
                const labelA = this.cleanLabel(a.label || '').toLowerCase();
                const labelB = this.cleanLabel(b.label || '').toLowerCase();
                return labelA.localeCompare(labelB);
            });

            if (!this.paymentOptionSearchQuery) {
                return sortedOptions;
            }

            const query = this.paymentOptionSearchQuery.toLowerCase();
            return sortedOptions.filter(option => {
                const label = this.cleanLabel(option.label || '').toLowerCase();
                return label.includes(query);
            });
        },
        filteredExecutives() {
            const sortedExecutives = [...this.salesExecutives].sort((a, b) => {
                return (a.name || '').localeCompare(b.name || '');
            });

            if (!this.executiveSearchQuery) {
                return sortedExecutives;
            }

            const query = this.executiveSearchQuery.toLowerCase();
            return sortedExecutives.filter(executive => {
                const name = (executive.name || '').toLowerCase();
                return name.includes(query);
            });
        }
    },
    mounted() {
        if (this.filters.paymentOptionId) {
            const option = this.paymentOptions.find(o => o.id == this.filters.paymentOptionId);
            if (option) {
                this.selectedPaymentOption = option;
                this.paymentOptionSearchQuery = this.cleanLabel(option.label);
            }
        }

        if (this.filters.salesExecutiveId) {
            const executive = this.salesExecutives.find(e => e.id == this.filters.salesExecutiveId);
            if (executive) {
                this.selectedExecutive = executive;
                this.executiveSearchQuery = executive.name;
            }
        }

        document.addEventListener('click', this.handleClickOutside);
    },
    beforeUnmount() {
        document.removeEventListener('click', this.handleClickOutside);
    },
    methods: {
        cleanLabel(label) {
            if (!label) return '';
            // Quitar "(Webpay)", "(Khipu)", "(VirtualPos)" etc del final
            return label
                .replace(/\s*\(Webpay\)\s*$/i, '')
                .replace(/\s*\(Khipu\)\s*$/i, '')
                .replace(/\s*\(VirtualPos\)\s*$/i, '')
                .trim();
        },

        performSearch: _.debounce(function () {
            this.$emit('filters-changed', this.filters);
        }, 300),

        navigateDropdown(direction) {
            const maxIndex = this.filteredPaymentOptions.length - 1;
            this.highlightedIndex += direction;

            if (this.highlightedIndex < -1) {
                this.highlightedIndex = maxIndex;
            } else if (this.highlightedIndex > maxIndex) {
                this.highlightedIndex = -1;
            }
        },

        selectHighlighted() {
            if (this.highlightedIndex === -1) {
                this.selectAllPaymentOptions();
            } else if (this.filteredPaymentOptions.length > 0 && this.highlightedIndex >= 0) {
                this.selectPaymentOption(this.filteredPaymentOptions[this.highlightedIndex]);
            }
        },

        selectPaymentOption(option) {
            this.selectedPaymentOption = option;
            this.filters.paymentOptionId = option.id;
            this.paymentOptionSearchQuery = this.cleanLabel(option.label);
            this.showPaymentOptionDropdown = false;
            this.performSearch();
        },

        selectAllPaymentOptions() {
            this.selectedPaymentOption = null;
            this.filters.paymentOptionId = "";
            this.paymentOptionSearchQuery = "";
            this.showPaymentOptionDropdown = false;
            this.performSearch();
        },

        clearPaymentOptionSelection() {
            this.selectedPaymentOption = null;
            this.filters.paymentOptionId = "";
            this.paymentOptionSearchQuery = "";
            this.performSearch();
        },

        handleClickOutside(event) {
            const paymentOptionContainer = event.target.closest('[data-payment-option-search]');
            const executiveContainer = event.target.closest('[data-executive-search]');

            if (!paymentOptionContainer) {
                this.showPaymentOptionDropdown = false;
            }
            if (!executiveContainer) {
                this.showExecutiveDropdown = false;
            }
        },

        navigateExecutiveDropdown(direction) {
            const maxIndex = this.filteredExecutives.length - 1;
            this.highlightedExecutiveIndex += direction;

            if (this.highlightedExecutiveIndex < -1) {
                this.highlightedExecutiveIndex = maxIndex;
            } else if (this.highlightedExecutiveIndex > maxIndex) {
                this.highlightedExecutiveIndex = -1;
            }
        },

        selectHighlightedExecutive() {
            if (this.highlightedExecutiveIndex === -1) {
                this.selectAllExecutives();
            } else if (this.filteredExecutives.length > 0 && this.highlightedExecutiveIndex >= 0) {
                this.selectExecutive(this.filteredExecutives[this.highlightedExecutiveIndex]);
            }
        },

        selectExecutive(executive) {
            this.selectedExecutive = executive;
            this.filters.salesExecutiveId = executive.id;
            this.executiveSearchQuery = executive.name;
            this.showExecutiveDropdown = false;
            this.performSearch();
        },

        selectAllExecutives() {
            this.selectedExecutive = null;
            this.filters.salesExecutiveId = "";
            this.executiveSearchQuery = "";
            this.showExecutiveDropdown = false;
            this.performSearch();
        },

        clearExecutiveSelection() {
            this.selectedExecutive = null;
            this.filters.salesExecutiveId = "";
            this.executiveSearchQuery = "";
            this.performSearch();
        },

        clearFilters() {
            this.filters = {
                paymentOptionId: "",
                salesExecutiveId: "",
                search: "",
            };
            this.selectedPaymentOption = null;
            this.paymentOptionSearchQuery = "";
            this.selectedExecutive = null;
            this.executiveSearchQuery = "";
            this.performSearch();
        }
    }
};
</script>
