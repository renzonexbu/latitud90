<template>
  <section class="py-12 bg-[#F9F9F9]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <h2 class="testimonials-title mb-12">
        Nuestros usuarios dicen
      </h2>

      <div class="relative">
        <!-- Flecha izquierda -->
        <button
          @click="prevTestimonial"
          class="absolute left-0 top-1/2 transform -translate-y-1/2 z-10 bg-white rounded-full p-2 shadow-md text-yellow-500 hover:bg-yellow-50 transition-colors">
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

        <!-- Testimonios -->
        <div class="max-w-4xl mx-auto overflow-hidden">
          <div 
            class="flex transition-transform duration-500 ease-in-out"
            :style="{ transform: `translateX(-${currentIndex * 100}%)` }">
            <div 
              v-for="(testimonial, index) in testimonials" 
              :key="index"
              class="w-full flex-shrink-0">
              <div class="testimonial-card bg-white rounded-lg p-8 text-center">
                <p class="testimonial-text mb-8">
                  <span class="quote-mark">"</span>{{ testimonial.text }}<span class="quote-mark">"</span>
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Información del autor para mobile -->
        <div class="mobile-author-section max-w-4xl mx-auto mt-16 overflow-hidden">
          <div 
            class="flex transition-transform duration-500 ease-in-out"
            :style="{ transform: `translateX(-${currentIndex * 100}%)` }">
            <div 
              v-for="(testimonial, index) in testimonials" 
              :key="index"
              class="w-full flex-shrink-0">
              <div class="text-center">
                <!-- Línea decorativa -->
                <svg 
                  xmlns="http://www.w3.org/2000/svg" 
                  width="332" 
                  height="4" 
                  viewBox="0 0 332 4" 
                  fill="none"
                  class="decorative-line-mobile">
                  <path 
                    fill-rule="evenodd" 
                    clip-rule="evenodd" 
                    d="M0.5 0.769531H331.5V3.76953H0.5V0.769531Z" 
                    fill="#007E93"/>
                </svg>
                <div class="mobile-author-name">{{ testimonial.author }}</div>
                <div class="mobile-author-affiliation">{{ testimonial.affiliation }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Flecha derecha -->
        <button
          @click="nextTestimonial"
          class="absolute right-0 top-1/2 transform -translate-y-1/2 z-10 bg-white rounded-full p-2 shadow-md text-yellow-500 hover:bg-yellow-50 transition-colors">
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

      <!-- Footer con nombres (solo desktop) -->
      <div class="desktop-footer max-w-4xl mx-auto mt-8">
        <div class="names">
          <div 
            v-for="(testimonial, index) in testimonials"
            :key="index"
            class="information">
            <div class="tittle">
              <!-- Línea decorativa -->
              <svg 
                xmlns="http://www.w3.org/2000/svg" 
                width="332" 
                height="4" 
                viewBox="0 0 332 4" 
                fill="none"
                class="decorative-line"
                :class="currentIndex === index ? 'active-line' : 'inactive-line'">
                <path 
                  fill-rule="evenodd" 
                  clip-rule="evenodd" 
                  d="M0.5 0.769531H331.5V3.76953H0.5V0.769531Z" 
                  :fill="currentIndex === index ? '#007E93' : '#c7c7c7'"/>
              </svg>
              <div 
                class="title"
                :class="currentIndex === index ? 'title3' : 'title'">
                {{ testimonial.author }}
              </div>
              <div 
                class="title2"
                :class="currentIndex === index ? 'title4' : 'title2'">
                {{ testimonial.affiliation }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Indicadores de paginación -->
      <div class="flex justify-center mt-8 gap-4">
        <button
          v-for="(testimonial, index) in testimonials"
          :key="index"
          @click="goToTestimonial(index)"
          class="h-3 w-3 rounded-full flex items-center justify-center border border-yellow-500 p-1 transition-all duration-300"
          :class="currentIndex === index ? 'bg-yellow-500' : 'bg-transparent hover:bg-yellow-200'">
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
            text: "Nunca pensé que este viaje me iba a marcar tanto, superó las expectativas y descubrí nuevas formas de conocerme y conocer",
            author: "Alfonso Izquierdo",
            affiliation: "Colegio San Esteban Diácono"
          },
          {
            text: "Queremos agradecer por todo el trabajo realizado, la voluntad de aceptar las dificultades, la energía para buscar soluciones y que, finalmente se transformaron en LA FELICIDAD DE TODOS L@S CHIC@S que viajaron y que llegaron MUCHÍSIMO más unidos de lo que salieron a esta aventura, fue una experiencia extraordinaria y los Guías que los acompañaron fueron UN LUJO. Por mi lado GRACIAS TOTALES",
            author: "Vladimir Obon",
            affiliation: "Colegio Pedro de Valdivia Las Condes"
          }
        ],
        currentIndex: 0,
        autoplayInterval: null
      };
    },
    mounted() {
      this.startAutoplay();
    },
    beforeUnmount() {
      this.stopAutoplay();
    },
    methods: {
      prevTestimonial() {
        this.currentIndex =
          (this.currentIndex - 1 + this.testimonials.length) %
          this.testimonials.length;
        this.restartAutoplay();
      },
      nextTestimonial() {
        this.currentIndex = (this.currentIndex + 1) % this.testimonials.length;
        this.restartAutoplay();
      },
      goToTestimonial(index) {
        this.currentIndex = index;
        this.restartAutoplay();
      },
      startAutoplay() {
        this.autoplayInterval = setInterval(() => {
          this.nextTestimonial();
        }, 5000);
      },
      stopAutoplay() {
        if (this.autoplayInterval) {
          clearInterval(this.autoplayInterval);
          this.autoplayInterval = null;
        }
      },
      restartAutoplay() {
        this.stopAutoplay();
        this.startAutoplay();
      }
    },
    computed: {
      currentTestimonial() {
        return this.testimonials[this.currentIndex];
      }
    }
  };
</script>

<style scoped>
.names,
.names * {
  box-sizing: border-box;
}
.names {
  display: flex;
  flex-direction: row;
  row-gap: 273px;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
  align-content: flex-start;
  flex-shrink: 0;
  position: relative;
}
.information {
  display: flex;
  flex-direction: row;
  gap: 16px;
  align-items: center;
  justify-content: flex-start;
  flex-shrink: 0;
  position: relative;
}
.tittle {
  display: flex;
  flex-direction: column;
  gap: 16px;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  position: relative;
}
.title {
  color: var(--colores-neutro-gris-3, #c7c7c7);
  text-align: left;
  font-family: var(--cuerpo-de-texto-xl-font-family, "Nexa-Bold", sans-serif);
  font-size: var(--cuerpo-de-texto-xl-font-size, 18px);
  line-height: var(--cuerpo-de-texto-xl-line-height, 22px);
  font-weight: var(--cuerpo-de-texto-xl-font-weight, 700);
  position: relative;
  display: flex;
  align-items: flex-end;
  justify-content: flex-start;
}
.title2 {
  color: var(--colores-neutro-gris-3, #c7c7c7);
  text-align: left;
  font-family: var(--cuerpo-de-texto-l-font-family, "Nexa-Regular", sans-serif);
  font-size: var(--cuerpo-de-texto-l-font-size, 16px);
  line-height: var(--cuerpo-de-texto-l-line-height, 22px);
  font-weight: var(--cuerpo-de-texto-l-font-weight, 400);
  position: relative;
  display: flex;
  align-items: flex-end;
  justify-content: flex-start;
}
.title3 {
  color: var(--colores-op2-turquesa, #007e93);
  text-align: left;
  font-family: var(--cuerpo-de-texto-xl-font-family, "Nexa-Bold", sans-serif);
  font-size: var(--cuerpo-de-texto-xl-font-size, 18px);
  line-height: var(--cuerpo-de-texto-xl-line-height, 22px);
  font-weight: var(--cuerpo-de-texto-xl-font-weight, 700);
  position: relative;
  display: flex;
  align-items: flex-end;
  justify-content: flex-start;
}
.title4 {
  color: var(--colores-op2-turquesa, #007e93);
  text-align: left;
  font-family: var(--cuerpo-de-texto-l-font-family, "Nexa-Regular", sans-serif);
  font-size: var(--cuerpo-de-texto-l-font-size, 16px);
  line-height: var(--cuerpo-de-texto-l-line-height, 22px);
  font-weight: var(--cuerpo-de-texto-l-font-weight, 400);
  position: relative;
  display: flex;
  align-items: flex-end;
  justify-content: flex-start;
}

.testimonials-title {
  color: var(--Colores-OP2-Turquesa, #007E93);
  text-align: center;
  font-family: Nexa;
  font-size: var(--Numeros-Titulo, 30px);
  font-style: normal;
  font-weight: 800;
  line-height: 36px;
}

.testimonial-text {
  color: var(--Colores-Neutro-Negro, #434343);
  font-family: Nexa;
  font-size: var(--Numeros-Cuerpo-de-texto-XL, 18px);
  font-style: normal;
  font-weight: 400;
  line-height: 22px;
  text-align: center;
}

.quote-mark {
  color: var(--Colores-OP2-Turquesa, #007E93);
  font-weight: bold;
}

.decorative-line {
  margin-bottom: 16px;
}

.active-line {
  opacity: 1;
}

.inactive-line {
  opacity: 0.5;
}

.testimonial-card {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Estilos para mobile */
@media (max-width: 768px) {
  .testimonials-title {
    color: var(--Colores-OP2-Turquesa, #007E93) !important;
    text-align: center !important;
    font-family: Nexa !important;
    font-size: var(--Numeros-Subtitulo, 18px) !important;
    font-style: normal !important;
    font-weight: 700 !important;
    line-height: 28px !important;
  }

  .testimonial-text {
    color: var(--Colores-Neutro-Negro, #434343) !important;
    font-family: Nexa !important;
    font-size: var(--Numeros-Cuerpo-de-texto-M, 12px) !important;
    font-style: normal !important;
    font-weight: 400 !important;
    line-height: 18px !important;
    text-align: center !important;
  }

  .mobile-author-section {
    display: block !important;
    margin-top: 64px !important;
  }

  .mobile-author-name {
    color: var(--Colores-OP2-Turquesa, #007E93) !important;
    font-family: Nexa !important;
    font-size: var(--Numeros-Cuerpo-de-texto-XL, 16px) !important;
    font-style: normal !important;
    font-weight: 700 !important;
    line-height: 22px !important;
    margin-top: 16px !important;
    margin-bottom: 8px !important;
  }

  .mobile-author-affiliation {
    color: var(--Colores-OP2-Turquesa, #007E93) !important;
    font-family: Nexa !important;
    font-size: var(--Numeros-Cuerpo-de-texto-L, 14px) !important;
    font-style: normal !important;
    font-weight: 400 !important;
    line-height: 22px !important;
  }

  .decorative-line-mobile {
    margin-bottom: 16px !important;
    display: block !important;
    margin-left: auto !important;
    margin-right: auto !important;
  }

  .testimonial-card {
    box-shadow: none !important;
  }

  .desktop-footer {
    display: none !important;
  }
}

/* Estilos para desktop */
@media (min-width: 769px) {
  .mobile-author-section {
    display: none !important;
  }
}
</style>
