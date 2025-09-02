<template>
    <div class="bg-white rounded-[20px] overflow-hidden min-h-[600px]">
        <!-- Table Container -->
        <div class="flex flex-col gap-0">
            <!-- Table Header -->
            <div
                class="bg-turquesa rounded-t-[20px] px-5 py-[11px] flex items-center justify-between h-[61.51px]"
            >
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[80px]"
                >
                    ID
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[200px]"
                >
                    Usuario
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[200px]"
                >
                    Email
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[150px]"
                >
                    Roles
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Estado
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Fecha de Creación
                </div>
                <!-- Columna de acciones (vacía en header) -->
                <div class="w-[120px]"></div>
            </div>

            <!-- Table Body -->
            <div class="flex flex-col overflow-y-auto min-h-[500px] pb-4">
                <div
                    v-for="(user, index) in users"
                    :key="user.id"
                    :class="[
                        'px-5 py-[14px] flex items-center justify-between cursor-pointer',
                        index % 2 === 0 ? 'bg-white' : 'bg-[#f9f9f9]',
                    ]"
                    @click="$emit('show-user-details', user)"
                >
                    <!-- ID -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[80px]"
                    >
                        #{{ user.id }}
                    </div>

                    <!-- Usuario -->
                    <div class="flex items-center justify-center w-[200px]">
                        <div class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px]">
                            {{ user.name }}
                        </div>
                    </div>

                    <!-- Email -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[200px]"
                    >
                        {{ user.email }}
                    </div>

                    <!-- Roles -->
                    <div class="flex justify-center items-center w-[150px]">
                        <div class="flex flex-wrap gap-1 justify-center">
                            <span
                                v-for="role in user.roles"
                                :key="role.id"
                                :class="[
                                    'rounded-[8px] px-[8px] py-[4px] text-white font-nexa-xbold text-[12px] leading-[12px] text-center',
                                    getRoleClass(role.name)
                                ]"
                            >
                                {{ getRoleLabel(role.name) }}
                            </span>
                        </div>
                    </div>

                    <!-- Estado -->
                    <div class="flex justify-center items-center w-[120px]">
                        <div
                            :class="[
                                'rounded-[12px] px-[10px] py-[6px] text-white font-nexa-xbold text-[14px] leading-[13px] text-center flex items-center justify-center',
                                getStatusClass(user.is_active),
                            ]"
                        >
                            {{ getStatusLabel(user.is_active) }}
                        </div>
                    </div>

                    <!-- Fecha de Creación -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        {{ formatDate(user.created_at) }}
                    </div>

                    <!-- Acciones -->
                    <div
                        class="flex gap-2 items-center justify-center w-[120px]"
                    >
                        <!-- Edit Button -->
                        <button
                            @click.stop="$emit('edit-user', user)"
                            class="w-[18px] h-[19px] hover:opacity-75 transition-opacity"
                            title="Editar usuario"
                        >
                            <svg
                                width="18"
                                height="19"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="#007e93"
                                stroke-width="2"
                            >
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </button>

                        <!-- Toggle Status Button -->
                        <button
                            @click.stop="$emit('toggle-status', user)"
                            :class="[
                                'w-[18px] h-[19px] hover:opacity-75 transition-opacity',
                                user.is_active ? 'text-red-600' : 'text-green-600'
                            ]"
                            :title="user.is_active ? 'Desactivar usuario' : 'Activar usuario'"
                        >
                            <svg
                                v-if="user.is_active"
                                width="18"
                                height="19"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                            <svg
                                v-else
                                width="18"
                                height="19"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>

                        <!-- Delete Button -->
                        <!-- <button
                            @click.stop="$emit('delete-user', user)"
                            class="w-[18px] h-[19px] hover:opacity-75 transition-opacity text-red-600"
                            title="Eliminar usuario"
                        >
                            <svg
                                width="18"
                                height="19"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <polyline points="3,6 5,6 21,6"/>
                                <path d="M19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"/>
                                <line x1="10" y1="11" x2="10" y2="17"/>
                                <line x1="14" y1="11" x2="14" y2="17"/>
                            </svg>
                        </button> -->
                    </div>
                </div>
            </div>
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
                'admin_contabilidad': 'bg-blue-600',
                'editor_contabilidad': 'bg-blue-500',
                'visualizador_contabilidad': 'bg-blue-400',
                'admin_marketing': 'bg-green-600',
                'editor_marketing': 'bg-green-500',
                'visualizador_marketing': 'bg-green-400',
            };
            return roleClasses[roleName] || 'bg-gray-500';
        },

        getRoleLabel(roleName) {
            const roleLabels = {
                'super_admin': 'Super Admin',
                'admin_contabilidad': 'Admin Cont.',
                'editor_contabilidad': 'Editor Cont.',
                'visualizador_contabilidad': 'Visual. Cont.',
                'admin_marketing': 'Admin Mkt.',
                'editor_marketing': 'Editor Mkt.',
                'visualizador_marketing': 'Visual. Mkt.',
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
