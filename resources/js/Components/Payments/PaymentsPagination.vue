<template>
    <div class="flex flex-row items-center justify-end gap-4 relative">
        <div class="text-[#9ca3af] font-normal text-sm">
            Total {{ totalPayments }} Pagos
        </div>
        <div class="flex flex-row gap-1.5 items-center justify-center">
            <div
                @click="currentPage > 1 && goToPage(currentPage - 1)"
                :class="['flex w-8 h-8 items-center justify-center rounded-full transition-colors', currentPage > 1 ? 'cursor-pointer bg-white border border-[#e5e7eb] text-[#6b7280] hover:border-[#1c4f4a]' : 'cursor-not-allowed bg-[#f3f4f6] text-[#d1d5db]']"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
            </div>
            <template v-for="item in visiblePages" :key="item.key">
                <div v-if="item.type === 'page'" @click="goToPage(item.value)" :class="['flex w-8 h-8 items-center justify-center rounded-full cursor-pointer transition-colors', currentPage === item.value ? 'bg-[#1c4f4a] text-white' : 'bg-white border border-[#e5e7eb] text-[#6b7280] hover:border-[#1c4f4a]']">
                    <span class="text-sm font-medium">{{ item.value }}</span>
                </div>
                <div v-else class="flex w-8 h-8 items-center justify-center text-[#9ca3af] text-sm">...</div>
            </template>
            <div
                @click="currentPage < totalPages && goToPage(currentPage + 1)"
                :class="['flex w-8 h-8 items-center justify-center rounded-full transition-colors', currentPage < totalPages ? 'cursor-pointer bg-white border border-[#e5e7eb] text-[#6b7280] hover:border-[#1c4f4a]' : 'cursor-not-allowed bg-[#f3f4f6] text-[#d1d5db]']"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "PaymentsPagination",
    props: {
        currentPage: { type: Number, default: 1 },
        totalPayments: { type: Number, required: true },
        paymentsPerPage: { type: Number, default: 6 },
    },
    computed: {
        totalPages() {
            const total = Number(this.totalPayments) || 0;
            const perPage = Number(this.paymentsPerPage) || 6;
            if (total <= 0 || perPage <= 0) return 1;
            return Math.ceil(total / perPage);
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
