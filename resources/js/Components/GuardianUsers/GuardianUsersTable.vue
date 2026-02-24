<template>
    <div class="bg-white rounded-lg border border-gray-200">
        <!-- Table Container -->
        <div class="overflow-x-auto">
            <table class="w-full text-xs" style="min-width: 1100px;">
                <!-- Table Header -->
                <thead class="bg-[#007e93] sticky top-0 z-10">
                    <tr>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            ID
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[160px]">
                            Nombre
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[200px]">
                            Email
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Documento
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Teléfono
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Estado
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Email Verificado
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Último Login
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Registro
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap w-[100px]">
                            Acciones
                        </th>
                    </tr>
                </thead>

                <!-- Table Body -->
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                        v-for="(user, index) in users"
                        :key="user.id"
                        :class="[
                            'hover:bg-gray-50 transition-colors',
                            index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                        ]"
                    >
                        <!-- ID -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs text-gray-900">#{{ user.id }}</div>
                        </td>

                        <!-- Nombre -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs font-medium text-gray-900">{{ user.name }}</div>
                        </td>

                        <!-- Email -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs text-gray-900">{{ user.email }}</div>
                        </td>

                        <!-- Documento -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs text-gray-900">{{ user.document || 'N/A' }}</div>
                        </td>

                        <!-- Teléfono -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs text-gray-900">
                                {{ user.phone ? `${user.phone_code || ''} ${user.phone}` : 'N/A' }}
                            </div>
                        </td>

                        <!-- Estado -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <span
                                :class="[
                                    'inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded-full text-white',
                                    getStatusClass(user.status),
                                ]"
                            >
                                {{ getStatusLabel(user.status) }}
                            </span>
                        </td>

                        <!-- Email Verificado -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <span
                                :class="[
                                    'inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded-full text-white',
                                    user.email_verified_at ? 'bg-[#4b8d7f]' : 'bg-gray-400',
                                ]"
                            >
                                {{ user.email_verified_at ? 'Verificado' : 'Pendiente' }}
                            </span>
                        </td>

                        <!-- Último Login -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs text-gray-900">{{ formatDate(user.last_login_at) }}</div>
                        </td>

                        <!-- Fecha Registro -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs text-gray-900">{{ formatDate(user.created_at) }}</div>
                        </td>

                        <!-- Acciones -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="flex gap-2 items-center justify-center">
                                <!-- Toggle Status Button -->
                                <button
                                    @click.stop="$emit('toggle-status', user)"
                                    :class="[
                                        'w-[18px] h-[18px] hover:opacity-75 transition-opacity',
                                        user.status === 'active' ? 'text-red-600' : 'text-green-600'
                                    ]"
                                    :title="user.status === 'active' ? 'Suspender usuario' : 'Activar usuario'"
                                >
                                    <svg
                                        v-if="user.status === 'active'"
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

                                <!-- Verify Email Button (solo si no está verificado) -->
                                <button
                                    v-if="!user.email_verified_at"
                                    @click.stop="$emit('verify-email', user)"
                                    class="w-[18px] h-[18px] hover:opacity-75 transition-opacity text-[#ffb232]"
                                    title="Verificar email manualmente"
                                >
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                        <polyline points="22 4 12 14.01 9 11.01"/>
                                    </svg>
                                </button>

                                <!-- Edit Button -->
                                <button
                                    @click.stop="$emit('edit-user', user)"
                                    class="w-[18px] h-[18px] hover:opacity-75 transition-opacity text-blue-600"
                                    title="Editar nombre y correo"
                                >
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>

                                <!-- Resend Password Reset Button -->
                                <button
                                    @click.stop="$emit('resend-password-reset', user)"
                                    class="w-[18px] h-[18px] hover:opacity-75 transition-opacity text-[#007e93]"
                                    title="Enviar email de recuperación de contraseña"
                                >
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                        <polyline points="22,6 12,13 2,6"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- Empty state -->
                    <tr v-if="!users || users.length === 0">
                        <td colspan="10" class="px-4 py-8 text-center text-gray-500 text-sm">
                            No se encontraron usuarios registrados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
export default {
    name: "GuardianUsersTable",
    props: {
        users: {
            type: Array,
            default: () => [],
        },
    },
    methods: {
        getStatusClass(status) {
            const classes = {
                'active': 'bg-[#4b8d7f]',
                'pending_payment': 'bg-[#ffb232]',
                'suspended': 'bg-[#d54b44]',
            };
            return classes[status] || 'bg-gray-500';
        },

        getStatusLabel(status) {
            const labels = {
                'active': 'Activo',
                'pending_payment': 'Pago Pendiente',
                'suspended': 'Suspendido',
            };
            return labels[status] || status;
        },

        formatDate(date) {
            if (!date) return "N/A";
            try {
                const dateObj = new Date(date);
                if (isNaN(dateObj.getTime())) return "N/A";
                return dateObj.toLocaleDateString("es-CL", {
                    day: "2-digit",
                    month: "2-digit",
                    year: "numeric",
                });
            } catch {
                return "N/A";
            }
        },
    },
};
</script>
