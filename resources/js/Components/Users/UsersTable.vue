<template>
    <div class="bg-white rounded-lg border border-gray-200">
        <!-- Table Container -->
        <div class="overflow-x-auto">
            <table class="w-full text-xs" style="min-width: 1000px;">
                <!-- Table Header -->
                <thead class="bg-[#007e93] sticky top-0 z-10">
                    <tr>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            ID
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[180px]">
                            Usuario
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[220px]">
                            Email
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[180px]">
                            Roles
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Estado
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Fecha Creación
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap w-[80px]">

                        </th>
                    </tr>
                </thead>

                <!-- Table Body -->
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                        v-for="(user, index) in users"
                        :key="user.id"
                        :class="[
                            'hover:bg-gray-50 transition-colors cursor-pointer',
                            index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                        ]"
                        @click="$emit('show-user-details', user)"
                    >
                        <!-- ID -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs text-gray-900">
                                #{{ user.id }}
                            </div>
                        </td>

                        <!-- Usuario -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs font-medium text-gray-900">
                                {{ user.name }}
                            </div>
                        </td>

                        <!-- Email -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs text-gray-900">
                                {{ user.email }}
                            </div>
                        </td>

                        <!-- Roles -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="flex flex-wrap gap-1 justify-center">
                                <span
                                    v-for="role in user.roles"
                                    :key="role.id"
                                    :class="[
                                        'inline-flex px-1.5 py-0.5 text-[9px] font-semibold rounded-full text-white',
                                        getRoleClass(role.name)
                                    ]"
                                >
                                    {{ getRoleLabel(role.name) }}
                                </span>
                            </div>
                        </td>

                        <!-- Estado -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <span
                                :class="[
                                    'inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded-full text-white',
                                    getStatusClass(user.is_active),
                                ]"
                            >
                                {{ getStatusLabel(user.is_active) }}
                            </span>
                        </td>

                        <!-- Fecha de Creación -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs text-gray-900">
                                {{ formatDate(user.created_at) }}
                            </div>
                        </td>

                        <!-- Acciones -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="flex gap-1 items-center justify-center">
                                <!-- Edit Button -->
                                <button
                                    @click.stop="$emit('edit-user', user)"
                                    class="w-[18px] h-[18px] hover:opacity-75 transition-opacity"
                                    title="Editar usuario"
                                >
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#007e93" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>

                                <!-- Toggle Status Button -->
                                <button
                                    @click.stop="$emit('toggle-status', user)"
                                    :class="[
                                        'w-[18px] h-[18px] hover:opacity-75 transition-opacity',
                                        user.is_active ? 'text-red-600' : 'text-green-600'
                                    ]"
                                    :title="user.is_active ? 'Desactivar usuario' : 'Activar usuario'"
                                >
                                    <svg
                                        v-if="user.is_active"
                                        width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    >
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                        <line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                    <svg
                                        v-else
                                        width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    >
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
export default {
    name: "UsersTable",
    props: {
        users: {
            type: Array,
            default: () => [],
        },
    },

    methods: {
        getStatusClass(isActive) {
            return isActive ? "bg-[#4b8d7f]" : "bg-[#d54b44]";
        },

        getStatusLabel(isActive) {
            return isActive ? "Activo" : "Inactivo";
        },

        getRoleClass(roleName) {
            const roleClasses = {
                'super_admin': 'bg-purple-600',
                'contabilidad': 'bg-blue-600',
                'marketing': 'bg-green-600'
            };
            return roleClasses[roleName] || 'bg-gray-500';
        },

        getRoleLabel(roleName) {
            const roleLabels = {
                'super_admin': 'Super Admin',
                'contabilidad': 'Contabilidad',
                'marketing': 'Marketing'
            };
            return roleLabels[roleName] || roleName;
        },

        formatDate(date) {
            if (!date) return "N/A";
            
            try {
                const dateObj = new Date(date);
                if (isNaN(dateObj.getTime())) {
                    return "Invalid Date";
                }
                
                return dateObj.toLocaleDateString("es-CL", {
                    day: "2-digit",
                    month: "2-digit",
                    year: "numeric",
                });
            } catch (error) {
                return "Error Date";
            }
        },
    },
};
</script>

<style scoped>
/* Custom font classes */
.font-nexa-bold {
    font-family: "Nexa-Bold", sans-serif;
    font-weight: 700;
}

.font-nexa-xbold {
    font-family: "Nexa-XBold", sans-serif;
    font-weight: 400;
}

.bg-turquesa {
    background-color: #007e93;
}
</style>
