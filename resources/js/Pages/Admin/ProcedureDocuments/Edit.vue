<template>
    <AdminLayout>
        <Head title="Editar Documento Procedimiento" />

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-3xl font-nexa-bold text-gray-900">Editar Documento Procedimiento</h1>
                    <p class="mt-2 text-sm text-gray-600">Modifica los datos del documento o reemplaza el archivo</p>
                </div>

                <!-- Form -->
                <div class="bg-white rounded-lg shadow p-6">
                    <form @submit.prevent="submit">
                        <!-- Título -->
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                Título *
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                placeholder="Ingresa el título del documento"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                :class="{ 'border-red-500': form.errors.title }"
                            />
                            <div v-if="form.errors.title" class="text-red-500 text-sm mt-1">
                                {{ form.errors.title }}
                            </div>
                        </div>

                        <!-- Descripción -->
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                Descripción
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="4"
                                placeholder="Describe el contenido del documento (opcional)"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                :class="{ 'border-red-500': form.errors.description }"
                            ></textarea>
                            <div v-if="form.errors.description" class="text-red-500 text-sm mt-1">
                                {{ form.errors.description }}
                            </div>
                        </div>

                        <!-- Estado -->
                        <div class="mb-6">
                            <label class="flex items-center">
                                <input
                                    v-model="form.active"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-[#007e93] shadow-sm focus:border-[#007e93] focus:ring focus:ring-[#007e93] focus:ring-opacity-50"
                                />
                                <span class="ml-2 text-sm text-gray-700">Documento activo</span>
                            </label>
                        </div>

                        <!-- Archivo Actual -->
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                Archivo Actual
                            </label>
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <svg class="h-8 w-8 text-[#007e93]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ document.file_name }}</p>
                                        <p class="text-xs text-gray-500">{{ document.file_type.toUpperCase() }} - {{ document.formatted_file_size }}</p>
                                    </div>
                                </div>
                                <a
                                    :href="route('admin.procedure-documents.download', document.id)"
                                    class="text-[#007e93] hover:text-[#006580] text-sm font-medium"
                                >
                                    Descargar
                                </a>
                            </div>
                        </div>

                        <!-- Nuevo Archivo (Opcional) -->
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                Reemplazar Archivo (Opcional)
                            </label>
                            <div
                                class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-[#007e93] transition-colors"
                                @click="$refs.fileInput.click()"
                                @dragover.prevent="isDragging = true"
                                @dragleave="isDragging = false"
                                @drop.prevent="handleDrop"
                                :class="{ 'border-[#007e93] bg-blue-50': isDragging }"
                            >
                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept=".pdf,.doc,.docx"
                                    @change="handleFileChange"
                                    class="hidden"
                                />
                                <div v-if="!form.file">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-600">
                                        Haz clic para seleccionar o arrastra un archivo aquí
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        PDF, DOC, DOCX (máx. 10MB)
                                    </p>
                                </div>
                                <div v-else class="flex items-center justify-center gap-3">
                                    <svg class="h-8 w-8 text-[#007e93]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <div class="text-left">
                                        <p class="text-sm font-medium text-gray-900">{{ form.file.name }}</p>
                                        <p class="text-xs text-gray-500">{{ formatFileSize(form.file.size) }}</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click.stop="removeFile"
                                        class="text-red-600 hover:text-red-800"
                                    >
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div v-if="form.errors.file" class="text-red-500 text-sm mt-1">
                                {{ form.errors.file }}
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end gap-3">
                            <Link
                                :href="route('admin.procedure-documents.index')"
                                class="px-6 py-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors"
                            >
                                Cancelar
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-3 bg-[#007e93] text-white rounded-lg hover:bg-[#006580] transition-colors font-nexa-bold disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="form.processing">Guardando...</span>
                                <span v-else">Guardar Cambios</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    document: Object
});

const fileInput = ref(null);
const isDragging = ref(false);

const form = useForm({
    title: props.document.title,
    description: props.document.description,
    active: props.document.active,
    file: null,
    _method: 'PUT'
});

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.file = file;
    }
};

const handleDrop = (e) => {
    isDragging.value = false;
    const file = e.dataTransfer.files[0];
    if (file && ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'].includes(file.type)) {
        form.file = file;
    }
};

const removeFile = () => {
    form.file = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const formatFileSize = (bytes) => {
    if (bytes >= 1048576) {
        return (bytes / 1048576).toFixed(2) + ' MB';
    } else if (bytes >= 1024) {
        return (bytes / 1024).toFixed(2) + ' KB';
    }
    return bytes + ' bytes';
};

const submit = () => {
    form.post(route('admin.procedure-documents.update', props.document.id));
};
</script>

<style scoped>
.font-nexa-bold {
    font-family: "Nexa-Bold", sans-serif;
}
</style>
