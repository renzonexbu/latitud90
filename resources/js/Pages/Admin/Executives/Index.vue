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

                <!-- Executives Table -->
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-turquesa">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-nexa-bold text-white uppercase tracking-wider">
                                    Nombre
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-nexa-bold text-white uppercase tracking-wider">
                                    Email
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-nexa-bold text-white uppercase tracking-wider">
                                    Teléfono
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
                            <tr v-if="executives.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 font-nexa-regular">
                                    No hay ejecutivos registrados
                                </td>
                            </tr>
                            <tr v-for="executive in executives" :key="executive.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-nexa-bold text-verde-oscuro">{{ executive.name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-nexa-regular">{{ executive.email || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-nexa-regular">{{ executive.phone || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="px-2 inline-flex text-xs leading-5 font-nexa-bold rounded-full bg-blue-100 text-blue-800">
                                        {{ executive.programs_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex justify-center gap-2">
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
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    executives: Array
});

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
</style>
