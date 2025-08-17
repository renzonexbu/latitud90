<template>
  <button 
    @click="handleClick"
    class="flex items-center gap-3 opacity-30 hover:opacity-60 transition-opacity duration-200"
  >
    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 30 30" fill="none">
      <g opacity="0.3">
        <path d="M20.625 15H9.375M9.375 15L13.75 19.375M9.375 15L13.75 10.625" stroke="#434343" stroke-width="1.875" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M26.25 15C26.25 16.4774 25.959 17.9403 25.3936 19.3052C24.8283 20.6701 23.9996 21.9103 22.955 22.955C21.9103 23.9996 20.6701 24.8283 19.3052 25.3936C17.9403 25.959 16.4774 26.25 15 26.25C13.5226 26.25 12.0597 25.959 10.6948 25.3936C9.3299 24.8283 8.08971 23.9996 7.04505 22.955C6.00039 21.9103 5.17172 20.6701 4.60636 19.3052C4.04099 17.9403 3.75 16.4774 3.75 15C3.75 12.0163 4.93526 9.15483 7.04505 7.04505C9.15483 4.93526 12.0163 3.75 15 3.75C17.9837 3.75 20.8452 4.93526 22.955 7.04505C25.0647 9.15483 26.25 12.0163 26.25 15Z" stroke="#434343" stroke-width="1.875" stroke-linecap="round" stroke-linejoin="round"/>
      </g>
    </svg>
    <span class="text-[#434343] font-outfit text-xl font-normal leading-normal">
      {{ buttonText }}
    </span>
  </button>
</template>

<script>
import { router } from "@inertiajs/vue3";

export default {
  props: {
    rut: {
      type: String,
      required: true
    },
    variant: {
      type: String,
      default: 'home', // 'home' o 'programs'
      validator: value => ['home', 'programs'].includes(value)
    },
    text: {
      type: String,
      default: null
    },
    route: {
      type: String,
      default: '/'
    }
  },
  computed: {
    buttonText() {
      // Si se proporciona texto personalizado, usarlo
      if (this.text) {
        return this.text;
      }
      // Si no, usar el texto por defecto según el variant
      return this.variant === 'programs' ? 'Volver a seleccionar viaje' : 'Volver al Home';
    }
  },
  methods: {
    handleClick() {
      // Usar la ruta proporcionada (por defecto es '/')
      if (this.route === '/' || this.route === null) {
        // Si es home, usar la lógica original según el variant
        if (this.variant === 'programs') {
          // En ProgramDetail.vue - volver a programas
          router.get(route('ecommerce.programs'), {
            rut: this.rut
          });
        } else {
          // En Programs.vue - volver al home
          router.visit('/');
        }
      } else {
        // Si es una ruta personalizada diferente a '/'
        router.visit(this.route, {
          data: { rut: this.rut }
        });
      }
    }
  }
};
</script> 