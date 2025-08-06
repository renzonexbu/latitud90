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
        rut: {
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
                return this.programs.data.map((program) => ({
                    ...program,
                    // Agregar campos calculados para compatibilidad con el card
                    price: program.price || program.trip_price,
                    duration: this.calculateDuration(program.start_date, program.end_date),
                    participants: 0, // Por ahora 0, se puede calcular después
                    paymentPercentage: 0, // Por ahora 0, se puede calcular después
                    paidAmount: 0, // Por ahora 0, se puede calcular después
                    totalAmount: program.price || program.trip_price,
                }));
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
                    rut: this.rut
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