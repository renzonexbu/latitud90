<template>
    <div>
        <!-- No Programs Message -->
        <div
            v-if="!programs || !programs.data || programs.data.length === 0"
            class="text-center py-12"
        >
            <div class="text-gray-500 text-lg mb-4">
                No hay programas disponibles
            </div>
            <div class="text-gray-400 text-sm">
                No se encontraron programas para este participante
            </div>
        </div>

        <!-- Programs Grid - 3x2 layout -->
        <div
            v-else
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-8 justify-items-center md:justify-items-stretch px-2 sm:px-0"
        >
            <ProgramCard
                v-for="program in paginatedPrograms"
                :key="program.id"
                :program="program"
                :show-status-badge="false"
                @click="handleProgramClick(program)"
            />
        </div>

        <!-- Pagination -->
        <ProgramsPagination
            v-if="programs && programs.data && programs.data.length > 0"
            :current-page="programs.current_page || 1"
            :total-programs="programs.total || 0"
            :programs-per-page="programs.per_page || 6"
            @page-changed="handlePageChange"
        />
    </div>
</template>

<script>
import { router } from "@inertiajs/vue3";
import ProgramCard from "../Programs/ProgramCard.vue";
import ProgramsPagination from "../Programs/ProgramsPagination.vue";

export default {
    name: "EcommerceProgramsGrid",
    components: {
        ProgramCard,
        ProgramsPagination,
    },
    props: {
        programs: {
            type: Object,
            required: true,
        },
        document: {
            type: String,
            required: true,
        },
        document_type: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            currentPage: 1,
            programsPerPage: 6,
        };
    },
    computed: {
        paginatedPrograms() {
            // Si no hay programas, retornar array vacío
            if (
                !this.programs ||
                !this.programs.data ||
                this.programs.data.length === 0
            ) {
                return [];
            }

            // Usar la paginación del backend si está disponible
            if (this.programs.data) {
                return this.programs.data.map((program) => {
                    // Replicar lógica de Admin/Edit.vue: total del participante = base + ajuste
                    const base = program.participant_amount ?? program.individual_price ?? null;
                    const adj = program.participant_adjustments ?? 0;
                    const participantTotal = program.participant_total_due ?? (base !== null ? base + adj : program.trip_price);

                    return {
                        ...program,
                        price: participantTotal,
                        duration: this.calculateDuration(program.start_date, program.end_date),
                        participants: program.participants ?? 0,
                        paymentPercentage: program.paymentPercentage ?? 0,
                        paidAmount: program.paidAmount ?? 0,
                        totalAmount: participantTotal,
                        total_installments: program.total_installments ?? 0,
                        paid_installments: program.paid_installments ?? 0,
                        installments_summary: program.installments_summary ?? null,
                    };
                });
            }

            return [];
        },
    },
    methods: {
        handlePageChange(page) {
            // Usar la paginación del backend
            router.get(
                route("ecommerce.programs"),
                { 
                    page,
                    document: this.document,
                    document_type: this.document_type
                },
                {
                    preserveState: true,
                    replace: true,
                }
            );
        },
        handleProgramClick(program) {
            // Emitir el evento para que el componente padre maneje la navegación
            this.$emit('program-click', program);
        },
        calculateDuration(startDate, endDate) {
            if (!startDate || !endDate) return 'Duración no especificada';

            const start = new Date(startDate);
            const end = new Date(endDate);
            const diffTime = end.getTime() - start.getTime();
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffDays === 1) {
                return '1 día';
            } else if (diffDays < 7) {
                return `${diffDays} días`;
            } else if (diffDays < 30) {
                const weeks = Math.ceil(diffDays / 7);
                return `${weeks} semana${weeks > 1 ? 's' : ''}`;
            } else {
                const months = Math.ceil(diffDays / 30);
                return `${months} mes${months > 1 ? 'es' : ''}`;
            }
        },
    },
};
</script> 