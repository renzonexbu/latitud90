<template>
  <div class="min-h-screen bg-gray-50 w-full p-3">
    <!-- Header -->
    <Header class="bg-transparent text-blanco shadow-none"> </Header>
    
    <!-- Contenido del Detalle del Programa -->
    <div class="py-8">
      <!-- Información del Participante -->
      <div class="max-w-6xl mx-auto px-4 mb-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
          <!-- Header del Participante -->
          <div class="mb-6">
            <ParticipantHeader 
              :participant-name="participant.first_name + ' ' + participant.last_name"
            />
          </div>
          
          <!-- Pasos del Proceso - Paso 2 marcado -->
          <div class="pt-4">
            <ProcessSteps :current-step="2" />
          </div>
        </div>
      </div>

      <!-- Detalle del Programa -->
      <div class="mx-[120px] bg-white rounded-lg shadow-lg p-6">
        <div class="flex gap-6">
          <!-- Componente Izquierdo - Contenido del Programa -->
          <div class="w-[696px] flex flex-col items-start gap-[46px] flex-shrink-0">
                        <!-- Header del Programa -->
            <div class="flex items-start justify-between w-full">
              <div class="flex-1">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ program.name }}</h1>
                <p class="text-gray-500 text-sm">{{ program.destination }}</p>
              </div>
              <div class="text-right">
                <div class="text-2xl font-bold text-green-600">{{ formatPrice(program.trip_price) }}</div>
                <div class="text-sm text-gray-500">{{ program.seller_name }}</div>
              </div>
            </div>

            <!-- Sección de Advertencia -->
            <div class="w-full bg-[#f7efee] rounded-xl p-[18px_24px] flex flex-row gap-6 items-center justify-start self-stretch flex-shrink-0 relative">
              <!-- Ícono de Triángulo de Peligro -->
              <div class="flex-shrink-0 w-[33px] h-[33px] relative overflow-visible">
                <svg xmlns="http://www.w3.org/2000/svg" width="33" height="33" viewBox="0 0 33 33" fill="none">
                  <path d="M16.5003 11.6875V19.25M16.5003 23.7146V23.0271M8.22279 14.7978C11.8363 7.68212 13.6403 4.125 16.5003 4.125C19.3603 4.125 21.1657 7.68212 24.7778 14.7978L25.2274 15.6833C28.2277 21.5958 29.7292 24.552 28.372 26.7135C27.0163 28.875 23.6613 28.875 16.9499 28.875H16.0507C9.34067 28.875 5.98429 28.875 4.62854 26.7135C3.27279 24.552 4.77292 21.5958 7.77317 15.6833L8.22279 14.7978Z" stroke="#D54B44" stroke-width="2.0625" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              
              <!-- Contenido de la Advertencia -->
              <div class="flex flex-col gap-1 items-start justify-start flex-1 relative">
                <div class="text-[#5b5b5b] text-left font-nexa-bold text-sm leading-[18px] font-bold relative w-full">
                  El itinerario o programa puede sufrir modificaciones por razones de fuerza mayor
                </div>
                <div class="text-left font-nexa-regular text-xs leading-[17px] font-normal relative self-stretch">
                  <span class="text-[#5b5b5b] font-nexa-regular text-xs">
                    Como condiciones meteorológicas, pandemia, normas sanitarias, cortes de puentes, pasos fronterizos, catástrofe o estado de excepción. Para más información, descarga
                  </span>
                  <span class="text-[#1a4b75] font-nexa-bold text-xs font-bold underline">
                    aquí
                  </span>
                  <span class="text-[#5b5b5b] font-nexa-regular text-xs">
                    el itinerario completo o consúltanos a nuestro correo eléctrico
                  </span>
                  <span class="text-[#1a4b75] font-nexa-bold text-xs font-bold underline">
                    educacion@latitud90.com
                  </span>
                </div>
              </div>
            </div>

        <!-- Imágenes del Programa -->
        <div class="w-full" v-if="program.images && program.images.length > 0">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <img 
              v-for="image in program.images" 
              :key="image.filename"
              :src="image.url" 
              :alt="program.name"
              class="w-full h-48 object-cover rounded-lg cursor-pointer hover:opacity-80 transition-opacity"
              @click="openImageModal(image.url)"
            />
          </div>
        </div>

        <!-- Descripción del Viaje -->
        <div class="w-full">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Descripción del Viaje</h2>
          <p class="text-gray-700 leading-relaxed">{{ program.trip_description }}</p>
        </div>

        <!-- Pilares -->
        <div class="w-full" v-if="program.pillars">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Pilares del Programa</h2>
          <div class="bg-blue-50 rounded-lg p-4">
            <p class="text-gray-700">{{ program.pillars }}</p>
          </div>
        </div>





        <!-- Características -->
        <div class="w-full" v-if="program.features && program.features.length > 0">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Características</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div 
              v-for="feature in program.features" 
              :key="feature.id"
              class="flex items-center p-3 bg-gray-50 rounded-lg"
            >
              <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                <span class="text-blue-600 text-sm font-bold">{{ feature.icon || '✓' }}</span>
              </div>
              <div>
                <h3 class="font-medium text-gray-900">{{ feature.name }}</h3>
                <p class="text-sm text-gray-600">{{ feature.description }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Requisitos -->
        <div class="w-full" v-if="program.requirements && program.requirements.length > 0">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Requisitos</h2>
          <div class="space-y-3">
            <div 
              v-for="requirement in program.requirements" 
              :key="requirement.id"
              class="flex items-start"
            >
              <div class="w-5 h-5 rounded-full mr-3 mt-0.5" 
                   :class="requirement.is_mandatory ? 'bg-red-500' : 'bg-gray-300'">
              </div>
              <div>
                <h3 class="font-medium text-gray-900">{{ requirement.name }}</h3>
                <p class="text-sm text-gray-600">{{ requirement.description }}</p>
                <span v-if="requirement.is_mandatory" class="text-xs text-red-600 font-medium">
                  Obligatorio
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Fechas -->
        <div class="w-full">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Fechas del Programa</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-blue-50 rounded-lg p-4">
              <h3 class="font-medium text-blue-900 mb-1">Fecha de Salida</h3>
              <p class="text-blue-700">{{ formatDate(program.departure_date) }}</p>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
              <h3 class="font-medium text-green-900 mb-1">Fecha Final de Pago</h3>
              <p class="text-green-700">{{ formatDate(program.final_payment_date) }}</p>
            </div>
          </div>
        </div>

        <!-- Archivos PDF -->
        <div class="w-full">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Documentos del Programa</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div v-if="program.itinerary_file" class="bg-yellow-50 rounded-lg p-4">
              <h3 class="font-medium text-yellow-900 mb-2">Itinerario</h3>
              <a 
                :href="program.itinerary_file" 
                target="_blank"
                class="inline-flex items-center text-yellow-700 hover:text-yellow-800"
              >
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                </svg>
                Ver Itinerario
              </a>
            </div>
            
            <div v-if="program.travel_assistance_coverage" class="bg-blue-50 rounded-lg p-4">
              <h3 class="font-medium text-blue-900 mb-2">Cobertura de Asistencia</h3>
              <a 
                :href="program.travel_assistance_coverage" 
                target="_blank"
                class="inline-flex items-center text-blue-700 hover:text-blue-800"
              >
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                </svg>
                Ver Cobertura
              </a>
            </div>
            
            <div v-if="program.equipment_list" class="bg-green-50 rounded-lg p-4">
              <h3 class="font-medium text-green-900 mb-2">Lista de Equipamiento</h3>
              <a 
                :href="program.equipment_list" 
                target="_blank"
                class="inline-flex items-center text-green-700 hover:text-green-800"
              >
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                </svg>
                Ver Equipamiento
              </a>
            </div>
          </div>
        </div>

        <!-- Descripción del Itinerario -->
        <div class="w-full" v-if="program.itinerary_description">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Descripción del Itinerario</h2>
          <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-gray-700 whitespace-pre-line">{{ program.itinerary_description }}</p>
          </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex gap-4 pt-6 border-t border-gray-200 w-full">
          <button 
            v-if="!program.is_enrolled"
            @click="enrollInProgram"
            class="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
          >
            Inscribirse en el Programa
          </button>
          <button 
            v-else
            class="flex-1 bg-gray-400 text-white py-3 px-6 rounded-lg font-semibold cursor-not-allowed"
            disabled
          >
            Ya Inscrito
          </button>
          <button 
            @click="goBack"
            class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition-colors"
          >
            Volver
          </button>
        </div>
          </div>

          <!-- Componente Derecho -->
          <div class="w-[454px] p-[40px_30px] flex items-center gap-[10px] flex-shrink-0">
            <!-- Aquí irá el contenido del componente derecho -->
            <div class="w-full">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Componente Derecho</h3>
              <p class="text-gray-600">Contenido del componente derecho aquí...</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Botón Volver a Seleccionar Viaje -->
      <div class="max-w-6xl mx-auto px-4 mt-8">
        <BackToHomeButton :rut="rut" variant="programs" />
      </div>
    </div>

    <!-- Footer -->
    <Footer class="rounded-lg"></Footer>
  </div>
</template>

<script>
  import { Head } from "@inertiajs/vue3";
  import { router } from "@inertiajs/vue3";
  import Header from "@/Components/Ecommerce/Header.vue";
  import Footer from "@/Components/Ecommerce/Footer.vue";
  import ParticipantHeader from "@/Components/Ecommerce/ParticipantHeader.vue";
  import ProcessSteps from "@/Components/Ecommerce/ProcessSteps.vue";
  import BackToHomeButton from "@/Components/Ecommerce/BackToHomeButton.vue";

  export default {
    components: {
      Header,
      Footer,
      Head,
      ParticipantHeader,
      ProcessSteps,
      BackToHomeButton
    },
    props: {
      participant: {
        type: Object,
        required: true
      },
      program: {
        type: Object,
        required: true
      },
      rut: {
        type: String,
        required: true
      }
    },
    methods: {
      formatPrice(price) {
        return new Intl.NumberFormat('es-CL', {
          style: 'currency',
          currency: 'CLP'
        }).format(price);
      },
      
      openImageModal(imageUrl) {
        // Abrir la imagen en una nueva pestaña
        window.open(imageUrl, '_blank');
      },
      
      formatDate(date) {
        if (!date) return 'No especificada';
        return new Date(date).toLocaleDateString('es-CL', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });
      },
      
      enrollInProgram() {
        // Aquí iría la lógica para inscribir al participante
        router.post(route('ecommerce.enroll'), {
          program_id: this.program.id,
          participant_id: this.participant.id,
          rut: this.rut
        });
      },
      
      goBack() {
        router.get(route('ecommerce.programs'), {
          rut: this.rut
        });
      }
    }
  };
</script>

<style scoped>
  /* Estilos adicionales si son necesarios */
</style>
