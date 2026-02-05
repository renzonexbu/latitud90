<template>
    <div class="flex flex-row items-center justify-end gap-4 relative">
        <div
            class="flex flex-row gap-[6.996px] items-center justify-start flex-shrink-0 relative"
        >
            <div
                class="w-[143px] h-[14px] flex-shrink-0 text-verde-oscuro font-nexa text-[14.582px] font-normal leading-[18.749px]"
            >
                Total {{ totalUsers }} Usuarios
            </div>

            <!-- Botón Anterior -->
            <div
                @click="currentPage > 1 && goToPage(currentPage - 1)"
                :class="[
                    'flex w-[26.647px] h-[26.028px] items-center justify-center rounded-[69.961px] relative overflow-hidden transition-colors',
                    currentPage > 1 ? 'cursor-pointer border border-[0.62px] border-gris-3' : 'cursor-not-allowed opacity-40',
                ]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
            </div>

            <!-- Páginas -->
            <template v-for="item in visiblePages" :key="item.key">
                <div
                    v-if="item.type === 'page'"
                    @click="goToPage(item.value)"
                    :class="[
                        'flex w-[26.647px] h-[26.028px] px-[11.155px] py-[6.197px] items-center gap-[6.996px] rounded-[69.961px] relative overflow-hidden cursor-pointer',
                        currentPage === item.value ? 'bg-verde-oscuro' : 'border border-[0.62px] border-gris-3',
                    ]"
                >
                    <div
                        :class="[
                            'text-center font-nexa w-[4.338px] h-[9.915px] flex-shrink-0 text-[9.915px] leading-[13.634px] font-normal',
                            currentPage === item.value ? 'text-blanco' : 'text-gris-3',
                        ]"
                    >
                        {{ item.value }}
                    </div>
                </div>
                <div v-else class="flex w-[26.647px] h-[26.028px] items-center justify-center text-gris-3 text-[9.915px]">...</div>
            </template>

            <!-- Botón Siguiente -->
            <div
                @click="currentPage < totalPages && goToPage(currentPage + 1)"
                :class="[
                    'flex w-[26.647px] h-[26.028px] items-center justify-center rounded-[69.961px] relative overflow-hidden transition-colors',
                    currentPage < totalPages ? 'cursor-pointer border border-[0.62px] border-gris-3' : 'cursor-not-allowed opacity-40',
                ]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "UsersPagination",
    props: {
        currentPage: { type: Number, default: 1 },
        totalUsers: { type: Number, required: true },
        usersPerPage: { type: Number, default: 10 },
    },
    computed: {
        totalPages() {
            const total = Math.ceil(this.totalUsers / this.usersPerPage);
            return total > 0 ? total : 1;
        },
        visiblePages() {
            const total = this.totalPages;
            const current = this.currentPage;
            const items = [];
            if (total <= 7) {
                for (let i = 1; i <= total; i++) items.push({ type: 'page', value: i, key: `page-${i}` });
                return items;
            }
            items.push({ type: 'page', value: 1, key: 'page-1' });
            if (current <= 3) {
                for (let i = 2; i <= 4; i++) items.push({ type: 'page', value: i, key: `page-${i}` });
                items.push({ type: 'ellipsis', key: 'ellipsis-end' });
            } else if (current >= total - 2) {
                items.push({ type: 'ellipsis', key: 'ellipsis-start' });
                for (let i = total - 3; i <= total - 1; i++) items.push({ type: 'page', value: i, key: `page-${i}` });
            } else {
                items.push({ type: 'ellipsis', key: 'ellipsis-start' });
                for (let i = current - 1; i <= current + 1; i++) items.push({ type: 'page', value: i, key: `page-${i}` });
                items.push({ type: 'ellipsis', key: 'ellipsis-end' });
            }
            items.push({ type: 'page', value: total, key: `page-${total}` });
            return items;
        },
    },
    methods: {
        goToPage(page) { this.$emit("page-changed", page); },
    },
};
</script>
