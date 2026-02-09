<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import GuardianUsersTable from "@/Components/GuardianUsers/GuardianUsersTable.vue";
import GuardianUsersHeader from "@/Components/GuardianUsers/GuardianUsersHeader.vue";
import GuardianUsersFilters from "@/Components/GuardianUsers/GuardianUsersFilters.vue";
import GuardianUsersPagination from "@/Components/GuardianUsers/GuardianUsersPagination.vue";
import AlertWrapper from "@/Components/Admin/AlertWrapper.vue";

const props = defineProps({
    guardianUsers: Object,
    filters: Object,
});

const handleFiltersChanged = (newFilters) => {
    router.get(
        route("admin.guardian-users.index"),
        newFilters,
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const handleToggleStatus = (user) => {
    const action = user.status === 'active' ? 'suspender' : 'activar';
    if (confirm(`¿Estás seguro de que quieres ${action} a ${user.name}?`)) {
        router.post(route("admin.guardian-users.toggle-status", user.id));
    }
};

const handleResendPasswordReset = (user) => {
    if (confirm(`¿Enviar email de recuperación de contraseña a ${user.email}?`)) {
        router.post(route("admin.guardian-users.resend-password-reset", user.id));
    }
};

const handleVerifyEmail = (user) => {
    if (confirm(`¿Verificar manualmente el email de ${user.name} (${user.email})?`)) {
        router.post(route("admin.guardian-users.verify-email", user.id));
    }
};

const handlePageChanged = (page) => {
    router.get(
        route("admin.guardian-users.index"),
        { ...props.filters, page },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};
</script>

<template>
    <AdminLayout>
        <Head title="Usuarios Registrados - Apoderados" />

        <!-- Sistema de Alertas -->
        <AlertWrapper ref="alertWrapper" />

        <div class="p-6">
            <!-- Header -->
            <GuardianUsersHeader />

            <!-- Filters -->
            <div class="mb-6">
                <GuardianUsersFilters
                    :initial-filters="filters"
                    @filters-changed="handleFiltersChanged"
                />
            </div>

            <!-- Table -->
            <div class="mb-6">
                <GuardianUsersTable
                    :users="guardianUsers.data"
                    @toggle-status="handleToggleStatus"
                    @resend-password-reset="handleResendPasswordReset"
                    @verify-email="handleVerifyEmail"
                />
            </div>

            <!-- Pagination -->
            <div v-if="guardianUsers.total > guardianUsers.per_page" class="flex justify-end">
                <GuardianUsersPagination
                    :current-page="guardianUsers.current_page"
                    :total-users="guardianUsers.total"
                    :users-per-page="guardianUsers.per_page"
                    @page-changed="handlePageChanged"
                />
            </div>
        </div>
    </AdminLayout>
</template>
