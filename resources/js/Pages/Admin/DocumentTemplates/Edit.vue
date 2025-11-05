<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import AlertWrapper from "@/Components/Admin/AlertWrapper.vue";
import WysiwygEditor from "@/Components/Admin/WysiwygEditor.vue";

const props = defineProps({
    template: Object,
    types: Object,
    exampleData: Object,
});

const form = useForm({
    name: props.template.name,
    content: props.template.content,
    notes: '',
    create_new_version: true,
});

const showVariables = ref(false);
const editorRef = ref(null);

const handleSubmit = () => {
    form.put(route("admin.document-templates.update", props.template.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Reset notes after success
            form.notes = '';
        },
    });
};

const handleCancel = () => {
    router.get(route("admin.document-templates.index"));
};

const insertVariable = (variable) => {
    if (editorRef.value) {
        editorRef.value.insertVariable(variable);
    }
};

const getTypeName = (type) => {
    return props.types[type] || type;
};

const handlePreview = () => {
    // Abrir preview en nueva ventana con contenido actual
    const previewForm = document.createElement('form');
    previewForm.method = 'POST';
    previewForm.action = route('admin.document-templates.preview-draft', props.template.id);
    previewForm.target = '_blank';

    // CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
    previewForm.appendChild(csrfInput);

    // Contenido
    const contentInput = document.createElement('input');
    contentInput.type = 'hidden';
    contentInput.name = 'content';
    contentInput.value = form.content;
    previewForm.appendChild(contentInput);

    document.body.appendChild(previewForm);
    previewForm.submit();
    document.body.removeChild(previewForm);
};
</script>

<template>
    <AdminLayout>
        <Head :title="`Editar Plantilla: ${template.name}`" />

        <AlertWrapper />

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">
                    Editar Plantilla
                </h1>
                <p class="mt-1 text-sm text-gray-600">
                    {{ getTypeName(template.type) }} - Versión {{ template.version }}
                </p>
            </div>

            <form @submit.prevent="handleSubmit">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-3 space-y-6">
                        <!-- Name Field -->
                        <div class="bg-white shadow-sm rounded-lg p-6">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nombre de la Plantilla
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                required
                            />
                            <div v-if="form.errors.name" class="mt-2 text-sm text-red-600">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <!-- Content Editor -->
                        <div class="bg-white shadow-sm rounded-lg p-6">
                            <div class="flex justify-between items-center mb-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    Contenido del Documento
                                </label>
                                <button
                                    type="button"
                                    @click="showVariables = !showVariables"
                                    class="text-sm text-indigo-600 hover:text-indigo-900"
                                >
                                    {{ showVariables ? 'Ocultar' : 'Mostrar' }} Variables
                                </button>
                            </div>

                            <div v-if="showVariables" class="mb-4 p-4 bg-gray-50 rounded-md">
                                <p class="text-sm font-medium text-gray-700 mb-3">Variables Disponibles (click para insertar):</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div
                                        v-for="(description, variable) in template.variables"
                                        :key="variable"
                                        class="bg-white p-3 rounded border border-gray-200"
                                    >
                                        <button
                                            type="button"
                                            @click="insertVariable(variable)"
                                            class="w-full text-left"
                                        >
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <code class="text-xs font-medium text-indigo-600 bg-indigo-50 px-2 py-1 rounded" v-text="`{{${variable}}}`"></code>
                                                    <p class="text-xs text-gray-600 mt-1">{{ description }}</p>
                                                    <p v-if="exampleData && exampleData[variable]" class="text-xs text-green-600 mt-1">
                                                        Ejemplo: <span class="font-medium">{{ exampleData[variable] }}</span>
                                                    </p>
                                                </div>
                                                <svg class="w-4 h-4 text-gray-400 ml-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <WysiwygEditor
                                ref="editorRef"
                                v-model="form.content"
                                :variables="template.variables"
                            />

                            <div v-if="form.errors.content" class="mt-2 text-sm text-red-600">
                                {{ form.errors.content }}
                            </div>
                            <p class="mt-2 text-sm text-gray-500">
                                Utiliza la barra de herramientas para formatear el texto. Click en las variables de arriba para insertarlas en el documento.
                            </p>
                        </div>

                        <!-- Notes Field -->
                        <div class="bg-white shadow-sm rounded-lg p-6">
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                Notas sobre los cambios
                            </label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Describe los cambios realizados..."
                            ></textarea>
                            <p class="mt-2 text-sm text-gray-500">
                                Estas notas ayudan a identificar los cambios en el historial de versiones.
                            </p>
                        </div>

                        <!-- Version Option -->
                        <div class="bg-white shadow-sm rounded-lg p-6">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input
                                        id="create-new-version"
                                        v-model="form.create_new_version"
                                        type="checkbox"
                                        class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
                                    />
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="create-new-version" class="font-medium text-gray-700">
                                        Crear nueva versión
                                    </label>
                                    <p class="text-gray-500">
                                        Si está activado, se creará una nueva versión en lugar de sobrescribir la actual. Recomendado para cambios importantes.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-between items-center">
                            <button
                                type="button"
                                @click="handlePreview"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
                            >
                                <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Previsualizar PDF
                            </button>

                            <div class="flex space-x-3">
                                <button
                                    type="button"
                                    @click="handleCancel"
                                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                                >
                                    {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="lg:col-span-1">
                        <div class="bg-white shadow-sm rounded-lg p-6 sticky top-6">
                            <h3 class="text-sm font-medium text-gray-900 mb-4">
                                Información
                            </h3>

                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">Tipo</dt>
                                    <dd class="mt-1">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ getTypeName(template.type) }}
                                        </span>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-xs font-medium text-gray-500">Versión Actual</dt>
                                    <dd class="mt-1 text-sm text-gray-900">v{{ template.version }}</dd>
                                </div>

                                <div>
                                    <dt class="text-xs font-medium text-gray-500">Estado</dt>
                                    <dd class="mt-1">
                                        <span :class="template.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                            {{ template.is_active ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-xs font-medium text-gray-500">Creado</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ new Date(template.created_at).toLocaleDateString() }}
                                    </dd>
                                </div>

                                <div v-if="template.creator">
                                    <dt class="text-xs font-medium text-gray-500">Creado por</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ template.creator.name }}
                                    </dd>
                                </div>
                            </dl>

                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <h4 class="text-xs font-medium text-gray-900 mb-2">Ayuda</h4>
                                <ul class="text-xs text-gray-600 space-y-2">
                                    <li>• Usa la barra de herramientas para formatear el texto</li>
                                    <li>• Click en "Mostrar Variables" para ver todas las variables disponibles</li>
                                    <li>• Cada variable muestra un ejemplo de cómo se verá</li>
                                    <li>• Click en "Previsualizar PDF" para ver cómo quedará el documento</li>
                                    <li>• Los cambios se guardan como nueva versión por defecto</li>
                                    <li>• Puedes deshacer/rehacer con los botones del editor</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
