<template>
  <div class="w-full bg-white h-[74px] flex items-center overflow-hidden">
    <!-- Carrusel infinito que cubre todo el ancho -->
    <div 
      class="flex animate-scroll whitespace-nowrap"
      :style="{ width: `${logos.length * 200}px` }"
    >
      <!-- Primera serie de logos -->
      <div 
        v-for="(logo, index) in logos" 
        :key="`first-${index}`"
        class="flex-shrink-0 w-48 h-12 mx-4 flex items-center justify-center"
      >
        <div class="text-gray-600 text-center">
          <div class="text-sm font-semibold">{{ logo.name }}</div>
          <div class="text-xs text-gray-400">{{ logo.description }}</div>
        </div>
      </div>
      
      <!-- Segunda serie de logos (para el efecto infinito) -->
      <div 
        v-for="(logo, index) in logos" 
        :key="`second-${index}`"
        class="flex-shrink-0 w-48 h-12 mx-4 flex items-center justify-center"
      >
        <div class="text-gray-600 text-center">
          <div class="text-sm font-semibold">{{ logo.name }}</div>
          <div class="text-xs text-gray-400">{{ logo.description }}</div>
        </div>
      </div>
      
      <!-- Tercera serie de logos para asegurar cobertura completa -->
      <div 
        v-for="(logo, index) in logos" 
        :key="`third-${index}`"
        class="flex-shrink-0 w-48 h-12 mx-4 flex items-center justify-center"
      >
        <div class="text-gray-600 text-center">
          <div class="text-sm font-semibold">{{ logo.name }}</div>
          <div class="text-xs text-gray-400">{{ logo.description }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

// Logos de ejemplo - puedes reemplazar con las imágenes reales
const logos = ref([
  { name: 'Acción Empresas', description: 'Empresa A' },
  { name: 'BRITISH AMERICAN TOBACCO', description: 'Empresa B' },
  { name: 'XBCi', description: 'Empresa C' },
  { name: 'Bsale', description: 'Empresa D' },
  { name: 'LOS HEROES', description: 'Empresa E' },
  { name: 'CCU', description: 'Empresa F' },
  { name: 'Dictuc', description: 'Empresa G' },
  { name: 'Mapocho Agencia', description: 'Empresa H' },
  { name: 'HAYS', description: 'Empresa I' },
  { name: 'HERBALIFE NUTRITION', description: 'Empresa J' },
  { name: 'Mastercard', description: 'Empresa K' },
  { name: 'Principal', description: 'Empresa L' }
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
  width: 100vw !important;
  margin-left: calc(-50vw + 50%);
}
</style> 