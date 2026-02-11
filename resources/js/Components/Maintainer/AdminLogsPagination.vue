<template>
    <div class="flex flex-row items-center justify-end gap-4 relative">
        <!-- Total Logs -->
        <div
            class="text-verde-oscuro font-nexa text-sm font-normal"
        >
            Total {{ totalLogs }} Logs
        </div>

        <!-- Page Numbers -->
        <div class="flex flex-row gap-[6.996px] items-center justify-center">
            <!-- Previous Button -->
            <button
                v-if="currentPage > 1"
                @click="goToPage(currentPage - 1)"
                class="flex w-8 h-8 items-center justify-center rounded-full cursor-pointer transition-colors border border-gris-3 text-gris-3 hover:border-verde-oscuro"
            >
                <span class="text-sm">&lt;</span>
            </button>

            <template v-for="(item, index) in paginationItems" :key="index">
                <!-- Ellipsis -->
                <div
                    v-if="item === '...'"
                    class="flex w-8 h-8 items-center justify-center text-gris-3"
                >
                    <span class="text-sm">...</span>
                </div>
                <!-- Page Number -->
                <div
                    v-else
                    @click="goToPage(item)"
                    :class="[
                        'flex w-8 h-8 items-center justify-center rounded-full cursor-pointer transition-colors',
                        currentPage === item
                            ? 'bg-verde-oscuro text-blanco'
                            : 'border border-gris-3 text-gris-3 hover:border-verde-oscuro',
                    ]"
                >
                    <span class="text-sm font-nexa font-normal">
                        {{ item }}
                    </span>
                </div>
            </template>

            <!-- Next Button -->
            <button
                v-if="currentPage < totalPages"
                @click="goToPage(currentPage + 1)"
                class="flex w-8 h-8 items-center justify-center rounded-full cursor-pointer transition-colors border border-gris-3 text-gris-3 hover:border-verde-oscuro"
            >
                <span class="text-sm">&gt;</span>
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: "AdminLogsPagination",
    props: {
        currentPage: {
            type: Number,
            default: 1,
        },
        totalLogs: {
            type: Number,
            required: true,
        },
        logsPerPage: {
            type: Number,
            default: 15,
        },
    },
    computed: {
        totalPages() {
            const total = Number(this.totalLogs) || 0;
            const perPage = Number(this.logsPerPage) || 15;

            if (total <= 0 || perPage <= 0) {
                return 1;
            }

            return Math.ceil(total / perPage);
        },

        paginationItems() {
            const total = this.totalPages;
            const current = this.currentPage;
            const items = [];

            // Si hay 7 páginas o menos, mostrar todas
            if (total <= 7) {
                for (let i = 1; i <= total; i++) {
                    items.push(i);
                }
                return items;
            }

            // Siempre mostrar la primera página
            items.push(1);

            // Calcular rango alrededor de la página actual
            let start = Math.max(2, current - 1);
            let end = Math.min(total - 1, current + 1);

            // Ajustar si estamos cerca del inicio
            if (current <= 3) {
                start = 2;
                end = 4;
            }

            // Ajustar si estamos cerca del final
            if (current >= total - 2) {
                start = total - 3;
                end = total - 1;
            }

            // Agregar ellipsis si hay gap al inicio
            if (start > 2) {
                items.push('...');
            }

            // Agregar páginas del rango
            for (let i = start; i <= end; i++) {
                items.push(i);
            }

            // Agregar ellipsis si hay gap al final
            if (end < total - 1) {
                items.push('...');
            }

            // Siempre mostrar la última página
            items.push(total);

            return items;
        },
    },
    methods: {
        goToPage(page) {
            this.$emit("page-changed", page);
        },
    },
};
</script>
