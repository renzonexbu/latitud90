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
                :document="document"
                :document-type="document_type"
                @program-click="handleProgramClick"
            />

            <!-- Botón Volver al Home -->
            <div class="mt-8">
                <BackToHomeButton :document="document" :document-type="document_type" variant="home" />
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
        document: {
            type: String,
            required: true,
        },
        document_type: {
            type: String,
            required: true,
        },
    },
    mounted() {
        // Registrar vista de lista de programas en analytics
        this.recordProgramListView();
    },
    methods: {
        recordProgramListView() {
            // Obtener session_id desde localStorage
            const sessionId = localStorage.getItem('analytics_session_id');
            
            if (!sessionId) {
                console.warn('No se encontró session_id en localStorage');
                return;
            }
            
            // Enviar datos de vista de lista de programas al backend
            fetch('/api/analytics/program-list-view', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    session_id: sessionId,
                })
            }).catch(error => {
                console.error('Error recording program list view:', error);
            });
        },
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
            // Guardar enrollment_code en localStorage para identificar pagos
            try {
                const participant = this.participant || {};
                let enrollmentCode = program.enrollment_code;
                
                if (!enrollmentCode && program.code && participant.document_number) {
                    if (this.document_type === 'RUT') {
                        // Para RUT: usar código del programa + RUT completo sin dígito verificador
                        const rutDigits = String(participant.document_number).replace(/\D/g, '').slice(0, -1);
                        enrollmentCode = `${program.code}${rutDigits}`;
                    } else {
                        // Para pasaporte: usar código del programa + número completo del pasaporte
                        enrollmentCode = `${program.code}${participant.document_number}`;
                    }
                }
                const payload = {
                    enrollment_code: enrollmentCode,
                    program_id: program.id,
                    participant_id: participant.id || null,
                };
                localStorage.setItem('selectedEnrollment', JSON.stringify(payload));
                
                // Registrar selección de programa en analytics
                this.recordProgramSelection(program, enrollmentCode);
            } catch (e) {
                // noop
            }
            // Navegar al detalle del programa
            router.get(route("ecommerce.program-detail", program.id), {
                document: this.document,
                document_type: this.document_type,
            });
        },
        
        recordProgramSelection(program, enrollmentCode) {
            // Obtener session_id desde localStorage
            const sessionId = localStorage.getItem('analytics_session_id');
            
            if (!sessionId) {
                console.warn('No se encontró session_id en localStorage');
                return;
            }
            
            // Enviar datos de selección de programa al backend
            fetch('/api/analytics/program-selection', {
                method: 'POST',
                credentials: 'same-origin', // Incluir cookies de sesión
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    session_id: sessionId,
                    program_id: program.id,
                    enrollment_code: enrollmentCode,
                    program_name: program.name,
                })
            }).catch(error => {
                console.error('Error recording program selection:', error);
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
