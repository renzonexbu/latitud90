<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref } from "vue";
import AlertWrapper from "@/Components/Admin/AlertWrapper.vue";

const props = defineProps({
    template: Object,
    versions: Array,
});

const showContent = ref(false);
const showVariables = ref(false);

const handleEdit = () => {
    router.get(route("admin.document-templates.edit", props.template.id));
};

const handlePreview = () => {
    window.open(route("admin.document-templates.preview", props.template.id), '_blank');
};

const handleBack = () => {
    router.get(route("admin.document-templates.index"));
};

const handleViewVersion = (version) => {
    router.get(route("admin.document-templates.show", version.id));
};

const handleActivateVersion = (version) => {
    if (confirm(`¿Deseas activar la versión ${version.version}? Esto desactivará la versión actual.`)) {
        router.post(route("admin.document-templates.toggle-active", version.id));
    }
};
</script>

<template>
    <AdminLayout>
        <Head :title="`Ver Plantilla: ${template.name}`" />

        <AlertWrapper />

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">
                            {{ template.name }}
                        </h1>
                        <p class="mt-1 text-sm text-gray-600">
                            Detalles de la plantilla
                        </p>
                    </div>
                    <div class="flex space-x-3">
                        <button
                            @click="handlePreview"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Previsualizar PDF
                        </button>
                        <button
                            @click="handleEdit"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Editar
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Info -->
                    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Información General</h2>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Tipo de Documento</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ template.type === 'contract' ? 'Contrato de Reserva' : 'Comprobante de Pago' }}
                                        </span>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Versión</dt>
                                    <dd class="mt-1 text-sm text-gray-900">v{{ template.version }}</dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Estado</dt>
                                    <dd class="mt-1">
                                        <span :class="template.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                            {{ template.is_active ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Fecha de Creación</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ new Date(template.created_at).toLocaleString() }}
                                    </dd>
                                </div>

                                <div v-if="template.creator" class="sm:col-span-2">
                                    <dt class="text-sm font-medium text-gray-500">Creado Por</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ template.creator.name }}</dd>
                                </div>

                                <div v-if="template.notes" class="sm:col-span-2">
                                    <dt class="text-sm font-medium text-gray-500">Notas</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ template.notes }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Variables -->
                    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                        <button
                            @click="showVariables = !showVariables"
                            class="w-full px-6 py-4 border-b border-gray-200 flex justify-between items-center hover:bg-gray-50"
                        >
                            <h2 class="text-lg font-medium text-gray-900">Variables Disponibles</h2>
                            <svg
                                :class="{ 'rotate-180': showVariables }"
                                class="h-5 w-5 text-gray-400 transform transition-transform"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div v-if="showVariables" class="px-6 py-4">
                            <div class="grid grid-cols-1 gap-4">
                                <div
                                    v-for="(description, variable) in template.variables"
                                    :key="variable"
                                    class="flex items-start"
                                >
                                    <code class="flex-shrink-0 px-2 py-1 bg-gray-100 text-indigo-600 rounded text-sm font-mono" v-text="`{{${variable}}}`"></code>
                                    <span class="ml-3 text-sm text-gray-600">{{ description }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                        <button
                            @click="showContent = !showContent"
                            class="w-full px-6 py-4 border-b border-gray-200 flex justify-between items-center hover:bg-gray-50"
                        >
                            <h2 class="text-lg font-medium text-gray-900">Contenido HTML</h2>
                            <svg
                                :class="{ 'rotate-180': showContent }"
                                class="h-5 w-5 text-gray-400 transform transition-transform"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div v-if="showContent" class="px-6 py-4">
                            <pre class="bg-gray-50 p-4 rounded-md overflow-x-auto text-xs font-mono"><code>{{ template.content }}</code></pre>
                        </div>
                    </div>
                </div>

                <!-- Sidebar - Version History -->
                <div class="lg:col-span-1">
                    <div class="bg-white shadow-sm rounded-lg overflow-hidden sticky top-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Historial de Versiones</h2>
                        </div>
                        <div class="px-6 py-4">
                            <div class="flow-root">
                                <ul class="-mb-8">
                                    <li v-for="(version, index) in versions" :key="version.id">
                                        <div class="relative pb-8">
                                            <span
                                                v-if="index !== versions.length - 1"
                                                class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                                                aria-hidden="true"
                                            ></span>
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span
                                                        :class="[
                                                            version.is_active ? 'bg-green-500' : 'bg-gray-400',
                                                            'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white',
                                                        ]"
                                                    >
                                                        <svg
                                                            v-if="version.is_active"
                                                            class="h-5 w-5 text-white"
                                                            fill="currentColor"
                                                            viewBox="0 0 20 20"
                                                        >
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                        <span v-else class="text-white text-xs">{{ version.version }}</span>
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div>
                                                        <div class="text-sm flex items-center justify-between">
                                                            <div>
                                                                <button
                                                                    v-if="version.id !== template.id"
                                                                    @click="handleViewVersion(version)"
                                                                    class="font-medium text-indigo-600 hover:text-indigo-900"
                                                                >
                                                                    v{{ version.version }}
                                                                </button>
                                                                <span v-else class="font-medium text-gray-900">
                                                                    v{{ version.version }}
                                                                </span>
                                                                <span v-if="version.is_active" class="ml-2 text-xs text-green-600">
                                                                    (Activa)
                                                                </span>
                                                            </div>
                                                            <button
                                                                v-if="!version.is_active"
                                                                @click="handleActivateVersion(version)"
                                                                class="text-xs text-blue-600 hover:text-blue-900 hover:underline"
                                                                title="Activar esta versión"
                                                            >
                                                                Activar
                                                            </button>
                                                        </div>
                                                        <p v-if="version.notes" class="mt-1 text-xs text-gray-500">
                                                            {{ version.notes }}
                                                        </p>
                                                        <p class="mt-1 text-xs text-gray-400">
                                                            {{ new Date(version.created_at).toLocaleDateString() }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Back Button -->
            <div class="mt-6">
                <button
                    @click="handleBack"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver al Listado
                </button>
            </div>
        </div>
    </AdminLayout>
</template>
