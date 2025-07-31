<template>
  <div>
    <!-- Header -->
    <Header
      :logo="true"
      class="bg-blanco text-gris-1 relative w-[96%] rounded-2xl mx-auto" />

    <!-- Contenido principal -->
    <main>
      <Stepper
        :step="step || 1"
        :tittle="tittle"
        class="mt-5" />
      <slot></slot>
    </main>

    <!-- Footer -->
    <Footer></Footer>
  </div>
</template>

<script setup>
  import Header from "@/Components/Ecommerce/Header.vue";
  import Stepper from "@/Components/Stepper.vue";
  import { defineProps } from "vue";
  import Footer from "@/Components/Ecommerce/Footer.vue";

  const props = defineProps({
    step: {
      type: Number,
      default: 1
    },
    tittle: {
      type: String,
      default: "Selecciona el método de pago"
    }
  });
</script>
