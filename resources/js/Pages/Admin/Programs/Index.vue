<template>
    <AdminLayout>
        <Head title="Gestión de Programas" />

        <div class="py-12">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8">
                <!-- Success Message -->
                <div v-if="$page.props.flash.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    <span class="block sm:inline">{{ $page.props.flash.success }}</span>
                </div>

                <!-- Header -->
                <ProgramsHeader
                    subtitle="Visualización de programas"
                    :create-route="route('admin.programs.create')"
                    :show-create-button="true"
                />

                <!-- Filters -->
                <div
                    class="bg-white overflow-hidden shadow-sm rounded-[20px] mb-6 p-6"
                >
                    <ProgramsFilters
                        :initial-filters="filters"
                        @filters-changed="handleFiltersChanged"
                    />
                </div>

                <!-- Programs Grid -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                    <ProgramsGrid :programs="programs" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import ProgramsHeader from "@/Components/Programs/ProgramsHeader.vue";
import ProgramsFilters from "@/Components/Programs/ProgramsFilters.vue";
import ProgramsGrid from "@/Components/Programs/ProgramsGrid.vue";
import _ from "lodash";

export default {
    name: "ProgramsIndex",
    setup() {
        const page = usePage();
        return { page };
    },
    components: {
        Head,
        Link,
        AdminLayout,
        ProgramsHeader,
        ProgramsFilters,
        ProgramsGrid,
    },
    props: {
        programs: {
            type: Object,
            required: true,
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            filters: {
                search: this.filters.search || "",
                destination: this.filters.destination || "",
                paymentPercentage: this.filters.paymentPercentage || "",
                institution: this.filters.institution || "",
                level: this.filters.level || "",
                grade: this.filters.grade || "",
                active:
                    this.filters.active !== undefined
                        ? this.filters.active
                        : true,
            },
        };
    },
    methods: {
        formatDate(date) {
            return new Date(date).toLocaleDateString("es-ES", {
                year: "numeric",
                month: "short",
                day: "numeric",
            });
        },
        formatPrice(price) {
            return new Intl.NumberFormat("es-CL", {
                style: "currency",
                currency: "CLP",
            })
                .format(price)
                .replace("CLP", "")
                .trim();
        },
        handleFiltersChanged(newFilters) {
            this.filters = newFilters;
            router.get(route("admin.programs.index"), this.filters, {
                preserveState: true,
                replace: true,
            });
        },
        toggleStatus(program) {
            if (
                confirm(
                    `¿Estás seguro de ${
                        program.active ? "desactivar" : "activar"
                    } este programa?`
                )
            ) {
                router.patch(
                    route("admin.programs.toggle-status", program.id),
                    {},
                    {
                        preserveScroll: true,
                    }
                );
            }
        },
    },
};
</script>
