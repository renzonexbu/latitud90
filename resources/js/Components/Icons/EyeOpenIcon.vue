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
      d="M8 1.5C3 1.5 1 4.5 1 4.5C1 4.5 3 7.5 8 7.5C13 7.5 15 4.5 15 4.5C15 4.5 13 1.5 8 1.5Z" 
      :stroke="strokeColor" 
      stroke-width="1.15625" 
      stroke-linecap="round" 
      stroke-linejoin="round"
    />
    <path 
      d="M8 5.5C8.82843 5.5 9.5 4.82843 9.5 4C9.5 3.17157 8.82843 2.5 8 2.5C7.17157 2.5 6.5 3.17157 6.5 4C6.5 4.82843 7.17157 5.5 8 5.5Z" 
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