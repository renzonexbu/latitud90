<template>
  <section class="py-12 relative overflow-hidden rounded-lg">
    <div
      class="absolute inset-0 bg-gradient-to-r from-teal-600 to-blue-500 opacity-80"></div>
    <div
      class="absolute inset-0 bg-[url('/resources/images/courses-bg.jpg')] bg-cover bg-center mix-blend-overlay"></div>

    <div
      class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col items-start">
      <div class="text-left mb-8">
        <h2 class="text-5xl font-bold text-white">
          Cursos
          <span class="text-yellow-400 block">de</span>
          <span class="text-white text-5xl">Educación</span>
        </h2>
      </div>

      <div class="flex justify-end w-full">
        <a
          href="#"
          class="bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-md font-medium transition-colors">
          Descubre más en nuestra web
        </a>
      </div>

      <!-- Indicadores circulares -->
      <div class="flex space-x-2 mt-12">
        <button class="h-3 w-3 rounded-full bg-orange-500"></button>
        <button class="h-3 w-3 rounded-full bg-orange-500 opacity-50"></button>
        <button class="h-3 w-3 rounded-full bg-orange-500 opacity-50"></button>
        <button class="h-3 w-3 rounded-full bg-orange-500 opacity-50"></button>
      </div>
    </div>
  </section>
</template>

<script>
  export default {
    name: "CoursesSection"
  };
</script>
