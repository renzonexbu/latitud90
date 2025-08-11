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
                Crea tu primer programa para comenzar
            </div>
        </div>

        <!-- Programs Grid - 3x2 layout -->
        <div
            v-else
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8"
        >
            <ProgramCard
                v-for="program in paginatedPrograms"
                :key="program.id"
                :program="program"
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
import ProgramCard from "./ProgramCard.vue";
import ProgramsPagination from "./ProgramsPagination.vue";

export default {
    name: "ProgramsGrid",
    components: {
        ProgramCard,
        ProgramsPagination,
    },
    props: {
        programs: {
            type: Object,
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
                    const base = program.participant_amount ?? program.individual_price ?? null;
                    const adj = program.participant_adjustments ?? 0;
                    const totalDue = base !== null ? base + adj : program.trip_price;
                    return {
                        ...program,
                        // Mostrar siempre el total a pagar por el participante en los cards
                        price: totalDue,
                        duration: this.calculateDuration(program.departure_date),
                        participants: 0,
                        paymentPercentage: 0,
                        paidAmount: 0,
                        totalAmount: totalDue,
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
                route("admin.programs.index"),
                { page },
                {
                    preserveState: true,
                    replace: true,
                }
            );
        },
        handleProgramClick(program) {
            // Ir al edit del programa
            router.visit(route("admin.programs.edit", program.id));
        },
        calculateDuration(departureDate) {
            if (!departureDate) return 0;

            const today = new Date();
            const departure = new Date(departureDate);
            const diffTime = departure.getTime() - today.getTime();
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            // Por ahora retornar un valor fijo, se puede calcular basado en la lógica del negocio
            return 7; // 7 días por defecto
        },
    },
};
</script>
