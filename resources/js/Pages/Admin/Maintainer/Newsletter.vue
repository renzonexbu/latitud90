<template>
    <AdminLayout>
        <Head title="Gestión de Newsletter" />
        
        <div class="p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <MaintainerHeader 
                    subtitle="Gestión de suscriptores del newsletter"
                    :show-back-button="true"
                />

                <!-- Filters -->
                <div class="mb-6">
                    <NewsletterFilters 
                        :initial-filters="filters"
                        :newsletters="newsletters"
                        @filters-changed="handleFiltersChanged"
                        @export="exportNewsletter"
                    />
                </div>

                <!-- Table -->
                <div class="mb-6">
                    <NewsletterTable 
                        :newsletters="newsletters"
                        @edit-newsletter="handleEditNewsletter"
                        @delete-newsletter="handleDeleteNewsletter"
                    />
                </div>

                <!-- Pagination -->
                <div class="flex justify-end">
                    <NewsletterPagination 
                        :current-page="currentPage"
                        :total-newsletters="totalNewsletters"
                        :newsletters-per-page="newslettersPerPage"
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
import NewsletterTable from "@/Components/Maintainer/NewsletterTable.vue";
import NewsletterFilters from "@/Components/Maintainer/NewsletterFilters.vue";
import NewsletterPagination from "@/Components/Maintainer/NewsletterPagination.vue";

export default {
    name: "NewsletterIndex",
    components: {
        Head,
        AdminLayout,
        MaintainerHeader,
        NewsletterTable,
        NewsletterFilters,
        NewsletterPagination,
    },
    props: {
        newsletters: {
            type: Object,
            default: () => ({
                data: [],
                links: []
            })
        },
        filters: {
            type: Object,
            default: () => ({})
        },
        currentPage: {
            type: Number,
            default: 1
        },
        totalNewsletters: {
            type: Number,
            default: 0
        },
        newslettersPerPage: {
            type: Number,
            default: 10
        }
    },
    methods: {
        handleFiltersChanged(newFilters) {
            this.$inertia.get(
                route('admin.maintainer.newsletter.index'),
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
                route('admin.maintainer.newsletter.index'),
                { ...this.filters, page },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                }
            );
        },

        handleEditNewsletter(newsletterId) {
            this.$inertia.get(route('admin.maintainer.newsletter.edit', newsletterId));
        },

        handleDeleteNewsletter(newsletterId) {
            if (confirm('¿Estás seguro de que quieres eliminar este suscriptor?')) {
                this.$inertia.delete(route('admin.maintainer.newsletter.destroy', newsletterId));
            }
        },

        exportNewsletter() {
            // Crear un formulario temporal para enviar POST con los filtros
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = route('admin.maintainer.newsletter.export');
            
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
