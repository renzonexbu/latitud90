<template>
    <AdminLayout>
        <Head title="Gestión de Programas" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <ProgramsHeader 
                    subtitle="Visualización de programas"
                    :create-route="route('admin.programs.create')"
                    :show-create-button="true"
                />

                <!-- Filters -->
                <div class="bg-white overflow-hidden shadow-sm rounded-[20px] mb-6 p-6">
                    <ProgramsFilters 
                        :initial-filters="filters"
                        @filters-changed="handleFiltersChanged"
                    />
                </div>

                <!-- Programs Table -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-turquesa text-blanco rounded-lg">
                                <tr>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider"
                                    >
                                        Programa
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider"
                                    >
                                        Tipo
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider"
                                    >
                                        Destino
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider"
                                    >
                                        Fecha Salida
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider"
                                    >
                                        Capacidad
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider"
                                    >
                                        Precio
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider"
                                    >
                                        Estado
                                    </th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Acciones</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="program in programs.data"
                                    :key="program.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div
                                                class="flex-shrink-0 h-12 w-12"
                                            >
                                                <img
                                                    v-if="program.image_url"
                                                    :src="`/storage/${program.image_url}`"
                                                    :alt="program.name"
                                                    class="h-12 w-12 rounded-lg object-cover"
                                                />
                                                <div
                                                    v-else
                                                    class="h-12 w-12 rounded-lg bg-gray-200 flex items-center justify-center"
                                                >
                                                    <svg
                                                        class="h-6 w-6 text-gray-400"
                                                        fill="currentColor"
                                                        viewBox="0 0 20 20"
                                                    >
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div
                                                    class="text-sm font-medium text-gray-900"
                                                >
                                                    {{ program.name }}
                                                </div>
                                                <div
                                                    class="text-sm text-gray-500"
                                                >
                                                    {{ program.duration_days }}
                                                    {{
                                                        program.duration_days ===
                                                        1
                                                            ? "día"
                                                            : "días"
                                                    }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800"
                                        >
                                            {{ program.service_type }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ program.destination }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ formatDate(program.departure_date) }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ program.available_spots }}/{{
                                            program.capacity
                                        }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                    >
                                        ${{ formatPrice(program.base_price) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="
                                                program.active
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-red-100 text-red-800'
                                            "
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        >
                                            {{
                                                program.active
                                                    ? "Activo"
                                                    : "Inactivo"
                                            }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2"
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'admin.programs.show',
                                                    program.id
                                                )
                                            "
                                            class="text-indigo-600 hover:text-indigo-900"
                                            >Ver</Link
                                        >
                                        <Link
                                            :href="
                                                route(
                                                    'admin.programs.edit',
                                                    program.id
                                                )
                                            "
                                            class="text-yellow-600 hover:text-yellow-900"
                                            >Editar</Link
                                        >
                                        <Link
                                            :href="
                                                route(
                                                    'admin.programs.passengers',
                                                    program.id
                                                )
                                            "
                                            class="text-green-600 hover:text-green-900"
                                            >Pasajeros</Link
                                        >
                                        <button
                                            @click="toggleStatus(program)"
                                            :class="
                                                program.active
                                                    ? 'text-red-600 hover:text-red-900'
                                                    : 'text-green-600 hover:text-green-900'
                                            "
                                        >
                                            {{
                                                program.active
                                                    ? "Desactivar"
                                                    : "Activar"
                                            }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="programs.links && programs.links.length > 3"
                        class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <Link
                                    v-if="programs.prev_page_url"
                                    :href="programs.prev_page_url"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Anterior
                                </Link>
                                <Link
                                    v-if="programs.next_page_url"
                                    :href="programs.next_page_url"
                                    class="relative ml-3 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Siguiente
                                </Link>
                            </div>
                            <div
                                class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between"
                            >
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Mostrando
                                        <span class="font-medium">{{
                                            programs.from
                                        }}</span>
                                        a
                                        <span class="font-medium">{{
                                            programs.to
                                        }}</span>
                                        de
                                        <span class="font-medium">{{
                                            programs.total
                                        }}</span>
                                        resultados
                                    </p>
                                </div>
                                <div>
                                    <nav
                                        class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
                                        aria-label="Pagination"
                                    >
                                        <template
                                            v-for="link in programs.links"
                                            :key="link.label"
                                        >
                                            <Link
                                                v-if="link.url"
                                                :href="link.url"
                                                :class="[
                                                    'relative inline-flex items-center px-2 py-2 border text-sm font-medium',
                                                    link.active
                                                        ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                        : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                                ]"
                                                v-html="link.label"
                                            />
                                            <span
                                                v-else
                                                :class="[
                                                    'relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-300 cursor-default',
                                                ]"
                                                v-html="link.label"
                                            />
                                        </template>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import { Head, Link, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import ProgramsHeader from "@/Components/Programs/ProgramsHeader.vue";
import ProgramsFilters from "@/Components/Programs/ProgramsFilters.vue";
import _ from "lodash";

export default {
    name: "ProgramsIndex",
    components: {
        Head,
        Link,
        AdminLayout,
        ProgramsHeader,
        ProgramsFilters,
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
                active: this.filters.active !== undefined ? this.filters.active : true,
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
