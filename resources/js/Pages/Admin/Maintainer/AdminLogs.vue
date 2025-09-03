<template>
    <AdminLayout>
        <Head title="Logs del Sistema" />
        
        <div class="p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <MaintainerHeader 
                    subtitle="Registro de actividades del sistema"
                    :show-back-button="true"
                />

                <!-- Filters -->
                <div class="mb-6">
                    <AdminLogsFilters 
                        :initial-filters="filters"
                        :admin-logs="adminLogs"
                        @filters-changed="handleFiltersChanged"
                        @export="exportLogs"
                    />
                </div>

                <!-- Table -->
                <div class="mb-6">
                    <AdminLogsTable 
                        :admin-logs="adminLogs"
                        @view-details="handleViewDetails"
                    />
                </div>

                <!-- Pagination -->
                <div class="flex justify-end">
                    <AdminLogsPagination 
                        :current-page="currentPage"
                        :total-logs="totalLogs"
                        :logs-per-page="logsPerPage"
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
import AdminLogsTable from "@/Components/Maintainer/AdminLogsTable.vue";
import AdminLogsFilters from "@/Components/Maintainer/AdminLogsFilters.vue";
import AdminLogsPagination from "@/Components/Maintainer/AdminLogsPagination.vue";

export default {
    name: "AdminLogsIndex",
    components: {
        Head,
        AdminLayout,
        MaintainerHeader,
        AdminLogsTable,
        AdminLogsFilters,
        AdminLogsPagination,
    },
    props: {
        adminLogs: {
            type: Array,
            default: () => []
        },
        filters: {
            type: Object,
            default: () => ({})
        },
        currentPage: {
            type: Number,
            default: 1
        },
        totalLogs: {
            type: Number,
            default: 0
        },
        logsPerPage: {
            type: Number,
            default: 15
        }
    },
    methods: {
        handleFiltersChanged(newFilters) {
            this.$inertia.get(
                route('admin.maintainer.admin-logs.index'),
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
                route('admin.maintainer.admin-logs.index'),
                { ...this.filters, page },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                }
            );
        },

        handleViewDetails(logId) {
            this.$inertia.get(route('admin.maintainer.admin-logs.show', logId));
        },

        exportLogs() {
            // Crear un formulario temporal para enviar POST con los filtros
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = route('admin.maintainer.admin-logs.export');
            
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
