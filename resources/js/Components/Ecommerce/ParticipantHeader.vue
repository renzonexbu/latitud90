<template>
  <div class="flex flex-col items-center justify-center text-center md:flex-row md:gap-3 md:text-left">
    <div
      class="text-[#5b5b5b] font-nexa-bold font-bold tracking-normal
             text-[14px] leading-[22px]
             md:text-xl md:leading-6 md:text-left"
    >
      Selecciona el programa a pagar de
    </div>
    <div
      class="text-[#007e93] font-nexa-xbold tracking-normal
             text-[14px] leading-[22px]
             md:text-xl md:leading-6 md:text-left"
    >
      {{ participantName }}
    </div>
  </div>
  
</template>

<script setup>
defineProps({
  participantName: {
    type: String,
    required: true
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
/* En mobile, cada texto ocupa su propia fila al usar flex-col en el contenedor */
</style> 