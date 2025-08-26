<template>
    <div class="flex flex-row items-center justify-end gap-4 relative">
        <!-- Pagination Controls -->
        <div
            class="flex flex-row gap-[6.996px] items-center justify-start flex-shrink-0 relative"
        >
            <!-- Total Newsletters -->
            <div
                class="w-[143px] h-[14px] flex-shrink-0 text-verde-oscuro font-nexa text-[14.582px] font-normal leading-[18.749px]"
            >
                Total {{ totalNewsletters }} Suscriptores
            </div>
            <!-- Page Numbers -->

            <div
                v-for="page in totalPages"
                :key="page"
                @click="goToPage(page)"
                :class="[
                    'flex w-[26.647px] h-[26.028px] px-[11.155px] py-[6.197px] items-center gap-[6.996px] rounded-[69.961px] relative overflow-hidden cursor-pointer',
                    currentPage === page
                        ? 'bg-verde-oscuro'
                        : 'border border-[0.62px] border-gris-3',
                ]"
            >
                <div
                    :class="[
                        'text-center font-nexa w-[4.338px] h-[9.915px] flex-shrink-0 text-[9.915px] leading-[13.634px] font-normal',
                        currentPage === page ? 'text-blanco' : 'text-gris-3',
                    ]"
                >
                    {{ page }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "NewsletterPagination",
    props: {
        currentPage: {
            type: Number,
            default: 1,
        },
        totalNewsletters: {
            type: Number,
            required: true,
        },
        newslettersPerPage: {
            type: Number,
            default: 10,
        },
    },
    computed: {
        totalPages() {
            return Math.ceil(this.totalNewsletters / this.newslettersPerPage);
        },
    },
    methods: {
        goToPage(page) {
            this.$emit("page-changed", page);
        },
    },
};
</script>
