<template>
  <div class="w-full">
    <p class="text-[#5B5B5B] text-left font-nexa text-sm leading-[18px] font-normal self-stretch">
      {{ description }}
    </p>
  </div>
</template>

<script>
export default {
  name: 'TripDescription',
  props: {
    description: {
      type: String,
      required: true
    }
  }
};
</script> 