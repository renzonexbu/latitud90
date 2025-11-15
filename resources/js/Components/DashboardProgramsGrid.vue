<template>
    <div>
        <div v-if="!programs || programs.length === 0" class="text-gray-500 text-sm">
            No hay programas activos.
        </div>
        <div v-else>
            <div
                class="mb-4"
                :style="{
                    color: 'var(--Colores-OP2-Verde-oscuro, #1C4F4A)',
                    fontFamily: 'Nexa',
                    fontSize: 'var(--Numeros-Subtitulo-S, 20px)',
                    fontStyle: 'normal',
                    fontWeight: 400,
                    lineHeight: '28px',
                }"
            >
                Programas activos
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <ProgramCard
                    v-for="program in limitedPrograms"
                    :key="program.id"
                    :program="normalizeProgram(program)"
                    mode="template"
                    @click="goToEdit(program)"
                />
            </div>
        </div>
    </div>
</template>

<script>
import { router } from '@inertiajs/vue3';
import ProgramCard from '@/Components/Programs/ProgramCard.vue';

export default {
    name: 'DashboardProgramsGrid',
    components: { ProgramCard },
    props: {
        programs: {
            type: Array,
            default: () => [],
        },
    },
    computed: {
        limitedPrograms() {
            // Mostrar solo 3 programas activos
            const actives = (this.programs || []).filter(p => p.active === true);
            return actives.slice(0, 3);
        },
    },
    methods: {
        goToEdit(program) {
            router.visit(route('admin.programs.edit', program.id));
        },
        normalizeProgram(p) {
            // Mapear datos mínimos esperados por ProgramCard
            const base = p.participant_amount ?? p.individual_price ?? null;
            const adj = p.participant_adjustments ?? 0;
            const totalDue = base !== null ? base + adj : (p.trip_price ?? 0);
            return {
                ...p,
                price: totalDue,
                duration: 7,
                participants: 0,
                paymentPercentage: 0,
                paidAmount: 0,
                totalAmount: totalDue,
            };
        },
    },
};
</script>


