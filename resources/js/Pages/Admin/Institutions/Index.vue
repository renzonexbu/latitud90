<template>
    <AdminLayout>
        <Head title="Gestión de Instituciones" />

        <div class="py-12">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="bg-white overflow-hidden shadow-sm rounded-[20px] mb-6 p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-[#434343] font-nexa-bold text-[24px] leading-[28px] font-bold">
                                Gestión de Instituciones
                            </h2>
                            <p class="text-[#5b5b5b] font-nexa-regular text-[14px] leading-[18px] font-normal mt-2">
                                Administra las instituciones educativas
                            </p>
                        </div>
                        <button
                            @click="openCreateModal"
                            class="bg-turquesa hover:bg-turquesa-dark text-white px-6 py-3 rounded-lg font-medium transition-colors inline-flex items-center gap-2"
                        >
                            <SchoolIcon stroke-color="white" class="w-5 h-5" />
                            Agregar nueva institución
                        </button>
                    </div>
                </div>

                <!-- Institutions List -->
                <div v-if="institutions.length === 0" class="bg-white overflow-hidden shadow-sm rounded-[20px] p-6">
                    <div class="text-center py-12">
                        <div class="text-gray-400 mb-4">
                            <SchoolIcon class="w-16 h-16 mx-auto" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            No hay instituciones disponibles
                        </h3>
                        <p class="text-gray-600 mb-6">
                            Comienza creando tu primera institución para mostrar aquí.
                        </p>
                        <button
                            @click="openCreateModal"
                            class="bg-turquesa hover:bg-turquesa-dark text-white px-6 py-3 rounded-lg font-medium transition-colors inline-flex items-center gap-2"
                        >
                            <SchoolIcon stroke-color="white" class="w-5 h-5" />
                            Agregar nueva institución
                        </button>
                    </div>
                </div>

                <!-- Institutions Table -->
                <div v-else class="bg-white overflow-hidden shadow-sm rounded-[20px] p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nombre
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tipo
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Email
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Teléfono
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="institution in institutions" :key="institution.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ institution.name }}
                                        </div>
                                        <div v-if="institution.address" class="text-sm text-gray-500">
                                            {{ institution.address }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                              :class="{
                                                  'bg-blue-100 text-blue-800': institution.type === 'school',
                                                  'bg-green-100 text-green-800': institution.type === 'university',
                                                  'bg-gray-100 text-gray-800': institution.type === 'other'
                                              }">
                                            {{ getTypeLabel(institution.type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ institution.email || 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ institution.phone || 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                              :class="institution.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                            {{ institution.active ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button
                                            @click="editInstitution(institution.id)"
                                            class="text-indigo-600 hover:text-indigo-900 mr-3"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            @click="deleteInstitution(institution.id)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Eliminar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Institution Modal -->
        <CreateInstitutionModal 
            :show="showCreateModal" 
            :errors="errors"
            @close="closeCreateModal"
            @institution-created="handleInstitutionCreated"
        />

        <!-- Success Message -->
        <div v-if="$page.props.flash.success" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                {{ $page.props.flash.success }}
                <button @click="closeSuccessMessage" class="ml-2 hover:bg-green-600 rounded p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import CreateInstitutionModal from "@/Components/Institutions/CreateInstitutionModal.vue";
import { SchoolIcon } from "@/Components/Icons";

export default {
    name: "InstitutionsIndex",
    setup() {
        const $page = usePage();
        return { $page };
    },
    components: {
        Head,
        Link,
        AdminLayout,
        CreateInstitutionModal,
        SchoolIcon,
    },
    props: {
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

    methods: {
        getTypeLabel(type) {
            const types = {
                'school': 'Escuela',
                'university': 'Universidad',
                'other': 'Otro'
            };
            return types[type] || type;
        },
        openCreateModal() {
            this.showCreateModal = true;
        },
        closeCreateModal() {
            this.showCreateModal = false;
        },
        handleInstitutionCreated(newInstitution) {
            // Add the new institution to the list
            this.institutions.push(newInstitution);
            // Close the modal
            this.closeCreateModal();
            // Show success message
            this.$page.props.flash.success = 'Institución creada exitosamente.';
        },
        editInstitution(institutionId) {
            // Navigate to edit institution page
            router.visit(route("admin.institutions.edit", institutionId));
        },
        deleteInstitution(institutionId) {
            if (confirm('¿Estás seguro de que quieres eliminar esta institución?')) {
                router.delete(route("admin.institutions.destroy", institutionId), {
                    onSuccess: () => {
                        this.$page.props.flash.success = 'Institución eliminada exitosamente.';
                    },
                });
            }
        },
        closeSuccessMessage() {
            // Clear the flash message
            this.$page.props.flash.success = null;
        },
    },
};
</script>

<style scoped>
.font-nexa-bold {
    font-family: 'Nexa-Bold', sans-serif;
}

.font-nexa-regular {
    font-family: 'Nexa-Regular', sans-serif;
}
</style>
