<template>
  <div class="w-full" v-if="description">
    <h2 class="text-[#434343] text-left font-nexa text-[20px] leading-[28px] font-bold self-stretch mb-4">
      Que vamos a hacer
    </h2>
    <div
      class="rich-content text-[#5B5B5B] text-left"
      v-html="description"
    />
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

<style scoped>
.rich-content {
  font-family: "Nexa-Regular", sans-serif;
  font-size: 14px;
  line-height: 18px;
}
.rich-content :deep(p) { margin: 0 0 8px 0; }
.rich-content :deep(h1) {
  font-family: "Nexa-Bold", sans-serif;
  font-size: 24px;
  line-height: 28px;
  margin: 8px 0;
  color: #434343;
}
.rich-content :deep(h2) {
  font-family: "Nexa-Bold", sans-serif;
  font-size: 20px;
  line-height: 28px;
  margin: 8px 0;
  color: #434343;
}
.rich-content :deep(ul) {
  list-style-type: disc !important;
  margin: 8px 0 8px 20px;
  padding-left: 20px;
}
.rich-content :deep(ol) {
  list-style-type: decimal !important;
  margin: 8px 0 8px 20px;
  padding-left: 20px;
}
.rich-content :deep(li) { margin: 2px 0; }
.rich-content :deep(.ql-align-center) { text-align: center; }
.rich-content :deep(.ql-align-right) { text-align: right; }
.rich-content :deep(.ql-align-justify) { text-align: justify; }
</style>