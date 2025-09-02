<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import UsersTable from "@/Components/Users/UsersTable.vue";
import UsersHeader from "@/Components/Users/UsersHeader.vue";
import UsersFilters from "@/Components/Users/UsersFilters.vue";
import UsersPagination from "@/Components/Users/UsersPagination.vue";
import AlertWrapper from "@/Components/Admin/AlertWrapper.vue";

const props = defineProps({
    users: Object,
    filters: Object,
});

const handleFiltersChanged = (newFilters) => {
    router.get(
        route("admin.users.index"),
        newFilters,
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const handleCreateUser = () => {
    router.get(route("admin.users.create"));
};

const handleEditUser = (user) => {
    router.get(route("admin.users.edit", user.id));
};

const handleDeleteUser = (user) => {
    if (confirm("¿Estás seguro de que quieres eliminar este usuario?")) {
        router.delete(route("admin.users.destroy", user.id));
    }
};

const handleToggleStatus = (user) => {
    router.patch(route("admin.users.toggle-status", user.id));
};

const handlePageChanged = (page) => {
    router.get(
        route("admin.users.index"),
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
        <Head title="Administrar Usuarios" />

        <!-- Sistema de Alertas -->
        <AlertWrapper ref="alertWrapper" />

        <div class="p-6">
            <!-- Header -->
            <UsersHeader 
                subtitle="Administración de usuarios del sistema"
                :show-create-button="true"
                @create-user="handleCreateUser"
            />

            <!-- Filters -->
            <div class="mb-6">
                <UsersFilters 
                    :initial-filters="filters"
                    @filters-changed="handleFiltersChanged"
                />
            </div>

            <!-- Table -->
            <div class="mb-6">
                <UsersTable 
                    :users="users.data"
                    @edit-user="handleEditUser"
                    @delete-user="handleDeleteUser"
                    @toggle-status="handleToggleStatus"
                />
            </div>

            <!-- Pagination -->
            <div v-if="users.links.length > 3" class="flex justify-end">
                <UsersPagination 
                    :current-page="users.current_page"
                    :total-users="users.total"
                    :users-per-page="users.per_page"
                    @page-changed="handlePageChanged"
                />
            </div>
        </div>
    </AdminLayout>
</template>
