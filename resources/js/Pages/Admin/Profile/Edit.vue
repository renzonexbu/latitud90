<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    user: {
        type: Object,
        required: true,
    },
});

const user = useForm({
    name: props.user.name,
});
</script>

<template>
    <AdminLayout>
        <Head title="Profile" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">Información del Perfil</h2>

                            <p class="mt-1 text-sm text-gray-600">
                                Actualiza la información de tu perfil y tu dirección de correo electrónico.
                            </p>
                        </header>

                        <form @submit.prevent="user.patch(route('admin.profile.update'))" class="mt-6 space-y-6">
                            <div>
                                <InputLabel for="name" value="Nombre" />

                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="user.name"
                                    required
                                    autofocus
                                    autocomplete="name"
                                />

                                <InputError class="mt-2" :message="user.errors.name" />
                            </div>

                            <div>
                                <InputLabel for="email" value="Email" />

                                <input
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full bg-gray-100 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 disabled:opacity-50 rounded-md shadow-sm"
                                    :value="props.user.email"
                                    disabled
                                    readonly
                                />
                            </div>

                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="user.processing">Guardar</PrimaryButton>

                                <Transition
                                    enter-active-class="transition ease-in-out"
                                    enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out"
                                    leave-to-class="opacity-0"
                                >
                                    <p v-if="user.recentlySuccessful" class="text-sm text-gray-600">Guardado.</p>
                                </Transition>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
