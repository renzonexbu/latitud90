<template>
  <div class="min-h-screen flex flex-col md:flex-row bg-[#F9F9F9]">
    <!-- Imagen a la izquierda -->
    <div
      class="hidden md:block md:w-1/2 h-screen rounded-l-3xl overflow-hidden">
      <img
        :src="contactImage"
        alt="Naturaleza"
        class="object-cover w-full h-full" />
    </div>
    <!-- Formulario a la derecha -->
    <div
      class="w-full md:w-1/2 flex items-center justify-center bg-[#F9F9F9] rounded-r-3xl p-8 md:p-16">
      <form class="w-full max-w-xl">
        <h2 class="text-4xl font-bold text-[#17939A] mb-2">Contactanos</h2>
        <p class="text-[#17939A] mb-4">
          Escríbenos para cotizar un programa, evento o pedir más información:
        </p>
        <hr class="mb-6" />
        <div class="flex flex-col md:flex-row md:space-x-4 mb-4">
          <div class="flex-1 mb-4 md:mb-0">
            <label class="block text-gray-700 mb-1">Nombre completo *</label>
            <input
              type="text"
              placeholder="Nombre y apellido"
              class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#17939A]" />
          </div>
          <div class="flex-1">
            <label class="block text-gray-700 mb-1">Teléfono*</label>
            <input
              type="text"
              placeholder="+56 9-- --- ---"
              class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#17939A]" />
          </div>
        </div>
        <div class="mb-4">
          <label class="block text-gray-700 mb-1">Email*</label>
          <input
            type="email"
            placeholder="Escriba aquí su email"
            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#17939A]" />
        </div>
        <div class="mb-6">
          <label class="block text-gray-700 mb-1">Mensaje</label>
          <textarea
            placeholder="Escriba aquí su mensaje"
            rows="3"
            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#17939A]"></textarea>
        </div>
        <button
          type="submit"
          class="w-full bg-[#FFB82E] hover:bg-[#e6a826] text-white font-semibold py-3 rounded-full text-lg transition-colors duration-200">
          Enviar mensaje
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
  import contactImage from "@images/image.png";
</script>

<style scoped>
  /* Si necesitas estilos adicionales, agrégalos aquí */
</style>
