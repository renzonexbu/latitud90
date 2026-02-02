<template>
    <AdminLayout>
        <Head title="Gestión de Plantillas" />

        <!-- Sistema de Alertas -->
        <AlertWrapper ref="alertWrapper" />

        <div class="py-6 lg:py-12">
            <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Header -->
                <ProgramsHeader
                    subtitle="Visualización de plantillas"
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
                    <ProgramsGrid
                        :programs="filteredPrograms"
                        item-label="Plantillas"
                        @page-changed="handlePageChange"
                    />
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
import AlertWrapper from "@/Components/Admin/AlertWrapper.vue";
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
        AlertWrapper,
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
                course_number: "",
                status: "",
                active: true,
            },
        };
    },
    mounted() {
        // Leer el parámetro page de la URL al montar
        const urlParams = new URLSearchParams(window.location.search);
        const pageParam = urlParams.get('page');
        if (pageParam) {
            this.currentPage = parseInt(pageParam, 10) || 1;
        }
    },
    computed: {
        // Todos los programas sin filtrar (para los dropdowns)
        allProgramsData() {
            if (!this.allPrograms || this.allPrograms.length === 0) return [];
            
            return this.allPrograms.map(program => ({
                ...program,
                price: program.course_total_amount || program.trip_price,
                duration: this.calculateDuration(program.departure_date),
                participants: program.course?.participants?.length || 0,
                paymentPercentage: program.course_payment_percentage || 0,
                paidAmount: program.course_paid_amount || 0,
                totalAmount: program.course_total_amount || program.trip_price,
            }));
        },
        
        // Programas filtrados
        filteredPrograms() {
            let filtered = [...this.allProgramsData];

            // Filtro por búsqueda
            if (this.localFilters.search) {
                const searchTerm = this.localFilters.search.toLowerCase();
                filtered = filtered.filter(program => {
                    const name = (program.name || '').toLowerCase();
                    const destination = (program.destination || '').toLowerCase();
                    return name.includes(searchTerm) || destination.includes(searchTerm);
                });
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
            
            // Filtro por curso (course_number)
            if (this.localFilters.course_number) {
                filtered = filtered.filter(program => 
                    String(program.course?.course_number || '') === String(this.localFilters.course_number)
                );
            }
            
            // Filtro por porcentaje de pago
            if (this.localFilters.paymentPercentage) {
                const percentage = parseInt(this.localFilters.paymentPercentage);
                filtered = filtered.filter(program => {
                    return (program.paymentPercentage || 0) >= percentage;
                });
            }
            
            // Filtro por programas activos/inactivos
            if (this.localFilters.active !== undefined) {
                filtered = filtered.filter(program => 
                    program.active === this.localFilters.active
                );
            }
            
            // Filtro por status (reserva, ejecutado)
            if (this.localFilters.status) {
                filtered = filtered.filter(program => 
                    program.status === this.localFilters.status
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
        handlePageChange(page) {
            this.currentPage = page;
            // Actualizar URL sin recargar (para que funcione el historial)
            const url = new URL(window.location);
            url.searchParams.set('page', page);
            window.history.pushState({}, '', url);
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
                    } esta plantilla?`
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
