<template>
  <div class="flex flex-row gap-3 items-center justify-center self-stretch flex-shrink-0 relative">
    <div class="text-[#5b5b5b] text-left font-nexa-bold text-xl leading-6 font-bold">
      Selecciona el programa a pagar de
    </div>
    <div class="text-[#007e93] text-left font-nexa-bold text-xl leading-6 font-bold">
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
</style> 