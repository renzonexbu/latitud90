<template>
  <div class="relative w-full">
    <div class="overflow-hidden rounded-3xl">
      <img
        :src="images[current]"
        class="w-full object-cover transition-all duration-500"
        alt="Imagen slider" />
    </div>
    <!-- Botones de navegación -->
    <button
      @click="prev"
      class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white text-[#17939A] rounded-full p-2 shadow-md">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        class="w-6 h-6">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M15 19l-7-7 7-7" />
      </svg>
    </button>
    <button
      @click="next"
      class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white text-[#17939A] rounded-full p-2 shadow-md">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        class="w-6 h-6">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M9 5l7 7-7 7" />
      </svg>
    </button>
  </div>
</template>

<script setup>
  import { ref, onMounted, onUnmounted } from "vue";
  import img1 from "@images/slider1.png";
  import img2 from "@images/slider2.png";
  import img3 from "@images/slider3.png";
  import img4 from "@images/slider4.png";
  import img5 from "@images/slider5.png";

  const images = [img1, img2, img3, img4, img5];
  const current = ref(0);
  let interval = null;

  function next() {
    current.value = (current.value + 1) % images.length;
  }
  function prev() {
    current.value = (current.value - 1 + images.length) % images.length;
  }

  onMounted(() => {
    interval = setInterval(next, 4000); // Cambia cada 4 segundos
  });
  onUnmounted(() => {
    clearInterval(interval);
  });
</script>

<style scoped>
  /* Puedes agregar estilos personalizados aquí */
</style>
