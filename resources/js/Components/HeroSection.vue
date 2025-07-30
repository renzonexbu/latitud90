<template>
  <div class="relative h-[80vh] rounded-3xl overflow-hidden mx-4 my-4">
    <div
      class="absolute inset-0 bg-[url('/resources/images/dashboard.png')] bg-cover bg-center rounded-3xl">
      <!-- Overlay para la imagen de fondo -->
      <div class="absolute inset-0 bg-black opacity-30 rounded-3xl"></div>
    </div>

    <!-- Esferas decorativas -->
    <div class="absolute right-0 top-1/2 transform -translate-y-1/2 flex flex-col hidden md:flex">
      <div class="w-[69px] h-[99px] relative">
        <svg width="103" height="103" viewBox="0 0 52 104" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="51.5" cy="51.8203" r="51.5" transform="rotate(-90 51.5 51.8203)" fill="#FBBD51"/>
        </svg>
      </div>
      <div class="w-[69px] h-[99px] relative">
        <svg width="103" height="103" viewBox="0 0 52 104" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="51.5" cy="51.8203" r="51.5" transform="rotate(-90 51.5 51.8203)" fill="#FBBD51"/>
        </svg>
      </div>
      <div class="w-[69px] h-[99px] relative">
        <svg width="103" height="103" viewBox="0 0 52 104" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="51.5" cy="51.8203" r="51.5" transform="rotate(-90 51.5 51.8203)" fill="#FBBD51"/>
        </svg>
      </div>
      <div class="w-[69px] h-[99px] relative">
        <svg width="103" height="103" viewBox="0 0 52 104" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="51.5" cy="51.8203" r="51.5" transform="rotate(-90 51.5 51.8203)" fill="#FBBD51"/>
        </svg>
      </div>
    </div>

    <!-- Esferas decorativas Mobile -->
    <div class="absolute left-0 right-0 bottom-0 flex flex-row justify-center md:hidden">
      <div class="mobile-circle-item"></div>
      <div class="mobile-circle-item"></div>
      <div class="mobile-circle-item"></div>
      <div class="mobile-circle-item"></div>
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
            placeholder="Ingresar Rut del viajero"
            class="w-full px-6 py-3 rounded-full border-0 shadow-lg focus:ring-2 focus:ring-teal-500"
            @keypress.enter="performSearch" />
          <button
            @click="performSearch"
            class="mt-3 sm:mt-0 sm:absolute sm:top-1/2 sm:right-2 sm:transform sm:-translate-y-1/2 bg-teal-500 hover:bg-teal-600 text-white font-semibold py-2 px-6 rounded-full transition-colors duration-200 mobile-search-btn">
            <!-- Ícono de lupa para mobile con fondo circular verde -->
            <div class="md:hidden w-8 h-8 bg-teal-500 rounded-full flex items-center justify-center">
              <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="15" height="16" viewBox="0 0 15 16" fill="none">
                <path d="M10.6212 11.0002L12.5189 12.8979M11.9194 7.66075C11.9194 8.88631 11.4325 10.0617 10.5659 10.9283C9.69932 11.7949 8.52395 12.2818 7.29838 12.2818C6.07281 12.2818 4.89744 11.7949 4.03083 10.9283C3.16422 10.0617 2.67737 8.88631 2.67737 7.66075C2.67737 6.43518 3.16422 5.2598 4.03083 4.3932C4.89744 3.52659 6.07281 3.03973 7.29838 3.03973C8.52395 3.03973 9.69932 3.52659 10.5659 4.3932C11.4325 5.2598 11.9194 6.43518 11.9194 7.66075Z" stroke="white" stroke-width="0.924202" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <!-- Texto para desktop -->
            <span class="hidden md:inline">Buscar programa</span>
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
  
  /* Color de fondo para toda la sección */
  .relative.h-\[80vh\].rounded-3xl.overflow-hidden.mx-4.my-4 {
    background-color: #F9F9F9;
  }
  
  /* Estilos específicos para mobile */
  @media (max-width: 768px) {
    /* Centrar lupa dentro del input en mobile */
    .mobile-search-btn {
      position: absolute !important;
      right: 8px !important;
      top: 19% !important;
      transform: none !important;
      background: transparent !important;
      padding: 0 !important;
      width: auto !important;
      height: auto !important;
      margin: 0 !important;
    }
    
    /* Contenedor interno de lupa */
    .md\\:hidden.w-8.h-8.bg-teal-500.rounded-full.flex.items-center.justify-center {
      width: 32px !important;
      height: 32px !important;
      background: #007e93 !important;
      border-radius: 50% !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
    }

    /* Componente mobile circles */
    .mobile-circle-item {
      width: 77.927px !important;
      height: 36.4635px !important;
      background: #FBBD51 !important;
      border-radius: 77.927px 77.927px 0 0 !important;
      flex-shrink: 0 !important;
      margin: 0 2px !important;
    }

    /* Ajustar distribución del navbar mobile */
    .absolute.left-0.right-0.bottom-0.flex.flex-row.justify-center.md\\:hidden {
      padding: 0 20px !important;
      gap: 8px !important;
    }

    /* Centrar logo SVG verticalmente */
    .capa-1 {
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
    }

    .capa-1 svg {
      align-self: center !important;
    }
  }
</style>
