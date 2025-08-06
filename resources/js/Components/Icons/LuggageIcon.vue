<template>
  <svg
    :width="width"
    :height="height"
    :class="classes"
    viewBox="0 0 47 46"
    fill="none"
    xmlns="http://www.w3.org/2000/svg">
    <path
      d="M23.5065 5.31755V13.9658M12.979 25.0558V43.7894M23.5 36.6399V43.9259M34.021 25.0558V43.7894M23.7048 8.80937C26.6147 9.02721 29.2319 9.02721 32.145 8.80937C32.4084 6.40346 32.4084 4.22514 32.145 1.82248C29.3357 1.60735 26.5141 1.60735 23.7048 1.82248C23.4406 4.14395 23.4406 6.4879 23.7048 8.80937ZM12.9075 26.197C10.4756 26.2522 8.95724 26.327 6.4668 26.418C4.64286 26.4863 3.09852 27.8031 2.85143 29.601C2.20443 34.3445 2.20443 35.6548 2.85143 40.3983C3.09852 42.1963 4.6396 43.513 6.4668 43.5813C18.7565 44.0397 28.2403 44.0397 40.5332 43.5813C42.3571 43.5098 43.9015 42.1963 44.1453 40.3983C44.7956 35.6548 44.7956 34.3445 44.1453 29.601C43.9015 27.8031 42.3571 26.4863 40.5365 26.418C38.0428 26.327 36.5374 26.2522 34.1055 26.197C34.0542 25.1755 33.9924 24.1545 33.9202 23.1343C33.3415 10.9259 13.6748 10.8934 13.0961 23.1148C13.0224 24.1704 12.9573 25.1977 12.9075 26.197Z"
      :stroke="strokeColor"
      :stroke-width="strokeWidth"
      stroke-linecap="round"
      stroke-linejoin="round" />
    <path
      d="M23.5 27.9691C25.9807 27.9691 27.3755 26.5808 27.3755 24.1164C27.3755 21.6519 25.9774 20.2637 23.5 20.2637C21.0193 20.2637 19.6245 21.6519 19.6245 24.1164C19.6245 26.5808 21.0193 27.9691 23.5 27.9691Z"
      :stroke="strokeColor"
      :stroke-width="strokeWidth"
      stroke-linecap="round"
      stroke-linejoin="round" />
  </svg>
</template>

<script setup>
  import { computed } from "vue";

  const props = defineProps({
    width: {
      type: [String, Number],
      default: 47
    },
    height: {
      type: [String, Number],
      default: 46
    },
    strokeColor: {
      type: String,
      default: "currentColor"
    },
    strokeWidth: {
      type: [String, Number],
      default: "3.25123"
    },
    class: {
      type: [String, Array, Object],
      default: ""
    }
  });

  const classes = computed(() => {
    const baseClasses = "block";
    return typeof props.class === "string"
      ? `${baseClasses} ${props.class}`.trim()
      : [baseClasses, props.class];
  });
</script>