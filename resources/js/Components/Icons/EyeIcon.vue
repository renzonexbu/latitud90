<template>
  <svg 
    xmlns="http://www.w3.org/2000/svg" 
    width="16" 
    height="9" 
    viewBox="0 0 16 9" 
    fill="none"
    :class="className"
  >
    <path 
      d="M13.8437 7.00553L11.9359 4.38778M8.0625 8.16178V5.46387M2.28125 7.00553L4.18444 4.39395M1.125 0.838867C3.9 7.00553 12.225 7.00553 15 0.838867" 
      :stroke="strokeColor" 
      stroke-width="1.15625" 
      stroke-linecap="round" 
      stroke-linejoin="round"
    />
  </svg>
</template>

<script setup>
defineProps({
  className: {
    type: String,
    default: ''
  },
  strokeColor: {
    type: String,
    default: '#636363'
  }
});
</script> 