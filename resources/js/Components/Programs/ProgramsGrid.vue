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
        // Cuando es true, prioriza métricas por-participante si están presentes
        preferParticipantMetrics: {
            type: Boolean,
            default: false,
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
                    if (this.preferParticipantMetrics) {
                        const totalAmount = program.participant_total_due ?? program.totalAmount ?? program.trip_price ?? 0;
                        const paidAmount = program.paidAmount ?? 0;
                        const paymentPercentage = program.paymentPercentage ?? (
                            totalAmount > 0 ? Math.round((paidAmount / totalAmount) * 100) : 0
                        );
                        return {
                            ...program,
                            price: totalAmount,
                            duration: this.calculateDuration(program.departure_date),
                            participants: 0,
                            paymentPercentage,
                            paidAmount,
                            totalAmount,
                        };
                    }
                    // Por defecto (Index de Programas): métricas agregadas del curso/programa completo
                    const totalAmount = program.course_total_amount ?? program.trip_price ?? 0;
                    const paidAmount = program.course_paid_amount ?? 0;
                    const paymentPercentage = program.course_payment_percentage ?? 0;
                    return {
                        ...program,
                        price: totalAmount,
                        duration: this.calculateDuration(program.departure_date),
                        participants: program.course?.participants?.length || 0,
                        paymentPercentage,
                        paidAmount,
                        totalAmount,
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
