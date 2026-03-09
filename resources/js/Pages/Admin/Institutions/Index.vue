<template>
    <AdminLayout>
        <Head title="Gestión de Instituciones" />

        <div class="py-6 lg:py-12">
            <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-3xl font-nexa-bold text-verde-oscuro">Instituciones</h1>
                        <p class="text-gray-600 mt-2 font-nexa-regular">Gestión de instituciones educativas</p>
                    </div>
                    <div class="flex gap-3">
                        <Link
                            :href="route('admin.courses.index')"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-nexa-bold transition-colors flex items-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Volver a Cursos
                        </Link>
                        <a
                            :href="route('admin.institutions.export')"
                            class="px-4 py-2 border border-green-600 text-green-600 rounded-lg hover:bg-green-600 hover:text-white font-nexa-bold transition-colors flex items-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Exportar
                        </a>
                        <button
                            @click="showImportModal = true"
                            class="px-4 py-2 border border-turquesa text-turquesa rounded-lg hover:bg-turquesa hover:text-white font-nexa-bold transition-colors flex items-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                            </svg>
                            Carga Masiva
                        </button>
                        <Link
                            :href="route('admin.institutions.create')"
                            class="bg-turquesa hover:bg-turquesa-dark text-white px-6 py-2 rounded-lg font-nexa-bold transition-colors flex items-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Nueva Institución
                        </Link>
                    </div>
                </div>

                <!-- Success Message -->
                <div v-if="successMessage" class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center gap-2 text-green-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ successMessage }}
                    </div>
                </div>

                <div v-if="$page.props.flash.success" class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center gap-2 text-green-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ $page.props.flash.success }}
                    </div>
                </div>

                <!-- Error Message -->
                <div v-if="errorMessage" class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center gap-2 text-red-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        {{ errorMessage }}
                    </div>
                </div>

                <div v-if="$page.props.flash.error" class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center gap-2 text-red-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        {{ $page.props.flash.error }}
                    </div>
                </div>

                <!-- Search Filter -->
                <div class="mb-4">
                    <div class="relative w-full max-w-md">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Buscar por nombre, razón social o RUT..."
                            class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-[50px] focus:ring-2 focus:ring-turquesa focus:border-transparent font-nexa-regular text-sm"
                        />
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Institutions Table -->
                <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-turquesa">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs font-nexa-bold text-white uppercase tracking-wider">
                                    Código
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-nexa-bold text-white uppercase tracking-wider">
                                    Nombre Fantasía
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-nexa-bold text-white uppercase tracking-wider">
                                    Razón Social
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-nexa-bold text-white uppercase tracking-wider">
                                    RUT
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-nexa-bold text-white uppercase tracking-wider">
                                    Tipo
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-nexa-bold text-white uppercase tracking-wider">
                                    Email
                                </th>
                                <th class="px-3 py-3 text-center text-xs font-nexa-bold text-white uppercase tracking-wider">
                                    Cursos
                                </th>
                                <th class="px-3 py-3 text-center text-xs font-nexa-bold text-white uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="filteredInstitutions.length === 0">
                                <td colspan="8" class="px-3 py-12 text-center text-gray-500 font-nexa-regular">
                                    {{ searchQuery ? 'No se encontraron instituciones con esa búsqueda' : 'No hay instituciones registradas' }}
                                </td>
                            </tr>
                            <tr v-for="institution in filteredInstitutions" :key="institution.id" class="hover:bg-gray-50">
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-nexa-regular">{{ institution.code || '-' }}</div>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="text-sm font-nexa-bold text-verde-oscuro">{{ institution.name }}</div>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-nexa-regular">{{ institution.razon_social || '-' }}</div>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-nexa-regular">{{ institution.rut || '-' }}</div>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-nexa-regular">{{ institution.type || '-' }}</div>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-nexa-regular">{{ institution.email || '-' }}</div>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-center">
                                    <span class="px-2 inline-flex text-xs leading-5 font-nexa-bold rounded-full bg-blue-100 text-blue-800">
                                        {{ institution.courses_count }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex justify-center gap-2">
                                        <Link
                                            :href="route('admin.institutions.edit', institution.id)"
                                            class="text-turquesa hover:text-turquesa-dark"
                                            title="Editar"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </Link>
                                        <button
                                            @click="confirmDelete(institution)"
                                            class="text-red-600 hover:text-red-900"
                                            title="Eliminar"
                                            :disabled="institution.courses_count > 0"
                                            :class="{ 'opacity-50 cursor-not-allowed': institution.courses_count > 0 }"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Import Modal -->
        <div v-if="showImportModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div @click.stop class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-4 border-b">
                    <h3 class="text-lg font-nexa-bold text-verde-oscuro">Carga Masiva de Instituciones</h3>
                    <button @click="closeImportModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-4">
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 font-nexa-regular mb-2">
                            Sube un archivo Excel (.xlsx, .xls) o CSV con las siguientes columnas:
                        </p>
                        <ul class="text-xs text-gray-500 font-nexa-regular list-disc ml-4 mb-4">
                            <li><strong>Nombre</strong> (requerido)</li>
                            <li>Código (opcional)</li>
                            <li>Tipo (opcional): school, colegio, liceo, university, other</li>
                            <li>Email (opcional)</li>
                            <li>Teléfono (opcional)</li>
                            <li>Dirección (opcional)</li>
                            <li>Sitio web (opcional)</li>
                        </ul>
                        <p class="text-xs text-gray-500 font-nexa-regular italic">
                            Si el nombre o código ya existe, se actualizará la institución existente.
                        </p>
                    </div>

                    <!-- File Input -->
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-turquesa transition-colors">
                        <input
                            type="file"
                            ref="fileInput"
                            @change="handleFileSelect"
                            accept=".xlsx,.xls,.csv"
                            class="hidden"
                        />
                        <div v-if="!selectedFile" @click="$refs.fileInput.click()" class="cursor-pointer">
                            <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <p class="text-sm text-gray-600 font-nexa-regular">
                                Haz clic para seleccionar un archivo
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                Excel (.xlsx, .xls) o CSV
                            </p>
                        </div>
                        <div v-else class="flex items-center justify-center gap-3">
                            <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <div class="text-left">
                                <p class="text-sm font-nexa-bold text-gray-700">{{ selectedFile.name }}</p>
                                <p class="text-xs text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
                            </div>
                            <button @click.stop="removeFile" class="text-red-500 hover:text-red-700 ml-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Import Results -->
                    <div v-if="importResult" class="mt-4 p-3 rounded-lg" :class="importResult.success ? 'bg-green-50' : 'bg-red-50'">
                        <p class="text-sm font-nexa-bold" :class="importResult.success ? 'text-green-800' : 'text-red-800'">
                            {{ importResult.message }}
                        </p>
                        <div v-if="importResult.stats" class="mt-2 text-xs text-gray-600">
                            <span class="inline-block mr-3">Creadas: <strong class="text-green-600">{{ importResult.stats.created }}</strong></span>
                            <span class="inline-block mr-3">Actualizadas: <strong class="text-blue-600">{{ importResult.stats.updated }}</strong></span>
                            <span class="inline-block mr-3">Omitidas: <strong class="text-yellow-600">{{ importResult.stats.skipped }}</strong></span>
                            <span class="inline-block">Errores: <strong class="text-red-600">{{ importResult.stats.failed }}</strong></span>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end gap-3 p-4 border-t">
                    <button
                        @click="closeImportModal"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-nexa-bold transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="uploadFile"
                        :disabled="!selectedFile || isUploading"
                        class="px-4 py-2 bg-turquesa text-white rounded-lg hover:bg-turquesa-dark font-nexa-bold transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    >
                        <svg v-if="isUploading" class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ isUploading ? 'Importando...' : 'Importar' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const props = defineProps({
    institutions: Array
});

const searchQuery = ref('');

const filteredInstitutions = computed(() => {
    if (!searchQuery.value) return props.institutions;
    const query = searchQuery.value.toLowerCase().trim();
    return props.institutions.filter(inst => {
        return (inst.name && inst.name.toLowerCase().includes(query))
            || (inst.razon_social && inst.razon_social.toLowerCase().includes(query))
            || (inst.rut && inst.rut.toLowerCase().includes(query));
    });
});

const showImportModal = ref(false);
const selectedFile = ref(null);
const isUploading = ref(false);
const importResult = ref(null);
const successMessage = ref(null);
const errorMessage = ref(null);
const fileInput = ref(null);

const confirmDelete = (institution) => {
    if (institution.courses_count > 0) {
        alert('No se puede eliminar esta institución porque tiene cursos asociados.');
        return;
    }

    if (confirm(`¿Estás seguro de eliminar la institución "${institution.name}"?`)) {
        router.delete(route('admin.institutions.destroy', institution.id), {
            preserveScroll: true
        });
    }
};

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        selectedFile.value = file;
        importResult.value = null;
    }
};

const removeFile = () => {
    selectedFile.value = null;
    importResult.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const uploadFile = async () => {
    if (!selectedFile.value) return;

    isUploading.value = true;
    importResult.value = null;

    const formData = new FormData();
    formData.append('file', selectedFile.value);

    try {
        const response = await axios.post(route('admin.institutions.import'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        importResult.value = response.data;

        if (response.data.success) {
            successMessage.value = response.data.message;
            setTimeout(() => {
                router.reload({ only: ['institutions'] });
            }, 1500);
        }
    } catch (error) {
        console.error('Error uploading file:', error);
        importResult.value = {
            success: false,
            message: error.response?.data?.message || 'Error al importar el archivo'
        };
        errorMessage.value = importResult.value.message;
    } finally {
        isUploading.value = false;
    }
};

const closeImportModal = () => {
    showImportModal.value = false;
    selectedFile.value = null;
    importResult.value = null;
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

.hover\:border-turquesa:hover {
    border-color: #007e93;
}
</style>
