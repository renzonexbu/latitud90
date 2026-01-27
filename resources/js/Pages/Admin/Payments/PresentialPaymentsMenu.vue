<template>
    <AdminLayout>
        <Head title="Gestión de Pagos Offline" />

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-8">
                        <!-- Header -->
                        <div class="flex justify-between items-center mb-8">
                            <div>
                                <h2 class="text-3xl font-bold text-gray-900">Gestión de Pagos Offline</h2>
                                <p class="text-gray-600 mt-2">Seleccione el método para procesar los pagos</p>
                            </div>
                            <Link
                                :href="route('admin.payments.index')"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200"
                            >
                                Volver
                            </Link>
                        </div>

                        <!-- Opciones de Pago -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Opción 1: Importar desde Excel -->
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 hover:border-turquesa hover:bg-[#007e93]/5 transition-all duration-200 cursor-pointer group"
                                 @click="handleImportExcel">
                                <div class="text-center">
                                    <!-- Icono de Excel -->
                                    <div class="mx-auto w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-purple-200 transition-colors">
                                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>

                                    <h3 class="text-xl font-semibold text-gray-900 mb-3 group-hover:text-turquesa transition-colors">
                                        Importar Pagos desde Excel
                                    </h3>

                                    <p class="text-gray-600 mb-4">
                                        Importe múltiples pagos offline desde un archivo Excel.
                                        Ideal para procesar pagos masivos.
                                    </p>

                                    <div class="text-sm text-gray-500">
                                        <p>• Formato Excel (.xlsx, .xls)</p>
                                        <p>• Múltiples registros</p>
                                        <p>• Validación automática</p>
                                        <p>• Omite suscripciones activas</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Opción 2: Ingresar Manualmente -->
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 hover:border-turquesa hover:bg-[#007e93]/5 transition-all duration-200 cursor-pointer group"
                                 @click="handleManualEntry">
                                <div class="text-center">
                                    <!-- Icono de Formulario -->
                                    <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-blue-200 transition-colors">
                                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </div>

                                    <h3 class="text-xl font-semibold text-gray-900 mb-3 group-hover:text-turquesa transition-colors">
                                        Ingresar Pago Manual
                                    </h3>

                                    <p class="text-gray-600 mb-4">
                                        Ingrese un pago offline individual mediante formulario.
                                        Perfecto para pagos específicos.
                                    </p>

                                    <div class="text-sm text-gray-500">
                                        <p>• Formulario detallado</p>
                                        <p>• Validación en tiempo real</p>
                                        <p>• Registro individual</p>
                                        <p>• Selección de cuota</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información adicional -->
                        <div class="mt-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-blue-800">Información importante</h4>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <p>• Los pagos se registran contra cuotas pendientes del participante</p>
                                        <p>• Se actualiza automáticamente el estado de las cuotas</p>
                                        <p>• Los participantes con suscripciones activas (VirtualPos) son omitidos en importaciones masivas</p>
                                        <p>• Todas las operaciones quedan registradas en el log de administración</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const handleImportExcel = () => {
    router.visit(route('admin.payments.presential.import'));
};

const handleManualEntry = () => {
    router.visit(route('admin.payments.presential.create'));
};
</script>

<style scoped>
.text-turquesa {
    color: #007e93;
}

.border-turquesa {
    border-color: #007e93;
}

.hover\:border-turquesa:hover {
    border-color: #007e93;
}

.hover\:text-turquesa:hover {
    color: #007e93;
}
</style>
