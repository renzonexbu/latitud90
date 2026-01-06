<template>
  <section class="py-12 bg-[#F9F9F9]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <h2 class="text-3xl font-bold text-center text-teal-600 mb-12 mobile-title">
        {{ getContent('titulo', 'Colegios que nos acompañan') }}
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
    props: {
      content: {
        type: Object,
        default: () => ({})
      },
      schools: {
        type: Array,
        default: () => []
      }
    },
    data() {
      return {
        currentSlide: 0,
        interval: null
      };
    },
    computed: {
      activeSchools() {
        // Usar schools del prop desde la base de datos
        if (this.schools && this.schools.length > 0) {
          return this.schools.map(s => ({
            name: s.name,
            logo: s.logo_url || s.logo
          }));
        }
        return [];
      },
      slides() {
        const slides = [];
        // En mobile: 3 colegios por slide, en desktop: 7 colegios por slide
        const itemsPerSlide = 7; // Mantenemos 7 para desktop por defecto

        for (let i = 0; i < this.activeSchools.length; i += itemsPerSlide) {
          const slide = this.activeSchools.slice(i, i + itemsPerSlide);

          // Si el slide no está completo, completarlo con colegios del inicio
          if (slide.length < itemsPerSlide) {
            const remainingItems = itemsPerSlide - slide.length;
            const itemsFromStart = this.activeSchools.slice(0, remainingItems);
            slide.push(...itemsFromStart);
          }

          slides.push(slide);
        }

        return slides;
      }
    },
    methods: {
      getContent(key, defaultValue) {
        return this.content?.[key]?.value || defaultValue;
      },
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
