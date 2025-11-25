<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import AlertWrapper from "@/Components/Admin/AlertWrapper.vue";

const props = defineProps({
    schools: Array,
    sectionContent: Array,
});

const showAddForm = ref(false);
const editingSchool = ref(null);
const uploadingLogo = ref(false);

// Form para el contenido de la sección (título)
const sectionForm = useForm({
    contents: props.sectionContent.map(item => ({
        id: item.id,
        key: item.key,
        value: item.value,
        default_value: item.default_value,
        use_default: item.use_default ?? true,
        label: item.label,
    })),
});

const newSchoolForm = useForm({
    name: '',
    logo: '',
    is_active: true,
});

const editForm = useForm({
    name: '',
    logo: '',
    is_active: true,
});

const handleSaveSectionContent = () => {
    sectionForm.put(route('admin.schools.update-section-content'), {
        preserveScroll: true,
    });
};

const handleAddSchool = () => {
    newSchoolForm.post(route('admin.schools.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddForm.value = false;
            newSchoolForm.reset();
        },
    });
};

const startEdit = (school) => {
    editingSchool.value = school.id;
    editForm.name = school.name;
    editForm.logo = school.logo || '';
    editForm.is_active = school.is_active;
};

const cancelEdit = () => {
    editingSchool.value = null;
    editForm.reset();
};

const handleUpdateSchool = (school) => {
    editForm.put(route('admin.schools.update', school.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingSchool.value = null;
            editForm.reset();
        },
    });
};

const handleDeleteSchool = (school) => {
    if (confirm(`¿Estás seguro de eliminar "${school.name}"?`)) {
        router.delete(route('admin.schools.destroy', school.id), {
            preserveScroll: true,
        });
    }
};

const handleLogoUpload = async (event, isEdit = false) => {
    const file = event.target.files[0];
    if (!file) return;

    uploadingLogo.value = true;

    const formData = new FormData();
    formData.append('logo', file);

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        const response = await fetch(route('admin.schools.upload-logo'), {
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

        if (data.success) {
            if (isEdit) {
                editForm.logo = data.path;
            } else {
                newSchoolForm.logo = data.path;
            }
        } else {
            alert(`Error: ${data.message}`);
        }
    } catch (error) {
        console.error('Error uploading logo:', error);
        alert('Error al subir el logo');
    } finally {
        uploadingLogo.value = false;
    }
};

const getLogoUrl = (logo) => {
    if (!logo) return null;
    if (logo.startsWith('http')) return logo;
    if (logo.startsWith('/')) return logo;
    return `/storage/${logo}`;
};
</script>

<template>
    <AdminLayout>
        <Head title="Gestión de Colegios" />

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
                        <span>Colegios</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">Gestión de Colegios</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ schools.length }} colegios registrados</p>
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
                        @click="showAddForm = !showAddForm"
                        class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors"
                    >
                        {{ showAddForm ? 'Cancelar' : 'Agregar Colegio' }}
                    </button>
                </div>
            </div>

            <!-- Section Content (Title) -->
            <div v-if="sectionForm.contents.length > 0" class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Contenido de la Sección</h3>
                <div v-for="(item, index) in sectionForm.contents" :key="item.id" class="mb-4 last:mb-0">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">{{ item.label || item.key }}</label>
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input
                                v-model="item.use_default"
                                type="checkbox"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span :class="item.use_default ? 'text-blue-600 font-medium' : ''">Por defecto</span>
                        </label>
                    </div>
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
                <div class="mt-4 flex justify-end">
                    <button
                        type="button"
                        @click="handleSaveSectionContent"
                        :disabled="sectionForm.processing"
                        class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors disabled:opacity-50"
                    >
                        <span v-if="sectionForm.processing">Guardando...</span>
                        <span v-else>Guardar Título</span>
                    </button>
                </div>
            </div>

            <!-- Add New School Form -->
            <div v-if="showAddForm" class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Agregar nuevo colegio</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del colegio</label>
                        <input
                            v-model="newSchoolForm.name"
                            type="text"
                            placeholder="Ej: Colegio San Pedro"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                        <div class="flex items-center gap-2">
                            <label class="cursor-pointer">
                                <span class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors inline-block text-sm">
                                    {{ uploadingLogo ? 'Subiendo...' : 'Subir logo' }}
                                </span>
                                <input
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    @change="(e) => handleLogoUpload(e, false)"
                                    :disabled="uploadingLogo"
                                />
                            </label>
                            <img
                                v-if="newSchoolForm.logo"
                                :src="getLogoUrl(newSchoolForm.logo)"
                                class="h-10 w-auto object-contain"
                                @error="(e) => e.target.style.display = 'none'"
                            />
                        </div>
                    </div>
                    <div class="flex items-end">
                        <button
                            type="button"
                            @click="handleAddSchool"
                            :disabled="newSchoolForm.processing || !newSchoolForm.name"
                            class="w-full px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors disabled:opacity-50"
                        >
                            Agregar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Schools Section Title -->
            <h3 class="text-lg font-medium text-gray-900 mb-4">Colegios del Carrusel</h3>

            <!-- Schools Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div
                    v-for="school in schools"
                    :key="school.id"
                    class="bg-white rounded-lg shadow-sm border border-gray-200 p-4"
                    :class="{ 'opacity-50': !school.is_active }"
                >
                    <!-- View Mode -->
                    <div v-if="editingSchool !== school.id">
                        <div class="flex items-center justify-center h-20 mb-3 bg-gray-50 rounded-lg">
                            <img
                                v-if="school.logo"
                                :src="getLogoUrl(school.logo)"
                                :alt="school.name"
                                class="max-h-16 max-w-full object-contain"
                                @error="(e) => e.target.style.display = 'none'"
                            />
                            <span v-else class="text-gray-400 text-sm">Sin logo</span>
                        </div>
                        <h3 class="font-medium text-gray-900 text-sm text-center mb-3 line-clamp-2">
                            {{ school.name }}
                        </h3>
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs px-2 py-1 rounded-full"
                                :class="school.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                            >
                                {{ school.is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                            <div class="flex gap-1">
                                <button
                                    @click="startEdit(school)"
                                    class="p-1.5 text-gray-500 hover:text-teal-600 hover:bg-teal-50 rounded transition-colors"
                                    title="Editar"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button
                                    @click="handleDeleteSchool(school)"
                                    class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                                    title="Eliminar"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Mode -->
                    <div v-else class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Nombre</label>
                            <input
                                v-model="editForm.name"
                                type="text"
                                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-teal-500 focus:border-teal-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Logo</label>
                            <div class="flex items-center gap-2">
                                <label class="cursor-pointer">
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs hover:bg-gray-200 transition-colors inline-block">
                                        Cambiar
                                    </span>
                                    <input
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="(e) => handleLogoUpload(e, true)"
                                    />
                                </label>
                                <img
                                    v-if="editForm.logo"
                                    :src="getLogoUrl(editForm.logo)"
                                    class="h-8 w-auto object-contain"
                                    @error="(e) => e.target.style.display = 'none'"
                                />
                            </div>
                        </div>
                        <div>
                            <label class="flex items-center gap-2 text-xs">
                                <input
                                    v-model="editForm.is_active"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                />
                                Activo
                            </label>
                        </div>
                        <div class="flex gap-2">
                            <button
                                @click="cancelEdit"
                                class="flex-1 px-2 py-1.5 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors"
                            >
                                Cancelar
                            </button>
                            <button
                                @click="handleUpdateSchool(school)"
                                :disabled="editForm.processing"
                                class="flex-1 px-2 py-1.5 text-xs bg-teal-600 text-white rounded hover:bg-teal-700 transition-colors disabled:opacity-50"
                            >
                                Guardar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="schools.length === 0" class="text-center py-12 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay colegios</h3>
                <p class="mt-1 text-sm text-gray-500">Comienza agregando colegios al carrusel.</p>
                <button
                    type="button"
                    @click="showAddForm = true"
                    class="mt-4 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors"
                >
                    Agregar Colegio
                </button>
            </div>

            <!-- Footer with Back Button -->
            <div class="mt-6 flex justify-start">
                <Link
                    :href="route('admin.site-content.index')"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver al menú
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
