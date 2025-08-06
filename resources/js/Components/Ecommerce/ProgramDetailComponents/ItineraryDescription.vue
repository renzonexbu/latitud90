<template>
  <div class="w-full" v-if="description">
    <h2 class="text-[#434343] text-left font-nexa text-[20px] leading-[28px] font-bold self-stretch mb-4">
      Que vamos a hacer
    </h2>
    <div>
      <p class="text-[#5B5B5B] text-left font-nexa text-sm leading-[18px] font-normal self-stretch whitespace-pre-line">{{ description }}</p>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ItineraryDescription',
  props: {
    description: {
      type: String,
      default: null
    }
  }
};
</script> 