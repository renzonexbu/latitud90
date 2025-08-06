<template>
    <AdminLayout>
        <div class="bg-white min-h-screen">
            <!-- Header -->
            <PaymentsHeader 
                subtitle="Visualización de pagos"
                :show-create-button="false"
            />

            <!-- Filters -->
            <div class="px-8">
                <PaymentsFilters 
                    :initial-filters="filters"
                    @filters-changed="handleFiltersChanged"
                />
            </div>

            <!-- Table -->
            <div class="px-8 py-6">
                <PaymentsTable 
                    v-if="samplePayments.length > 0"
                    :payments="samplePayments"
                />
                
                <!-- Empty State -->
                <div v-else class="text-center py-12">
                    <div class="text-gray-500 text-lg">No se encontraron pagos</div>
                    <div class="text-gray-400 text-sm mt-2">Prueba a cambiar los filtros de búsqueda</div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="px-8 pb-6">
                <PaymentsPagination 
                    :current-page="currentPage"
                    :total-payments="samplePayments.length"
                    :payments-per-page="6"
                    @page-changed="handlePageChanged"
                />
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaymentsHeader from '@/Components/Payments/PaymentsHeader.vue';
import PaymentsFilters from '@/Components/Payments/PaymentsFilters.vue';
import PaymentsTable from '@/Components/Payments/PaymentsTable.vue';
import PaymentsPagination from '@/Components/Payments/PaymentsPagination.vue';

export default {
    name: "PaymentsIndex",
    components: {
        AdminLayout,
        PaymentsHeader,
        PaymentsFilters,
        PaymentsTable,
        PaymentsPagination,
    },
    props: {
        payments: {
            type: Array,
            default: () => []
        },
        filters: {
            type: Object,
            default: () => ({})
        }
    },
    data() {
        return {
            currentPage: 1,
        };
    },
    computed: {
        // Sample data based on the payments migration structure
        samplePayments() {
            return [
                {
                    id: 1,
                    buy_order: "ORD-2025-001",
                    order_id: 1,
                    amount: 450000,
                    status: "completed",
                    authorization_code: "ABC123456",
                    transaction_date: "2025-01-27T14:30:00",
                    card_number: "**** 1234",
                    card_type: "Visa",
                    participant: "Juan Pérez",
                    program: "Bariloche Aventura",
                    institution: "Colegio San Patricio"
                },
                {
                    id: 2,
                    buy_order: "ORD-2025-002",
                    order_id: 2,
                    amount: 380000,
                    status: "pending",
                    authorization_code: null,
                    transaction_date: "2025-01-27T15:45:00",
                    card_number: "**** 5678",
                    card_type: "Mastercard",
                    participant: "María García",
                    program: "Ruta de Lagos",
                    institution: "Instituto Moderno"
                },
                {
                    id: 3,
                    buy_order: "ORD-2025-003",
                    order_id: 3,
                    amount: 520000,
                    status: "failed",
                    authorization_code: null,
                    transaction_date: "2025-01-27T16:20:00",
                    card_number: "**** 9012",
                    card_type: "Visa",
                    participant: "Carlos Rodríguez",
                    program: "Aventura Patagónica",
                    institution: "Colegio La Salle"
                },
                {
                    id: 4,
                    buy_order: "ORD-2025-004",
                    order_id: 4,
                    amount: 295000,
                    status: "authorized",
                    authorization_code: "DEF789012",
                    transaction_date: "2025-01-27T17:10:00",
                    card_number: "**** 3456",
                    card_type: "Mastercard",
                    participant: "Ana Martínez",
                    program: "Torres del Paine",
                    institution: "Escuela Nacional"
                },
                {
                    id: 5,
                    buy_order: "ORD-2025-005",
                    order_id: 5,
                    amount: 410000,
                    status: "completed",
                    authorization_code: "GHI345678",
                    transaction_date: "2025-01-27T18:05:00",
                    card_number: "**** 7890",
                    card_type: "Visa",
                    participant: "Luis González",
                    program: "Mendoza Experience",
                    institution: "Colegio Bilingüe"
                },
                {
                    id: 6,
                    buy_order: "ORD-2025-006",
                    order_id: 6,
                    amount: 360000,
                    status: "reversed",
                    authorization_code: "JKL901234",
                    transaction_date: "2025-01-27T19:30:00",
                    card_number: "**** 2345",
                    card_type: "Mastercard",
                    participant: "Sofia Herrera",
                    program: "Valle Nevado",
                    institution: "Academia Continental"
                }
            ];
        }
    },
    methods: {
        handleFiltersChanged(newFilters) {
            console.log('Filters changed:', newFilters);
            // Here you would typically make an API call to filter payments
        },
        
        handlePageChanged(page) {
            this.currentPage = page;
            console.log('Page changed to:', page);
            // Here you would typically make an API call to load the new page
        }
    }
};
</script>
