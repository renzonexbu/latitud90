<template>
    <AdminLayout>
        <Head title="Gestión de Ejecutivos" />

        <div class="py-6 lg:py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-3xl font-nexa-bold text-verde-oscuro">Ejecutivos</h1>
                        <p class="text-gray-600 mt-2 font-nexa-regular">Gestión de ejecutivos comerciales</p>
                    </div>
                    <div class="flex gap-3">
                        <Link
                            :href="route('admin.courses.index')"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-nexa-bold transition-colors flex items-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Volver a Cursos
                        </Link>
                        <Link
                            :href="route('admin.executives.create')"
                            class="bg-turquesa hover:bg-turquesa-dark text-white px-6 py-2 rounded-lg font-nexa-bold transition-colors flex items-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Nuevo Ejecutivo
                        </Link>
                    </div>
                </div>

                <!-- Success Message -->
                <div v-if="$page.props.flash.success" class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center gap-2 text-green-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ $page.props.flash.success }}
                    </div>
                </div>

                <!-- Error Message -->
                <div v-if="$page.props.flash.error" class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center gap-2 text-red-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        {{ $page.props.flash.error }}
                    </div>
                </div>

                <!-- Search -->
                <div class="mb-4">
                    <div class="relative max-w-md">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Buscar por código o nombre..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent font-nexa-regular"
                        />
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Executives Table -->
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-turquesa">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-nexa-bold text-white uppercase tracking-wider">
                                    Código
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-nexa-bold text-white uppercase tracking-wider">
                                    Nombre
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-nexa-bold text-white uppercase tracking-wider">
                                    Email
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-nexa-bold text-white uppercase tracking-wider">
                                    Estado
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-nexa-bold text-white uppercase tracking-wider">
                                    P-Cursos
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-nexa-bold text-white uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="filteredExecutives.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 font-nexa-regular">
                                    {{ searchQuery ? 'No se encontraron ejecutivos' : 'No hay ejecutivos registrados' }}
                                </td>
                            </tr>
                            <tr v-for="executive in filteredExecutives" :key="executive.id" class="hover:bg-gray-50" :class="{ 'opacity-60': !executive.active }">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-nexa-bold text-verde-oscuro">{{ executive.code || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-nexa-bold text-verde-oscuro">{{ executive.name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-nexa-regular">{{ executive.email || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-nexa-bold rounded-full"
                                        :class="executive.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                    >
                                        {{ executive.active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="px-2 inline-flex text-xs leading-5 font-nexa-bold rounded-full bg-blue-100 text-blue-800">
                                        {{ executive.programs_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex justify-center gap-2">
                                        <button
                                            @click="toggleActive(executive)"
                                            :class="executive.active ? 'text-orange-600 hover:text-orange-900' : 'text-green-600 hover:text-green-900'"
                                            :title="executive.active ? 'Desactivar' : 'Activar'"
                                        >
                                            <svg v-if="executive.active" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                            </svg>
                                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </button>
                                        <Link
                                            :href="route('admin.executives.edit', executive.id)"
                                            class="text-turquesa hover:text-turquesa-dark"
                                            title="Editar"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </Link>
                                        <button
                                            @click="confirmDelete(executive)"
                                            class="text-red-600 hover:text-red-900"
                                            title="Eliminar"
                                            :disabled="executive.programs_count > 0"
                                            :class="{ 'opacity-50 cursor-not-allowed': executive.programs_count > 0 }"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    executives: Array
});

const searchQuery = ref('');

const filteredExecutives = computed(() => {
    if (!searchQuery.value) {
        return props.executives;
    }
    const query = searchQuery.value.toLowerCase();
    return props.executives.filter(executive =>
        (executive.code && executive.code.toLowerCase().includes(query)) ||
        (executive.name && executive.name.toLowerCase().includes(query))
    );
});

const toggleActive = (executive) => {
    const action = executive.active ? 'desactivar' : 'activar';
    const message = executive.active
        ? `¿Estás seguro de desactivar al ejecutivo "${executive.name}"? No aparecerá en los reportes.`
        : `¿Estás seguro de activar al ejecutivo "${executive.name}"?`;

    if (confirm(message)) {
        router.patch(route('admin.executives.toggle-active', executive.id), {}, {
            preserveScroll: true
        });
    }
};

const confirmDelete = (executive) => {
    if (executive.programs_count > 0) {
        alert('No se puede eliminar este ejecutivo porque tiene programas asociados.');
        return;
    }

    if (confirm(`¿Estás seguro de eliminar al ejecutivo "${executive.name}"?`)) {
        router.delete(route('admin.executives.destroy', executive.id), {
            preserveScroll: true
        });
    }
};
</script>

<style scoped>
.font-nexa-bold {
    font-family: 'Nexa-Bold', sans-serif;
    font-weight: 700;
}

.font-nexa-regular {
    font-family: 'Nexa-Regular', sans-serif;
    font-weight: 400;
}

.text-verde-oscuro {
    color: #1c4f4a;
}

.bg-turquesa {
    background-color: #007e93;
}

.hover\:bg-turquesa-dark:hover {
    background-color: #006477;
}

.text-turquesa {
    color: #007e93;
}

.hover\:text-turquesa-dark:hover {
    color: #006477;
}

.focus\:ring-turquesa:focus {
    --tw-ring-color: #007e93;
}
</style>
