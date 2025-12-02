<template>
    <AdminLayout>
        <Head title="Marketing Mails" />
        
        <div class="p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <MaintainerHeader 
                    subtitle="Gestión de emails para marketing"
                    :show-back-button="true"
                />

                <!-- Action Buttons -->
                <div class="mb-6 flex justify-end">
                    <button
                        @click="handleSync"
                        :disabled="syncing"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-turquesa hover:bg-turquesa-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-turquesa disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg v-if="!syncing" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <svg v-else class="animate-spin w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ syncing ? 'Sincronizando...' : 'Sincronizar Emails' }}
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Emails</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ stats.total }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Emails Activos</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ stats.active }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Emails Inactivos</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ stats.inactive }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="mb-6">
                    <MarketingMailFilters 
                        :initial-filters="filters"
                        @filters-changed="handleFiltersChanged"
                        @export="handleExport"
                    />
                </div>

                <!-- Table -->
                <div class="mb-6">
                    <MarketingMailTable 
                        :marketing-mails="marketingMails.data"
                        @toggle-status="handleToggleStatus"
                        @delete-email="handleDeleteEmail"
                    />
                </div>

                <!-- Pagination -->
                <div class="flex justify-end">
                    <MarketingMailPagination 
                        :current-page="marketingMails.current_page"
                        :total-emails="marketingMails.total"
                        :emails-per-page="parseInt(filters.per_page)"
                        @page-changed="handlePageChanged"
                    />
                </div>
            </div>
        </div>

        <!-- Modal de Confirmación de Sincronización -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showSyncModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <!-- Overlay -->
                        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="showSyncModal = false"></div>

                        <!-- Modal -->
                        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6 transform transition-all">
                            <div class="flex items-center mb-4">
                                <div class="flex-shrink-0 w-10 h-10 bg-turquesa/10 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-turquesa" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </div>
                                <h3 class="ml-3 text-lg font-semibold text-gray-900">Sincronizar Emails</h3>
                            </div>

                            <p class="text-gray-600 mb-6">
                                ¿Deseas importar los emails de compradores que aceptaron recibir comunicaciones de marketing?
                            </p>

                            <div class="flex justify-end space-x-3">
                                <button
                                    @click="showSyncModal = false"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
                                >
                                    Cancelar
                                </button>
                                <button
                                    @click="confirmSync"
                                    class="px-4 py-2 text-sm font-medium text-white bg-turquesa hover:bg-turquesa-dark rounded-md transition-colors"
                                >
                                    Sincronizar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Modal de Confirmación de Eliminación -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <!-- Overlay -->
                        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="showDeleteModal = false"></div>

                        <!-- Modal -->
                        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6 transform transition-all">
                            <div class="flex items-center mb-4">
                                <div class="flex-shrink-0 w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </div>
                                <h3 class="ml-3 text-lg font-semibold text-gray-900">Eliminar Email</h3>
                            </div>

                            <p class="text-gray-600 mb-6">
                                ¿Estás seguro de que deseas eliminar este email de la lista de marketing? Esta acción no se puede deshacer.
                            </p>

                            <div class="flex justify-end space-x-3">
                                <button
                                    @click="showDeleteModal = false"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
                                >
                                    Cancelar
                                </button>
                                <button
                                    @click="confirmDelete"
                                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors"
                                >
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Modal de Resultado -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showResultModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <!-- Overlay -->
                        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="showResultModal = false"></div>

                        <!-- Modal -->
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

                            <div class="text-gray-600 mb-6 whitespace-pre-line">{{ resultModal.message }}</div>

                            <div class="flex justify-end">
                                <button
                                    @click="closeResultModal"
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
import MaintainerHeader from "@/Components/Maintainer/MaintainerHeader.vue";
import MarketingMailTable from "@/Components/Maintainer/MarketingMailTable.vue";
import MarketingMailFilters from "@/Components/Maintainer/MarketingMailFilters.vue";
import MarketingMailPagination from "@/Components/Maintainer/MarketingMailPagination.vue";

export default {
    name: "MarketingMailIndex",
    components: {
        Head,
        AdminLayout,
        MaintainerHeader,
        MarketingMailTable,
        MarketingMailFilters,
        MarketingMailPagination,
    },
    props: {
        marketingMails: {
            type: Object,
            default: () => ({
                data: [],
                current_page: 1,
                total: 0,
                per_page: 15
            })
        },
        filters: {
            type: Object,
            default: () => ({})
        },
        stats: {
            type: Object,
            default: () => ({
                total: 0,
                active: 0,
                inactive: 0
            })
        }
    },
    data() {
        return {
            syncing: false,
            showSyncModal: false,
            showDeleteModal: false,
            showResultModal: false,
            deleteEmailId: null,
            resultModal: {
                success: true,
                title: '',
                message: ''
            }
        };
    },
    methods: {
        handleFiltersChanged(newFilters) {
            this.$inertia.get(
                route('admin.maintainer.marketing.mails.index'),
                newFilters,
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                }
            );
        },

        handlePageChanged(page) {
            this.$inertia.get(
                route('admin.maintainer.marketing.mails.index'),
                { ...this.filters, page },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                }
            );
        },

        async handleToggleStatus(emailId) {
            try {
                const response = await fetch(`/admin/maintainer/marketing-mails/${emailId}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    this.showResult(true, 'Estado Actualizado', result.message);
                    this.$inertia.reload();
                } else {
                    this.showResult(false, 'Error', result.message);
                }
            } catch (error) {
                this.showResult(false, 'Error', 'Error al cambiar el estado del email');
            }
        },

        handleDeleteEmail(emailId) {
            this.deleteEmailId = emailId;
            this.showDeleteModal = true;
        },

        async confirmDelete() {
            this.showDeleteModal = false;

            try {
                const response = await fetch(`/admin/maintainer/marketing-mails/${this.deleteEmailId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    this.showResult(true, 'Email Eliminado', result.message);
                    this.$inertia.reload();
                } else {
                    this.showResult(false, 'Error', result.message);
                }
            } catch (error) {
                this.showResult(false, 'Error', 'Error al eliminar el email');
            } finally {
                this.deleteEmailId = null;
            }
        },

        handleExport() {
            // Crear un formulario temporal para enviar POST con los filtros
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = route('admin.maintainer.marketing.mails.export');

            // Agregar CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);

            // Agregar filtros como campos ocultos
            Object.keys(this.filters).forEach(key => {
                if (this.filters[key]) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = this.filters[key];
                    form.appendChild(input);
                }
            });

            // Agregar el formulario al DOM, enviarlo y removerlo
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        },

        handleSync() {
            if (this.syncing) return;
            this.showSyncModal = true;
        },

        async confirmSync() {
            this.showSyncModal = false;
            this.syncing = true;

            try {
                const response = await fetch(route('admin.maintainer.marketing.mails.sync'), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    const message = `Total procesados: ${result.results.total_processed}\n` +
                        `Nuevos emails agregados: ${result.results.new_emails_added}\n` +
                        `Emails actualizados: ${result.results.existing_emails_updated}`;

                    this.showResult(true, 'Sincronización Completada', message);
                    this.$inertia.reload();
                } else {
                    this.showResult(false, 'Error', result.message);
                }
            } catch (error) {
                console.error('Error al sincronizar:', error);
                this.showResult(false, 'Error', 'Error al sincronizar emails');
            } finally {
                this.syncing = false;
            }
        },

        showResult(success, title, message) {
            this.resultModal = { success, title, message };
            this.showResultModal = true;
        },

        closeResultModal() {
            this.showResultModal = false;
        }
    }
};
</script>

<style scoped>
/* Modal transitions */
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
</style>
