<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AlertWrapper from "@/Components/Admin/AlertWrapper.vue";

const props = defineProps({
    sections: Object,
    contentBySection: Object,
});

const getSectionIcon = (section) => {
    const icons = {
        hero: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
        schools: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        transform: 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z',
        experiences: 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
        courses: 'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9',
        payment_form: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        faq: 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        footer: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
    };
    return icons[section] || 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';
};

const handleEditSection = (section) => {
    // Redirigir a la gestión de colegios si es la sección schools
    if (section === 'schools') {
        router.get(route("admin.schools.index"));
    } else if (section === 'faq') {
        // Redirigir al nuevo mantenedor de FAQs
        router.get(route("admin.maintainer.faqs.index"));
    } else if (section === 'payment_form') {
        // Redirigir al mantenedor del formulario de pago
        router.get(route("admin.maintainer.payment-form.index"));
    } else {
        router.get(route("admin.site-content.edit", section));
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Contenido del Sitio" />

        <AlertWrapper ref="alertWrapper" />

        <div class="p-6">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Contenido del Sitio</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Administra el contenido editable de la página principal (tipo WordPress ACF)
                </p>
            </div>

            <!-- Sections Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="(label, section) in sections"
                    :key="section"
                    class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow cursor-pointer"
                    @click="handleEditSection(section)"
                >
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getSectionIcon(section)" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3 class="text-lg font-medium text-gray-900">{{ label }}</h3>
                                <p class="text-sm text-gray-500">
                                    {{ contentBySection[section]?.items?.length || 0 }} elementos
                                </p>
                            </div>
                            <div class="ml-4">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Instrucciones</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Selecciona una sección para editar su contenido. Puedes modificar textos, descripciones e imágenes que se muestran en la página principal del sitio.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
