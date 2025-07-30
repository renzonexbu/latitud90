<template>
  <section class="py-12 bg-[#F9F9F9]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <h2 class="text-3xl font-bold text-center text-teal-600 mb-12">
        Nuestros usuarios dicen
      </h2>

      <div class="relative">
        <!-- Flecha izquierda -->
        <button
          class="absolute left-0 top-1/2 transform -translate-y-1/2 z-10 bg-white rounded-full p-2 shadow-md text-yellow-500 hover:bg-yellow-50">
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

        <!-- Testimonio -->
        <div class="max-w-2xl mx-auto">
          <div class="bg-white rounded-lg p-6 text-center">
            <p class="text-gray-600 text-sm md:text-base italic mb-6">
              "Queremos agradecer por todo el trabajo realizado, la voluntad de
              aceptar las dificultades, la energía para buscar soluciones y que,
              finalmente se transformaron en LA FELICIDAD DE TODOS LOS CHIC@S
              que viajaron y que llegaron MUCHÍSIMO más unidos de lo que
              salieron a esta aventura; fue una experiencia extraordinaria y los
              Guías que los acompañaron fueron UN LUJO. Por mi lado GRACIAS
              TOTALES"
            </p>

            <div class="border-t pt-4">
              <p class="font-medium text-gray-800">Vladimir Obon</p>
              <p class="text-sm text-gray-500">
                Colegio Pedro de Valdivia Las Condes
              </p>
            </div>
          </div>
        </div>

        <!-- Flecha derecha -->
        <button
          class="absolute right-0 top-1/2 transform -translate-y-1/2 z-10 bg-white rounded-full p-2 shadow-md text-yellow-500 hover:bg-yellow-50">
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
      <div class="flex justify-center mt-8 gap-4">
        <button
          class="h-5 w-5 rounded-full flex items-center justify-center border border-yellow-500 p-1">
          <div class="h-full w-full rounded-full bg-yellow-500"></div>
        </button>
        <button
          class="h-5 w-5 rounded-full flex items-center justify-center border border-yellow-500 p-1">
          <div class="h-full w-full rounded-full bg-transparent"></div>
        </button>
        <button
          class="h-5 w-5 rounded-full flex items-center justify-center border border-yellow-500 p-1">
          <div class="h-full w-full rounded-full bg-transparent"></div>
        </button>
      </div>
    </div>
  </section>
</template>

<script>
  export default {
    name: "TestimonialsSection",
    data() {
      return {
        testimonials: [
          {
            text: '"Queremos agradecer por todo el trabajo realizado, la voluntad de aceptar las dificultades, la energía para buscar soluciones y que, finalmente se transformaron en LA FELICIDAD DE TODOS LOS CHIC@S que viajaron y que llegaron MUCHÍSIMO más unidos de lo que salieron a esta aventura; fue una experiencia extraordinaria y los Guías que los acompañaron fueron UN LUJO. Por mi lado GRACIAS TOTALES"',
            author: "Vladimir Obon",
            affiliation: "Colegio Pedro de Valdivia Las Condes"
          }
        ],
        currentIndex: 0
      };
    },
    methods: {
      prevTestimonial() {
        this.currentIndex =
          (this.currentIndex - 1 + this.testimonials.length) %
          this.testimonials.length;
      },
      nextTestimonial() {
        this.currentIndex = (this.currentIndex + 1) % this.testimonials.length;
      }
    },
    computed: {
      currentTestimonial() {
        return this.testimonials[this.currentIndex];
      }
    }
  };
</script>
