<script setup>
  import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
  import DeleteUserForm from "./Partials/DeleteUserForm.vue";
  import UpdatePasswordForm from "./Partials/UpdatePasswordForm.vue";
  import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm.vue";
  import NavLink from "@/Components/NavLink.vue";
  import { Head } from "@inertiajs/vue3";

  defineProps({
    mustVerifyEmail: {
      type: Boolean
    },
    status: {
      type: String
    }
  });
</script>

<template>
  <Head title="Profile" />

  <AuthenticatedLayout>
    <template
      class="bd-trasparent"
      #header>
      <h2 class="font-semibold text-xl text-turquesa leading-tight float-start">
        Perfil
      </h2>
      <NavLink
        :href="route('admin.logout')"
        method="post"
        as="button"
        class="block px-4 py-2 bg-red-700 text-white rounded-xl float-end">
        Cerrar Sesión
      </NavLink>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
          <UpdateProfileInformationForm
            :must-verify-email="mustVerifyEmail"
            :status="status"
            class="max-w-xl" />
        </div>

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
          <UpdatePasswordForm class="max-w-xl" />
        </div>

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
          <DeleteUserForm class="max-w-xl" />
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
