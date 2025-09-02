<template>
    <div class="rounded-[20px] border border-gray-300 overflow-hidden">
        <!-- Table Header -->
        <div class="bg-turquesa rounded-t-[20px] px-6 py-4 flex items-center justify-between h-[75px]">
            <div class="text-white font-nexa-bold text-sm w-[150px]">
                Usuario
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[100px]">
                Acción
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[120px]">
                Módulo
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[200px]">
                Descripción
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[120px]">
                Método
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[150px]">
                Fecha
            </div>
            <div class="w-[60px] h-[20px] flex-shrink-0">
                <!-- Empty space for actions column -->
            </div>
        </div>

        <!-- Table Body -->
        <div class="flex flex-col">
            <div 
                v-for="(log, index) in adminLogs" 
                :key="log.id"
                :class="[
                    'px-6 py-[18px] flex items-center justify-between',
                    index % 2 === 0 ? 'bg-white' : 'bg-gray-50'
                ]"
            >
                <!-- Usuario -->
                <div class="text-verde-oscuro font-nexa-bold text-sm w-[150px]">
                    <div class="flex flex-col">
                        <span class="font-semibold">{{ log.user_name || 'Sistema' }}</span>
                        <span class="text-xs text-gray-500">{{ log.user_email || 'N/A' }}</span>
                    </div>
                </div>

                <!-- Acción -->
                <div class="w-[100px] flex justify-center">
                    <div 
                        :class="[
                            'rounded-xl px-2.5 py-1.5 flex items-center justify-center w-[80px]',
                            getActionColor(log.action)
                        ]"
                    >
                        <span class="font-nexa-xbold text-xs text-center text-white">
                            {{ getActionText(log.action) }}
                        </span>
                    </div>
                </div>

                <!-- Módulo -->
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[120px]">
                    {{ capitalizeWords(log.module) }}
                </div>

                <!-- Descripción -->
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[200px]">
                    <div class="truncate" :title="log.description">
                        {{ log.description || 'Sin descripción' }}
                    </div>
                </div>

                <!-- Método HTTP -->
                <div class="w-[120px] flex justify-center">
                    <div 
                        :class="[
                            'rounded-lg px-2 py-1 text-xs font-bold text-white',
                            getMethodColor(log.request_method)
                        ]"
                    >
                        {{ log.request_method || 'N/A' }}
                    </div>
                </div>

                <!-- Fecha -->
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[150px]">
                    {{ formatDate(log.created_at) }}
                </div>

                <!-- Acciones -->
                <div class="w-[60px] h-[20px] flex-shrink-0 flex justify-center">
                    <button 
                        @click="viewDetails(log.id)"
                        class="p-1 hover:bg-gray-100 rounded transition-colors"
                        :title="`Ver detalles del log`"
                    >
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "AdminLogsTable",
    props: {
        adminLogs: {
            type: Array,
            default: () => []
        }
    },
    methods: {
        viewDetails(logId) {
            this.$emit('view-details', logId);
        },

        getActionColor(action) {
            const colors = {
                'create': 'bg-green-500',
                'update': 'bg-blue-500',
                'delete': 'bg-red-500',
                'view': 'bg-gray-500',
                'export': 'bg-purple-500',
                'login': 'bg-indigo-500',
                'logout': 'bg-yellow-500'
            };
            return colors[action] || 'bg-gray-400';
        },

        getActionText(action) {
            const texts = {
                'create': 'Crear',
                'update': 'Actualizar',
                'delete': 'Eliminar',
                'view': 'Ver',
                'export': 'Exportar',
                'login': 'Login',
                'logout': 'Logout'
            };
            return texts[action] || action;
        },

        getMethodColor(method) {
            const colors = {
                'GET': 'bg-blue-500',
                'POST': 'bg-green-500',
                'PUT': 'bg-yellow-500',
                'PATCH': 'bg-orange-500',
                'DELETE': 'bg-red-500'
            };
            return colors[method] || 'bg-gray-500';
        },

        formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString('es-CL', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        capitalizeWords(string) {
            if (!string) return '';
            return string.split('_').map(word => 
                word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
            ).join(' ');
        }
    }
};
</script>

<style scoped>
/* Custom font classes */
.font-nexa-bold {
    font-family: 'Nexa-Bold', sans-serif;
    font-weight: 700;
}

.font-nexa-xbold {
    font-family: 'Nexa-XBold', sans-serif;
    font-weight: 400;
}

.text-verde-oscuro {
    color: #1c4f4a;
}

.bg-turquesa {
    background-color: #007e93;
}
</style>
