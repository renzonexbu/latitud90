<template>
  <div class="w-full bg-white h-20 md:h-24 flex items-center overflow-hidden">
    <!-- Carrusel infinito que cubre todo el ancho -->
    <div 
      class="flex animate-scroll whitespace-nowrap"
      :style="{ width: `${logos.length * 160}px` }"
    >
      <!-- Primera serie de logos -->
      <div 
        v-for="(logo, index) in logos" 
        :key="`first-${index}`"
        class="flex-shrink-0 w-32 md:w-40 h-16 md:h-20 mx-2 md:mx-3 flex items-center justify-center"
      >
        <img 
          :src="logo.image" 
          :alt="logo.name"
          class="max-w-full max-h-full object-contain"
        />
      </div>
      
      <!-- Segunda serie de logos (para el efecto infinito) -->
      <div 
        v-for="(logo, index) in logos" 
        :key="`second-${index}`"
        class="flex-shrink-0 w-32 md:w-40 h-16 md:h-20 mx-2 md:mx-3 flex items-center justify-center"
      >
        <img 
          :src="logo.image" 
          :alt="logo.name"
          class="max-w-full max-h-full object-contain"
        />
      </div>
      
      <!-- Tercera serie de logos para asegurar cobertura completa -->
      <div 
        v-for="(logo, index) in logos" 
        :key="`third-${index}`"
        class="flex-shrink-0 w-32 md:w-40 h-16 md:h-20 mx-2 md:mx-3 flex items-center justify-center"
      >
        <img 
          :src="logo.image" 
          :alt="logo.name"
          class="max-w-full max-h-full object-contain"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

// Logos de empresas con URLs reales
const logos = ref([
  { 
    name: 'Acción Empresa', 
    image: 'https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/07/AccionEmpresa.png'
  },
  { 
    name: 'BAT', 
    image: 'https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/07/BAT.png'
  },
  { 
    name: 'BCI', 
    image: 'https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/07/BCI.png'
  },
  { 
    name: 'Bsale', 
    image: 'https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/07/bsale.png'
  },
  { 
    name: 'Caja Los Héroes', 
    image: 'https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/07/CajalosHeroes.png'
  },
  { 
    name: 'CCU', 
    image: 'https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/07/CCU.png'
  },
  { 
    name: 'Dictuc', 
    image: 'https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/07/Dictuc.png'
  },
  { 
    name: 'Esmax', 
    image: 'https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/07/Esmax.png'
  },
  { 
    name: 'Fundación Iñañez Atkinson', 
    image: 'https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/07/FundacionInanezAtkinson.png'
  },
  { 
    name: 'Fundación Mapocho', 
    image: 'https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/07/FundacionMapocho.png'
  },
  { 
    name: 'Hays', 
    image: 'https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/07/Hays.png'
  },
  { 
    name: 'Herbalife', 
    image: 'https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/07/herbalife.png'
  },
  { 
    name: 'Mastercard', 
    image: 'https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/07/mastercard.png'
  },
  { 
    name: 'Principal', 
    image: 'https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/07/Principal.png'
  },
  { 
    name: 'Security', 
    image: 'https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/07/Security.png'
  },
  { 
    name: 'Soprole', 
    image: 'https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/07/Soprole.png'
  },
  { 
    name: 'YPO', 
    image: 'https://sp-ao.shortpixel.ai/client/to_webp,q_lossy,ret_img/https://latitud90.com/wp-content/uploads/2024/07/YPO.png'
  }
]);
</script>

<style scoped>
@keyframes scroll {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(calc(-100% / 3));
  }
}

.animate-scroll {
  animation: scroll 40s linear infinite;
}

/* Pausar animación al hacer hover */
.animate-scroll:hover {
  animation-play-state: paused;
}

/* Asegurar que el contenedor principal no tenga limitaciones */
.w-full {
  width: 100% !important;
}
</style> 