<template>
  <div class="min-h-screen bg-gray-50 w-full p-3">
    <!-- Header -->
    <Header class="bg-transparent text-blanco shadow-none"> </Header>
    
    <!-- Contenido de Términos y Condiciones -->
    <div class="max-w-4xl mx-auto py-8 px-4">
      <div class="bg-white rounded-lg shadow-lg p-8">
        <h1 class="terms-title">Términos y Condiciones</h1>
        
        <!-- Acordeón de Términos y Condiciones -->
        <div class="space-y-4">
          <!-- Término 1 -->
          <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button 
              @click="toggleAccordion(0)"
              class="w-full px-6 py-4 text-left bg-white hover:bg-gray-50 transition-colors duration-200 flex justify-between items-center"
              :class="{ 'text-teal-600': openAccordion === 0, 'text-gray-700': openAccordion !== 0 }"
            >
              <span class="font-semibold">1. Aceptación de los Términos</span>
              <svg 
                class="w-5 h-5 transition-transform duration-200"
                :class="{ 'rotate-180': openAccordion === 0 }"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            <transition name="accordion" mode="out-in">
              <div 
                v-show="openAccordion === 0"
                class="accordion-content"
              >
                <p class="text-gray-700 leading-relaxed">
                  Al acceder y utilizar los servicios de Latitud 90, usted acepta estar sujeto a estos términos y condiciones. 
                  Si no está de acuerdo con alguna parte de estos términos, no debe utilizar nuestros servicios.
                </p>
              </div>
            </transition>
          </div>

          <!-- Término 2 -->
          <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button 
              @click="toggleAccordion(1)"
              class="w-full px-6 py-4 text-left bg-white hover:bg-gray-50 transition-colors duration-200 flex justify-between items-center"
              :class="{ 'text-teal-600': openAccordion === 1, 'text-gray-700': openAccordion !== 1 }"
            >
              <span class="font-semibold">2. Descripción del Servicio</span>
              <svg 
                class="w-5 h-5 transition-transform duration-200"
                :class="{ 'rotate-180': openAccordion === 1 }"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            <transition name="accordion" mode="out-in">
              <div 
                v-show="openAccordion === 1"
                class="accordion-content"
              >
                <p class="text-gray-700 leading-relaxed">
                  Latitud 90 ofrece servicios de viajes educativos, tours, excursiones, intercambios estudiantiles y cruceros. 
                  Nos reservamos el derecho de modificar, suspender o discontinuar cualquier aspecto de nuestros servicios en cualquier momento.
                </p>
              </div>
            </transition>
          </div>

          <!-- Término 3 -->
          <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button 
              @click="toggleAccordion(2)"
              class="w-full px-6 py-4 text-left bg-white hover:bg-gray-50 transition-colors duration-200 flex justify-between items-center"
              :class="{ 'text-teal-600': openAccordion === 2, 'text-gray-700': openAccordion !== 2 }"
            >
              <span class="font-semibold">3. Reservas y Pagos</span>
              <svg 
                class="w-5 h-5 transition-transform duration-200"
                :class="{ 'rotate-180': openAccordion === 2 }"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            <transition name="accordion" mode="out-in">
              <div 
                v-show="openAccordion === 2"
                class="accordion-content"
              >
                <p class="text-gray-700 leading-relaxed">
                  Las reservas están sujetas a disponibilidad. Los pagos deben realizarse según las condiciones especificadas 
                  para cada programa. Los precios están sujetos a cambios sin previo aviso hasta que se confirme la reserva.
                </p>
              </div>
            </transition>
          </div>

          <!-- Término 4 -->
          <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button 
              @click="toggleAccordion(3)"
              class="w-full px-6 py-4 text-left bg-white hover:bg-gray-50 transition-colors duration-200 flex justify-between items-center"
              :class="{ 'text-teal-600': openAccordion === 3, 'text-gray-700': openAccordion !== 3 }"
            >
              <span class="font-semibold">4. Cancelaciones y Reembolsos</span>
              <svg 
                class="w-5 h-5 transition-transform duration-200"
                :class="{ 'rotate-180': openAccordion === 3 }"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            <transition name="accordion" mode="out-in">
              <div 
                v-show="openAccordion === 3"
                class="accordion-content"
              >
                <p class="text-gray-700 leading-relaxed">
                  Las políticas de cancelación varían según el programa. Consulte las condiciones específicas de cada viaje. 
                  Los reembolsos están sujetos a las políticas de nuestros proveedores y pueden incurrir en cargos administrativos.
                </p>
              </div>
            </transition>
          </div>

          <!-- Término 5 -->
          <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button 
              @click="toggleAccordion(4)"
              class="w-full px-6 py-4 text-left bg-white hover:bg-gray-50 transition-colors duration-200 flex justify-between items-center"
              :class="{ 'text-teal-600': openAccordion === 4, 'text-gray-700': openAccordion !== 4 }"
            >
              <span class="font-semibold">5. Responsabilidades del Viajero</span>
              <svg 
                class="w-5 h-5 transition-transform duration-200"
                :class="{ 'rotate-180': openAccordion === 4 }"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            <transition name="accordion" mode="out-in">
              <div 
                v-show="openAccordion === 4"
                class="accordion-content"
              >
                <p class="text-gray-700 leading-relaxed">
                  Los viajeros son responsables de obtener la documentación necesaria, incluyendo pasaportes, visas y vacunas requeridas. 
                  También deben cumplir con las leyes y regulaciones del país de destino.
                </p>
              </div>
            </transition>
          </div>

          <!-- Término 6 -->
          <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button 
              @click="toggleAccordion(5)"
              class="w-full px-6 py-4 text-left bg-white hover:bg-gray-50 transition-colors duration-200 flex justify-between items-center"
              :class="{ 'text-teal-600': openAccordion === 5, 'text-gray-700': openAccordion !== 5 }"
            >
              <span class="font-semibold">6. Limitación de Responsabilidad</span>
              <svg 
                class="w-5 h-5 transition-transform duration-200"
                :class="{ 'rotate-180': openAccordion === 5 }"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            <transition name="accordion" mode="out-in">
              <div 
                v-show="openAccordion === 5"
                class="accordion-content"
              >
                <p class="text-gray-700 leading-relaxed">
                  Latitud 90 no será responsable por daños indirectos, incidentales o consecuentes que puedan surgir del uso 
                  de nuestros servicios, excepto donde la ley lo requiera.
                </p>
              </div>
            </transition>
          </div>

          <!-- Término 7 -->
          <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button 
              @click="toggleAccordion(6)"
              class="w-full px-6 py-4 text-left bg-white hover:bg-gray-50 transition-colors duration-200 flex justify-between items-center"
              :class="{ 'text-teal-600': openAccordion === 6, 'text-gray-700': openAccordion !== 6 }"
            >
              <span class="font-semibold">7. Privacidad y Datos Personales</span>
              <svg 
                class="w-5 h-5 transition-transform duration-200"
                :class="{ 'rotate-180': openAccordion === 6 }"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            <transition name="accordion" mode="out-in">
              <div 
                v-show="openAccordion === 6"
                class="accordion-content"
              >
                <p class="text-gray-700 leading-relaxed">
                  Su privacidad es importante para nosotros. Consulte nuestra Política de Privacidad para obtener información 
                  sobre cómo recopilamos, usamos y protegemos su información personal.
                </p>
              </div>
            </transition>
          </div>

          <!-- Término 8 -->
          <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button 
              @click="toggleAccordion(7)"
              class="w-full px-6 py-4 text-left bg-white hover:bg-gray-50 transition-colors duration-200 flex justify-between items-center"
              :class="{ 'text-teal-600': openAccordion === 7, 'text-gray-700': openAccordion !== 7 }"
            >
              <span class="font-semibold">8. Modificaciones</span>
              <svg 
                class="w-5 h-5 transition-transform duration-200"
                :class="{ 'rotate-180': openAccordion === 7 }"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            <transition name="accordion" mode="out-in">
              <div 
                v-show="openAccordion === 7"
                class="accordion-content"
              >
                <p class="text-gray-700 leading-relaxed">
                  Nos reservamos el derecho de modificar estos términos y condiciones en cualquier momento. 
                  Los cambios entrarán en vigor inmediatamente después de su publicación en nuestro sitio web.
                </p>
              </div>
            </transition>
          </div>

          <!-- Término 9 -->
          <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button 
              @click="toggleAccordion(8)"
              class="w-full px-6 py-4 text-left bg-white hover:bg-gray-50 transition-colors duration-200 flex justify-between items-center"
              :class="{ 'text-teal-600': openAccordion === 8, 'text-gray-700': openAccordion !== 8 }"
            >
              <span class="font-semibold">9. Contacto</span>
              <svg 
                class="w-5 h-5 transition-transform duration-200"
                :class="{ 'rotate-180': openAccordion === 8 }"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            <transition name="accordion" mode="out-in">
              <div 
                v-show="openAccordion === 8"
                class="accordion-content"
              >
                <p class="text-gray-700 leading-relaxed">
                  Si tiene alguna pregunta sobre estos términos y condiciones, puede contactarnos a través de 
                  nuestros canales de atención al cliente disponibles en nuestro sitio web.
                </p>
              </div>
            </transition>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <Footer class="rounded-lg"></Footer>
  </div>
</template>

<script>
  import { Head } from "@inertiajs/vue3";
  import { ref } from "vue";
  import Header from "@/Components/Ecommerce/Header.vue";
  import Footer from "@/Components/Ecommerce/Footer.vue";

  export default {
    components: {
      Header,
      Footer,
      Head
    },
    setup() {
      const openAccordion = ref(0); // El primer acordeón estará abierto por defecto

      const toggleAccordion = (index) => {
        openAccordion.value = openAccordion.value === index ? null : index;
      };

      return {
        openAccordion,
        toggleAccordion
      };
    }
  };
</script>

<style scoped>
  /* Transiciones suaves para el acordeón */
  .transition-transform {
    transition: transform 0.2s ease-in-out;
  }
  
  .transition-colors {
    transition: color 0.2s ease-in-out, background-color 0.2s ease-in-out;
  }

  /* Estilos del contenido del acordeón */
  .accordion-content {
    padding: 0 1.5rem 1rem 1.5rem;
    background-color: white;
    overflow: hidden;
  }

  /* Transiciones del acordeón */
  .accordion-enter-active,
  .accordion-leave-active {
    transition: all 0.3s ease-in-out;
    max-height: 200px;
    opacity: 1;
  }

  .accordion-enter-from,
  .accordion-leave-to {
    max-height: 0;
    opacity: 0;
    padding-top: 0;
    padding-bottom: 0;
  }

  .accordion-enter-to,
  .accordion-leave-from {
    max-height: 200px;
    opacity: 1;
  }

  /* Estilos del título */
  .terms-title {
    color: var(--Colores-OP2-Turquesa, #007E93);
    font-family: Nexa;
    font-size: var(--Numeros-Subtitulo, 24px);
    font-style: normal;
    font-weight: 700;
    line-height: 28px; /* 116.667% */
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 13px;
    margin-bottom: 2rem;
    text-align: center;
    width: 100%;
  }
</style>
