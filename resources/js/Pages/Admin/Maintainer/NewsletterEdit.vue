<template>
    <AdminLayout>
        <Head title="Editar Newsletter" />
        
        <div class="p-6">
            <div class="max-w-4xl mx-auto">
                <!-- Header -->
                <MaintainerHeader 
                    subtitle="Editar suscriptor del newsletter"
                    :show-action-button="false"
                />

                <!-- Form -->
                <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 mt-6">
                    <form @submit.prevent="submitForm">
                        <!-- Email Field -->
                        <div class="mb-6">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email
                            </label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="w-full h-[46px] bg-white rounded-[10px] border border-gray-300 px-4 py-2 text-black text-left font-nexa-regular text-[14px] leading-[18px] font-normal outline-none focus:border-turquesa focus:ring-1 focus:ring-turquesa"
                                :class="{ 'border-red-500': errors.email }"
                                required
                            />
                            <div v-if="errors.email" class="mt-1 text-sm text-red-600">
                                {{ errors.email }}
                            </div>
                        </div>

                        <!-- Status Field -->
                        <div class="mb-6">
                            <label for="is_active" class="block text-sm font-medium text-gray-700 mb-2">
                                Estado
                            </label>
                            <select
                                id="is_active"
                                v-model="form.is_active"
                                class="w-full h-[46px] bg-white rounded-[10px] border border-gray-300 px-4 py-2 text-black text-left font-nexa-regular text-[14px] leading-[18px] font-normal outline-none focus:border-turquesa focus:ring-1 focus:ring-turquesa"
                                :class="{ 'border-red-500': errors.is_active }"
                            >
                                <option :value="true">Activo</option>
                                <option :value="false">Inactivo</option>
                            </select>
                            <div v-if="errors.is_active" class="mt-1 text-sm text-red-600">
                                {{ errors.is_active }}
                            </div>
                        </div>

                        <!-- Subscription Date (Read Only) -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Fecha de Suscripción
                            </label>
                            <div class="w-full h-[46px] bg-gray-100 rounded-[10px] border border-gray-300 px-4 py-2 text-gray-600 text-left font-nexa-regular text-[14px] leading-[18px] font-normal">
                                {{ formatDate(newsletter.subscribed_at) }}
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end gap-4 pt-6 border-t border-gray-200">
                            <button
                                type="button"
                                @click="goBack"
                                class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors font-nexa-regular text-[14px]"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="processing"
                                class="px-6 py-2 bg-turquesa hover:bg-turquesa-dark text-white rounded-md transition-colors font-nexa-regular text-[14px] disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ processing ? 'Guardando...' : 'Guardar Cambios' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import { Head, useForm } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import MaintainerHeader from "@/Components/Maintainer/MaintainerHeader.vue";

export default {
    name: "NewsletterEdit",
    components: {
        Head,
        AdminLayout,
        MaintainerHeader,
    },
    props: {
        newsletter: {
            type: Object,
            required: true
        },
        errors: {
            type: Object,
            default: () => ({})
        }
    },
    setup(props) {
        const form = useForm({
            email: props.newsletter.email,
            is_active: props.newsletter.is_active
        });

        return { form };
    },
    data() {
        return {
            processing: false
        };
    },
    methods: {
        submitForm() {
            this.processing = true;
            this.form.put(route('admin.maintainer.newsletter.update', this.newsletter.id), {
                onSuccess: () => {
                    this.processing = false;
                },
                onError: () => {
                    this.processing = false;
                }
            });
        },

        goBack() {
            this.$inertia.visit(route('admin.maintainer.newsletter.index'));
        },

        formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString('es-CL', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
};
</script>
