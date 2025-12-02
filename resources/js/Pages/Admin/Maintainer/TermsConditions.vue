<template>
    <AdminLayout>
        <Head title="Términos y Condiciones" />

        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Términos y Condiciones</h1>
                    <p class="text-gray-600 mt-1">Gestiona los términos y condiciones del sitio</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="px-4 py-2 bg-turquesa text-white rounded-lg hover:bg-turquesa-dark transition-colors flex items-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Agregar Término
                </button>
            </div>

            <!-- Lista de términos -->
            <div class="bg-white rounded-lg shadow">
                <div v-if="localTerms.length === 0" class="p-12 text-center">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No hay términos registrados</h3>
                    <p class="text-gray-500 mb-4">Comienza agregando tu primer término y condición</p>
                    <button
                        @click="openCreateModal"
                        class="px-4 py-2 bg-turquesa text-white rounded-lg hover:bg-turquesa-dark transition-colors"
                    >
                        Agregar Término
                    </button>
                </div>

                <div v-else class="divide-y divide-gray-200">
                    <div class="px-6 py-3 bg-gray-50 text-sm font-medium text-gray-500 flex items-center">
                        <span class="w-12 text-center">#</span>
                        <span class="flex-1">Título</span>
                        <span class="w-24 text-center">Estado</span>
                        <span class="w-32 text-center">Acciones</span>
                    </div>

                    <draggable
                        v-model="localTerms"
                        item-key="id"
                        handle=".drag-handle"
                        @end="onDragEnd"
                        class="divide-y divide-gray-200"
                    >
                        <template #item="{ element, index }">
                            <div class="px-6 py-4 flex items-center hover:bg-gray-50 transition-colors">
                                <div class="w-12 flex items-center justify-center">
                                    <button class="drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="flex-1">
                                    <h3 class="font-medium text-gray-900">{{ index + 1 }}. {{ element.title }}</h3>
                                    <p class="text-sm text-gray-500 line-clamp-2 mt-1">{{ element.content }}</p>
                                </div>

                                <div class="w-24 text-center">
                                    <span
                                        :class="[
                                            'px-2 py-1 text-xs font-medium rounded-full',
                                            element.is_active
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-gray-100 text-gray-800'
                                        ]"
                                    >
                                        {{ element.is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>

                                <div class="w-32 flex items-center justify-center gap-2">
                                    <button
                                        @click="openEditModal(element)"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                        title="Editar"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="toggleStatus(element)"
                                        :class="[
                                            'p-2 rounded-lg transition-colors',
                                            element.is_active
                                                ? 'text-yellow-600 hover:bg-yellow-50'
                                                : 'text-green-600 hover:bg-green-50'
                                        ]"
                                        :title="element.is_active ? 'Desactivar' : 'Activar'"
                                    >
                                        <svg v-if="element.is_active" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                        </svg>
                                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="confirmDelete(element)"
                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Eliminar"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </draggable>
                </div>
            </div>
        </div>

        <!-- Modal Crear/Editar -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showFormModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="closeFormModal"></div>

                        <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full p-6 transform transition-all">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ editingTerm ? 'Editar Término' : 'Nuevo Término' }}
                                </h3>
                                <button @click="closeFormModal" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <form @submit.prevent="saveTerm">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                                        <input
                                            v-model="formData.title"
                                            type="text"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-turquesa focus:border-turquesa"
                                            placeholder="Ej: Aceptación de los Términos"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Contenido</label>
                                        <textarea
                                            v-model="formData.content"
                                            rows="6"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-turquesa focus:border-turquesa"
                                            placeholder="Describe el término o condición..."
                                            required
                                        ></textarea>
                                    </div>

                                    <div class="flex items-center">
                                        <input
                                            v-model="formData.is_active"
                                            type="checkbox"
                                            id="is_active"
                                            class="h-4 w-4 text-turquesa border-gray-300 rounded focus:ring-turquesa"
                                        />
                                        <label for="is_active" class="ml-2 text-sm text-gray-700">Activo</label>
                                    </div>
                                </div>

                                <div class="flex justify-end gap-3 mt-6">
                                    <button
                                        type="button"
                                        @click="closeFormModal"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
                                    >
                                        Cancelar
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="saving"
                                        class="px-4 py-2 text-sm font-medium text-white bg-turquesa hover:bg-turquesa-dark rounded-md transition-colors disabled:opacity-50"
                                    >
                                        {{ saving ? 'Guardando...' : (editingTerm ? 'Actualizar' : 'Crear') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Modal Confirmar Eliminación -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="showDeleteModal = false"></div>

                        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6 transform transition-all">
                            <div class="flex items-center mb-4">
                                <div class="flex-shrink-0 w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </div>
                                <h3 class="ml-3 text-lg font-semibold text-gray-900">Eliminar Término</h3>
                            </div>

                            <p class="text-gray-600 mb-6">
                                ¿Estás seguro de que deseas eliminar "<strong>{{ termToDelete?.title }}</strong>"? Esta acción no se puede deshacer.
                            </p>

                            <div class="flex justify-end space-x-3">
                                <button
                                    @click="showDeleteModal = false"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
                                >
                                    Cancelar
                                </button>
                                <button
                                    @click="deleteTerm"
                                    :disabled="deleting"
                                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors disabled:opacity-50"
                                >
                                    {{ deleting ? 'Eliminando...' : 'Eliminar' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Modal Resultado -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showResultModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="showResultModal = false"></div>

                        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6 transform transition-all">
                            <div class="flex items-center mb-4">
                                <div :class="[
                                    'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center',
                                    resultModal.success ? 'bg-green-100' : 'bg-red-100'
                                ]">
                                    <svg v-if="resultModal.success" class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <svg v-else class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </div>
                                <h3 class="ml-3 text-lg font-semibold text-gray-900">{{ resultModal.title }}</h3>
                            </div>

                            <p class="text-gray-600 mb-6">{{ resultModal.message }}</p>

                            <div class="flex justify-end">
                                <button
                                    @click="showResultModal = false"
                                    :class="[
                                        'px-4 py-2 text-sm font-medium text-white rounded-md transition-colors',
                                        resultModal.success ? 'bg-turquesa hover:bg-turquesa-dark' : 'bg-gray-600 hover:bg-gray-700'
                                    ]"
                                >
                                    Aceptar
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
import { Head } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import draggable from "vuedraggable";

export default {
    components: {
        Head,
        AdminLayout,
        draggable
    },
    props: {
        terms: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            localTerms: [...this.terms],
            showFormModal: false,
            showDeleteModal: false,
            showResultModal: false,
            editingTerm: null,
            termToDelete: null,
            saving: false,
            deleting: false,
            formData: {
                title: '',
                content: '',
                is_active: true
            },
            resultModal: {
                success: true,
                title: '',
                message: ''
            }
        };
    },
    methods: {
        openCreateModal() {
            this.editingTerm = null;
            this.formData = {
                title: '',
                content: '',
                is_active: true
            };
            this.showFormModal = true;
        },

        openEditModal(term) {
            this.editingTerm = term;
            this.formData = {
                title: term.title,
                content: term.content,
                is_active: term.is_active
            };
            this.showFormModal = true;
        },

        closeFormModal() {
            this.showFormModal = false;
            this.editingTerm = null;
        },

        async saveTerm() {
            this.saving = true;

            try {
                const url = this.editingTerm
                    ? `/admin/maintainer/terms-conditions/${this.editingTerm.id}`
                    : '/admin/maintainer/terms-conditions';

                const method = this.editingTerm ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.formData)
                });

                const result = await response.json();

                if (result.success) {
                    this.closeFormModal();
                    this.showResult(true, this.editingTerm ? 'Término Actualizado' : 'Término Creado', result.message);
                    this.$inertia.reload();
                } else {
                    this.showResult(false, 'Error', result.message || 'Error al guardar');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showResult(false, 'Error', 'Error al guardar el término');
            } finally {
                this.saving = false;
            }
        },

        confirmDelete(term) {
            this.termToDelete = term;
            this.showDeleteModal = true;
        },

        async deleteTerm() {
            this.deleting = true;

            try {
                const response = await fetch(`/admin/maintainer/terms-conditions/${this.termToDelete.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                this.showDeleteModal = false;

                if (result.success) {
                    this.showResult(true, 'Término Eliminado', result.message);
                    this.$inertia.reload();
                } else {
                    this.showResult(false, 'Error', result.message || 'Error al eliminar');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showResult(false, 'Error', 'Error al eliminar el término');
            } finally {
                this.deleting = false;
                this.termToDelete = null;
            }
        },

        async toggleStatus(term) {
            try {
                const response = await fetch(`/admin/maintainer/terms-conditions/${term.id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    term.is_active = result.term.is_active;
                    this.showResult(true, 'Estado Actualizado', result.message);
                } else {
                    this.showResult(false, 'Error', result.message || 'Error al cambiar estado');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showResult(false, 'Error', 'Error al cambiar el estado');
            }
        },

        async onDragEnd() {
            const order = this.localTerms.map(term => term.id);

            try {
                const response = await fetch('/admin/maintainer/terms-conditions/reorder', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ order })
                });

                const result = await response.json();

                if (!result.success) {
                    this.showResult(false, 'Error', 'Error al reordenar');
                    this.$inertia.reload();
                }
            } catch (error) {
                console.error('Error:', error);
                this.$inertia.reload();
            }
        },

        showResult(success, title, message) {
            this.resultModal = { success, title, message };
            this.showResultModal = true;
        }
    },
    watch: {
        terms: {
            handler(newTerms) {
                this.localTerms = [...newTerms];
            },
            deep: true
        }
    }
};
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.2s ease, opacity 0.2s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
    transform: scale(0.95);
    opacity: 0;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
