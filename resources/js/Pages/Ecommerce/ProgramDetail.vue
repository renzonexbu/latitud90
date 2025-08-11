<template>
    <div class="min-h-screen bg-gray-50 w-full p-3 pb-[100px] md:pb-0">
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
            <div class="mx-4 md:mx-[120px] bg-white rounded-lg shadow-lg p-6">
                <div class="flex flex-col md:flex-row gap-6">
                    <!-- Componente Izquierdo - Contenido del Programa -->
                    <div
                        class="w-full md:w-[850px] flex flex-col items-start gap-[46px] flex-shrink-0 p-4 md:p-10"
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
                    <div class="hidden md:block flex-1 p-0 md:p-10">
                        <PaymentPanel 
                            :final-payment-date="program.final_payment_date" 
                            :program-id="program.id"
                            :program="program"
                        />
                    </div>
                </div>
            </div>

            <!-- Botón Volver a Seleccionar Viaje -->
            <div class="mx-4 md:mx-[120px] mt-8">
                <BackToHomeButton :rut="rut" variant="programs" />
            </div>
        </div>

        <!-- Footer -->
        <Footer class="rounded-lg"></Footer>

        <!-- Sticky Payment Bar (Mobile Only) -->
        <div class="md:hidden fixed bottom-0 left-0 right-0 z-50" v-show="!isMobilePaymentOpen">
            <div
                class="flex w-full max-w-[375px] h-[88px] pt-[19px] pb-[21px] px-[20px] justify-center items-center mx-auto flex-shrink-0
                       rounded-t-[16px] border-t border-[#F0F0F0] bg-white shadow-[0_4px_11.6px_0_rgba(163,163,163,0.11)]"
            >
                <div class="flex flex-row items-center justify-between w-full">
                    <div class="flex flex-col items-start">
                        <span class="text-[#434343] font-nexa text-[16px] leading-[22px] font-bold">Faltan pagar</span>
                        <span class="text-[#434343] font-nexa text-[18px] leading-[28px] font-bold">$1800</span>
                    </div>
                    <button
                        class="flex h-[48px] px-[21px] py-[13px] justify-center items-center gap-[10px] rounded-[35px] bg-[#FBBD51]"
                        @click="openMobilePaymentPanel"
                    >
                        <span class="text-white font-nexa-xbold text-[14px] leading-[22px]">Iniciar pago</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Payment Overlay -->
        <div v-if="isMobilePaymentOpen" class="md:hidden fixed inset-x-0 bottom-0 z-50 flex justify-center">
            <div class="bg-white rounded-tl-2xl rounded-tr-2xl border border-[#F0F0F0] pt-5 pr-5 pb-[21px] pl-5 flex flex-col gap-6 items-end justify-start w-[375px] max-h-[90vh] overflow-y-auto" style="box-shadow: 0px 4px 59.3px 0px rgba(229, 229, 229, 0.25)">
                <!-- Header with Close -->
                <div class="flex items-center justify-end w-full">
                    <button @click="closeMobilePaymentPanel" aria-label="Cerrar pago" class="p-2 -mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 22 22" fill="none">
                          <path d="M14.3333 6.55556L7.66667 15.4444M14.3333 15.4444L7.66667 6.55556M1 11C1 12.3132 1.25866 13.6136 1.7612 14.8268C2.26375 16.0401 3.00035 17.1425 3.92893 18.0711C4.85752 18.9997 5.95991 19.7363 7.17317 20.2388C8.38642 20.7413 9.68678 21 11 21C12.3132 21 13.6136 20.7413 14.8268 20.2388C16.0401 19.7363 17.1425 18.9997 18.0711 18.0711C18.9997 17.1425 19.7362 16.0401 20.2388 14.8268C20.7413 13.6136 21 12.3132 21 11C21 8.34784 19.9464 5.8043 18.0711 3.92893C16.1957 2.05357 13.6522 1 11 1C8.34783 1 5.8043 2.05357 3.92893 3.92893C2.05357 5.8043 1 8.34784 1 11Z" stroke="#C7C7C7" stroke-width="1.667" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
                <!-- Payment Panel Content -->
                <div class="w-full self-stretch">
                    <PaymentPanel 
                        :final-payment-date="program.final_payment_date" 
                        :program-id="program.id"
                        :program="program"
                        :is-mobile-overlay="true"
                        :show-header="true"
                        :show-warning="true"
                        :show-remaining-amount="true"
                        :show-payment-button="true"
                    />
                </div>
            </div>
        </div>
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

    data() {
        return {
            isMobilePaymentOpen: false,
        };
    },
    methods: {
        openMobilePaymentPanel() {
            this.isMobilePaymentOpen = true;
        },
        closeMobilePaymentPanel() {
            this.isMobilePaymentOpen = false;
        },
        startPayment() {
            router.visit(`/programs/${this.program.id}/payment`, {
                data: { rut: this.rut }
            });
        }
    }
};
</script>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>
