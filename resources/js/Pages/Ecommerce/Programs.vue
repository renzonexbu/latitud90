<template>
    <div class="min-h-screen bg-gray-50 w-full p-3">
        <!-- Header -->
        <Header class="bg-transparent text-blanco shadow-none"> </Header>

        <!-- Contenido de Programas -->
        <div class="max-w-6xl mx-auto py-8 px-4">
            <!-- Información del Participante -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <!-- Header del Participante -->
                <div class="mb-6">
                    <ParticipantHeader
                        :participant-name="
                            participant.first_name + ' ' + participant.last_name
                        "
                    />
                </div>

                <!-- Pasos del Proceso -->
                <div class="pt-4">
                    <ProcessSteps :current-step="1" />
                </div>
            </div>

            <!-- Grid de Programas -->
            <ProgramsGrid
                :programs="programs"
                :rut="rut"
                @program-click="handleProgramClick"
            />

            <!-- Botón Volver al Home -->
            <div class="mt-8">
                <BackToHomeButton :rut="rut" variant="home" />
            </div>
        </div>

        <!-- Footer -->
        <Footer class="rounded-lg"></Footer>
    </div>
</template>

<script>
import { Head } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";
import Header from "@/Components/Ecommerce/Header.vue";
import Footer from "@/Components/Ecommerce/Footer.vue";
import ProgramsGrid from "@/Components/Ecommerce/ProgramsGrid.vue";
import ParticipantHeader from "@/Components/Ecommerce/ParticipantHeader.vue";
import ProcessSteps from "@/Components/Ecommerce/ProcessSteps.vue";
import BackToHomeButton from "@/Components/Ecommerce/BackToHomeButton.vue";

export default {
    components: {
        Header,
        Footer,
        Head,
        ProgramsGrid,
        ParticipantHeader,
        ProcessSteps,
        BackToHomeButton,
    },
    props: {
        participant: {
            type: Object,
            required: true,
        },
        programs: {
            type: Array,
            required: true,
        },
        rut: {
            type: String,
            required: true,
        },
    },
    methods: {
        formatRut(rut) {
            if (!rut) return "";
            const cleanRut = rut.replace(/\./g, "").replace(/-/g, "");
            if (cleanRut.length > 1) {
                const body = cleanRut.slice(0, -1);
                const dv = cleanRut.slice(-1);
                let formattedBody = "";
                for (let i = body.length - 1, j = 0; i >= 0; i--, j++) {
                    if (j > 0 && j % 3 === 0) {
                        formattedBody = "." + formattedBody;
                    }
                    formattedBody = body[i] + formattedBody;
                }
                return `${formattedBody}-${dv}`;
            }
            return rut;
        },

        handleProgramClick(program) {
            // Navegar al detalle del programa
            router.get(route("ecommerce.program-detail", program.id), {
                rut: this.rut,
            });
        },
    },
};
</script>

<style scoped>
/* Estilos del título */
.programs-title {
    color: var(--Colores-OP2-Turquesa, #007e93);
    font-family: Nexa;
    font-size: var(--Numeros-Subtitulo, 24px);
    font-style: normal;
    font-weight: 700;
    line-height: 28px;
    margin-bottom: 1rem;
}

/* Estilos para el texto truncado */
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Transiciones suaves */
.transition-shadow {
    transition: box-shadow 0.3s ease-in-out;
}

.transition-colors {
    transition: color 0.2s ease-in-out, background-color 0.2s ease-in-out;
}
</style>
