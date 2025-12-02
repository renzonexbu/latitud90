<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
    content: {
        type: Object,
        default: () => ({})
    }
});

// Configuración de campos con sus valores por defecto
const fieldsConfig = {
    title: {
        label: 'Título principal',
        type: 'text',
        default_value: 'Seleccione la forma de pago',
    },
    subtitle: {
        label: 'Subtítulo / Descripción',
        type: 'textarea',
        default_value: '¡Selecciona la forma de pago que mejor se adapte a ti, total o en cuotas! Para cualquier consulta, no dudes en escribirnos por WhatsApp',
    },
    total_payment_title: {
        label: 'Título opción "Pago Total"',
        type: 'text',
        default_value: 'Pago total',
    },
    subscription_title: {
        label: 'Título opción "Suscripción"',
        type: 'text',
        default_value: 'Suscripción',
    },
    khipu_warning: {
        label: 'Mensaje de advertencia Khipu',
        type: 'textarea',
        default_value: '⚠️ Importante: la primera transferencia a una cuenta nueva tiene un límite bancario de $250.000. Si el monto supera este valor, escríbanos a pagos@latitud90.com para recibir un link de pago.',
    },
};

// Crear array de items para el formulario
const createFormItems = () => {
    return Object.keys(fieldsConfig).map(key => ({
        key,
        label: fieldsConfig[key].label,
        type: fieldsConfig[key].type,
        value: props.content?.[key]?.value || fieldsConfig[key].default_value,
        default_value: fieldsConfig[key].default_value,
        use_default: props.content?.[key]?.use_default ?? true,
    }));
};

const form = useForm({
    items: createFormItems(),
});

const handleSubmit = () => {
    form.post(route('admin.maintainer.payment-form.update'), {
        preserveScroll: true,
    });
};

const getTypeLabel = (type) => {
    const types = {
        text: 'Texto corto',
        textarea: 'Texto largo',
    };
    return types[type] || type;
};
</script>

<template>
    <AdminLayout>
        <Head title="Formulario de Pago" />

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                        <Link :href="route('admin.site-content.index')" class="hover:text-teal-600">
                            Contenido del Sitio
                        </Link>
                        <span>/</span>
                        <span>Formulario de Pago</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">Editar Formulario de Pago</h1>
                </div>
                <div class="flex gap-3">
                    <Link
                        :href="route('admin.site-content.index')"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Volver
                    </Link>
                    <button
                        type="button"
                        @click="handleSubmit"
                        :disabled="form.processing"
                        class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors disabled:opacity-50"
                    >
                        <span v-if="form.processing">Guardando...</span>
                        <span v-else>Guardar Cambios</span>
                    </button>
                </div>
            </div>

            <!-- Mensaje de éxito -->
            <div v-if="$page.props.flash?.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
                {{ $page.props.flash.success }}
            </div>

            <!-- Content Items -->
            <div class="space-y-4">
                <div
                    v-for="(item, index) in form.items"
                    :key="item.key"
                    class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"
                >
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="font-medium text-gray-900">{{ item.label }}</h3>
                            <p class="text-sm text-gray-500">
                                Key: <code class="bg-gray-100 px-1 rounded">{{ item.key }}</code>
                                <span class="mx-2">|</span>
                                Tipo: {{ getTypeLabel(item.type) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 text-sm text-gray-600">
                                <input
                                    v-model="item.use_default"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                />
                                <span :class="item.use_default ? 'text-blue-600 font-medium' : ''">
                                    Por defecto
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Text Input -->
                    <div v-if="item.type === 'text'">
                        <div v-if="item.use_default && item.default_value" class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-sm text-blue-700">{{ item.default_value }}</p>
                            <p class="text-xs text-blue-500 mt-1">Valor por defecto activo</p>
                        </div>
                        <input
                            v-else
                            v-model="item.value"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500"
                        />
                    </div>

                    <!-- Textarea Input -->
                    <div v-else-if="item.type === 'textarea'">
                        <div v-if="item.use_default && item.default_value" class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-sm text-blue-700 whitespace-pre-wrap">{{ item.default_value }}</p>
                            <p class="text-xs text-blue-500 mt-1">Valor por defecto activo</p>
                        </div>
                        <textarea
                            v-else
                            v-model="item.value"
                            rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="mt-6 flex justify-end gap-3">
                <Link
                    :href="route('admin.site-content.index')"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
                >
                    Cancelar
                </Link>
                <button
                    type="button"
                    @click="handleSubmit"
                    :disabled="form.processing"
                    class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors disabled:opacity-50"
                >
                    <span v-if="form.processing">Guardando...</span>
                    <span v-else>Guardar Cambios</span>
                </button>
            </div>
        </div>
    </AdminLayout>
</template>
