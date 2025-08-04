<script setup>
  import ApplicationLogo from "@/Components/Icons/ApplicationLogo.vue";
  import { Link } from "@inertiajs/vue3";
  import backgroundImage from "../../images/admin/background.png";
</script>

<template>
  <div
    class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0"
    :style="`background-image: url('${backgroundImage}'); background-size: cover; background-position: center; background-repeat: no-repeat;`">

    <div
      class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-2xl">
      <div class="flex-row text-center justify-center mb-6">
        <Link
          href="/"
          class="flex justify-center">
          <ApplicationLogo class="w-20 h-20 fill-current text-gray-500" />
        </Link>
        <slot name="header">
          <div v-if="$page.url === '/login'">
            <h1 class="text-turquesa text-2xl font-bold mt-3">
              Bienvenido al portal administrativo
            </h1>
            <p class="text-turquesa">
              Solamente puede ingresar personal de Lat90
            </p>
          </div>
          <div v-else-if="$page.url === '/register'">
            <h1 class="text-turquesa text-2xl font-bold mt-3">
              Crear cuenta Administrador
            </h1>
            <p class="text-turquesa">
              Solamente puede ingresar personal de Lat90
            </p>
          </div>
        </slot>
      </div>

      <div class="">
        <slot />
      </div>
    </div>
  </div>
</template>
