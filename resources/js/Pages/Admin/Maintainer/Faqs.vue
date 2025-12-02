<template>
    <AdminLayout>
        <Head title="Preguntas Frecuentes" />

        <div class="p-6">
            <div class="max-w-5xl mx-auto">
                <!-- Header -->
                <MaintainerHeader
                    subtitle="Administra las preguntas frecuentes que se muestran en el home"
                    :show-action-button="true"
                    action-text="Agregar Pregunta"
                    @action="openCreateModal"
                >
                    <template #title>
                        <div class="flex items-center gap-3">
                            <span>Preguntas Frecuentes</span>
                            <button
                                @click="openTitleModal"
                                class="text-sm text-gray-500 hover:text-turquesa transition-colors"
                                title="Editar título de la sección"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </MaintainerHeader>

                <!-- Título actual -->
                <div class="mb-6 p-4 bg-teal-50 border border-teal-200 rounded-lg">
                    <p class="text-sm text-teal-700">
                        <span class="font-medium">Título de la sección:</span> {{ titulo }}
                    </p>
                </div>

                <!-- Lista de FAQs con Drag & Drop -->
                <div v-if="localFaqs.length > 0" class="bg-white rounded-lg shadow-md border border-gray-200">
                    <draggable
                        v-model="localFaqs"
                        item-key="id"
                        handle=".drag-handle"
                        @end="handleReorder"
                        class="divide-y divide-gray-200"
                    >
                        <template #item="{ element, index }">
                            <div class="p-4 hover:bg-gray-50 transition-colors">
                                <div class="flex items-start gap-4">
                                    <!-- Drag Handle -->
                                    <div class="drag-handle cursor-move pt-1">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                        </svg>
                                    </div>

                                    <!-- Número -->
                                    <div class="flex-shrink-0 w-8 h-8 bg-turquesa text-white rounded-full flex items-center justify-center font-semibold text-sm">
                                        {{ index + 1 }}
                                    </div>

                                    <!-- Contenido -->
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-medium text-gray-900 mb-1">
                                            {{ element.question }}
                                        </h3>
                                        <p class="text-sm text-gray-600 line-clamp-2">
                                            {{ element.answer }}
                                        </p>
                                    </div>

                                    <!-- Acciones -->
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="openEditModal(element)"
                                            class="p-2 text-gray-400 hover:text-turquesa transition-colors"
                                            title="Editar"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="openDeleteModal(element)"
                                            class="p-2 text-gray-400 hover:text-red-500 transition-colors"
                                            title="Eliminar"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </draggable>
                </div>

                <!-- Estado vacío -->
                <div v-else class="bg-white rounded-lg shadow-md border border-gray-200 p-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No hay preguntas frecuentes</h3>
                    <p class="text-gray-500 mb-4">Comienza agregando la primera pregunta frecuente.</p>
                    <button
                        @click="openCreateModal"
                        class="bg-turquesa text-white px-4 py-2 rounded-md hover:bg-turquesa-dark transition-colors"
                    >
                        Agregar primera pregunta
                    </button>
                </div>

                <!-- Info Box -->
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Instrucciones</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>Arrastra las preguntas para reordenarlas. Los cambios de orden se guardan automaticamente.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Crear/Editar -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="closeModal"></div>

                        <div class="relative inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                            <div class="absolute top-0 right-0 pt-4 pr-4">
                                <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="sm:flex sm:items-start">
                                <div class="w-full mt-3 text-center sm:mt-0 sm:text-left">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                                        {{ editingFaq ? 'Editar Pregunta' : 'Nueva Pregunta' }}
                                    </h3>

                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Pregunta *
                                            </label>
                                            <input
                                                v-model="form.question"
                                                type="text"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-turquesa focus:border-turquesa"
                                                placeholder="Escribe la pregunta..."
                                            />
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Respuesta *
                                            </label>
                                            <textarea
                                                v-model="form.answer"
                                                rows="4"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-turquesa focus:border-turquesa"
                                                placeholder="Escribe la respuesta..."
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-3">
                                <button
                                    @click="saveForm"
                                    :disabled="!form.question || !form.answer || processing"
                                    class="w-full sm:w-auto px-4 py-2 bg-turquesa text-white rounded-md hover:bg-turquesa-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ processing ? 'Guardando...' : (editingFaq ? 'Actualizar' : 'Crear') }}
                                </button>
                                <button
                                    @click="closeModal"
                                    class="mt-3 sm:mt-0 w-full sm:w-auto px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Modal Eliminar -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="closeDeleteModal"></div>

                        <div class="relative inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                            <div class="sm:flex sm:items-start">
                                <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-red-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                                        Eliminar pregunta
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            Esta accion eliminara permanentemente la pregunta. Esta accion no se puede deshacer.
                                        </p>
                                        <p class="mt-2 text-sm font-medium text-gray-700" v-if="deletingFaq">
                                            "{{ deletingFaq.question }}"
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-3">
                                <button
                                    @click="confirmDelete"
                                    :disabled="processing"
                                    class="w-full sm:w-auto px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors disabled:opacity-50"
                                >
                                    {{ processing ? 'Eliminando...' : 'Eliminar' }}
                                </button>
                                <button
                                    @click="closeDeleteModal"
                                    class="mt-3 sm:mt-0 w-full sm:w-auto px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Modal Titulo -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showTitleModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="closeTitleModal"></div>

                        <div class="relative inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                            <div class="absolute top-0 right-0 pt-4 pr-4">
                                <button @click="closeTitleModal" class="text-gray-400 hover:text-gray-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="sm:flex sm:items-start">
                                <div class="w-full mt-3 text-center sm:mt-0 sm:text-left">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                                        Editar Titulo de la Seccion
                                    </h3>

                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Titulo
                                        </label>
                                        <input
                                            v-model="titleForm.titulo"
                                            type="text"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-turquesa focus:border-turquesa"
                                            placeholder="Titulo de la seccion..."
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-3">
                                <button
                                    @click="saveTitle"
                                    :disabled="!titleForm.titulo || processing"
                                    class="w-full sm:w-auto px-4 py-2 bg-turquesa text-white rounded-md hover:bg-turquesa-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ processing ? 'Guardando...' : 'Guardar' }}
                                </button>
                                <button
                                    @click="closeTitleModal"
                                    class="mt-3 sm:mt-0 w-full sm:w-auto px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>

<script>
import { Head, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import MaintainerHeader from "@/Components/Maintainer/MaintainerHeader.vue";
import draggable from "vuedraggable";

export default {
    name: "MaintainerFaqs",
    components: {
        Head,
        AdminLayout,
        MaintainerHeader,
        draggable,
    },
    props: {
        faqs: {
            type: Array,
            default: () => []
        },
        titulo: {
            type: String,
            default: 'Preguntas frecuentes'
        }
    },
    data() {
        return {
            localFaqs: [...this.faqs],
            showModal: false,
            showDeleteModal: false,
            showTitleModal: false,
            editingFaq: null,
            deletingFaq: null,
            processing: false,
            form: {
                question: '',
                answer: ''
            },
            titleForm: {
                titulo: this.titulo
            }
        };
    },
    watch: {
        faqs: {
            handler(newFaqs) {
                this.localFaqs = [...newFaqs];
            },
            deep: true
        }
    },
    methods: {
        openCreateModal() {
            this.editingFaq = null;
            this.form = { question: '', answer: '' };
            this.showModal = true;
        },
        openEditModal(faq) {
            this.editingFaq = faq;
            this.form = {
                question: faq.question,
                answer: faq.answer
            };
            this.showModal = true;
        },
        closeModal() {
            this.showModal = false;
            this.editingFaq = null;
            this.form = { question: '', answer: '' };
        },
        openDeleteModal(faq) {
            this.deletingFaq = faq;
            this.showDeleteModal = true;
        },
        closeDeleteModal() {
            this.showDeleteModal = false;
            this.deletingFaq = null;
        },
        openTitleModal() {
            this.titleForm.titulo = this.titulo;
            this.showTitleModal = true;
        },
        closeTitleModal() {
            this.showTitleModal = false;
        },
        saveForm() {
            this.processing = true;

            if (this.editingFaq) {
                router.put(route('admin.maintainer.faqs.update', this.editingFaq.id), this.form, {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.closeModal();
                    },
                    onFinish: () => {
                        this.processing = false;
                    }
                });
            } else {
                router.post(route('admin.maintainer.faqs.store'), this.form, {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.closeModal();
                    },
                    onFinish: () => {
                        this.processing = false;
                    }
                });
            }
        },
        confirmDelete() {
            if (!this.deletingFaq) return;

            this.processing = true;
            router.delete(route('admin.maintainer.faqs.destroy', this.deletingFaq.id), {
                preserveScroll: true,
                onSuccess: () => {
                    this.closeDeleteModal();
                },
                onFinish: () => {
                    this.processing = false;
                }
            });
        },
        saveTitle() {
            this.processing = true;
            router.post(route('admin.maintainer.faqs.update-title'), this.titleForm, {
                preserveScroll: true,
                onSuccess: () => {
                    this.closeTitleModal();
                },
                onFinish: () => {
                    this.processing = false;
                }
            });
        },
        handleReorder() {
            const order = this.localFaqs.map(faq => faq.id);

            fetch(route('admin.maintainer.faqs.reorder'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ order })
            });
        }
    }
};
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Transiciones del modal */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.3s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
    transform: scale(0.95);
}
</style>
