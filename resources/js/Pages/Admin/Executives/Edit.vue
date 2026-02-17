<template>
    <AdminLayout>
        <Head title="Editar Ejecutivo" />

        <div class="py-6 lg:py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-3xl font-nexa-bold text-verde-oscuro">Editar Ejecutivo</h1>
                        <p class="text-gray-600 mt-2 font-nexa-regular">Modificar los datos del ejecutivo</p>
                    </div>
                    <Link
                        :href="route('admin.executives.index')"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-nexa-bold transition-colors flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Volver
                    </Link>
                </div>

                <!-- Form Card -->
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <form @submit.prevent="submit" class="p-6">
                        <div class="max-w-2xl mx-auto space-y-6">
                            <!-- Code -->
                            <div>
                                <label for="code" class="block text-sm font-nexa-bold text-gray-700 mb-2">
                                    Código *
                                </label>
                                <input
                                    id="code"
                                    v-model="form.code"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent font-nexa-regular"
                                    :class="{ 'border-red-500': form.errors.code }"
                                    placeholder="Ej: SE-001"
                                />
                                <p v-if="form.errors.code" class="mt-1 text-sm text-red-600 font-nexa-regular">
                                    {{ form.errors.code }}
                                </p>
                            </div>

                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-nexa-bold text-gray-700 mb-2">
                                    Nombre del Ejecutivo *
                                </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent font-nexa-regular"
                                    :class="{ 'border-red-500': form.errors.name }"
                                    placeholder="Ingrese el nombre completo"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 font-nexa-regular">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-nexa-bold text-gray-700 mb-2">
                                    Email
                                </label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent font-nexa-regular"
                                    :class="{ 'border-red-500': form.errors.email }"
                                    placeholder="correo@ejemplo.com"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600 font-nexa-regular">
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-sm font-nexa-bold text-gray-700 mb-2">
                                    Teléfono
                                </label>
                                <input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent font-nexa-regular"
                                    :class="{ 'border-red-500': form.errors.phone }"
                                    placeholder="Ej: +56 9 1234 5678"
                                />
                                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600 font-nexa-regular">
                                    {{ form.errors.phone }}
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200">
                                <Link
                                    :href="route('admin.executives.index')"
                                    class="px-6 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 font-nexa-bold transition-colors"
                                >
                                    Cancelar
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-6 py-2 bg-turquesa text-white rounded-lg hover:bg-turquesa-dark font-nexa-bold transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    executive: Object
});

const form = useForm({
    code: props.executive.code || '',
    name: props.executive.name || '',
    email: props.executive.email || '',
    phone: props.executive.phone || ''
});

const toProperCase = (str) => {
    if (!str) return '';
    return str.trim().split(/\s+/).map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join(' ');
};

const submit = () => {
    form.name = toProperCase(form.name);
    form.put(route('admin.executives.update', props.executive.id), {
        preserveScroll: true
    });
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
