<template>
    <AdminLayout>
        <Head title="Reportes Ejecutivo Comercial" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Menú de Navegación de Reportes -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold">
                                Reportes Ejecutivo Comercial
                            </h2>
                            <Link
                                :href="route('admin.reports.index')"
                                class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg flex items-center transition-colors"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Volver a reportes
                            </Link>
                        </div>

                        <p class="text-gray-600 mb-6">
                            Reportes destinados para ejecutivos comerciales con información de contacto pagador.
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Consolidado Área Ingresos -->
                            <Link
                                :href="route('admin.reports.executives.consolidated')"
                                class="bg-teal-50 hover:bg-teal-100 p-6 rounded-lg border border-teal-200 transition-colors"
                            >
                                <div class="flex items-center mb-3">
                                    <div class="p-2 bg-teal-100 rounded-lg mr-3">
                                        <svg
                                            class="w-6 h-6 text-teal-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                            ></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-semibold text-teal-800">
                                        Consolidado Área Ingresos
                                    </h3>
                                </div>
                                <p class="text-sm text-teal-600">
                                    Reporte consolidado de ingresos con información de contacto pagador.
                                </p>
                            </Link>

                            <!-- Estado de Cuenta Parcial -->
                            <Link
                                :href="route('admin.reports.executives.partial-account')"
                                class="bg-cyan-50 hover:bg-cyan-100 p-6 rounded-lg border border-cyan-200 transition-colors"
                            >
                                <div class="flex items-center mb-3">
                                    <div class="p-2 bg-cyan-100 rounded-lg mr-3">
                                        <svg
                                            class="w-6 h-6 text-cyan-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                            ></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-semibold text-cyan-800">
                                        Estado de Cuenta Parcial
                                    </h3>
                                </div>
                                <p class="text-sm text-cyan-600">
                                    Estado de cuenta parcial con datos de contacto pagador.
                                </p>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Información adicional -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex items-start">
                            <div class="p-2 bg-amber-100 rounded-lg mr-4">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">Nota importante</h3>
                                <p class="text-sm text-gray-600">
                                    Estos reportes contienen información sensible de contacto pagador y solo deben ser utilizados
                                    por personal autorizado para la gestión de cobranzas y seguimiento de pagos.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Documentos Procedimientos -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Documentos Procedimientos</h3>

                        <div v-if="documents && documents.length > 0" class="space-y-3">
                            <a
                                v-for="document in documents"
                                :key="document.id"
                                :href="route('admin.reports.procedure-documents.download', document.id)"
                                class="flex items-center justify-between p-4 bg-gray-50 hover:bg-blue-50 rounded-lg border border-gray-200 hover:border-blue-300 transition-all group"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-blue-100 rounded-lg group-hover:bg-blue-200 transition-colors">
                                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900 group-hover:text-blue-700">{{ document.title }}</h4>
                                        <p v-if="document.description" class="text-sm text-gray-500 mt-1">{{ document.description }}</p>
                                        <div class="flex items-center gap-3 mt-1">
                                            <span class="text-xs text-gray-400">{{ document.file_type.toUpperCase() }}</span>
                                            <span class="text-xs text-gray-400">{{ document.formatted_file_size }}</span>
                                        </div>
                                    </div>
                                </div>
                                <svg class="w-5 h-5 text-blue-600 group-hover:text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                            </a>
                        </div>

                        <div v-else class="text-center py-8">
                            <svg class="w-16 h-16 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-gray-500">No hay documentos disponibles</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

defineProps({
    documents: {
        type: Array,
        default: () => []
    }
});
</script>
