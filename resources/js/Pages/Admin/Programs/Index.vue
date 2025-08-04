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
                        :initial-filters="localFilters"
                        :programs="allProgramsData"
                        @filters-changed="handleFiltersChanged"
                    />
                </div>

                <!-- Programs Grid -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                    <ProgramsGrid :programs="filteredPrograms" />
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
        allPrograms: {
            type: Array,
            default: () => [],
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            currentPage: 1,
            localFilters: {
                search: "",
                destination: "",
                paymentPercentage: "",
                institution: "",
                level: "",
                grade: "",
                active: true,
            },
        };
    },
    computed: {
        // Todos los programas sin filtrar (para los dropdowns)
        allProgramsData() {
            if (!this.allPrograms || this.allPrograms.length === 0) return [];
            
            return this.allPrograms.map(program => ({
                ...program,
                price: program.trip_price,
                duration: this.calculateDuration(program.departure_date),
                participants: 0,
                paymentPercentage: 0,
                paidAmount: 0,
                totalAmount: program.trip_price,
            }));
        },
        
        // Programas filtrados
        filteredPrograms() {
            let filtered = [...this.allProgramsData];
            
            // Filtro por búsqueda
            if (this.localFilters.search) {
                const searchTerm = this.localFilters.search.toLowerCase();
                filtered = filtered.filter(program => 
                    program.name.toLowerCase().includes(searchTerm) ||
                    program.destination.toLowerCase().includes(searchTerm)
                );
            }
            
            // Filtro por destino
            if (this.localFilters.destination) {
                filtered = filtered.filter(program => 
                    program.destination === this.localFilters.destination
                );
            }
            
            // Filtro por institución
            if (this.localFilters.institution) {
                filtered = filtered.filter(program => 
                    program.course?.institution?.name === this.localFilters.institution
                );
            }
            
            // Filtro por nivel educativo
            if (this.localFilters.level) {
                filtered = filtered.filter(program => 
                    program.course?.education_level === this.localFilters.level
                );
            }
            
            // Filtro por grado
            if (this.localFilters.grade) {
                filtered = filtered.filter(program => 
                    program.course?.grade === this.localFilters.grade
                );
            }
            
            // Filtro por porcentaje de pago (simulado)
            if (this.localFilters.paymentPercentage) {
                // Por ahora filtramos por un porcentaje simulado
                const percentage = parseInt(this.localFilters.paymentPercentage);
                filtered = filtered.filter(program => {
                    // Simular porcentaje de pago basado en algún criterio
                    const simulatedPercentage = Math.floor(Math.random() * 100);
                    return simulatedPercentage >= percentage;
                });
            }
            
            // Filtro por programas activos/inactivos
            if (this.localFilters.active !== undefined) {
                filtered = filtered.filter(program => 
                    program.active === this.localFilters.active
                );
            }
            
            // Paginación
            const itemsPerPage = 6;
            const startIndex = (this.currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            
            return {
                data: filtered.slice(startIndex, endIndex),
                current_page: this.currentPage,
                total: filtered.length,
                per_page: itemsPerPage,
                last_page: Math.ceil(filtered.length / itemsPerPage),
            };
        },
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
            this.localFilters = newFilters;
            this.currentPage = 1; // Resetear a la primera página cuando se cambian los filtros
            // No hacemos router.get aquí para mantener todo interno
        },
        calculateDuration(departureDate) {
            if (!departureDate) return 0;
            const today = new Date();
            const departure = new Date(departureDate);
            const diffTime = departure.getTime() - today.getTime();
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            return 7; // 7 días por defecto
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
