<template>
  <div class="relative w-full">
    <!-- Contenedor de pasos -->
    <div class="flex flex-row items-start justify-between relative z-10 w-full">
      <!-- Paso 1 -->
      <div class="flex flex-col gap-3 items-center justify-center flex-1 relative">
        <div 
          :class="currentStep >= 1 ? 'bg-[#007e93]' : 'bg-[#d3d3d3]'"
          class="rounded-full w-10 h-10 flex items-center justify-center relative z-10"
        >
          <div class="text-white font-nexa-xbold text-lg font-bold">
            1
          </div>
        </div>
        <div 
          :class="currentStep >= 1 ? 'text-[#007e93]' : 'text-[#c7c7c7]'"
          class="text-center font-nexa-bold text-sm leading-5 font-bold max-w-[120px]"
        >
          Busca al viajero mediante su RUT
        </div>
        <!-- Línea desde el primer círculo -->
        <div class="absolute top-[19px] left-1/2 w-full h-0 border-t-2 border-[#d3d3d3] z-0"></div>
      </div>

      <!-- Paso 2 -->
      <div class="flex flex-col gap-3 items-center justify-center flex-1 relative">
        <div 
          :class="currentStep >= 2 ? 'bg-[#007e93]' : 'bg-[#d3d3d3]'"
          class="rounded-full w-10 h-10 flex items-center justify-center relative z-10"
        >
          <div class="text-white font-nexa-xbold text-lg font-bold">
            2
          </div>
        </div>
        <div 
          :class="currentStep >= 2 ? 'text-[#007e93]' : 'text-[#c7c7c7]'"
          class="text-center font-nexa-bold text-sm leading-5 font-bold max-w-[120px]"
        >
          Seleccione el programa a pagar
        </div>
        <!-- Línea desde el paso 2 hacia el paso 3 -->
        <div class="absolute top-[19px] left-1/2 w-full h-0 border-t-2 border-[#d3d3d3] z-0"></div>
      </div>

      <!-- Paso 3 -->
      <div class="flex flex-col gap-3 items-center justify-center flex-1 relative">
        <div 
          :class="currentStep >= 3 ? 'bg-[#007e93]' : 'bg-[#d3d3d3]'"
          class="rounded-full w-10 h-10 flex items-center justify-center relative z-10"
        >
          <div class="text-white font-nexa-xbold text-lg font-bold">
            3
          </div>
        </div>
        <div 
          :class="currentStep >= 3 ? 'text-[#007e93]' : 'text-[#c7c7c7]'"
          class="text-center font-nexa-bold text-sm leading-5 font-bold max-w-[120px]"
        >
          Elige el modo de pago
        </div>
        <!-- Línea desde el paso 3 hacia el paso 4 -->
        <div class="absolute top-[19px] left-1/2 w-full h-0 border-t-2 border-[#d3d3d3] z-0"></div>
      </div>

      <!-- Paso 4 -->
      <div class="flex flex-col gap-3 items-center justify-center flex-1 relative">
        <div 
          :class="currentStep >= 4 ? 'bg-[#007e93]' : 'bg-[#d3d3d3]'"
          class="rounded-full w-10 h-10 flex items-center justify-center relative z-10"
        >
          <div class="text-white font-nexa-xbold text-lg font-bold">
            4
          </div>
        </div>
        <div 
          :class="currentStep >= 4 ? 'text-[#007e93]' : 'text-[#c7c7c7]'"
          class="text-center font-nexa-bold text-sm leading-5 font-bold max-w-[120px]"
        >
          Paga y Listo
        </div>
        <!-- Línea hasta el último círculo -->
        <div class="absolute top-[19px] right-1/2 w-full h-0 border-t-2 border-[#d3d3d3] z-0"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  currentStep: {
    type: Number,
    default: 1
  }
});
</script>

<style scoped>
.font-nexa-bold {
  font-family: "Nexa-Bold", sans-serif;
}

.font-nexa-xbold {
  font-family: "Nexa-XBold", sans-serif;
}
</style> 