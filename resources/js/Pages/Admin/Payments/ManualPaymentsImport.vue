<template>
    <AdminLayout>
        <Head title="Importar Pagos Presenciales" />

        <!-- Sistema de Alertas -->
        <AlertWrapper ref="alertWrapper" />

        <!-- Processing Overlay -->
        <div v-if="form.processing" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
            <div class="bg-white rounded-xl p-8 max-w-md w-full mx-4 shadow-2xl">
                <div class="flex flex-col items-center">
                    <!-- Spinner -->
                    <div class="relative">
                        <div class="w-16 h-16 border-4 border-turquesa/20 rounded-full"></div>
                        <div class="w-16 h-16 border-4 border-turquesa border-t-transparent rounded-full animate-spin absolute top-0 left-0"></div>
                    </div>

                    <!-- Title -->
                    <h3 class="mt-6 text-xl font-nexa-bold text-verde-oscuro">
                        {{ isConfirming ? 'Insertando pagos en la base de datos...' : 'Procesando archivo...' }}
                    </h3>

                    <!-- Message -->
                    <p class="mt-2 text-gray-600 font-nexa-regular text-center">
                        {{ isConfirming ? 'Por favor espera mientras se insertan los pagos.' : 'Esto puede tardar varios minutos dependiendo del tamaño del archivo.' }}
                    </p>

                    <!-- File info -->
                    <div v-if="form.file" class="mt-4 px-4 py-2 bg-gray-100 rounded-lg">
                        <p class="text-sm text-gray-700 font-nexa-regular">
                            <span class="font-nexa-bold">{{ form.file.name }}</span>
                            <span class="text-gray-500 ml-2">({{ formatFileSize(form.file.size) }})</span>
                        </p>
                    </div>

                    <!-- Warning -->
                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-xs text-yellow-800 font-nexa-regular text-center">
                            Por favor, no cierres esta ventana ni navegues a otra página mientras se procesa el archivo.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <div v-if="showPreviewModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl w-full max-w-7xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col">
                <!-- Modal Header -->
                <div class="bg-turquesa text-white px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-2xl font-nexa-bold">Vista Previa de Importación</h3>
                        <p class="text-sm font-nexa-regular mt-1 opacity-90">
                            Revisa los datos antes de confirmar la inserción en la base de datos
                        </p>
                    </div>
                    <button @click="closePreview" class="text-white hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Stats Summary -->
                <div v-if="previewData" class="px-6 py-4 bg-gray-50 border-b">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <div class="text-2xl font-nexa-bold text-verde-oscuro">{{ previewData.stats.successful }}</div>
                            <div class="text-sm text-gray-600 font-nexa-regular">Pagos válidos</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <div class="text-2xl font-nexa-bold text-yellow-600">{{ previewData.stats.skipped }}</div>
                            <div class="text-sm text-gray-600 font-nexa-regular">Registros omitidos</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <div class="text-2xl font-nexa-bold text-red-600">{{ previewData.stats.failed }}</div>
                            <div class="text-sm text-gray-600 font-nexa-regular">Registros con errores</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <div class="text-2xl font-nexa-bold text-blue-600">{{ previewData.previewed_rows }}</div>
                            <div class="text-sm text-gray-600 font-nexa-regular">Filas procesadas</div>
                        </div>
                    </div>
                </div>

                <!-- Preview Content -->
                <div class="flex-1 overflow-y-auto px-6 py-4">
                    <div v-if="previewData && previewData.details" class="space-y-4">
                        <!-- Success Records -->
                        <div v-if="successfulDetails.length > 0" class="bg-white rounded-lg border border-gray-200">
                            <div class="bg-green-50 px-4 py-3 border-b border-green-200">
                                <h4 class="font-nexa-bold text-green-800 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Pagos Válidos ({{ successfulDetails.length }})
                                </h4>
                            </div>
                            <div class="p-4 max-h-96 overflow-y-auto">
                                <div class="space-y-2">
                                    <div v-for="(detail, index) in successfulDetails" :key="'success-' + index"
                                        class="p-3 bg-green-50 rounded border border-green-200 text-sm">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <div class="font-nexa-bold text-green-900 mb-1">
                                                    Fila {{ detail.row }}: {{ detail.data?.participant_name }}
                                                </div>
                                                <div class="text-green-800 font-nexa-regular grid grid-cols-2 gap-x-4 gap-y-1">
                                                    <div><span class="font-semibold">RUT:</span> {{ detail.data?.participant_rut }}</div>
                                                    <div><span class="font-semibold">Programa:</span> {{ detail.data?.program_name }}</div>
                                                    <div><span class="font-semibold">Monto:</span> ${{ formatNumber(detail.data?.payment_amount) }}</div>
                                                    <div><span class="font-semibold">Tipo:</span> {{ detail.data?.payment_type_label }}</div>
                                                    <div><span class="font-semibold">Saldo Anterior:</span> ${{ formatNumber(detail.data?.previous_balance) }}</div>
                                                    <div><span class="font-semibold">Nuevo Saldo:</span> ${{ formatNumber(detail.data?.new_balance) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Skipped Records -->
                        <div v-if="skippedDetails.length > 0" class="bg-white rounded-lg border border-gray-200">
                            <div class="bg-yellow-50 px-4 py-3 border-b border-yellow-200">
                                <h4 class="font-nexa-bold text-yellow-800 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    Registros Omitidos ({{ skippedDetails.length }})
                                </h4>
                            </div>
                            <div class="p-4 max-h-96 overflow-y-auto">
                                <div class="space-y-2">
                                    <div v-for="(detail, index) in skippedDetails" :key="'skipped-' + index"
                                        class="p-3 bg-yellow-50 rounded border border-yellow-200 text-sm">
                                        <div class="font-nexa-bold text-yellow-900 mb-1">Fila {{ detail.row }}</div>
                                        <div class="text-yellow-800 font-nexa-regular">{{ detail.message }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Failed Records -->
                        <div v-if="failedDetails.length > 0" class="bg-white rounded-lg border border-gray-200">
                            <div class="bg-red-50 px-4 py-3 border-b border-red-200">
                                <h4 class="font-nexa-bold text-red-800 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Registros con Errores ({{ failedDetails.length }})
                                </h4>
                            </div>
                            <div class="p-4 max-h-96 overflow-y-auto">
                                <div class="space-y-2">
                                    <div v-for="(detail, index) in failedDetails" :key="'failed-' + index"
                                        class="p-3 bg-red-50 rounded border border-red-200 text-sm">
                                        <div class="font-nexa-bold text-red-900 mb-1">Fila {{ detail.row }}</div>
                                        <div class="text-red-800 font-nexa-regular">{{ detail.message }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t flex justify-between items-center">
                    <div class="text-sm text-gray-600 font-nexa-regular">
                        <span v-if="previewData && previewData.stats.successful > 0" class="text-verde-oscuro font-nexa-bold">
                            {{ previewData.stats.successful }} pagos válidos
                        </span>
                        <span v-if="previewData && previewData.stats.failed > 0" class="ml-3 text-red-600 font-nexa-bold">
                            {{ previewData.stats.failed }} con errores
                        </span>
                    </div>
                    <div class="flex gap-3">
                        <button @click="closePreview"
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 font-nexa-bold transition-colors">
                            Cancelar
                        </button>
                        <button @click="confirmImport"
                            :disabled="!previewData || previewData.stats.successful === 0 || form.processing"
                            class="px-6 py-2 bg-turquesa hover:bg-turquesa-dark text-white font-nexa-bold rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                            <svg v-if="form.processing" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Confirmar e Insertar Pagos
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="py-6 lg:py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <div class="flex items-center gap-2 mb-2">
                        <Link :href="route('admin.payments.presential.menu')" class="text-turquesa hover:text-turquesa-dark">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                        </Link>
                        <h1 class="text-2xl font-nexa-bold text-verde-oscuro">
                            Importar Pagos Presenciales Masivos
                        </h1>
                    </div>
                    <p class="text-gray-600 font-nexa-regular">
                        Carga múltiples pagos presenciales desde un archivo Excel
                    </p>
                </div>

                <!-- Upload Form -->
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <form @submit.prevent="submitPreview">
                        <!-- File Upload Area -->
                        <div
                            @dragover.prevent="dragOver = true"
                            @dragleave.prevent="dragOver = false"
                            @drop.prevent="handleDrop"
                            :class="[
                                'border-2 border-dashed rounded-lg p-8 text-center transition-colors',
                                dragOver ? 'border-turquesa bg-turquesa/5' : 'border-gray-300',
                                form.file ? 'bg-green-50 border-green-300' : ''
                            ]"
                        >
                            <input
                                type="file"
                                ref="fileInput"
                                @change="handleFileSelect"
                                accept=".xlsx,.xls"
                                class="hidden"
                            />

                            <div v-if="!form.file">
                                <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                <p class="text-gray-600 font-nexa-regular mb-2">
                                    Arrastra tu archivo Excel aquí o
                                </p>
                                <button
                                    type="button"
                                    @click="$refs.fileInput.click()"
                                    class="text-turquesa hover:text-turquesa-dark font-nexa-bold"
                                >
                                    haz clic para seleccionar
                                </button>
                                <p class="text-xs text-gray-500 mt-2 font-nexa-regular">
                                    Archivos .xlsx o .xls (máximo 10MB)
                                </p>
                            </div>

                            <div v-else class="flex items-center justify-center gap-3">
                                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <div class="text-left">
                                    <p class="font-nexa-bold text-verde-oscuro">{{ form.file.name }}</p>
                                    <p class="text-sm text-gray-600 font-nexa-regular">{{ formatFileSize(form.file.size) }}</p>
                                </div>
                                <button
                                    type="button"
                                    @click="removeFile"
                                    class="ml-4 text-red-600 hover:text-red-700"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Error Messages -->
                        <div v-if="form.errors.file" class="mt-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
                            {{ form.errors.file }}
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-6 flex gap-3">
                            <button
                                type="submit"
                                :disabled="!form.file || form.processing"
                                class="flex-1 bg-turquesa hover:bg-turquesa-dark text-white font-nexa-bold py-3 px-6 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                            >
                                <svg v-if="form.processing" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ form.processing ? 'Procesando...' : 'Ver Vista Previa' }}
                            </button>
                            <Link
                                :href="route('admin.payments.presential.menu')"
                                class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-nexa-bold transition-colors"
                            >
                                Cancelar
                            </Link>
                        </div>
                    </form>
                </div>

                <!-- Results Display (if available) -->
                <div v-if="results" class="mt-6 space-y-6">
                    <!-- Summary Cards -->
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="font-nexa-bold text-verde-oscuro mb-4 text-lg">Resumen de la Importación</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                                <div class="text-2xl font-nexa-bold text-green-700">{{ results.successful }}</div>
                                <div class="text-sm text-green-600 font-nexa-regular">Pagos exitosos</div>
                            </div>
                            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <div class="text-2xl font-nexa-bold text-yellow-700">{{ results.skipped }}</div>
                                <div class="text-sm text-yellow-600 font-nexa-regular">Registros omitidos</div>
                            </div>
                            <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                                <div class="text-2xl font-nexa-bold text-red-700">{{ results.failed }}</div>
                                <div class="text-sm text-red-600 font-nexa-regular">Registros fallidos</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AlertWrapper from '@/Components/Admin/AlertWrapper.vue';
import axios from 'axios';

const page = usePage();
const fileInput = ref(null);
const dragOver = ref(false);
const results = ref(null);
const alertWrapper = ref(null);
const showPreviewModal = ref(false);
const previewData = ref(null);
const isConfirming = ref(false);

const form = useForm({
    file: null
});

// Computed properties to filter details
const successfulDetails = computed(() => {
    if (!previewData.value || !previewData.value.details) return [];
    return previewData.value.details.filter(detail => detail.status === 'success');
});

const skippedDetails = computed(() => {
    if (!previewData.value || !previewData.value.details) return [];
    return previewData.value.details.filter(detail => detail.status === 'skipped');
});

const failedDetails = computed(() => {
    if (!previewData.value || !previewData.value.details) return [];
    return previewData.value.details.filter(detail => detail.status === 'error');
});

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        validateAndSetFile(file);
    }
};

const handleDrop = (event) => {
    dragOver.value = false;
    const file = event.dataTransfer.files[0];
    if (file) {
        validateAndSetFile(file);
    }
};

const validateAndSetFile = (file) => {
    // Validate file type
    const allowedTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel'
    ];

    if (!allowedTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls)$/i)) {
        alert('Por favor selecciona un archivo Excel válido (.xlsx o .xls)');
        return;
    }

    // Validate file size (10MB max)
    if (file.size > 10 * 1024 * 1024) {
        alert('El archivo es demasiado grande. El tamaño máximo es 10MB.');
        return;
    }

    form.file = file;
};

const removeFile = () => {
    form.file = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    previewData.value = null;
    showPreviewModal.value = false;
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const formatNumber = (value) => {
    if (!value) return '0';
    return new Intl.NumberFormat('es-CL').format(value);
};

const submitPreview = async () => {
    if (!form.file) {
        alertWrapper.value?.showError('Error', 'Debe seleccionar un archivo para importar.');
        return;
    }

    form.processing = true;

    try {
        const formData = new FormData();
        formData.append('file', form.file);

        const response = await axios.post(route('admin.payments.presential.import-preview'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        if (response.data.success) {
            previewData.value = response.data;
            showPreviewModal.value = true;
        } else {
            alertWrapper.value?.showError('Error al previsualizar', response.data.error || 'Ocurrió un error al procesar el archivo.');
        }
    } catch (error) {
        console.error('Error al previsualizar:', error);
        const errorMessage = error.response?.data?.error || error.message || 'Ocurrió un error al procesar el archivo.';
        alertWrapper.value?.showError('Error al previsualizar', errorMessage);
    } finally {
        form.processing = false;
    }
};

const closePreview = () => {
    showPreviewModal.value = false;
};

const confirmImport = () => {
    isConfirming.value = true;
    form.post(route('admin.payments.presential.import-store'), {
        preserveScroll: true,
        onSuccess: () => {
            showPreviewModal.value = false;
            isConfirming.value = false;

            // Handle success - results will be in flash data
            if (page.props.flash.importResults) {
                results.value = page.props.flash.importResults;

                // Mostrar alerta según los resultados
                const { successful, failed, skipped } = results.value;

                if (failed === 0 && skipped === 0) {
                    // Todo exitoso
                    alertWrapper.value?.showSuccess(
                        'Importación completada',
                        `Se importaron ${successful} pagos exitosamente.`
                    );
                } else if (successful > 0) {
                    // Parcialmente exitoso
                    alertWrapper.value?.showWarning(
                        'Importación completada con observaciones',
                        `${successful} pagos importados, ${skipped} omitidos, ${failed} con errores.`
                    );
                } else {
                    // Todo falló
                    alertWrapper.value?.showError(
                        'Error en la importación',
                        `No se pudo importar ningún pago. ${skipped} omitidos, ${failed} con errores.`
                    );
                }
            } else {
                // Si no hay resultados pero fue exitoso
                alertWrapper.value?.showSuccess(
                    'Proceso completado',
                    'El archivo fue procesado correctamente.'
                );
            }
            form.reset();
            previewData.value = null;
        },
        onError: (errors) => {
            isConfirming.value = false;
            showPreviewModal.value = false;

            // Mostrar alerta de error
            const errorMessage = errors.file || 'Ocurrió un error al procesar el archivo.';
            alertWrapper.value?.showError(
                'Error al importar',
                errorMessage
            );
        }
    });
};
</script>

<style scoped>
.font-nexa-bold {
    font-family: 'Nexa-Bold', sans-serif;
    font-weight: 700;
}

.font-nexa-regular {
    font-family: 'Nexa-Regular', sans-serif;
    font-weight: 400;
}

.text-verde-oscuro {
    color: #1c4f4a;
}

.bg-turquesa {
    background-color: #007e93;
}

.hover\:bg-turquesa-dark:hover {
    background-color: #006477;
}

.text-turquesa {
    color: #007e93;
}

.hover\:text-turquesa-dark:hover {
    color: #006477;
}

.border-turquesa {
    border-color: #007e93;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
