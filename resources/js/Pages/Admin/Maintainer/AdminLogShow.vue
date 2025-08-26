<template>
    <AdminLayout>
        <Head title="Detalles del Log" />
        
        <div class="p-6">
            <div class="max-w-4xl mx-auto">
                <!-- Header -->
                <MaintainerHeader 
                    subtitle="Detalles del log del sistema"
                    :show-action-button="false"
                />

                <!-- Log Details -->
                <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 mt-6">
                    <!-- Basic Information -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Información Básica</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ID del Log</label>
                                <div class="text-sm text-gray-900 bg-gray-50 p-2 rounded">{{ adminLog.id }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha y Hora</label>
                                <div class="text-sm text-gray-900 bg-gray-50 p-2 rounded">{{ formatDate(adminLog.created_at) }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Acción</label>
                                <div class="flex items-center">
                                    <div 
                                        :class="[
                                            'rounded-lg px-3 py-1 text-xs font-bold text-white mr-2',
                                            getActionColor(adminLog.action)
                                        ]"
                                    >
                                        {{ getActionText(adminLog.action) }}
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Módulo</label>
                                <div class="text-sm text-gray-900 bg-gray-50 p-2 rounded">{{ capitalizeWords(adminLog.module) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- User Information -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Información del Usuario</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Usuario</label>
                                <div class="text-sm text-gray-900 bg-gray-50 p-2 rounded">{{ adminLog.user_name || 'Sistema' }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <div class="text-sm text-gray-900 bg-gray-50 p-2 rounded">{{ adminLog.user_email || 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Request Information -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Información de la Petición</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Método HTTP</label>
                                <div class="flex items-center">
                                    <div 
                                        :class="[
                                            'rounded-lg px-3 py-1 text-xs font-bold text-white mr-2',
                                            getMethodColor(adminLog.request_method)
                                        ]"
                                    >
                                        {{ adminLog.request_method || 'N/A' }}
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">IP Address</label>
                                <div class="text-sm text-gray-900 bg-gray-50 p-2 rounded">{{ adminLog.ip_address || 'N/A' }}</div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">URL</label>
                                <div class="text-sm text-gray-900 bg-gray-50 p-2 rounded break-all">{{ adminLog.request_url || 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Descripción</h3>
                        <div class="text-sm text-gray-900 bg-gray-50 p-3 rounded">
                            {{ adminLog.description || 'Sin descripción disponible' }}
                        </div>
                    </div>

                    <!-- Resource Information -->
                    <div v-if="adminLog.resource_type || adminLog.resource_id" class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recurso Afectado</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Recurso</label>
                                <div class="text-sm text-gray-900 bg-gray-50 p-2 rounded">{{ adminLog.resource_type || 'N/A' }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ID del Recurso</label>
                                <div class="text-sm text-gray-900 bg-gray-50 p-2 rounded">{{ adminLog.resource_id || 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Changes -->
                    <div v-if="adminLog.old_values || adminLog.new_values" class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Cambios de Datos</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div v-if="adminLog.old_values">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Valores Anteriores</label>
                                <div class="text-sm text-gray-900 bg-gray-50 p-3 rounded">
                                    <pre class="whitespace-pre-wrap">{{ JSON.stringify(adminLog.old_values, null, 2) }}</pre>
                                </div>
                            </div>
                            <div v-if="adminLog.new_values">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Valores Nuevos</label>
                                <div class="text-sm text-gray-900 bg-gray-50 p-3 rounded">
                                    <pre class="whitespace-pre-wrap">{{ JSON.stringify(adminLog.new_values, null, 2) }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Data -->
                    <div v-if="adminLog.additional_data" class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Datos Adicionales</h3>
                        <div class="text-sm text-gray-900 bg-gray-50 p-3 rounded">
                            <pre class="whitespace-pre-wrap">{{ JSON.stringify(adminLog.additional_data, null, 2) }}</pre>
                        </div>
                    </div>

                    <!-- Request Data -->
                    <div v-if="adminLog.request_data" class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Datos de la Petición</h3>
                        <div class="text-sm text-gray-900 bg-gray-50 p-3 rounded">
                            <pre class="whitespace-pre-wrap">{{ JSON.stringify(adminLog.request_data, null, 2) }}</pre>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end gap-4 pt-6 border-t border-gray-200">
                        <button
                            @click="goBack"
                            class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors font-nexa-regular text-[14px]"
                        >
                            Volver
                        </button>
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

export default {
    name: "AdminLogShow",
    components: {
        Head,
        AdminLayout,
        MaintainerHeader,
    },
    props: {
        adminLog: {
            type: Object,
            required: true
        }
    },
    methods: {
        goBack() {
            this.$inertia.visit(route('admin.maintainer.admin-logs.index'));
        },

        formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString('es-CL', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        },

        getActionColor(action) {
            const colors = {
                'create': 'bg-green-500',
                'update': 'bg-blue-500',
                'delete': 'bg-red-500',
                'view': 'bg-gray-500',
                'export': 'bg-purple-500',
                'login': 'bg-indigo-500',
                'logout': 'bg-yellow-500'
            };
            return colors[action] || 'bg-gray-400';
        },

        getActionText(action) {
            const texts = {
                'create': 'Crear',
                'update': 'Actualizar',
                'delete': 'Eliminar',
                'view': 'Ver',
                'export': 'Exportar',
                'login': 'Login',
                'logout': 'Logout'
            };
            return texts[action] || action;
        },

        getMethodColor(method) {
            const colors = {
                'GET': 'bg-blue-500',
                'POST': 'bg-green-500',
                'PUT': 'bg-yellow-500',
                'PATCH': 'bg-orange-500',
                'DELETE': 'bg-red-500'
            };
            return colors[method] || 'bg-gray-500';
        },

        capitalizeWords(string) {
            if (!string) return '';
            return string.split('_').map(word => 
                word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
            ).join(' ');
        }
    }
};
</script>
