<template>
    <AdminLayout>
        <Head title="Dashboard" />
        
        <!-- Sistema de Alertas -->
        <AlertWrapper ref="alertWrapper" />
        
        <div class="p-4">
            <HomeHeader />
            <div class="mt-6">
                <InstitutionsPaymentsTable :rows="institutionsPayments" />
            </div>
            <div class="mt-6">
                <DashboardProgramsGrid :programs="mostUsedTemplates" />
            </div>
        </div>
    </AdminLayout>
    
</template>

<script>
import { Head } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import HomeHeader from "@/Components/HomeHeader.vue";
import DashboardProgramsGrid from "@/Components/DashboardProgramsGrid.vue";
import InstitutionsPaymentsTable from "@/Components/Payments/InstitutionsPaymentsTable.vue";
import AlertWrapper from "@/Components/Admin/AlertWrapper.vue";

export default {
    name: "AdminDashboard",
    components: {
        Head,
        AdminLayout,
        HomeHeader,
        DashboardProgramsGrid,
        InstitutionsPaymentsTable,
        AlertWrapper,
    },
    props: {
        mostUsedTemplates: {
            type: Array,
            default: () => [],
        },
        institutionsPayments: {
            type: Array,
            default: () => [],
        },
    },
};
</script>
