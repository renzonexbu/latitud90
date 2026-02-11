<template>
    <AdminLayout>
        <Head title="Documentos Procedimientos" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-nexa-bold text-gray-900">Documentos Procedimientos</h1>
                        <p class="mt-2 text-sm text-gray-600">Consulta y descarga los documentos de procedimientos disponibles</p>
                    </div>
                    <button
                        @click="goBack"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Volver
                    </button>
                </div>

                <!-- Documents Grid -->
                <div v-if="documents.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="document in documents"
                        :key="document.id"
                        class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6"
                    >
                        <!-- Document Icon -->
                        <div class="flex items-center gap-4 mb-4">
                            <div class="flex-shrink-0">
                                <svg
                                    class="h-12 w-12"
                                    :class="getFileIconColor(document.file_type)"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <span
                                    class="inline-block px-2 py-1 text-xs font-semibold rounded"
                                    :class="getFileTypeBadgeColor(document.file_type)"
                                >
                                    {{ document.file_type.toUpperCase() }}
                                </span>
                            </div>
                        </div>

                        <!-- Document Info -->
                        <h3 class="text-lg font-nexa-bold text-gray-900 mb-2">
                            {{ document.title }}
                        </h3>
                        <p v-if="document.description" class="text-sm text-gray-600 mb-4 line-clamp-3">
                            {{ document.description }}
                        </p>

                        <!-- Document Meta -->
                        <div class="space-y-1 mb-4 text-xs text-gray-500">
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <span>{{ document.formatted_file_size }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>{{ formatDate(document.created_at) }}</span>
                            </div>
                        </div>

                        <!-- Download Button -->
                        <a
                            :href="route('admin.reports.procedure-documents.download', document.id)"
                            class="block w-full bg-[#007e93] text-white text-center px-4 py-3 rounded-lg hover:bg-[#006580] transition-colors font-nexa-bold"
                        >
                            <div class="flex items-center justify-center gap-2">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Descargar
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-white rounded-lg shadow p-12 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No hay documentos disponibles</h3>
                    <p class="mt-2 text-sm text-gray-500">Los documentos de procedimientos aparecerán aquí cuando estén disponibles.</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    documents: Array
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-CL', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const getFileIconColor = (fileType) => {
    const type = fileType.toLowerCase();
    if (type === 'pdf') return 'text-red-500';
    if (type === 'doc' || type === 'docx') return 'text-blue-500';
    return 'text-gray-500';
};

const goBack = () => {
    window.history.back();
};

const getFileTypeBadgeColor = (fileType) => {
    const type = fileType.toLowerCase();
    if (type === 'pdf') return 'bg-red-100 text-red-800';
    if (type === 'doc' || type === 'docx') return 'bg-blue-100 text-blue-800';
    return 'bg-gray-100 text-gray-800';
};
</script>

<style scoped>
.font-nexa-bold {
    font-family: "Nexa-Bold", sans-serif;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
