<template>
  <div class="w-full" v-if="pillars && pillarsArray.length > 0">
    <h2 class="text-[#434343] text-left font-nexa text-[20px] leading-[28px] font-bold self-stretch">
      Pilares
    </h2>
    <p class="text-[#5B5B5B] text-left font-nexa text-sm leading-[18px] font-normal h-[26px] self-stretch mt-2">
      En el desarrollo de nuestros programas incorporamos de manera transversal {{ pillarsArray.length }} pilares fundamentales:
    </p>
    
    <!-- Pilares Grid -->
    <div class="flex flex-row gap-[70px] items-start justify-start self-stretch flex-shrink-0 relative mt-4">
      <!-- Columna Izquierda -->
      <div class="flex flex-col gap-[20px] items-end justify-start flex-shrink-0 w-[237px] relative">
        <!-- Aventura -->
        <div v-if="hasPillar('aventura')" class="flex flex-row gap-[18px] items-center justify-start self-stretch flex-shrink-0 relative">
          <div class="flex-shrink-0 w-[45px] h-[45px] relative overflow-visible">
            <svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" viewBox="0 0 45 45" fill="none">
              <path d="M24.3866 28.6217L28.356 22.7342M28.356 22.7342L28.8266 22.0404C29.1296 21.5812 29.5505 21.2119 30.0453 20.9711C30.54 20.7303 31.0903 20.6269 31.6387 20.6718C32.187 20.7166 32.7133 20.9081 33.1623 21.226C33.6113 21.544 33.9666 21.9768 34.191 22.4792L39.0585 32.8685C40.0635 35.0098 38.5522 37.4998 36.2516 37.4998H8.74911C6.45411 37.4998 4.94286 35.0248 5.93474 32.8835L16.8247 9.33353C17.0678 8.78327 17.4669 8.31634 17.9725 7.99049C18.4782 7.66464 19.0683 7.49417 19.6698 7.50015C20.2713 7.50614 20.8579 7.68832 21.357 8.02417C21.8561 8.36001 22.2458 8.83479 22.4779 9.38978L28.356 22.7342Z" stroke="#1A4B75" stroke-width="2.8125" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="text-[#1a4b75] text-left font-nexa-regular text-base leading-[22px] font-normal relative">
            Aventura
          </div>
        </div>
        
        <!-- Entretenimiento -->
        <div v-if="hasPillar('entretenimiento')" class="flex flex-row gap-[18px] items-center justify-start self-stretch flex-shrink-0 relative">
          <div class="flex-shrink-0 w-[45px] h-[45px] relative overflow-visible">
            <svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" viewBox="0 0 45 45" fill="none">
              <path d="M27.6938 39.375C29.7994 30.6581 32.355 27.9394 39.375 25.5675C31.9987 23.0775 29.7 20.055 27.6938 11.7619C25.5863 20.4787 23.0288 23.1956 16.0088 25.5675C23.3775 28.0556 25.6894 31.0987 27.6919 39.375M12.1144 20.9663C13.0744 16.4288 14.9306 14.3813 18.6037 13.2956C14.9306 12.21 13.0744 10.1625 12.1162 5.625C11.1975 9.96563 9.46312 12.1594 5.625 13.2938C9.29812 14.3813 11.1544 16.4288 12.1144 20.9663Z" stroke="#1A4B75" stroke-width="2.8125" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="text-[#1a4b75] text-left font-nexa-regular text-base leading-[22px] font-normal relative">
            Entretenimiento
          </div>
        </div>
      </div>
      
      <!-- Columna Derecha -->
      <div class="flex flex-col gap-[20px] items-start justify-start flex-shrink-0 w-[234px] relative">
        <!-- Educación -->
        <div v-if="hasPillar('educacion')" class="flex flex-row gap-[18px] items-center justify-start self-stretch flex-shrink-0 relative">
          <div class="flex-shrink-0 w-[45px] h-[45px] relative overflow-visible">
            <svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" viewBox="0 0 45 45" fill="none">
              <path d="M22.4998 37.5L22.4998 18.375C22.4998 15.18 22.7005 11.655 19.4286 9.98813C18.2248 9.375 16.6498 9.375 13.4998 9.375L8.62484 9.375C6.30734 9.375 5.62484 10.1944 5.62484 12.375L5.62484 28.875C5.62484 29.94 5.55734 31.1156 6.64859 31.6706C7.04984 31.875 7.57297 31.875 8.62109 31.875H13.9311C18.388 31.875 20.0436 33.8175 22.498 37.5C24.9542 33.8175 26.6098 31.875 31.0667 31.875H36.3786C37.4267 31.875 37.9517 31.875 38.3511 31.6706C39.4405 31.1156 39.3748 29.9381 39.3748 28.875V12.375C39.3748 10.1925 38.6905 9.375 36.3748 9.375L31.4998 9.375C28.3498 9.375 26.773 9.375 25.5692 9.98813C22.2955 11.655 22.498 15.18 22.498 18.375" stroke="#1A4B75" stroke-width="2.8125" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="text-[#1a4b75] text-left font-nexa-regular text-base leading-[22px] font-normal relative">
            Educación
          </div>
        </div>
        
        <!-- Seguridad -->
        <div v-if="hasPillar('seguridad')" class="flex flex-row gap-[18px] items-center justify-start self-stretch flex-shrink-0 relative">
          <div class="flex-shrink-0 w-[45px] h-[45px] relative overflow-visible">
            <svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" viewBox="0 0 45 45" fill="none">
              <path d="M19.2334 21.0787L21.1721 23.2931C21.3503 23.4975 21.6709 23.4806 21.8284 23.2631L25.7659 17.8125" stroke="#1A4B75" stroke-width="2.8125" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M21.5007 39.0392C21.7879 39.2579 22.139 39.3763 22.5 39.3763C22.8611 39.3763 23.2121 39.2579 23.4994 39.0392C26.565 36.7386 35.625 29.2255 35.625 20.6567V11.413C35.627 11.1745 35.5404 10.9438 35.3822 10.7655C35.2239 10.5871 35.0051 10.4738 34.7682 10.4474C31.0138 10.0628 27.4155 8.74392 24.3019 6.61111L23.1582 5.82923C22.9645 5.69621 22.735 5.625 22.5 5.625C22.2651 5.625 22.0356 5.69621 21.8419 5.82923L20.6982 6.61111C17.5846 8.74392 13.9863 10.0628 10.2319 10.4474C9.99493 10.4738 9.77616 10.5871 9.61789 10.7655C9.45963 10.9438 9.3731 11.1745 9.37503 11.413V20.6567C9.37503 29.2255 18.435 36.7386 21.5007 39.0355" stroke="#1A4B75" stroke-width="2.8125" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="text-[#1a4b75] text-left font-nexa-regular text-base leading-[22px] font-normal relative">
            Seguridad
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProgramPillars',
  props: {
    pillars: {
      type: String,
      default: null
    }
  },
  computed: {
    pillarsArray() {
      if (!this.pillars) return [];
      
      // Convertir el string de pilares en un array
      // Asumiendo que los pilares están separados por comas, puntos, o líneas
      const pillars = this.pillars
        .split(/[,.\n]/)
        .map(pillar => pillar.trim().toLowerCase())
        .filter(pillar => pillar.length > 0);
      
      return pillars;
    }
  },
  methods: {
    hasPillar(pillarName) {
      return this.pillarsArray.some(pillar => 
        pillar.includes(pillarName) || 
        pillar.includes(pillarName.replace('ó', 'o')) ||
        pillar.includes(pillarName.replace('ó', 'o').replace('á', 'a'))
      );
    }
  }
};
</script> 