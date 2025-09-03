<template>
    <div class="flex flex-row items-center justify-end gap-6 relative">
        <!-- Total Emails -->
        <div class="text-[#9ca3af] font-normal text-sm">
            Total {{ totalEmails }} Emails
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
    name: "MarketingMailPagination",
    props: {
        currentPage: {
            type: Number,
            default: 1,
        },
        totalEmails: {
            type: Number,
            required: true,
        },
        emailsPerPage: {
            type: Number,
            default: 15,
        },
    },
    computed: {
        totalPages() {
            // Validar que tengamos valores numéricos válidos
            const total = Number(this.totalEmails) || 0;
            const perPage = Number(this.emailsPerPage) || 15;
            
            if (total <= 0 || perPage <= 0) {
                return 1; // Al menos una página
            }
            
            return Math.ceil(total / perPage);
        },
        
        validPages() {
            // Crear un array seguro de páginas
            const pages = [];
            const total = this.totalPages;
            
            // Validar que totalPages sea un número válido
            if (!Number.isFinite(total) || total <= 0) {
                return [1]; // Retornar al menos la página 1
            }
            
            // Limitar a un máximo razonable para evitar arrays muy grandes
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
