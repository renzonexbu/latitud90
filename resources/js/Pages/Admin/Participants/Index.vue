<template>
  <AdminLayout>
        <Head title="Gestión de Participantes" />

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <ParticipantsHeader
                    subtitle="Visualización de participantes"
                    :show-create-button="true"
                    @create-participant="openCreateModal"
                />

                <!-- Filters -->
                <div class="bg-white overflow-hidden shadow-sm rounded-[20px] mb-6 p-6">
                    <ParticipantsFilters
                        :initial-filters="filters"
                        @filters-changed="handleFiltersChanged"
                    />
              </div>

                <!-- Participants List -->
                <div v-if="participants.data.length === 0 && sampleParticipants.length === 0" 
                     class="bg-white overflow-hidden shadow-sm rounded-[20px] p-6">
                    <div class="text-center py-12">
                        <div class="text-gray-400 mb-4">
                            <PersonsIcon class="w-16 h-16 mx-auto" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            No hay participantes disponibles
                        </h3>
                        <p class="text-gray-600 mb-6">
                            Comienza agregando participantes para mostrar aquí.
                        </p>
                        <button
                            @click="openCreateModal"
                            class="bg-turquesa hover:bg-turquesa-dark text-white px-6 py-3 rounded-lg font-medium transition-colors inline-flex items-center gap-2"
                        >
                            <PersonsIcon stroke-color="white" class="w-5 h-5" />
                            Agregar nuevo participante
                        </button>
                      </div>
            </div>

                <!-- Participants Table -->
                <div v-else class="space-y-6">
                    <div class="bg-white rounded-[20px] p-0 overflow-hidden">
                        <ParticipantsTable 
                            :participants="sampleParticipants" 
                            @edit-participant="handleEditParticipant"
                            @contact-whatsapp="handleWhatsAppContact"
                        />
                        
                        <!-- Pagination -->
                        <div class="p-4">
                            <ParticipantsPagination
                                :current-page="1"
                                :total-participants="sampleParticipants.length || 0"
                                :participants-per-page="10"
                                @page-changed="handlePageChanged"
                            />
            </div>
          </div>
        </div>
      </div>
    </div>

        <!-- Create Participant Modal -->
        <CreateParticipantModal 
            :show="showCreateModal" 
            :courses="courses"
            :institutions="institutions"
            :errors="errors"
            @close="closeCreateModal" 
        />
  </AdminLayout>
</template>

<script>
import { Head, Link, router } from "@inertiajs/vue3";
  import AdminLayout from "@/Layouts/AdminLayout.vue";
import ParticipantsHeader from "@/Components/Participants/ParticipantsHeader.vue";
import ParticipantsFilters from "@/Components/Participants/ParticipantsFilters.vue";
import ParticipantsPagination from "@/Components/Participants/ParticipantsPagination.vue";
import ParticipantsTable from "@/Components/Participants/ParticipantsTable.vue";
import CreateParticipantModal from "./Create.vue";
import { PersonsIcon } from "@/Components/Icons";
import _ from "lodash";

export default {
    name: "ParticipantsIndex",
    components: {
        Head,
        Link,
        AdminLayout,
        ParticipantsHeader,
        ParticipantsFilters,
        ParticipantsPagination,
        ParticipantsTable,
        CreateParticipantModal,
        PersonsIcon,
    },
    props: {
        participants: {
            type: Object,
            default: () => ({ data: [] }),
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
        courses: {
            type: Array,
            default: () => [],
        },
        institutions: {
            type: Array,
            default: () => [],
        },
        errors: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            showCreateModal: false
        };
    },
    computed: {
        sampleParticipants() {
            // Datos de ejemplo basados en el Figma
            return [
                {
                    id: 1,
                    document_number: '0000000',
                    first_name: 'Lorena',
                    last_name: 'Villagómez',
                    course: {
                        institution_name: 'Pedro de Valdivia Providencia',
                        education_level: 'Preescolar',
                        grade: '5',
                        shift: 'mañana',
                        program: {
                            name: 'Ruta de lagos',
                            destination: 'Bariloche Arg- CL'
                        }
                    },
                    status: 'pending_payment',
                    payment_percentage: 80,
                    paid_amount: 3000
                },
                {
                    id: 2,
                    document_number: '0000000',
                    first_name: 'Lorena',
                    last_name: 'Villagómez',
                    course: {
                        institution_name: 'Pedro de Valdivia Providencia',
                        education_level: 'Preescolar',
                        grade: '5',
                        shift: 'mañana',
                        program: {
                            name: 'Ruta de lagos',
                            destination: 'Bariloche Arg- CL'
                        }
                    },
                    status: 'confirmed',
                    payment_percentage: 100,
                    paid_amount: 4000
                },
                {
                    id: 3,
                    document_number: '0000000',
                    first_name: 'Lorena',
                    last_name: 'Villagómez',
                    course: {
                        institution_name: 'Pedro de Valdivia Providencia',
                        education_level: 'Preescolar',
                        grade: '5',
                        shift: 'mañana',
                        program: {
                            name: 'Ruta de lagos',
                            destination: 'Bariloche Arg- CL'
                        }
                    },
                    status: 'pending_payment',
                    payment_percentage: 10,
                    paid_amount: 1000
                },
                {
                    id: 4,
                    document_number: '0000000',
                    first_name: 'Lorena',
                    last_name: 'Villagómez',
                    course: {
                        institution_name: 'Pedro de Valdivia Providencia',
                        education_level: 'Preescolar',
                        grade: '5',
                        shift: 'mañana',
                        program: {
                            name: 'Ruta de lagos',
                            destination: 'Bariloche Arg- CL'
                        }
                    },
                    status: 'confirmed',
                    payment_percentage: 100,
                    paid_amount: 4000
                },
                {
                    id: 5,
                    document_number: '0000000',
                    first_name: 'Lorena',
                    last_name: 'Villagómez',
                    course: {
                        institution_name: 'Pedro de Valdivia Providencia',
                        education_level: 'Preescolar',
                        grade: '5',
                        shift: 'mañana',
                        program: {
                            name: 'Ruta de lagos',
                            destination: 'Bariloche Arg- CL'
                        }
                    },
                    status: 'pending_payment',
                    payment_percentage: 80,
                    paid_amount: 1000
                },
                {
                    id: 6,
                    document_number: '0000000',
                    first_name: 'Lorena',
                    last_name: 'Villagómez',
                    course: {
                        institution_name: 'Pedro de Valdivia Providencia',
                        education_level: 'Preescolar',
                        grade: '5',
                        shift: 'mañana',
                        program: {
                            name: 'Ruta de lagos',
                            destination: 'Bariloche Arg- CL'
                        }
                    },
                    status: 'pending_payment',
                    payment_percentage: 10,
                    paid_amount: 1000
                },
                {
                    id: 7,
                    document_number: '0000000',
                    first_name: 'Lorena',
                    last_name: 'Villagómez',
                    course: {
                        institution_name: 'Pedro de Valdivia Providencia',
                        education_level: 'Preescolar',
                        grade: '5',
                        shift: 'mañana',
                        program: {
                            name: 'Ruta de lagos',
                            destination: 'Bariloche Arg- CL'
                        }
                    },
                    status: 'cancelled',
                    payment_percentage: 0,
                    paid_amount: 0
                },
                {
                    id: 8,
                    document_number: '0000000',
                    first_name: 'Lorena',
                    last_name: 'Villagómez',
                    course: {
                        institution_name: 'Pedro de Valdivia Providencia',
                        education_level: 'Preescolar',
                        grade: '5',
                        shift: 'mañana',
                        program: {
                            name: 'Ruta de lagos',
                            destination: 'Bariloche Arg- CL'
                        }
                    },
                    status: 'confirmed',
                    payment_percentage: 100,
                    paid_amount: 1000
                }
            ];
        }
    },
    methods: {
        handleFiltersChanged(newFilters) {
            router.get(route("admin.participants.index"), newFilters, {
                preserveState: true,
                replace: true,
            });
        },
        handlePageChanged(page) {
            router.get(route("admin.participants.index"), { 
                ...this.filters, 
                page 
            }, {
                preserveState: true,
                replace: true,
            });
        },
        handleEditParticipant(participantId) {
            router.visit(route("admin.participants.edit", participantId));
        },
        handleWhatsAppContact(participant) {
            const phone = `${participant.code_phone}${participant.phone}`;
            const message = `Hola ${participant.first_name}, te contacto sobre tu participación en el programa ${participant.course?.program?.name || 'N/A'}.`;
            const whatsappUrl = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
        },
        openCreateModal() {
            this.showCreateModal = true;
        },
        closeCreateModal() {
            this.showCreateModal = false;
        },
    },
  };
</script>