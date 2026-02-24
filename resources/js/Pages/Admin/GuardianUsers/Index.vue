<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";
import GuardianUsersTable from "@/Components/GuardianUsers/GuardianUsersTable.vue";
import GuardianUsersHeader from "@/Components/GuardianUsers/GuardianUsersHeader.vue";
import GuardianUsersFilters from "@/Components/GuardianUsers/GuardianUsersFilters.vue";
import GuardianUsersPagination from "@/Components/GuardianUsers/GuardianUsersPagination.vue";
import AlertWrapper from "@/Components/Admin/AlertWrapper.vue";

const props = defineProps({
    guardianUsers: Object,
    filters: Object,
});

const alertWrapper = ref(null);

// Edit modal state
const showEditModal = ref(false);
const editingUser = ref(null);
const editForm = ref({ name: '', email: '' });
const editErrors = ref({});
const isSaving = ref(false);

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

const handleEditUser = (user) => {
    editingUser.value = user;
    editForm.value = { name: user.name, email: user.email };
    editErrors.value = {};
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingUser.value = null;
    editForm.value = { name: '', email: '' };
    editErrors.value = {};
};

const saveEdit = () => {
    editErrors.value = {};
    isSaving.value = true;

    router.put(route("admin.guardian-users.update", editingUser.value.id), editForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
            isSaving.value = false;
        },
        onError: (errors) => {
            editErrors.value = errors;
            isSaving.value = false;
        },
    });
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
                    @edit-user="handleEditUser"
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

        <!-- Edit Modal -->
        <div v-if="showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Editar Usuario</h3>
                    <button @click="closeEditModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            class="w-full px-3 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm"
                            :class="editErrors.name ? 'border-red-300' : 'border-gray-300'"
                            placeholder="Nombre completo"
                        />
                        <p v-if="editErrors.name" class="mt-1 text-xs text-red-600">{{ editErrors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
                        <input
                            v-model="editForm.email"
                            type="email"
                            class="w-full px-3 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm"
                            :class="editErrors.email ? 'border-red-300' : 'border-gray-300'"
                            placeholder="correo@ejemplo.com"
                        />
                        <p v-if="editErrors.email" class="mt-1 text-xs text-red-600">{{ editErrors.email }}</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button
                        @click="closeEditModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="saveEdit"
                        :disabled="isSaving"
                        class="px-4 py-2 text-sm font-medium text-white bg-[#007e93] hover:bg-[#006a7c] rounded-md transition-colors disabled:opacity-50"
                    >
                        {{ isSaving ? 'Guardando...' : 'Guardar Cambios' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
