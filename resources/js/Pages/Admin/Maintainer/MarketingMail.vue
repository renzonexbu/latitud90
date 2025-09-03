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
                    alert(result.message);
                    // Refresh the page to get updated data
                    this.$inertia.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Error al cambiar el estado del email');
            }
        },

        async handleDeleteEmail(emailId) {
            if (!confirm('¿Estás seguro de que quieres eliminar este email?')) {
                return;
            }
            
            try {
                const response = await fetch(`/admin/maintainer/marketing-mails/${emailId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(result.message);
                    // Refresh the page to get updated data
                    this.$inertia.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Error al eliminar el email');
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
        }
    }
};
</script>

<style scoped>
/* Custom styles if needed */
</style>
