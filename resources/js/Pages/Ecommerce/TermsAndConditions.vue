<template>
  <div class="min-h-screen bg-gray-50 w-full p-3">
    <!-- Header -->
    <Header class="bg-transparent text-blanco shadow-none"> </Header>

    <!-- Contenido de Términos y Condiciones -->
    <div class="max-w-4xl mx-auto py-8 px-4">
      <div class="bg-white rounded-lg shadow-lg p-8">
        <h1 class="terms-title">Términos y Condiciones</h1>

        <!-- Acordeón de Términos y Condiciones -->
        <div class="space-y-4">
          <div
            v-for="(term, index) in terms"
            :key="term.id"
            class="border border-gray-200 rounded-lg overflow-hidden"
          >
            <button
              @click="toggleAccordion(index)"
              class="w-full px-6 py-4 text-left bg-white hover:bg-gray-50 transition-colors duration-200 flex justify-between items-center"
              :class="{ 'text-teal-600': openAccordion === index, 'text-gray-700': openAccordion !== index }"
            >
              <span class="font-semibold">{{ index + 1 }}. {{ term.title }}</span>
              <svg
                class="w-5 h-5 transition-transform duration-200"
                :class="{ 'rotate-180': openAccordion === index }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            <transition name="accordion" mode="out-in">
              <div
                v-show="openAccordion === index"
                class="accordion-content"
              >
                <p class="text-gray-700 leading-relaxed whitespace-pre-line">{{ term.content }}</p>
              </div>
            </transition>
          </div>

          <!-- Mensaje si no hay términos -->
          <div v-if="terms.length === 0" class="text-center py-8 text-gray-500">
            <p>No hay términos y condiciones disponibles.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <Footer class="rounded-lg"></Footer>
  </div>
</template>

<script>
  import { Head } from "@inertiajs/vue3";
  import { ref } from "vue";
  import Header from "@/Components/Ecommerce/Header.vue";
  import Footer from "@/Components/Ecommerce/Footer.vue";

  export default {
    components: {
      Header,
      Footer,
      Head
    },
    props: {
      terms: {
        type: Array,
        default: () => []
      }
    },
    setup() {
      const openAccordion = ref(0); // El primer acordeón estará abierto por defecto

      const toggleAccordion = (index) => {
        openAccordion.value = openAccordion.value === index ? null : index;
      };

      return {
        openAccordion,
        toggleAccordion
      };
    }
  };
</script>

<style scoped>
  /* Transiciones suaves para el acordeón */
  .transition-transform {
    transition: transform 0.2s ease-in-out;
  }

  .transition-colors {
    transition: color 0.2s ease-in-out, background-color 0.2s ease-in-out;
  }

  /* Estilos del contenido del acordeón */
  .accordion-content {
    padding: 0 1.5rem 1rem 1.5rem;
    background-color: white;
    overflow: hidden;
  }

  /* Transiciones del acordeón */
  .accordion-enter-active,
  .accordion-leave-active {
    transition: all 0.3s ease-in-out;
    max-height: 500px;
    opacity: 1;
  }

  .accordion-enter-from,
  .accordion-leave-to {
    max-height: 0;
    opacity: 0;
    padding-top: 0;
    padding-bottom: 0;
  }

  .accordion-enter-to,
  .accordion-leave-from {
    max-height: 500px;
    opacity: 1;
  }

  /* Estilos del título */
  .terms-title {
    color: var(--Colores-OP2-Turquesa, #007E93);
    font-family: Nexa;
    font-size: var(--Numeros-Subtitulo, 24px);
    font-style: normal;
    font-weight: 700;
    line-height: 28px; /* 116.667% */
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 13px;
    margin-bottom: 2rem;
    text-align: center;
    width: 100%;
  }

  .whitespace-pre-line {
    white-space: pre-line;
  }
</style>
