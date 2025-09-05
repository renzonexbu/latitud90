<template>
    <AdminLayout>
        <Head title="Importar Devoluciones desde Excel" />

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-8">
                        <!-- Header -->
                        <div class="flex justify-between items-center mb-8">
                            <div>
                                <h2 class="text-3xl font-bold text-gray-900">Importar Devoluciones desde Excel</h2>
                                <p class="text-gray-600 mt-2">Importe múltiples notas de crédito desde un archivo Excel</p>
                            </div>
                            <div class="flex gap-3">
                                <Link
                                    :href="route('admin.payments.refunds.menu')"
                                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200"
                                >
                                    Volver al Menú
                                </Link>
                                <Link
                                    :href="route('admin.payments.index')"
                                    class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors duration-200"
                                >
                                    Cancelar
                                </Link>
                            </div>
                        </div>

                        <!-- Información del formato -->
                        <div class="mb-8 p-6 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-blue-800 mb-2">Formato requerido del archivo Excel</h3>
                                    <div class="text-sm text-blue-700 space-y-1">
                                        <p><strong>Columnas requeridas:</strong></p>
                                        <ul class="list-disc list-inside ml-4 space-y-1">
                                            <li><strong>Cod. SII:</strong> Código SII de la nota de crédito</li>
                                            <li><strong>N. Documento:</strong> Número de documento de la nota de crédito</li>
                                            <li><strong>Fecha:</strong> Fecha en formato YYYY-MM-DD</li>
                                            <li><strong>RUT:</strong> RUT del cliente (con o sin puntos y guión)</li>
                                            <li><strong>Nombre del Cliente:</strong> Nombre completo del cliente</li>
                                            <li><strong>Neto Afecto:</strong> Monto neto afecto a IVA (negativo)</li>
                                            <li><strong>Neto Exento:</strong> Monto neto exento de IVA (negativo)</li>
                                            <li><strong>Iva:</strong> Monto del IVA (negativo)</li>
                                            <li><strong>Total:</strong> Monto total de la devolución (negativo)</li>
                                            <li><strong>Negocio Afiliado:</strong> Código de inscripción (enrollment_code)</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Formulario de importación -->
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Selección de archivo -->
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-[#e74c3c] transition-colors">
                                <div class="space-y-4">
                                    <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                    </div>
                                    
                                    <div>
                                        <label for="excel_file" class="cursor-pointer">
                                            <span class="text-lg font-medium text-gray-700">Seleccionar archivo Excel</span>
                                            <input
                                                id="excel_file"
                                                type="file"
                                                accept=".xlsx,.xls"
                                                @change="handleFileSelect"
                                                class="hidden"
                                                ref="fileInput"
                                            />
                                        </label>
                                        <p class="text-sm text-gray-500 mt-2">
                                            Formatos soportados: .xlsx, .xls
                                        </p>
                                    </div>
                                    
                                    <div v-if="selectedFile" class="mt-4 p-3 bg-green-50 rounded-lg border border-green-200">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="text-sm font-medium text-green-800">
                                                Archivo seleccionado: {{ selectedFile.name }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-green-600 mt-1">
                                            Tamaño: {{ formatFileSize(selectedFile.size) }}
                                        </p>
                                    </div>
                                </div>
                            </div>


                            <!-- Botones -->
                            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                                <Link
                                    :href="route('admin.payments.refunds.menu')"
                                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200"
                                >
                                    Cancelar
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="!selectedFile || form.processing"
                                    class="bg-[#e74c3c] hover:bg-[#c0392b] disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center"
                                >
                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ form.processing ? 'Procesando...' : 'Importar Devoluciones' }}
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const selectedFile = ref(null);
const fileInput = ref(null);

const form = useForm({
    file: null
});

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        // Validar tipo de archivo
        const allowedTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
            'application/vnd.ms-excel' // .xls
        ];
        
        if (!allowedTypes.includes(file.type)) {
            alert('Por favor seleccione un archivo Excel válido (.xlsx o .xls)');
            return;
        }
        
        // Validar tamaño (máximo 10MB)
        if (file.size > 10 * 1024 * 1024) {
            alert('El archivo es demasiado grande. El tamaño máximo permitido es 10MB.');
            return;
        }
        
        selectedFile.value = file;
        form.file = file;
    }
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};


const submit = () => {
    if (!selectedFile.value) {
        alert('Por favor seleccione un archivo Excel');
        return;
    }
    
    form.file = selectedFile.value;
    form.post(route('admin.payments.refunds.import-store'), {
        onSuccess: () => {
            // Limpiar el archivo seleccionado
            selectedFile.value = null;
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
        onError: (errors) => {
            console.error('Errores de validación:', errors);
        }
    });
};
</script>

<style scoped>
/* Estilos personalizados para el input de archivo */
input[type="file"]:focus + label {
    outline: 2px solid #e74c3c;
    outline-offset: 2px;
}
</style>
