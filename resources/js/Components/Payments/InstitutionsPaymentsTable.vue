<template>
    <div
        class="bg-white rounded-[20px] pt-[30px] pr-[30px] pb-[15px] pl-[30px] flex flex-col gap-[17px] items-end justify-end w-full"
    >
        <!-- Header row: title + search -->
        <div class="flex flex-row items-center justify-between w-full">
            <div
                :style="{
                    color: 'var(--Colores-OP2-Verde-oscuro, #1C4F4A)',
                    fontFamily: 'Nexa',
                    fontSize: 'var(--Numeros-Subtitulo-S, 20px)',
                    fontStyle: 'normal',
                    fontWeight: 400,
                    lineHeight: '28px',
                }"
            >
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

        <!-- Table -->
        <div
            class="rounded-[20px] border border-colores-neutro-gris-3 w-full overflow-hidden"
        >
            <!-- Head -->
            <div
                class="bg-turquesa text-white flex flex-row items-center justify-between h-[67.51px] px-[25px]"
            >
                <div class="w-[150px] text-left">Nombre de institución</div>
                <div class="w-[90px] text-center">Nivel</div>
                <div class="w-[53px] text-center">Curso</div>
                <div class="w-[39px] text-center">Año</div>
                <div class="w-[150px] text-center">Programa</div>
                <div class="w-[150px] text-center">Destino</div>
                <div class="w-[90px] text-center">Alumnos</div>
                <div class="w-[110px] text-center">Estado</div>
                <div class="w-[121px] text-center">Total recolectado</div>
                <div class="w-[18px]"></div>
            </div>

            <!-- Body rows -->
            <div
                v-for="(row, index) in filteredRows"
                :key="row.id || index"
                :class="
                    index % 2 === 0 ? 'bg-white' : 'bg-colores-neutro-gris-1'
                "
                class="flex flex-row items-center justify-between px-[25px] py-[17px]"
            >
                <div
                    class="w-[150px] text-left text-colores-op2-verde-oscuro truncate"
                >
                    {{ row.institutionName }}
                </div>
                <div
                    class="w-[90px] text-center text-colores-op2-verde-oscuro truncate"
                >
                    {{ capitalize(row.educationLevel) }}
                </div>
                <div class="w-[53px] text-center text-colores-op2-verde-oscuro truncate">
                    {{ row.course !== undefined && row.course !== null ? row.course : '—' }}
                </div>
                <div
                    class="w-[39px] text-center text-colores-op2-verde-oscuro truncate"
                >
                    {{ row.year }}
                </div>
                <div
                    class="w-[150px] text-center text-colores-op2-verde-oscuro truncate"
                >
                    {{ capitalize(row.programName) }}
                </div>
                <div
                    class="w-[150px] text-center text-colores-op2-verde-oscuro truncate"
                >
                    {{ capitalize(row.destination) }}
                </div>
                <div
                    class="w-[90px] text-center text-colores-op2-verde-oscuro truncate"
                >
                    {{ row.students }}
                </div>

                <!-- Estado badge -->
                <div class="w-[110px] flex items-center justify-center">
                    <div
                        :style="badgeStyle(row.percent ?? 0)"
                        class="flex w-[100px] py-[6px] px-[10px] justify-center items-center gap-[10px] rounded-[12px] text-white"
                    >
                        <span
                            class="font-[Nexa-XBold] text-[13px] leading-[13px]"
                            >{{ row.percent ?? 0 }}%</span
                        >
                    </div>
                </div>

                <div
                    class="w-[121px] text-center text-colores-op2-verde-oscuro truncate"
                >
                    {{ formatAmount(row.totalCollected) }}
                </div>
                <div class="w-[18px]"></div>
            </div>
        </div>

        <!-- Footer pagination -->
        <div class="flex flex-row items-center justify-end gap-4">
            <div class="w-[143px] h-[14px] flex-shrink-0 text-verde-oscuro font-nexa text-[14.582px] font-normal leading-[18.749px]">
                Total {{ totalRows }} Programas
            </div>
            <div
                v-for="page in totalPages"
                :key="page"
                @click="goToPage(page)"
                :class="[
                    'flex w-[26.647px] h-[26.028px] px-[11.155px] py-[6.197px] items-center gap-[6.996px] rounded-[69.961px] relative overflow-hidden cursor-pointer',
                    currentPage === page ? 'bg-verde-oscuro' : 'border border-gris-3',
                ]"
            >
                <div :class="[
                        'text-center font-nexa w-[4.338px] h-[9.915px] flex-shrink-0 text-[9.915px] leading-[13.634px] font-normal',
                        currentPage === page ? 'text-blanco' : 'text-gris-3',
                    ]">
                    {{ page }}
                </div>
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
        badgeStyle(percent) {
            // Colores según porcentaje
            // < 33%: Amarillo, 33-66%: Azul Oscuro, > 66%: Verde Oscuro
            let bg = "var(--Colores-OP2-Amarillo, #FFB232)";
            if (percent >= 66) bg = "var(--Colores-OP2-Verde-oscuro, #1C4F4A)";
            else if (percent >= 33)
                bg = "var(--Colores-Primario-Azul-oscuro, #0A3250)";
            return { background: bg };
        },
        formatAmount(value) {
            if (value === null || value === undefined) return "";
            try {
                // Mostrar como CLP con signo de peso
                return new Intl.NumberFormat("es-CL", {
                    style: "currency",
                    currency: "CLP",
                    maximumFractionDigits: 0,
                }).format(value);
            } catch (e) {
                return `${value}`;
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
