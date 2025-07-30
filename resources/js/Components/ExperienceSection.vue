<template>
  <section class="py-12 bg-[#F9F9F9]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <h2 class="text-3xl font-bold text-center text-teal-600 mb-12">
        Nuestras experiencias
      </h2>

      <div class="relative flex justify-center">
        <!-- Flecha izquierda -->
        <button
          class="absolute left-0 top-1/2 transform -translate-y-1/2 z-10 bg-white rounded-full p-2 shadow-md text-teal-600 hover:bg-teal-50">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 19l-7-7 7-7" />
          </svg>
        </button>

        <!-- Carrusel de imágenes -->
        <div
          class="flex overflow-x-auto gap-4 pb-4 hide-scrollbar items-center justify-center min-h-[300px]">
          <div class="flex-shrink-0 w-64 sm:w-80 flex items-center">
            <div class="overflow-hidden rounded-lg relative w-full">
              <img
                src="../../images/experiences/patagonia.png"
                alt="Ciclismo"
                class="w-full h-full object-cover" />
            </div>
          </div>

          <div class="flex-shrink-0 w-64 sm:w-80 flex items-center">
            <div class="overflow-hidden rounded-lg relative w-full">
              <img
                src="../../images/experiences/waterfall.png"
                alt="Cascada"
                class="w-full h-full object-cover" />
            </div>
          </div>

          <div class="flex-shrink-0 w-64 sm:w-80 flex items-center">
            <div class="overflow-hidden rounded-lg relative w-full">
              <img
                src="../../images/experiences/mountain.png"
                alt="Montaña"
                class="w-full h-full object-cover" />
            </div>
          </div>
        </div>

        <!-- Flecha derecha -->
        <button
          class="absolute right-0 top-1/2 transform -translate-y-1/2 z-10 bg-white rounded-full p-2 shadow-md text-teal-600 hover:bg-teal-50">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>

      <!-- Indicadores de paginación -->
      <div class="flex justify-center mt-4 gap-1">
        <button class="h-2 w-2 rounded-full bg-teal-500"></button>
        <button class="h-2 w-2 rounded-full bg-gray-300"></button>
        <button class="h-2 w-2 rounded-full bg-gray-300"></button>
      </div>
    </div>
  </section>
</template>

<script>
  export default {
    name: "ExperienceSection"
  };
</script>

<style scoped>
  .hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
  }
  .hide-scrollbar::-webkit-scrollbar {
    display: none;
  }
</style>
