<template>
    <div class="flex flex-row items-center justify-end gap-6 relative">
        <!-- Total Records -->
        <div class="text-[#9ca3af] font-normal text-sm">
            Total {{ totalParticipants }} Registros
        </div>
        
        <!-- Page Numbers -->
        <div class="flex flex-row gap-2 items-center justify-center">
            <div
                v-for="page in validPages"
                :key="page"
                @click="goToPage(page)"
                :class="[
                    'flex w-8 h-8 items-center justify-center rounded-full cursor-pointer transition-colors',
                    currentPage === page
                        ? 'bg-[#1c4f4a] text-white'
                        : 'bg-white border border-[#e5e7eb] text-[#6b7280] hover:border-[#1c4f4a]',
                ]"
            >
                <span class="text-sm font-medium">
                    {{ page }}
                </span>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "ExecutivesConsolidatedPagination",
    props: {
        currentPage: {
            type: Number,
            default: 1,
        },
        totalParticipants: {
            type: Number,
            required: true,
        },
        participantsPerPage: {
            type: Number,
            default: 25,
        },
    },
    computed: {
        totalPages() {
            const total = Number(this.totalParticipants) || 0;
            const perPage = Number(this.participantsPerPage) || 25;
            
            if (total <= 0 || perPage <= 0) {
                return 1;
            }
            
            return Math.ceil(total / perPage);
        },
        
        validPages() {
            const pages = [];
            const total = this.totalPages;
            
            if (!Number.isFinite(total) || total <= 0) {
                return [1];
            }
            
            const maxPages = Math.min(total, 100);
            
            for (let i = 1; i <= maxPages; i++) {
                pages.push(i);
            }
            
            return pages;
        },
    },
    methods: {
        goToPage(page) {
            this.$emit("page-changed", page);
        },
    },
};
</script>
