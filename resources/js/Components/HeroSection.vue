<template>
  <div class="relative h-[80vh] rounded-3xl overflow-hidden mx-4 my-4">
    <div
      class="absolute inset-0 bg-[url('/resources/images/dashboard.png')] bg-cover bg-center rounded-3xl">
      <!-- Overlay para la imagen de fondo -->
      <div class="absolute inset-0 bg-black opacity-30 rounded-3xl"></div>
    </div>

    <!-- Esferas decorativas -->
    <div class="absolute right-0 top-1/2 transform -translate-y-1/2 flex flex-col">
      <div class="w-[70px] h-[98px] relative">
        <svg width="103" height="103" viewBox="0 0 52 104" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="51.5" cy="51.8203" r="51.5" transform="rotate(-90 51.5 51.8203)" fill="#FBBD51"/>
        </svg>
      </div>
      <div class="w-[70px] h-[98px] relative">
        <svg width="103" height="103" viewBox="0 0 52 104" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="51.5" cy="51.8203" r="51.5" transform="rotate(-90 51.5 51.8203)" fill="#FBBD51"/>
        </svg>
      </div>
      <div class="w-[70px] h-[98px] relative">
        <svg width="103" height="103" viewBox="0 0 52 104" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="51.5" cy="51.8203" r="51.5" transform="rotate(-90 51.5 51.8203)" fill="#FBBD51"/>
        </svg>
      </div>
      <div class="w-[70px] h-[98px] relative">
        <svg width="103" height="103" viewBox="0 0 52 104" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="51.5" cy="51.8203" r="51.5" transform="rotate(-90 51.5 51.8203)" fill="#FBBD51"/>
        </svg>
      </div>
    </div>

    <div class="relative h-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col justify-center text-left">
      <h1
        class="text-white text-3xl md:text-5xl lg:text-6xl font-extrabold leading-tight">
        Bienvenido<span
          class="block md:inline text-2xl md:text-4xl lg:text-5xl text-amber-400 font-medium ml-0 md:ml-2">
          al portal</span
        >
      </h1>

      <h2 class="text-white md:pl-4 text-2xl md:text-4xl font-light mb-6">
        <span class="text-amber-400">de pago</span>
        <span class="font-bold">Latitud 90.</span>
      </h2>

      <div class="max-w-lg my-8">
        <div
          class="flex flex-col sm:flex-row items-center justify-center md:justify-start relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Ingrese Rut del alumno"
            class="w-full px-6 py-3 rounded-full border-0 shadow-lg focus:ring-2 focus:ring-teal-500"
            @keypress.enter="performSearch" />
          <button
            @click="performSearch"
            class="mt-3 sm:mt-0 sm:absolute sm:top-1/2 sm:right-2 sm:transform sm:-translate-y-1/2 bg-teal-500 hover:bg-teal-600 text-white font-semibold py-2 px-6 rounded-full transition-colors duration-200">
            Buscar programa
          </button>
        </div>
      </div>

      <!-- Indicadores del proceso con iconos -->
      <div
        class="flex flex-col md:flex-row items-start space-y-2 md:space-y-0 md:space-x-6 mt-8 text-white text-sm">
        <div class="flex items-center">
          <div class="bg-teal-500 rounded-full p-1 mr-2">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-4 w-4"
              viewBox="0 0 20 20"
              fill="currentColor">
              <path
                fill-rule="evenodd"
                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                clip-rule="evenodd" />
            </svg>
          </div>
          <span>Ingrese el número Rut del alumno</span>
        </div>

        <div class="flex items-center">
          <div class="bg-teal-500 rounded-full p-1 mr-2">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-4 w-4"
              viewBox="0 0 20 20"
              fill="currentColor">
              <path
                fill-rule="evenodd"
                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                clip-rule="evenodd" />
            </svg>
          </div>
          <span>Seleccione el programa a pagar</span>
        </div>

        <div class="flex items-center">
          <div class="bg-teal-500 rounded-full p-1 mr-2">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-4 w-4"
              viewBox="0 0 20 20"
              fill="currentColor">
              <path
                fill-rule="evenodd"
                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                clip-rule="evenodd" />
            </svg>
          </div>
          <span>Seleccione el método de pago y boleta</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
  import { ref } from "vue";
  import { router } from "@inertiajs/vue3";

  const searchQuery = ref("");

  const performSearch = () => {
    if (searchQuery.value.trim()) {
      router.get("/buscar-viajes", {
        rut: searchQuery.value
      });
    }
  };
</script>

<style scoped>
  /* Estilos adicionales si son necesarios */
</style>
