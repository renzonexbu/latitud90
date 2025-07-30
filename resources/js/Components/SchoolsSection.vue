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
                  class="h-12 w-12 lg:h-16 lg:w-16 object-contain" />
              </div>
              <p class="text-xs text-gray-600 mt-2 text-center">
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
            name: "Colegio Alemán",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_1.jpg"
          },
          {
            name: "Colegio Almenar",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_2.jpg"
          },
          {
            name: "Colegio American British",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_3.jpg"
          },
          {
            name: "Colegio Arrayanes",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_4.jpg"
          },
          {
            name: "Colegio Campanario",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_5.jpg"
          },
          {
            name: "Colegio Carampangue",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_6.jpg"
          },
          {
            name: "The International School",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_6q.jpg"
          },
          {
            name: "Colegio Cumbres",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_8.jpg"
          },
          {
            name: "Colegio La Cruz",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_9.jpg"
          },
          {
            name: "Colegio Verbo Divino",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_10.jpg"
          },
          {
            name: "Colegio Compañía de María",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_11.jpg"
          },
          {
            name: "Colegio Dunalastair",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_12.jpg"
          },
          {
            name: "Colegio Everest",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_14.jpg"
          },
          {
            name: "Colegio Itahue",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_16.jpg"
          },
          {
            name: "Colegio Kilpatrick",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_17.jpg"
          },
          {
            name: "Colegio La Fontaine",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_18.jpg"
          },
          {
            name: "Colegio La Maisonnette",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_19.jpg"
          },
          {
            name: "Colegio Lincoln",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_20.jpg"
          },
          {
            name: "Colegio Los Alerces",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_22.jpg"
          },
          {
            name: "Colegio Los Ceibos",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_23.jpg"
          },
          {
            name: "Colegio Sagrados Corazones",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_24.jpg"
          },
          {
            name: "Colegio Mayflower",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_25.jpg"
          },
          {
            name: "Colegio Mayor",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_26.jpg"
          },
          {
            name: "Colegio Newland",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_27.jpg"
          },
          {
            name: "Colegio Nido de Águilas",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_28.jpg"
          },
          {
            name: "Colegio Nuestra Señora del Camino",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_29.jpg"
          },
          {
            name: "Colegio Orchard",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_30.jpg"
          },
          {
            name: "Colegio Padre Hurtado y Juanita de Los Andes",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_31.jpg"
          },
          {
            name: "Colegio Pedro de Valdivia",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_32.jpg"
          },
          {
            name: "Colegio Pinares",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_35.jpg"
          },
          {
            name: "Colegio Pumahue",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_36.jpg"
          },
          {
            name: "Colegio Redland",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_37.jpg"
          },
          {
            name: "Colegio Saint Johns",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_39.jpg"
          },
          {
            name: "Colegio San Esteban",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_40.jpg"
          },
          {
            name: "Colegio San Felipe",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_41.jpg"
          },
          {
            name: "Colegio San Juan Evangelista",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_42.jpg"
          },
          {
            name: "Colegio San Luis del Alba",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_43.jpg"
          },
          {
            name: "Colegio San Miguel",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_44.jpg"
          },
          {
            name: "Colegio San Nicolás",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_45.jpg"
          },
          {
            name: "Colegio San Pedro Nolasco",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_47.jpg"
          },
          {
            name: "Colegio Santa Cruz",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_49.jpg"
          },
          {
            name: "Colegio Santiago College",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_50.jpg"
          },
          {
            name: "Colegio Santiago Evangelista",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_51.jpg"
          },
          {
            name: "Colegio Santo Domingo",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_52.jpg"
          },
          {
            name: "Colegio Southern Cross",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_53.jpg"
          },
          {
            name: "Colegio Southland",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_54.jpg"
          },
          {
            name: "Colegio Sagrados Corazones Monjas Francesas",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_55.jpg"
          },
          {
            name: "Colegio Sagrados Corazones Padres Franceses",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_56.jpg"
          },
          {
            name: "Colegio St Anne's",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_57.jpg"
          },
          {
            name: "Colegio St Gabriel San Gabriel",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_58.jpg"
          },
          {
            name: "Colegio Teresiano",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_59.jpg"
          },
          {
            name: "Colegio Grange",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_60.jpg"
          },
          {
            name: "Colegio Trebulco",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_62.jpg"
          },
          {
            name: "Colegio Trewhela",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_63.jpg"
          },
          {
            name: "Colegio Santa Úrsula Ursulinas",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_64.jpg"
          },
          {
            name: "Colegio Villa María Academy",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_65.jpg"
          },
          {
            name: "Colegio Wenlock",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_66.jpg"
          },
          {
            name: "Colegio Windsor",
            logo: "https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/05/Lat_90_Logo_Colegio_67.jpg"
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

@media (max-width: 1023px) {
  .mobile-title {
    color: #007E93;
    font-size: 18px;
  }
}
</style>
