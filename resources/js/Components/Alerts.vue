<template>
  <Transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="transform opacity-0 scale-95 translate-x-full"
    enter-to-class="transform opacity-100 scale-100 translate-x-0"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="transform opacity-100 scale-100 translate-x-0"
    leave-to-class="transform opacity-0 scale-95 translate-x-full"
  >
    <div
      v-if="show"
      :class="[
        'fixed top-4 right-4 z-[9999] max-w-sm w-full rounded-lg shadow-xl p-4',
        'transform transition-all duration-300 ease-in-out',
        'border-l-4 backdrop-blur-sm',
        alertClasses
      ]"
    >
      <div class="flex items-start">
        <!-- Ícono -->
        <div class="flex-shrink-0">
          <component 
            :is="icon" 
            :class="[
              'h-5 w-5',
              iconClasses
            ]" 
          />
        </div>
        
        <!-- Contenido -->
        <div class="ml-3 flex-1">
          <p class="text-sm font-medium" :class="textClasses">
            {{ title }}
          </p>
          <p v-if="message" class="mt-1 text-sm" :class="textClasses">
            {{ message }}
          </p>
        </div>
        
        <!-- Botón de cerrar -->
        <div class="ml-4 flex-shrink-0 flex">
          <button
            @click="close"
            :class="[
              'inline-flex text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600',
              'transition ease-in-out duration-150 rounded-full p-1 hover:bg-gray-100'
            ]"
            aria-label="Cerrar alerta"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </div>
      
      <!-- Barra de progreso para auto-cierre -->
      <div v-if="autoClose" class="mt-3">
        <div class="w-full bg-gray-200 rounded-full h-1">
          <div 
            :class="progressBarClasses"
            class="h-1 rounded-full transition-all duration-100 ease-linear"
            :style="{ width: progressWidth + '%' }"
          ></div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { 
  CheckCircleIcon, 
  ExclamationTriangleIcon, 
  InformationCircleIcon,
  XCircleIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  type: {
    type: String,
    default: 'success',
    validator: (value) => ['success', 'error', 'warning', 'info'].includes(value)
  },
  title: {
    type: String,
    required: true
  },
  message: {
    type: String,
    default: ''
  },
  autoClose: {
    type: Boolean,
    default: true
  },
  duration: {
    type: Number,
    default: 5000 // 5 segundos
  }
});

const emit = defineEmits(['close']);

// Estado interno
const progressWidth = ref(100);
const autoCloseTimer = ref(null);
const progressTimer = ref(null);

// Clases dinámicas según el tipo
const alertClasses = computed(() => {
  const baseClasses = 'bg-white/95 border-l-4 border-opacity-100';
  
  switch (props.type) {
    case 'success':
      return `${baseClasses} border-green-500`;
    case 'error':
      return `${baseClasses} border-red-500`;
    case 'warning':
      return `${baseClasses} border-yellow-500`;
    case 'info':
      return `${baseClasses} border-blue-500`;
    default:
      return `${baseClasses} border-green-500`;
  }
});

const iconClasses = computed(() => {
  switch (props.type) {
    case 'success':
      return 'text-green-500';
    case 'error':
      return 'text-red-500';
    case 'warning':
      return 'text-yellow-500';
    case 'info':
      return 'text-blue-500';
    default:
      return 'text-green-500';
  }
});

const textClasses = computed(() => {
  switch (props.type) {
    case 'success':
      return 'text-green-800';
    case 'error':
      return 'text-red-800';
    case 'warning':
      return 'text-yellow-800';
    case 'info':
      return 'text-blue-800';
    default:
      return 'text-green-800';
  }
});

const progressBarClasses = computed(() => {
  switch (props.type) {
    case 'success':
      return 'bg-green-500';
    case 'error':
      return 'bg-red-500';
    case 'warning':
      return 'bg-yellow-500';
    case 'info':
      return 'bg-blue-500';
    default:
      return 'bg-green-500';
  }
});

// Ícono según el tipo
const icon = computed(() => {
  switch (props.type) {
    case 'success':
      return CheckCircleIcon;
    case 'error':
      return XCircleIcon;
    case 'warning':
      return ExclamationTriangleIcon;
    case 'info':
      return InformationCircleIcon;
    default:
      return CheckCircleIcon;
  }
});

// Función para cerrar la alerta
const close = () => {
  clearTimers();
  emit('close');
};

// Función para limpiar timers
const clearTimers = () => {
  if (autoCloseTimer.value) {
    clearTimeout(autoCloseTimer.value);
    autoCloseTimer.value = null;
  }
  if (progressTimer.value) {
    clearInterval(progressTimer.value);
    progressTimer.value = null;
  }
};

// Función para iniciar auto-cierre
const startAutoClose = () => {
  if (!props.autoClose || !props.show) return;
  
  clearTimers();
  
  // Timer para cerrar la alerta
  autoCloseTimer.value = setTimeout(() => {
    close();
  }, props.duration);
  
  // Timer para la barra de progreso
  const progressInterval = 50; // Actualizar cada 50ms
  const totalSteps = props.duration / progressInterval;
  let currentStep = 0;
  
  progressTimer.value = setInterval(() => {
    currentStep++;
    progressWidth.value = 100 - (currentStep / totalSteps) * 100;
    
    if (currentStep >= totalSteps) {
      clearInterval(progressTimer.value);
    }
  }, progressInterval);
};

// Watcher para cuando cambie la prop show
watch(() => props.show, (newValue) => {
  if (newValue) {
    progressWidth.value = 100;
    startAutoClose();
  } else {
    clearTimers();
  }
});

// Cleanup al desmontar
onUnmounted(() => {
  clearTimers();
});
</script>

<style scoped>
/* Asegurar que las transiciones funcionen correctamente */
.transform {
  transform: translateZ(0);
}

/* Mejorar la apariencia en dispositivos móviles */
@media (max-width: 640px) {
  .fixed.top-4.right-4 {
    top: 0.5rem;
    right: 0.5rem;
    left: 0.5rem;
    max-width: none;
    width: calc(100% - 1rem);
    z-index: 9999 !important;
  }
}

/* Asegurar que esté por encima de todo */
.fixed.z-\[9999\] {
  z-index: 9999 !important;
}

/* Mejorar la sombra y contraste */
.shadow-xl {
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(0, 0, 0, 0.05);
}

/* Asegurar que el backdrop blur funcione */
.backdrop-blur-sm {
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
}

/* Mejorar la visibilidad en móvil */
@media (max-width: 640px) {
  .bg-white\/95 {
    background-color: rgba(255, 255, 255, 0.98) !important;
  }
  
  /* Asegurar que el texto sea legible */
  .text-sm {
    font-size: 14px !important;
    line-height: 1.4 !important;
  }
  
  /* Mejorar el padding en móvil */
  .p-4 {
    padding: 1rem !important;
  }
}

/* Asegurar que esté por encima de modales y otros elementos */
.z-\[9999\] {
  z-index: 9999 !important;
  position: fixed !important;
}

/* Mejorar el contraste del texto */
.text-green-800,
.text-red-800,
.text-yellow-800,
.text-blue-800 {
  font-weight: 500 !important;
}
</style>
