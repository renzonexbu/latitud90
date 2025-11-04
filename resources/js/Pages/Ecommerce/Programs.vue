<template>
    <div class="min-h-screen bg-gray-50 w-full p-3 sm:p-4">
        <!-- Header -->
        <Header class="bg-transparent text-blanco shadow-none"> </Header>

        <!-- Alertas del sistema -->
        <Alerts 
            :show="showErrorAlert"
            type="error"
            title="Ha ocurrido un error"
            message="Lo sentimos, ha ocurrido un error inesperado. Por favor, intenta nuevamente."
            @close="showErrorAlert = false"
        />

        <!-- Contenido de Programas -->
        <div class="max-w-6xl mx-auto py-8 px-4 sm:px-6">
            <!-- Información del Participante -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <!-- Header del Participante -->
                <div class="mb-6">
                    <ParticipantHeader
                        :participant-name="
                            formatParticipantName(participant.first_name, participant.second_name, participant.first_last_name, participant.second_last_name)
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
                :token="token"
                @program-click="handleProgramClick"
            />

            <!-- Botón Volver al Home -->
            <div class="mt-8">
                <BackToHomeButton :token="token" variant="home" />
            </div>
        </div>

        <!-- Footer -->
        <Footer class="rounded-lg"></Footer>
    </div>
</template>

<script>
import { Head } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";
import { ref } from "vue";
import { decodeParticipantToken } from "@/utils/tokenUtils";
import Header from "@/Components/Ecommerce/Header.vue";
import Footer from "@/Components/Ecommerce/Footer.vue";
import ProgramsGrid from "@/Components/Ecommerce/ProgramsGrid.vue";
import ParticipantHeader from "@/Components/Ecommerce/ParticipantHeader.vue";
import ProcessSteps from "@/Components/Ecommerce/ProcessSteps.vue";
import BackToHomeButton from "@/Components/Ecommerce/BackToHomeButton.vue";
import Alerts from "@/Components/Alerts.vue";

export default {
    components: {
        Header,
        Footer,
        Head,
        ProgramsGrid,
        ParticipantHeader,
        ProcessSteps,
        BackToHomeButton,
        Alerts,
    },
    props: {
        participant: {
            type: Object,
            required: true,
        },
        programs: {
            type: [Array, Object],
            required: true,
        },
        token: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            document: null,
            document_type: null,
        };
    },
    created() {
        // Decodificar token para obtener document y document_type
        const data = decodeParticipantToken(this.token);
        if (data) {
            this.document = data.document;
            this.document_type = data.document_type;
        }
    },
    setup() {
        const showErrorAlert = ref(false);

        const showGenericError = () => {
            showErrorAlert.value = true;
        };

        return {
            showErrorAlert,
            showGenericError
        };
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
                // Mostrar error genérico sin detalles técnicos
                this.showGenericError();
            });
        },
        formatParticipantName(firstName, secondName, firstLastName, secondLastName) {
            // Filtrar valores undefined, null o vacíos y construir el nombre completo
            const nameParts = [firstName, secondName, firstLastName, secondLastName]
                .filter(part => part && part.trim() !== '');
            
            // Si no hay nombre válido, mostrar un valor por defecto
            if (nameParts.length === 0) {
                return 'Participante';
            }
            
            // Unir todas las partes del nombre
            const fullName = nameParts.join(' ').trim();
            
            // Convertir a Title Case (primera letra de cada palabra en mayúscula)
            return fullName
                .toLowerCase()
                .split(' ')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
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
            try {
                // Guardar enrollment_code en localStorage para identificar pagos
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
                
                // Navegar al detalle del programa
                router.get(route("ecommerce.program-detail", program.id), {
                    token: this.token,
                });
            } catch (error) {
                console.error('Error al procesar programa:', error);
                // Mostrar error genérico sin detalles técnicos
                this.showGenericError();
            }
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
                // Mostrar error genérico sin detalles técnicos
                this.showGenericError();
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
