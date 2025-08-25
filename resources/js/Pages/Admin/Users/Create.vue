<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, useForm, Link } from "@inertiajs/vue3";
import { ref } from "vue";
import { EyeIcon, EyeSlashIcon, ArrowLeftIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    roles: Object,
});

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    is_active: true,
    roles: [],
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const submit = () => {
    form.post(route("admin.users.store"));
};

const toggleRole = (roleName) => {
    const index = form.roles.indexOf(roleName);
    if (index > -1) {
        form.roles.splice(index, 1);
    } else {
        form.roles.push(roleName);
    }
};

const isRoleSelected = (roleName) => {
    return form.roles.includes(roleName);
};

const getRoleDisplayName = (roleName) => {
    const roleNames = {
        'super_admin': 'Super Administrador',
        'admin_contabilidad': 'Administrador de Contabilidad',
        'editor_contabilidad': 'Editor de Contabilidad',
        'visualizador_contabilidad': 'Visualizador de Contabilidad',
        'admin_marketing': 'Administrador de Marketing',
        'editor_marketing': 'Editor de Marketing',
        'visualizador_marketing': 'Visualizador de Marketing'
    };
    return roleNames[roleName] || roleName;
};

const getRoleDescription = (roleName) => {
    const roleDescriptions = {
        'super_admin': 'Acceso total al sistema',
        'admin_contabilidad': 'Control total sobre el grupo de contabilidad',
        'editor_contabilidad': 'Puede editar pero no crear usuarios ni eliminar',
        'visualizador_contabilidad': 'Solo puede visualizar información',
        'admin_marketing': 'Control total sobre el grupo de marketing',
        'editor_marketing': 'Puede editar pero no crear usuarios ni eliminar',
        'visualizador_marketing': 'Solo puede visualizar información'
    };
    return roleDescriptions[roleName] || '';
};
</script>

<template>
    <AdminLayout>
        <Head title="Crear Usuario" />

        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('admin.users.index')"
                        class="text-gray-600 hover:text-gray-900 transition-colors"
                    >
                        <ArrowLeftIcon class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Crear Usuario</h1>
                        <p class="text-gray-600">Agregar un nuevo usuario al sistema</p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="flex justify-center">
                <div class="max-w-2xl w-full">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <form @submit.prevent="submit">
                        <!-- Name -->
                        <div class="mb-6">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nombre Completo *
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                :class="{ 'border-red-500': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Email -->
                        <div class="mb-6">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email *
                            </label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                :class="{ 'border-red-500': form.errors.email }"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Password -->
                        <div class="mb-6">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                Contraseña *
                            </label>
                            <div class="relative">
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                    :class="{ 'border-red-500': form.errors.password }"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                    @click="showPassword = !showPassword"
                                >
                                    <EyeIcon v-if="!showPassword" class="w-5 h-5 text-gray-400" />
                                    <EyeSlashIcon v-else class="w-5 h-5 text-gray-400" />
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-6">
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                                Confirmar Contraseña *
                            </label>
                            <div class="relative">
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    required
                                    class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                    :class="{ 'border-red-500': form.errors.password_confirmation }"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                    @click="showConfirmPassword = !showConfirmPassword"
                                >
                                    <EyeIcon v-if="!showConfirmPassword" class="w-5 h-5 text-gray-400" />
                                    <EyeSlashIcon v-else class="w-5 h-5 text-gray-400" />
                                </button>
                            </div>
                            <p v-if="form.errors.password_confirmation" class="mt-1 text-sm text-red-600">
                                {{ form.errors.password_confirmation }}
                            </p>
                        </div>

                        <!-- Status -->
                        <div class="mb-6">
                            <label class="flex items-center">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-turquesa focus:ring-turquesa"
                                />
                                <span class="ml-2 text-sm text-gray-700">Usuario activo</span>
                            </label>
                        </div>

                        <!-- Roles -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-4">
                                Roles *
                            </label>
                            
                            <!-- Roles por grupos -->
                            <div class="space-y-6">
                                <div v-for="(group, groupKey) in roles" :key="groupKey" class="border border-gray-200 rounded-lg p-4">
                                    <!-- Grupo header -->
                                    <div class="mb-4">
                                        <h4 class="text-lg font-semibold text-gray-900">{{ group.name }}</h4>
                                        <p class="text-sm text-gray-600">{{ group.description }}</p>
                                    </div>

                                    <!-- Roles del grupo -->
                                    <div class="space-y-3">
                                        <div v-for="role in group.roles" :key="role.id" class="flex items-center">
                                            <input
                                                :id="`role-${role.id}`"
                                                :checked="isRoleSelected(role.name)"
                                                @change="toggleRole(role.name)"
                                                type="checkbox"
                                                class="h-4 w-4 text-turquesa focus:ring-turquesa border-gray-300 rounded"
                                            />
                                            <label :for="`role-${role.id}`" class="ml-3 flex flex-col">
                                                <span class="text-sm font-medium text-gray-900">{{ getRoleDisplayName(role.name) }}</span>
                                                <span class="text-xs text-gray-500">{{ getRoleDescription(role.name) }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <p v-if="form.errors.roles" class="mt-1 text-sm text-red-600">
                                {{ form.errors.roles }}
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                            <Link
                                :href="route('admin.users.index')"
                                class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                            >
                                Cancelar
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-2 bg-turquesa text-white rounded-lg hover:bg-turquesa-dark transition-colors disabled:opacity-50"
                            >
                                {{ form.processing ? 'Creando...' : 'Crear Usuario' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </div>
    </AdminLayout>
</template>
