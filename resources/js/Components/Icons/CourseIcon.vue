<template>
  <svg
    :width="width"
    :height="height"
    :class="classes"
    viewBox="0 0 23 22"
    fill="none"
    xmlns="http://www.w3.org/2000/svg">
    <g clip-path="url(#clip0_806_8209)">
      <path
        d="M11.569 2.73886V6.82714M6.59242 12.0697V20.9255M11.566 17.5458V20.9901M16.5395 12.0697V20.9255M11.6628 4.38954C13.0384 4.49252 14.2756 4.49252 15.6527 4.38954C15.7772 3.2522 15.7772 2.22245 15.6527 1.08665C14.3247 0.984952 12.9908 0.984952 11.6628 1.08665C11.5379 2.18407 11.5379 3.29212 11.6628 4.38954ZM6.5586 12.6091C5.40897 12.6353 4.69121 12.6706 3.51391 12.7136C2.65169 12.7459 1.92164 13.3684 1.80483 14.2183C1.49898 16.4607 1.49898 17.0801 1.80483 19.3225C1.92164 20.1724 2.65015 20.7949 3.51391 20.8272C9.32357 21.0439 13.8068 21.0439 19.618 20.8272C20.4803 20.7934 21.2103 20.1724 21.3256 19.3225C21.633 17.0801 21.633 16.4607 21.3256 14.2183C21.2103 13.3684 20.4803 12.7459 19.6196 12.7136C18.4407 12.6706 17.7291 12.6353 16.5795 12.6091C16.5552 12.1262 16.526 11.6436 16.4919 11.1613C16.2183 5.3901 6.92132 5.37473 6.64775 11.1521C6.61291 11.6511 6.58217 12.1368 6.5586 12.6091Z"
        :stroke="strokeColor"
        :stroke-width="strokeWidth"
        stroke-linecap="round"
        stroke-linejoin="round" />
      <path
        d="M11.5659 13.4468C12.7386 13.4468 13.398 12.7905 13.398 11.6255C13.398 10.4605 12.7371 9.8042 11.5659 9.8042C10.3932 9.8042 9.73389 10.4605 9.73389 11.6255C9.73389 12.7905 10.3932 13.4468 11.5659 13.4468Z"
        :stroke="strokeColor"
        :stroke-width="strokeWidth"
        stroke-linecap="round"
        stroke-linejoin="round" />
    </g>
    <defs>
      <clipPath id="clip0_806_8209">
        <rect width="21.5172" height="21.5172" fill="white" transform="translate(0.807373 0.241333)"/>
      </clipPath>
    </defs>
  </svg>
</template>

<script setup>
  import { computed } from "vue";

  const props = defineProps({
    width: {
      type: [String, Number],
      default: 23
    },
    height: {
      type: [String, Number],
      default: 22
    },
    strokeColor: {
      type: String,
      default: "white"
    },
    strokeWidth: {
      type: [String, Number],
      default: "1.53695"
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