<template>
    <AdminLayout>
        <Head title="Mantenedor del Sistema" />
        
        <div class="p-6">
            <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <MaintainerHeader 
                subtitle="Panel de administración avanzada para super administradores"
                :show-action-button="false"
            />

            <!-- Content Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Gestión de Newsletter - Visible para Super Admin y Marketing -->
                <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
                    <div class="flex items-center mb-4">
                        <SettingsIcon class="w-8 h-8 text-turquesa mr-3" />
                        <h3 class="text-lg font-semibold text-gray-900">
                            Gestión de Newsletter
                        </h3>
                    </div>
                    <p class="text-gray-600 mb-4">
                        Administrar suscriptores del newsletter, exportar listas y gestionar campañas de email.
                    </p>
                    <button 
                        @click="goToNewsletter"
                        class="bg-turquesa text-white px-4 py-2 rounded-md hover:bg-turquesa-dark transition-colors"
                    >
                        Acceder
                    </button>
                </div>

                <!-- Marketing Mails - Visible para Super Admin y Marketing -->
                <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
                    <div class="flex items-center mb-4">
                        <svg class="w-8 h-8 text-turquesa mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900">
                            Marketing Mails
                        </h3>
                    </div>
                    <p class="text-gray-600 mb-4">
                        Gestionar lista de emails para marketing, activar/desactivar suscriptores y administrar campañas.
                    </p>
                    <button 
                        @click="goToMarketingMails"
                        class="bg-turquesa text-white px-4 py-2 rounded-md hover:bg-turquesa-dark transition-colors"
                    >
                        Acceder
                    </button>
                </div>

                <!-- Actividad de Usuarios - Solo Super Admin -->
                <div v-if="isSuperAdmin" class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
                    <div class="flex items-center mb-4">
                        <ReportIcon class="w-8 h-8 text-turquesa mr-3" />
                        <h3 class="text-lg font-semibold text-gray-900">
                            Actividad de Usuarios
                        </h3>
                    </div>
                    <p class="text-gray-600 mb-4">
                        Revisar todas las acciones realizadas por los usuarios en el panel administrativo.
                    </p>
                    <button
                        @click="goToAdminLogs"
                        class="bg-turquesa text-white px-4 py-2 rounded-md hover:bg-turquesa-dark transition-colors"
                    >
                        Acceder
                    </button>
                </div>

                <!-- Plantillas de Documentos - Solo Super Admin -->
                <div v-if="isSuperAdmin" class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
                    <div class="flex items-center mb-4">
                        <svg class="w-8 h-8 text-turquesa mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900">
                            Plantillas de Documentos
                        </h3>
                    </div>
                    <p class="text-gray-600 mb-4">
                        Editar plantillas de PDFs como contratos de reserva y comprobantes de pago.
                    </p>
                    <button
                        @click="goToDocumentTemplates"
                        class="bg-turquesa text-white px-4 py-2 rounded-md hover:bg-turquesa-dark transition-colors"
                    >
                        Acceder
                    </button>
                </div>

                <!-- Términos y Condiciones - Solo Super Admin -->
                <div v-if="isSuperAdmin" class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
                    <div class="flex items-center mb-4">
                        <svg class="w-8 h-8 text-turquesa mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900">
                            Términos y Condiciones
                        </h3>
                    </div>
                    <p class="text-gray-600 mb-4">
                        Gestionar los términos y condiciones del sitio. Agregar, editar o eliminar secciones.
                    </p>
                    <button
                        @click="goToTermsConditions"
                        class="bg-turquesa text-white px-4 py-2 rounded-md hover:bg-turquesa-dark transition-colors"
                    >
                        Acceder
                    </button>
                </div>

                <!-- Documentos Procedimientos - Solo Super Admin -->
                <div v-if="isSuperAdmin" class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
                    <div class="flex items-center mb-4">
                        <svg class="w-8 h-8 text-turquesa mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900">
                            Documentos Procedimientos
                        </h3>
                    </div>
                    <p class="text-gray-600 mb-4">
                        Gestionar documentos Word y PDF de procedimientos que verán los ejecutivos comerciales.
                    </p>
                    <button
                        @click="goToProcedureDocuments"
                        class="bg-turquesa text-white px-4 py-2 rounded-md hover:bg-turquesa-dark transition-colors"
                    >
                        Acceder
                    </button>
                </div>

            </div>

            <!-- Información del Usuario -->
            <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <UserIcon class="w-6 h-6 text-blue-600" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                            Información de Permisos
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p><strong>Rol:</strong> {{ userRoleDisplay }}</p>
                            <p><strong>Permisos:</strong> {{ userPermissionsDisplay }}</p>
                            <p><strong>Último acceso:</strong> {{ new Date().toLocaleString('es-CL') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </AdminLayout>
</template>

<script>
import { Head } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import MaintainerHeader from "@/Components/Maintainer/MaintainerHeader.vue";
import { SettingsIcon, UserIcon, ReportIcon } from "@/Components/Icons";

export default {
    name: "MaintainerIndex",
    components: {
        Head,
        AdminLayout,
        MaintainerHeader,
        SettingsIcon,
        UserIcon,
        ReportIcon,
    },
    props: {
        userPermissions: {
            type: Object,
            default: () => ({})
        }
    },
    computed: {
        isSuperAdmin() {
            return this.$page.props.auth.user &&
                   this.$page.props.auth.user.roles &&
                   this.$page.props.auth.user.roles.includes('super_admin');
        },
        isMarketing() {
            return this.$page.props.auth.user &&
                   this.$page.props.auth.user.roles &&
                   (this.$page.props.auth.user.roles.includes('admin_marketing') ||
                    this.$page.props.auth.user.roles.includes('editor_marketing') ||
                    this.$page.props.auth.user.roles.includes('visualizador_marketing'));
        },
        userRoleDisplay() {
            if (this.isSuperAdmin) return 'Super Administrador';
            if (this.isMarketing) return 'Marketing';
            return 'Usuario';
        },
        userPermissionsDisplay() {
            if (this.isSuperAdmin) return 'Acceso total al sistema';
            if (this.isMarketing) return 'Acceso a Newsletter y Marketing Mails';
            return 'Acceso limitado';
        }
    },
    methods: {
        goToNewsletter() {
            this.$inertia.visit(route('admin.maintainer.newsletter.index'));
        },
        goToMarketingMails() {
            this.$inertia.visit(route('admin.maintainer.marketing.mails.index'));
        },
        goToAdminLogs() {
            this.$inertia.visit(route('admin.maintainer.admin-logs.index'));
        },
        goToDocumentTemplates() {
            this.$inertia.visit(route('admin.document-templates.index'));
        },
        goToTermsConditions() {
            this.$inertia.visit(route('admin.maintainer.terms-conditions.index'));
        },
        goToProcedureDocuments() {
            this.$inertia.visit(route('admin.procedure-documents.index'));
        }
    }
};
</script>
