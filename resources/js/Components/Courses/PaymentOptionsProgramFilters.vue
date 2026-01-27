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

            <!-- Filtro por estado -->
            <select
                v-model="filters.active"
                @change="performSearch"
                class="h-[46px] bg-white rounded-[50px] border border-[#f0f0f0] px-4 py-2 text-black text-left font-nexa-regular text-[12px] leading-[18px] font-normal shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none min-w-[150px]"
            >
                <option value="">Todos los estados</option>
                <option value="active">Activos</option>
                <option value="inactive">Inactivos</option>
            </select>

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
        }
    },
    data() {
        return {
            filters: {
                paymentOptionId: this.initialFilters.paymentOptionId || "",
                active: this.initialFilters.active || "",
                search: this.initialFilters.search || "",
            },
            paymentOptionSearchQuery: "",
            showPaymentOptionDropdown: false,
            highlightedIndex: -1,
            selectedPaymentOption: null,
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
            const searchContainer = event.target.closest('[data-payment-option-search]');
            if (!searchContainer) {
                this.showPaymentOptionDropdown = false;
            }
        },

        clearFilters() {
            this.filters = {
                paymentOptionId: "",
                active: "",
                search: "",
            };
            this.selectedPaymentOption = null;
            this.paymentOptionSearchQuery = "";
            this.performSearch();
        }
    }
};
</script>
