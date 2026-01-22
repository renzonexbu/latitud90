<template>
    <div class="bg-white rounded-[20px] p-6 flex flex-col gap-4 w-full">
        <!-- Header row: title + search -->
        <div class="flex flex-row items-center justify-between w-full">
            <div class="text-[#1C4F4A] font-nexa text-[20px] font-normal leading-[28px]">
                Estado de pago por institución
            </div>
            <div class="relative w-[310px]">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar institución"
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
        </div>

        <!-- Table with horizontal scroll -->
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full text-xs" style="min-width: 1200px;">
                    <!-- Head -->
                    <thead class="bg-[#007e93] sticky top-0 z-10">
                        <tr>
                            <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[180px]">Institución</th>
                            <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">Nivel</th>
                            <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">Curso</th>
                            <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">Año</th>
                            <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[220px]">Programa</th>
                            <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[140px]">Destino</th>
                            <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">Participantes</th>
                            <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">% Pago</th>
                            <th class="px-2 py-2 text-right text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[180px]">Recaudado / Total</th>
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
                            <td class="px-2 py-2 whitespace-nowrap">
                                <div class="text-xs font-medium text-gray-900">
                                    {{ capitalize(row.institutionName) }}
                                </div>
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-center">
                                <div class="text-xs text-gray-900">
                                    {{ capitalize(row.educationLevel) }}
                                </div>
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-center">
                                <div class="text-xs text-gray-900">
                                    {{ row.course !== undefined && row.course !== null ? row.course : '—' }}
                                </div>
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-center">
                                <div class="text-xs text-gray-900">
                                    {{ row.year }}
                                </div>
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap">
                                <div class="text-xs font-medium text-[#1c4f4a]">
                                    {{ capitalize(row.programName) }}
                                </div>
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap">
                                <div class="text-xs font-medium text-[#1c4f4a]">
                                    {{ capitalize(row.destination) }}
                                </div>
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-center">
                                <div class="text-xs text-gray-900">
                                    {{ row.students }}
                                </div>
                            </td>

                            <!-- Estado badge -->
                            <td class="px-2 py-2 whitespace-nowrap text-center">
                                <span
                                    :class="[
                                        'inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded-full text-white',
                                        getBadgeClass(row.percent ?? 0)
                                    ]"
                                >
                                    {{ row.percent ?? 0 }}%
                                </span>
                            </td>

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
export default {
    name: "InstitutionsPaymentsTable",
    props: {
        rows: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            search: "",
            currentPage: 1,
            perPage: 10,
        };
    },
    computed: {
        filteredRows() {
            const term = this.search.trim().toLowerCase();
            if (!term) return this.rows;
            return this.rows.filter((r) => {
                const a = (r.institutionName || "").toLowerCase();
                const b = (r.programName || "").toLowerCase();
                const c = (r.destination || "").toLowerCase();
                return a.includes(term) || b.includes(term) || c.includes(term);
            });
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
    },
    methods: {
        getBadgeClass(percent) {
            // Colores según porcentaje - mismo estilo que CoursesTable
            if (percent >= 100) {
                return 'bg-[#1a4b75]'; // Azul oscuro para 100%
            } else if (percent >= 75) {
                return 'bg-[#4b8d7f]'; // Verde para 75%+
            } else if (percent >= 50) {
                return 'bg-yellow-500'; // Amarillo para 50%+
            } else if (percent >= 25) {
                return 'bg-orange-500'; // Naranja para 25%+
            } else {
                return 'bg-[#d54a42]'; // Rojo para menos de 25%
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
