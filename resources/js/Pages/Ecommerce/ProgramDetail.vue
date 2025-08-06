<template>
    <div class="min-h-screen bg-gray-50 w-full p-3">
        <!-- Header -->
        <Header class="bg-transparent text-blanco shadow-none"> </Header>

        <!-- Contenido del Detalle del Programa -->
        <div class="py-8">
            <!-- Información del Participante -->
            <div class="max-w-6xl mx-auto px-4 mb-8">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <!-- Header del Participante -->
                    <div class="mb-6">
                        <ParticipantHeader
                            :participant-name="
                                participant.first_name +
                                ' ' +
                                participant.last_name
                            "
                        />
                    </div>

                    <!-- Pasos del Proceso - Paso 2 marcado -->
                    <div class="pt-4">
                        <ProcessSteps :current-step="2" />
                    </div>
                </div>
            </div>

            <!-- Detalle del Programa -->
            <div class="mx-[120px] bg-white rounded-lg shadow-lg p-6">
                <div class="flex gap-6">
                    <!-- Componente Izquierdo - Contenido del Programa -->
                    <div
                        class="w-[850px] flex flex-col items-start gap-[46px] flex-shrink-0 p-10"
                    >
                        <!-- Carousel de Imágenes del Programa -->
                        <ImageCarousel
                            :images="program.images"
                            :program-name="program.name"
                        />

                        <!-- Header del Programa -->
                        <ProgramHeader
                            :program-name="program.name"
                            :destination="program.destination"
                            :departure-date="program.departure_date"
                        />

                        <!-- Descripción del Viaje -->
                        <TripDescription
                            :description="program.trip_description"
                        />

                        <!-- Línea Decorativa -->
                        <DecorativeLine />

                        <!-- Pilares -->
                        <ProgramPillars :pillars="program.pillars" />
                        <!-- Línea Decorativa -->
                        <DecorativeLine />

                        <!-- Descripción del Itinerario -->
                        <ItineraryDescription
                            :description="program.itinerary_description"
                        />

                        <!-- Archivos PDF -->
                        <ProgramDocuments
                            :itinerary-file="program.itinerary_file"
                            :travel-assistance-coverage="
                                program.travel_assistance_coverage
                            "
                            :equipment-list="program.equipment_list"
                        />
                        <!-- Sección de Advertencia -->
                        <WarningSection />
                    </div>

                    <!-- Componente Derecho -->
                    <div class="flex-1 p-10">
                        <PaymentPanel 
                            :final-payment-date="program.final_payment_date" 
                            :program-id="program.id"
                        />
                    </div>
                </div>
            </div>

            <!-- Botón Volver a Seleccionar Viaje -->
            <div class="mx-[120px] mt-8">
                <BackToHomeButton :rut="rut" variant="programs" />
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
import Header from "@/Components/Ecommerce/Header.vue";
import Footer from "@/Components/Ecommerce/Footer.vue";
import ParticipantHeader from "@/Components/Ecommerce/ParticipantHeader.vue";
import ProcessSteps from "@/Components/Ecommerce/ProcessSteps.vue";
import BackToHomeButton from "@/Components/Ecommerce/BackToHomeButton.vue";
import ImageCarousel from "@/Components/Ecommerce/ProgramDetailComponents/ImageCarousel.vue";
import ProgramHeader from "@/Components/Ecommerce/ProgramDetailComponents/ProgramHeader.vue";
import TripDescription from "@/Components/Ecommerce/ProgramDetailComponents/TripDescription.vue";
import DecorativeLine from "@/Components/Ecommerce/ProgramDetailComponents/DecorativeLine.vue";
import WarningSection from "@/Components/Ecommerce/ProgramDetailComponents/WarningSection.vue";
import ProgramPillars from "@/Components/Ecommerce/ProgramDetailComponents/ProgramPillars.vue";
import ProgramDocuments from "@/Components/Ecommerce/ProgramDetailComponents/ProgramDocuments.vue";
import ItineraryDescription from "@/Components/Ecommerce/ProgramDetailComponents/ItineraryDescription.vue";
import PaymentPanel from "@/Components/Ecommerce/ProgramDetailComponents/PaymentPanel.vue";

export default {
    components: {
        Header,
        Footer,
        Head,
        ParticipantHeader,
        ProcessSteps,
        BackToHomeButton,
        ImageCarousel,
        ProgramHeader,
        TripDescription,
        DecorativeLine,
        WarningSection,
        ProgramPillars,
        ProgramDocuments,
        ItineraryDescription,
        PaymentPanel,
    },
    props: {
        participant: {
            type: Object,
            required: true,
        },
        program: {
            type: Object,
            required: true,
        },
        rut: {
            type: String,
            required: true,
        },
    },

};
</script>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>
