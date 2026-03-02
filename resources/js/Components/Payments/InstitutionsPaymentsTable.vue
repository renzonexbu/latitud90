<template>
    <div class="bg-white rounded-[20px] p-6 flex flex-col gap-4 w-full">
        <!-- Header row: title + filters -->
        <div class="flex flex-col gap-4 w-full">
            <div class="flex flex-row items-center justify-between w-full">
                <div class="text-[#1C4F4A] font-nexa text-[20px] font-normal leading-[28px]">
                    Estado de pago por institución
                </div>
                <div class="flex flex-row items-center gap-3">
                    <!-- Filtro Ejecutivo Comercial con Autocomplete -->
                    <div class="relative" ref="executiveDropdown">
                        <div class="relative w-[220px]">
                            <input
                                v-model="executiveSearch"
                                type="text"
                                placeholder="Filtrar por ejecutivo"
                                @focus="showExecutiveDropdown = true"
                                @input="showExecutiveDropdown = true"
                                class="w-full h-[45.79px] bg-white rounded-[50px] border border-[#f0f0f0] px-[17px] py-2 text-[#434343] text-left font-nexa-bold text-[12px] leading-[18px] font-bold pr-10 outline-none shadow-[0px_0.83px_3.33px_0px_rgba(25,33,61,0.08)]"
                            />
                            <button
                                v-if="selectedExecutiveId"
                                @click="clearExecutiveFilter"
                                type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            <span v-else class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </div>
                        <!-- Dropdown opciones -->
                        <div
                            v-if="showExecutiveDropdown && filteredExecutives.length > 0"
                            class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                        >
                            <div
                                v-for="exec in filteredExecutives"
                                :key="exec.id"
                                @click="selectExecutive(exec)"
                                class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm text-gray-700"
                            >
                                {{ exec.name }} <span class="text-gray-400 text-xs">({{ exec.code }})</span>
                            </div>
                        </div>
                    </div>
                    <!-- Filtro Institución con Autocomplete -->
                    <div class="relative" ref="institutionDropdown">
                        <div class="relative w-[220px]">
                            <input
                                v-model="institutionSearch"
                                type="text"
                                placeholder="Filtrar por institución"
                                @focus="showInstitutionDropdown = true"
                                @input="handleInstitutionInput"
                                class="w-full h-[45.79px] bg-white rounded-[50px] border border-[#f0f0f0] px-[17px] py-2 text-[#434343] text-left font-nexa-bold text-[12px] leading-[18px] font-bold pr-10 outline-none shadow-[0px_0.83px_3.33px_0px_rgba(25,33,61,0.08)]"
                            />
                            <button
                                v-if="selectedInstitution"
                                @click="clearInstitutionFilter"
                                type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            <span v-else class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </div>
                        <!-- Dropdown opciones -->
                        <div
                            v-if="showInstitutionDropdown && filteredInstitutions.length > 0"
                            class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                        >
                            <div
                                v-for="inst in filteredInstitutions"
                                :key="inst"
                                @click="selectInstitution(inst)"
                                class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm text-gray-700"
                            >
                                {{ capitalize(inst) }}
                            </div>
                        </div>
                    </div>
                    <!-- Buscador general -->
                    <div class="relative w-[310px]">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Buscar por código de programa"
                            class="w-full h-[45.79px] bg-white rounded-[50px] border border-[#f0f0f0] px-[17px] py-2 text-[#434343] text-left font-nexa-bold text-[12px] leading-[18px] font-bold pr-12 outline-none shadow-[0px_0.83px_3.33px_0px_rgba(25,33,61,0.08)]"
                        />
                        <button
                            type="button"
                            class="absolute right-2 top-1/2 -translate-y-1/2 bg-turquesa rounded-[41.67px] w-[30px] h-[30px] flex items-center justify-center shadow-[0px_0.83px_3.33px_0px_rgba(25,33,61,0.08)]"
                            aria-label="Buscar"
                        >
                            <svg
                                class="w-[14.79px] h-[14.79px]"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    d="M21 21L16.514 16.506L21 21ZM19 10.5C19 15.194 15.194 19 10.5 19C5.806 19 2 15.194 2 10.5C2 5.806 5.806 2 10.5 2C15.194 2 19 5.806 19 10.5Z"
                                    stroke="white"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </button>
                    </div>
                    <!-- Botón Limpiar filtros -->
                    <button
                        @click="clearAllFilters"
                        type="button"
                        class="h-[45.79px] px-4 bg-white rounded-[50px] border border-[#f0f0f0] text-[#434343] font-nexa-bold text-[12px] leading-[18px] font-bold hover:bg-gray-50 shadow-[0px_0.83px_3.33px_0px_rgba(25,33,61,0.08)] flex items-center gap-2 whitespace-nowrap"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Limpiar filtros
                    </button>
                </div>
            </div>
        </div>

        <!-- Table with horizontal scroll -->
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full text-xs" style="min-width: 1200px;">
                    <!-- Head -->
                    <thead class="bg-[#007e93] sticky top-0 z-10">
                        <tr>
                            <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[180px]">Institución</th>
                            <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[100px]">Código de Programa</th>
                            <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[140px]">Destino</th>
                            <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[150px]">Ejecutivo Comercial</th>
                            <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[100px]">Fecha de Inicio</th>
                            <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">Participantes</th>
                            <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">% Pago</th>
                            <th class="px-2 py-2 text-right text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[180px]">Recaudado Total</th>
                        </tr>
                    </thead>

                    <!-- Body rows -->
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="(row, index) in paginatedRows"
                            :key="row.id || index"
                            :class="[
                                'hover:bg-gray-50 transition-colors',
                                index % 2 === 0 ? 'bg-white' : 'bg-gray-50'
                            ]"
                        >
                            <!-- Institución -->
                            <td class="px-2 py-2 whitespace-nowrap">
                                <div class="text-xs font-medium text-gray-900">
                                    {{ capitalize(row.institutionName) }}
                                </div>
                            </td>
                            <!-- Código de Programa -->
                            <td class="px-2 py-2 whitespace-nowrap text-center">
                                <div class="text-xs font-medium text-[#1c4f4a]">
                                    {{ row.programCode || '—' }}
                                </div>
                            </td>
                            <!-- Destino -->
                            <td class="px-2 py-2 whitespace-nowrap">
                                <div class="text-xs font-medium text-[#1c4f4a]">
                                    {{ capitalize(row.destination) }}
                                </div>
                            </td>
                            <!-- Ejecutivo Comercial -->
                            <td class="px-2 py-2 whitespace-nowrap">
                                <div class="text-xs text-gray-900">
                                    {{ capitalize(row.executiveName) }}
                                </div>
                            </td>
                            <!-- Fecha de Inicio -->
                            <td class="px-2 py-2 whitespace-nowrap text-center">
                                <div class="flex flex-col items-center">
                                    <span class="text-xs text-gray-900">
                                        {{ row.departureDateFormatted || '—' }}
                                    </span>
                                    <span
                                        v-if="row.departureDate"
                                        :class="[
                                            'text-[9px] font-medium',
                                            isPastDate(row.departureDate) ? 'text-gray-400' : 'text-green-600'
                                        ]"
                                    >
                                        {{ isPastDate(row.departureDate) ? 'Ejecutado' : 'Próximo' }}
                                    </span>
                                </div>
                            </td>
                            <!-- Participantes -->
                            <td class="px-2 py-2 whitespace-nowrap text-center">
                                <div class="text-xs text-gray-900">
                                    {{ row.students }}
                                </div>
                            </td>
                            <!-- % Pago badge -->
                            <td class="px-2 py-2 whitespace-nowrap text-center">
                                <span
                                    :class="[
                                        'inline-flex px-2 py-0.5 text-xs font-bold rounded-full text-white',
                                        getBadgeClass(row.percent ?? 0)
                                    ]"
                                >
                                    {{ row.percent ?? 0 }}%
                                </span>
                            </td>
                            <!-- Recaudado Total -->
                            <td class="px-2 py-2 whitespace-nowrap text-right">
                                <div class="text-xs font-bold text-gray-900">
                                    {{ formatAmount(row.totalCollected) }} / {{ formatAmount(row.targetAmount) }}
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer pagination -->
        <div class="flex flex-row items-center justify-end gap-4">
            <div class="text-[#1C4F4A] font-nexa text-[14px] font-normal leading-[18px]">
                Total {{ totalRows }} Programas
            </div>
            <div
                v-for="page in totalPages"
                :key="page"
                @click="goToPage(page)"
                :class="[
                    'flex w-[26px] h-[26px] items-center justify-center rounded-full cursor-pointer',
                    currentPage === page ? 'bg-[#1C4F4A] text-white' : 'border border-gray-300 text-gray-400',
                ]"
            >
                <span class="text-[10px] font-nexa">{{ page }}</span>
            </div>
        </div>
    </div>
</template>

<script>
import { router } from '@inertiajs/vue3';

export default {
    name: "InstitutionsPaymentsTable",
    props: {
        rows: {
            type: Array,
            default: () => [],
        },
        salesExecutives: {
            type: Array,
            default: () => [],
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            search: "",
            currentPage: 1,
            perPage: 10,
            executiveSearch: "",
            selectedExecutiveId: this.filters?.salesExecutiveId || null,
            showExecutiveDropdown: false,
            institutionSearch: "",
            selectedInstitution: null,
            showInstitutionDropdown: false,
        };
    },
    computed: {
        filteredRows() {
            let result = this.rows;

            // Filtro por institución
            if (this.selectedInstitution) {
                const instName = this.selectedInstitution.toLowerCase();
                result = result.filter((r) => {
                    return (r.institutionName || "").toLowerCase() === instName;
                });
            }

            // Filtro por código de programa
            const term = this.search.trim().toLowerCase();
            if (term) {
                result = result.filter((r) => {
                    const programCode = (r.programCode || "").toLowerCase();
                    return programCode.includes(term);
                });
            }

            return result;
        },
        uniqueInstitutions() {
            const names = this.rows
                .map((r) => r.institutionName)
                .filter((name) => name && name.trim() !== "" && name !== "—");
            return [...new Set(names)].sort();
        },
        filteredInstitutions() {
            const term = this.institutionSearch.trim().toLowerCase();
            if (!term) return this.uniqueInstitutions;
            return this.uniqueInstitutions.filter((inst) =>
                inst.toLowerCase().includes(term)
            );
        },
        paginatedRows() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredRows.slice(start, start + this.perPage);
        },
        totalRows() {
            return this.filteredRows.length;
        },
        totalPages() {
            return Math.ceil(this.totalRows / this.perPage) || 1;
        },
        filteredExecutives() {
            const term = this.executiveSearch.trim().toLowerCase();
            if (!term) return this.salesExecutives;
            return this.salesExecutives.filter((exec) => {
                const name = (exec.name || "").toLowerCase();
                const code = (exec.code || "").toLowerCase();
                return name.includes(term) || code.includes(term);
            });
        },
    },
    watch: {
        'filters.salesExecutiveId': {
            immediate: true,
            handler(newVal) {
                this.selectedExecutiveId = newVal || null;
                if (newVal) {
                    const exec = this.salesExecutives.find(e => e.id === newVal);
                    if (exec) {
                        this.executiveSearch = exec.name;
                    }
                } else {
                    this.executiveSearch = "";
                }
            }
        }
    },
    mounted() {
        // Cerrar dropdown al hacer click fuera
        document.addEventListener('click', this.handleClickOutside);
        // Inicializar el valor del filtro si existe
        if (this.filters?.salesExecutiveId) {
            const exec = this.salesExecutives.find(e => e.id === this.filters.salesExecutiveId);
            if (exec) {
                this.executiveSearch = exec.name;
            }
        }
    },
    beforeUnmount() {
        document.removeEventListener('click', this.handleClickOutside);
    },
    methods: {
        handleClickOutside(event) {
            const execDropdown = this.$refs.executiveDropdown;
            if (execDropdown && !execDropdown.contains(event.target)) {
                this.showExecutiveDropdown = false;
            }
            const instDropdown = this.$refs.institutionDropdown;
            if (instDropdown && !instDropdown.contains(event.target)) {
                this.showInstitutionDropdown = false;
            }
        },
        handleInstitutionInput() {
            this.showInstitutionDropdown = true;
            // Si el usuario borra el texto, limpiar la selección
            if (!this.institutionSearch.trim()) {
                this.selectedInstitution = null;
                this.currentPage = 1;
            }
        },
        selectInstitution(name) {
            this.selectedInstitution = name;
            this.institutionSearch = name;
            this.showInstitutionDropdown = false;
            this.currentPage = 1;
        },
        clearInstitutionFilter() {
            this.selectedInstitution = null;
            this.institutionSearch = "";
            this.currentPage = 1;
        },
        selectExecutive(exec) {
            this.selectedExecutiveId = exec.id;
            this.executiveSearch = exec.name;
            this.showExecutiveDropdown = false;
            this.applyExecutiveFilter(exec.id);
        },
        clearExecutiveFilter() {
            this.selectedExecutiveId = null;
            this.executiveSearch = "";
            this.applyExecutiveFilter(null);
        },
        clearAllFilters() {
            this.search = "";
            this.selectedExecutiveId = null;
            this.executiveSearch = "";
            this.selectedInstitution = null;
            this.institutionSearch = "";
            this.currentPage = 1;
            router.get(route('admin.dashboard'), {}, {
                preserveState: false,
                preserveScroll: true,
            });
        },
        applyExecutiveFilter(executiveId) {
            router.get(route('admin.dashboard'), {
                salesExecutiveId: executiveId,
            }, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        isPastDate(dateStr) {
            if (!dateStr) return false;
            const date = new Date(dateStr);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            return date < today;
        },
        getBadgeClass(percent) {
            if (percent >= 100) {
                return 'bg-green-500'; // Verde para 100%
            } else if (percent >= 75) {
                return 'bg-blue-500'; // Azul para 75-99%
            } else if (percent >= 51) {
                return 'bg-yellow-500'; // Amarillo para 51-74%
            } else {
                return 'bg-red-500'; // Rojo para menos de 50%
            }
        },
        formatAmount(value) {
            if (value === null || value === undefined) return "$0";
            try {
                return new Intl.NumberFormat("es-CL", {
                    style: "currency",
                    currency: "CLP",
                    maximumFractionDigits: 0,
                }).format(value);
            } catch (e) {
                return `$${value}`;
            }
        },
        capitalize(text) {
            if (!text) return "";
            return text
                .toString()
                .split(" ")
                .map(
                    (w) => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()
                )
                .join(" ");
        },
        goToPage(page) {
            if (page < 1 || page > this.totalPages) return;
            this.currentPage = page;
        },
    },
};
</script>

<style scoped>
.font-nexa-bold {
    font-family: "Nexa-Bold", sans-serif;
    font-weight: 700;
}

.font-nexa-xbold {
    font-family: "Nexa-XBold", sans-serif;
    font-weight: 400;
}

.font-nexa {
    font-family: "Nexa", sans-serif;
}

.bg-turquesa {
    background-color: #007e93;
}
</style>
