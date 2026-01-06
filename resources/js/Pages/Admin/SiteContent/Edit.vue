<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import AlertWrapper from "@/Components/Admin/AlertWrapper.vue";
import FocalPointSelector from "@/Components/Admin/FocalPointSelector.vue";

const props = defineProps({
    section: String,
    sectionLabel: String,
    contents: Array,
    types: Object,
});

const form = useForm({
    contents: props.contents.map(item => ({
        id: item.id,
        key: item.key,
        type: item.type,
        value: item.value,
        default_value: item.default_value,
        use_default: item.use_default ?? true,
        label: item.label,
        order: item.order,
        is_active: item.is_active,
        focal_point_mobile_x: item.focal_point_mobile_x ?? 50,
        focal_point_mobile_y: item.focal_point_mobile_y ?? 50,
        focal_point_desktop_x: item.focal_point_desktop_x ?? 50,
        focal_point_desktop_y: item.focal_point_desktop_y ?? 50,
    })),
});

const uploadingImage = ref(null);

const handleSubmit = () => {
    form.put(route('admin.site-content.update', props.section), {
        preserveScroll: true,
    });
};

const handleDeleteItem = (item) => {
    if (confirm('¿Estás seguro de eliminar este contenido?')) {
        router.delete(route('admin.site-content.destroy', item.id), {
            preserveScroll: true,
        });
    }
};

const handleImageUpload = async (event, index) => {
    const file = event.target.files[0];
    if (!file) return;

    uploadingImage.value = index;

    const formData = new FormData();
    formData.append('image', file);
    formData.append('section', props.section);

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
            || document.head.querySelector('meta[name="csrf-token"]')?.content;

        const response = await fetch(route('admin.site-content.upload-image'), {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await response.json();

        if (!response.ok) {
            console.error('Upload validation error:', data);
            alert(`Error al subir imagen: ${data.message || 'Error desconocido'}\n${JSON.stringify(data.errors || {})}`);
            return;
        }

        if (data.success) {
            form.contents[index].value = data.path;
        } else {
            console.error('Upload failed:', data.message);
            alert(`Error: ${data.message}`);
        }
    } catch (error) {
        console.error('Error uploading image:', error);
        alert('Error al subir la imagen. Revisa la consola para más detalles.');
    } finally {
        uploadingImage.value = null;
    }
};

const getImagePreview = (item) => {
    if (!item.value) return null;
    // URL absoluta
    if (item.value.startsWith('http')) return item.value;
    // Rutas que ya tienen prefix correcto (public)
    if (item.value.startsWith('/home_images/') || item.value.startsWith('/storage/')) {
        return item.value;
    }
    // Ruta relativa a storage (para imágenes subidas)
    if (item.value.startsWith('site-content/')) {
        return `/storage/${item.value}`;
    }
    // Para otras rutas, intentar como ruta directa
    return item.value.startsWith('/') ? item.value : `/${item.value}`;
};

const getTypeLabel = (type) => {
    return props.types[type] || type;
};
</script>

<template>
    <AdminLayout>
        <Head :title="`Editar ${sectionLabel}`" />

        <AlertWrapper ref="alertWrapper" />

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                        <Link :href="route('admin.site-content.index')" class="hover:text-teal-600">
                            Contenido del Sitio
                        </Link>
                        <span>/</span>
                        <span>{{ sectionLabel }}</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">Editar {{ sectionLabel }}</h1>
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

            <!-- Content Items -->
            <div class="space-y-4">
                <div
                    v-for="(item, index) in form.contents"
                    :key="item.id || index"
                    class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"
                >
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="font-medium text-gray-900">{{ item.label || item.key }}</h3>
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

                    <!-- HTML Editor -->
                    <div v-else-if="item.type === 'html'">
                        <div v-if="item.use_default && item.default_value" class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="text-sm text-blue-700" v-html="item.default_value"></div>
                            <p class="text-xs text-blue-500 mt-1">Valor por defecto activo</p>
                        </div>
                        <template v-else>
                            <textarea
                                v-model="item.value"
                                rows="6"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500 font-mono text-sm"
                                placeholder="Puedes usar HTML aquí..."
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500">Puedes usar etiquetas HTML como &lt;strong&gt;, &lt;em&gt;, &lt;br&gt;, etc.</p>
                        </template>
                    </div>

                    <!-- Image Input -->
                    <div v-else-if="item.type === 'image'" class="space-y-4">
                        <!-- Modo por defecto -->
                        <div v-if="item.use_default && item.default_value" class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-start gap-4">
                                <img
                                    :src="getImagePreview({ value: item.default_value })"
                                    class="w-32 h-32 object-cover rounded-lg border border-blue-200"
                                    @error="(e) => e.target.style.display = 'none'"
                                />
                                <div class="flex-1">
                                    <p class="text-sm text-blue-700 break-all">{{ item.default_value }}</p>
                                    <p class="text-xs text-blue-500 mt-1">Valor por defecto activo</p>
                                </div>
                            </div>
                        </div>
                        <!-- Modo edición -->
                        <template v-else>
                            <div v-if="item.value" class="flex items-start gap-4">
                                <img
                                    :src="getImagePreview(item)"
                                    class="w-32 h-32 object-cover rounded-lg border border-gray-200"
                                    @error="(e) => e.target.style.display = 'none'"
                                />
                                <div class="flex-1">
                                    <p class="text-sm text-gray-600 break-all">{{ item.value }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <label class="cursor-pointer">
                                    <span class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors inline-block">
                                        {{ uploadingImage === index ? 'Subiendo...' : 'Subir imagen' }}
                                    </span>
                                    <input
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="(e) => handleImageUpload(e, index)"
                                        :disabled="uploadingImage === index"
                                    />
                                </label>
                                <span class="text-sm text-gray-500">o</span>
                                <input
                                    v-model="item.value"
                                    type="text"
                                    placeholder="URL o ruta de la imagen"
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500"
                                />
                            </div>

                            <!-- Focal Point Selector -->
                            <div v-if="item.value" class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <h4 class="text-sm font-medium text-gray-900 mb-3">
                                    🎯 Configurar puntos focales para esta imagen
                                </h4>
                                <FocalPointSelector
                                    :image-url="getImagePreview(item)"
                                    v-model:mobile-x="item.focal_point_mobile_x"
                                    v-model:mobile-y="item.focal_point_mobile_y"
                                    v-model:desktop-x="item.focal_point_desktop_x"
                                    v-model:desktop-y="item.focal_point_desktop_y"
                                />
                            </div>
                        </template>
                    </div>
                </div>

            </div>

            <!-- Footer Actions -->
            <div v-if="form.contents.length > 0" class="mt-6 flex justify-end gap-3">
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
