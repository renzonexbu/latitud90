<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Overlay -->
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75" @click="closeModal"></div>
            </div>

            <!-- Modal -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <!-- Header -->
                <div class="bg-turquesa px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-white">
                            Gestionar Roles - {{ user.name }}
                        </h3>
                        <button
                            @click="closeModal"
                            class="text-white hover:text-gray-200 transition-colors"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-6 py-4">
                    <form @submit.prevent="saveRoles">
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
                                            v-model="selectedRoles"
                                            :value="role.name"
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

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200 mt-6">
                            <button
                                type="button"
                                @click="closeModal"
                                class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="isSaving"
                                class="px-4 py-2 bg-turquesa text-white rounded-lg hover:bg-turquesa-dark transition-colors disabled:opacity-50"
                            >
                                {{ isSaving ? 'Guardando...' : 'Guardar Roles' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    user: {
        type: Object,
        required: true
    },
    roles: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['close']);

const selectedRoles = ref([]);
const isSaving = ref(false);

// Inicializar roles seleccionados cuando se abre el modal
watch(() => props.show, (newValue) => {
    if (newValue && props.user.roles) {
        selectedRoles.value = props.user.roles.map(role => role.name);
    }
});

const closeModal = () => {
    emit('close');
};

const saveRoles = async () => {
    isSaving.value = true;
    
    try {
        await router.put(route('admin.users.update', props.user.id), {
            name: props.user.name,
            email: props.user.email,
            is_active: props.user.is_active,
            roles: selectedRoles.value
        }, {
            preserveState: true,
            onSuccess: () => {
                closeModal();
            }
        });
    } catch (error) {
        console.error('Error saving roles:', error);
    } finally {
        isSaving.value = false;
    }
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

<style scoped>
.bg-turquesa {
    background-color: #007e93;
}

.bg-turquesa-dark {
    background-color: #006b7d;
}
</style>
