<template>
  <section class="py-12 bg-[#F9F9F9]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <h2 class="text-3xl font-bold text-center text-teal-600 mb-12 mobile-title">
        Colegios que nos acompañan
      </h2>

      <!-- Carousel Container -->
      <div class="relative overflow-hidden">
        <div 
          class="flex transition-transform duration-500 ease-in-out"
          :style="{ transform: `translateX(-${currentSlide * 100}%)` }"
        >
          <div 
            v-for="(slide, slideIndex) in slides" 
            :key="slideIndex"
            class="w-full flex-shrink-0 grid grid-cols-3 lg:grid-cols-7 gap-4 lg:gap-6"
          >
            <div
              v-for="(school, index) in slide"
              :key="`${slideIndex}-${index}`"
              class="flex flex-col items-center"
              :class="index >= 3 ? 'hidden lg:flex' : ''"
            >
              <div
                class="bg-white rounded-full p-2 lg:p-3 shadow-sm flex items-center justify-center h-16 w-16 lg:h-24 lg:w-24">
                <img
                  :src="school.logo"
                  :alt="school.name"
                  class="h-12 w-12 lg:h-16 lg:w-16 object-contain school-logo"
                  :class="{ 'grayscale-logo': school.name === 'Colegio Dunalastair' || school.name === 'Colegio Everest' }" />
              </div>
              <p class="text-xs font-bold text-gray-600 mt-2 text-center">
                {{ school.name }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Indicadores de paginación -->
      <div class="flex justify-center mt-8 gap-1 lg:gap-2">
        <button 
          v-for="(slide, index) in slides" 
          :key="index"
          @click="goToSlide(index)"
          class="h-2 w-2 lg:h-3 lg:w-3 rounded-full transition-all duration-300"
          :class="currentSlide === index ? 'bg-[#FBBD51]' : 'bg-gray-300'"
        ></button>
      </div>
    </div>
  </section>
</template>

<script>
  export default {
    name: "SchoolsSection",
    data() {
      return {
        currentSlide: 0,
        interval: null,
        schools: [
          {
            name: "Alianza Francesa - Viña del Mar",
            logo: "/images/schools/Alianza Francesa - Viña del Mar.png"
          },
          {
            name: "British High School",
            logo: "/images/schools/British High School.png"
          },
          {
            name: "Colegio Aleman de Chicureo",
            logo: "/images/schools/Colegio Aleman de Chicureo.png"
          },
          {
            name: "Colegio Aleman de Santiago",
            logo: "/images/schools/Colegio Aleman de Santiago.png"
          },
          {
            name: "colegio altamira",
            logo: "/images/schools/colegio altamira.png"
          },
          {
            name: "Colegio Arrayanes - San Fernando",
            logo: "/images/schools/Colegio Arrayanes - San Fernando.png"
          },
          {
            name: "Colegio Cahuala-Castro",
            logo: "/images/schools/Colegio Cahuala-Castro.png"
          },
          {
            name: "Colegio Cambridge College - Providencia",
            logo: "/images/schools/Colegio Cambridge College - Providencia.png"
          },
          {
            name: "Colegio Campanario",
            logo: "/images/schools/Colegio Campanario.png"
          },
          {
            name: "colegio carampangue",
            logo: "/images/schools/colegio carampangue.png"
          },
          {
            name: "Colegio Cordillera",
            logo: "/images/schools/Colegio Cordillera.png"
          },
          {
            name: "Colegio Cumbres",
            logo: "/images/schools/Colegio Cumbres.png"
          },
          {
            name: "Colegio Dunalastair",
            logo: "/images/schools/Colegio Dunalastair.jpeg"
          },
          {
            name: "Colegio Everest",
            logo: "/images/schools/Colegio Everest.png"
          },
          {
            name: "Colegio Highlands",
            logo: "/images/schools/Colegio Highlands.png"
          },
          {
            name: "Colegio Ingles de Talca",
            logo: "/images/schools/Colegio Ingles de Talca.png"
          },
          {
            name: "Colegio Itahue - Concepción",
            logo: "/images/schools/Colegio Itahue - Concepción.png"
          },
          {
            name: "Colegio Kilpatrick",
            logo: "/images/schools/Colegio Kilpatrick.png"
          },
          {
            name: "Colegio Kimen Montessori",
            logo: "/images/schools/Colegio Kimen Montessori.png"
          },
          {
            name: "Colegio La Cruz-Rancagua",
            logo: "/images/schools/Colegio La Cruz-Rancagua.png"
          },
          {
            name: "Colegio La maisonnete",
            logo: "/images/schools/Colegio La maisonnete.png"
          },
          {
            name: "Colegio Los Alerces",
            logo: "/images/schools/Colegio Los Alerces.png"
          },
          {
            name: "Colegio Mariano de Schoenstatt",
            logo: "/images/schools/Colegio Mariano de Schoenstatt.png"
          },
          {
            name: "Colegio Mayor de Peñalolén",
            logo: "/images/schools/Colegio Mayor de Peñalolén.png"
          },
          {
            name: "Colegio Nido de Aguilas",
            logo: "/images/schools/Colegio Nido de Aguilas.png"
          },
          {
            name: "Colegio Padre Hurtado y Juanita de los Andes",
            logo: "/images/schools/Colegio Padre Hurtado y Juanita de los Andes.png"
          },
          {
            name: "Colegio Pedro de Valdivia",
            logo: "/images/schools/Colegio Pedro de Valdivia.png"
          },
          {
            name: "Colegio Pinares - Concepción",
            logo: "/images/schools/Colegio Pinares - Concepción.png"
          },
          {
            name: "Colegio Pucalán Montessori",
            logo: "/images/schools/Colegio Pucalán Montessori.png"
          },
          {
            name: "colegio saint george",
            logo: "/images/schools/colegio saint george.png"
          },
          {
            name: "Colegio San Esteban Diacono",
            logo: "/images/schools/Colegio San Esteban Diacono.png"
          },
          {
            name: "Colegio San Felipe Diacono",
            logo: "/images/schools/Colegio San Felipe Diacono.png"
          },
          {
            name: "COLEGIO SAN JUAN EVANGELISTA",
            logo: "/images/schools/COLEGIO SAN JUAN EVANGELISTA.png"
          },
          {
            name: "COLEGIO SAN LUIS DE ALBA - VALDIVIA",
            logo: "/images/schools/COLEGIO SAN LUIS DE ALBA - VALDIVIA.png"
          },
          {
            name: "COLEGIO SAN MIGUEL ARCANGEL",
            logo: "/images/schools/COLEGIO SAN MIGUEL ARCANGEL.png"
          },
          {
            name: "Colegio san nicolas de myra",
            logo: "/images/schools/Colegio san nicolas de myra.png"
          },
          {
            name: "Colegio San Pedro de Nolasco",
            logo: "/images/schools/Colegio San Pedro de Nolasco.png"
          },
          {
            name: "COLEGIO SANTA URSULA DE VITACURA",
            logo: "/images/schools/COLEGIO SANTA URSULA DE VITACURA.png"
          },
          {
            name: "Colegio SSCC de Apoquindo",
            logo: "/images/schools/Colegio SSCC de Apoquindo.png"
          },
          {
            name: "Colegio St John's-Concepción",
            logo: "/images/schools/Colegio St John's-Concepción.png"
          },
          {
            name: "Colegio Suizo",
            logo: "/images/schools/Colegio Suizo.png"
          },
          {
            name: "Colegio TEO",
            logo: "/images/schools/Colegio TEO.png"
          },
          {
            name: "Liceo Manuel de Salas",
            logo: "/images/schools/Liceo Manuel de Salas.png"
          },
          {
            name: "Lincoln International Academy",
            logo: "/images/schools/Lincoln International Academy.png"
          },
          {
            name: "Orchard College - Curicó",
            logo: "/images/schools/Orchard College - Curicó.png"
          },
          {
            name: "Redland School",
            logo: "/images/schools/Redland School.png"
          },
          {
            name: "SAINT GABRIEL SCHOOL",
            logo: "/images/schools/SAINT GABRIEL SCHOOL.png"
          },
          {
            name: "santiago college",
            logo: "/images/schools/santiago college.png"
          },
          {
            name: "Scuola Italiana",
            logo: "/images/schools/Scuola Italiana.png"
          },
          {
            name: "SOUTHERN CROSS SCHOOL",
            logo: "/images/schools/SOUTHERN CROSS SCHOOL.png"
          },
          {
            name: "St Gaspar College",
            logo: "/images/schools/St Gaspar College.png"
          },
          {
            name: "ST JOHNS VILLA ACADEMY",
            logo: "/images/schools/ST JOHNS VILLA ACADEMY.png"
          },
          {
            name: "The Craighouse School",
            logo: "/images/schools/The Craighouse School.png"
          },
          {
            name: "The Grange School",
            logo: "/images/schools/The Grange School.png"
          },
          {
            name: "The Newland School",
            logo: "/images/schools/The Newland School.png"
          },
          {
            name: "The Southland School",
            logo: "/images/schools/The Southland School.png"
          },
          {
            name: "The Trewhelas School-Chicureo",
            logo: "/images/schools/The Trewhelas School-Chicureo.png"
          },
          {
            name: "Trebulco School",
            logo: "/images/schools/Trebulco School.png"
          },
          {
            name: "Verbo Divino Chicureo",
            logo: "/images/schools/Verbo Divino Chicureo.png"
          },
          {
            name: "VERBO DIVINO",
            logo: "/images/schools/VERBO DIVINO.png"
          },
          {
            name: "VILLA MARIA",
            logo: "/images/schools/VILLA MARIA.png"
          },
          {
            name: "WENLOCK",
            logo: "/images/schools/WENLOCK.png"
          }
        ]
      };
    },
    computed: {
      slides() {
        const slides = [];
        // En mobile: 3 colegios por slide, en desktop: 7 colegios por slide
        const itemsPerSlide = 7; // Mantenemos 7 para desktop por defecto
        
        for (let i = 0; i < this.schools.length; i += itemsPerSlide) {
          const slide = this.schools.slice(i, i + itemsPerSlide);
          
          // Si el slide no está completo, completarlo con colegios del inicio
          if (slide.length < itemsPerSlide) {
            const remainingItems = itemsPerSlide - slide.length;
            const itemsFromStart = this.schools.slice(0, remainingItems);
            slide.push(...itemsFromStart);
          }
          
          slides.push(slide);
        }
        
        return slides;
      }
    },
    methods: {
      nextSlide() {
        this.currentSlide = (this.currentSlide + 1) % this.slides.length;
      },
      goToSlide(index) {
        this.currentSlide = index;
        this.resetInterval();
      },
      startInterval() {
        this.interval = setInterval(() => {
          this.nextSlide();
        }, 3000);
      },
      resetInterval() {
        if (this.interval) {
          clearInterval(this.interval);
        }
        this.startInterval();
      }
    },
    mounted() {
      this.startInterval();
    },
    beforeUnmount() {
      if (this.interval) {
        clearInterval(this.interval);
      }
    }
  };
</script>

<style scoped>
.mobile-title {
  font-family: 'Nexa', sans-serif;
  font-weight: 700;
  line-height: 28px;
}

.school-logo {
  opacity: 0.7;
  transition: opacity 0.3s ease, filter 0.3s ease, transform 0.3s ease;
}

.school-logo:hover {
  opacity: 1;
}

.grayscale-logo {
  filter: grayscale(100%);
  opacity: 0.7;
  transform: scale(0.8);
}

.grayscale-logo:hover {
  filter: grayscale(0%);
  opacity: 1;
  transform: scale(1);
}

@media (max-width: 1023px) {
  .mobile-title {
    color: #007E93;
    font-size: 18px;
  }
}
</style>
