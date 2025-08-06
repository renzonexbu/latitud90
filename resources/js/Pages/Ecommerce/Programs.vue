<template>
  <div class="min-h-screen bg-gray-50 w-full p-3">
    <!-- Header -->
    <Header class="bg-transparent text-blanco shadow-none"> </Header>
    
    <!-- Contenido de Programas -->
    <div class="max-w-6xl mx-auto py-8 px-4">
      <!-- Información del Participante -->
      <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold text-teal-600 mb-4">Bienvenido, {{ participant.full_name }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
          <div>
            <span class="font-semibold text-gray-700">RUT:</span>
            <span class="ml-2 text-gray-600">{{ formatRut(participant.document_number) }}</span>
          </div>
          <div>
            <span class="font-semibold text-gray-700">Email:</span>
            <span class="ml-2 text-gray-600">{{ participant.email }}</span>
          </div>
          <div>
            <span class="font-semibold text-gray-700">Teléfono:</span>
            <span class="ml-2 text-gray-600">{{ participant.code_phone }} {{ participant.phone }}</span>
          </div>
        </div>
      </div>

      <!-- Título de Programas -->
      <div class="text-center mb-8">
        <h1 class="programs-title">Programas Disponibles</h1>
        <p class="text-gray-600 mt-2">Selecciona el programa que deseas pagar</p>
      </div>

      <!-- Grid de Programas -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="program in programs" 
          :key="program.id"
          class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300"
        >
          <!-- Imagen del Programa -->
          <div class="relative h-48 bg-gray-200">
            <img 
              :src="program.image_url" 
              :alt="program.name"
              class="w-full h-full object-cover"
              @error="$event.target.src = '/images/default-program.jpg'"
            />
            <!-- Badge de Estado -->
            <div class="absolute top-4 right-4">
              <span 
                :class="[
                  'px-3 py-1 rounded-full text-xs font-semibold',
                  program.status === 'available' ? 'bg-green-500 text-white' : 
                  program.status === 'enrolled' ? 'bg-blue-500 text-white' :
                  'bg-yellow-500 text-white'
                ]"
              >
                {{ getStatusText(program.status) }}
              </span>
            </div>
          </div>

          <!-- Contenido del Programa -->
          <div class="p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-2">{{ program.name }}</h3>
            <p class="text-gray-600 text-sm mb-4 line-clamp-3">{{ program.description }}</p>
            
            <!-- Información del Programa -->
            <div class="space-y-2 mb-4">
              <div class="flex items-center text-sm">
                <svg class="w-4 h-4 text-teal-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="text-gray-700">{{ program.destination }}</span>
              </div>
              
              <div class="flex items-center text-sm">
                <svg class="w-4 h-4 text-teal-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span class="text-gray-700">{{ formatDate(program.start_date) }} - {{ formatDate(program.end_date) }}</span>
              </div>
              
              <div class="flex items-center text-sm">
                <svg class="w-4 h-4 text-teal-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-gray-700">{{ program.duration_days }} días</span>
              </div>
              
              <div class="flex items-center text-sm">
                <svg class="w-4 h-4 text-teal-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <span class="text-gray-700">{{ program.available_spots }} cupos disponibles</span>
              </div>
            </div>

            <!-- Precio -->
            <div class="mb-4">
              <span class="text-2xl font-bold text-teal-600">${{ formatPrice(program.base_price) }}</span>
              <span class="text-sm text-gray-500 ml-2">CLP</span>
            </div>

            <!-- Botón de Acción -->
            <div class="flex space-x-2">
              <button 
                v-if="program.status === 'available'"
                @click="selectProgram(program)"
                class="flex-1 bg-teal-500 hover:bg-teal-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200"
              >
                Seleccionar Programa
              </button>
              
              <button 
                v-else-if="program.status === 'enrolled'"
                @click="viewProgramDetail(program)"
                class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200"
              >
                Ver Detalles
              </button>
              
              <button 
                v-else
                @click="viewProgramDetail(program)"
                class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200"
              >
                Ver Estado
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Mensaje si no hay programas -->
      <div v-if="programs.length === 0" class="text-center py-12">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.47-.881-6.08-2.33"></path>
        </svg>
        <h3 class="text-lg font-semibold text-gray-600 mb-2">No hay programas disponibles</h3>
        <p class="text-gray-500">En este momento no hay programas disponibles para tu perfil.</p>
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

  export default {
    components: {
      Header,
      Footer,
      Head
    },
    props: {
      participant: {
        type: Object,
        required: true
      },
      programs: {
        type: Array,
        required: true
      },
      rut: {
        type: String,
        required: true
      }
    },
    methods: {
      formatRut(rut) {
        if (!rut) return '';
        const cleanRut = rut.replace(/\./g, '').replace(/-/g, '');
        if (cleanRut.length > 1) {
          const body = cleanRut.slice(0, -1);
          const dv = cleanRut.slice(-1);
          let formattedBody = '';
          for (let i = body.length - 1, j = 0; i >= 0; i--, j++) {
            if (j > 0 && j % 3 === 0) {
              formattedBody = '.' + formattedBody;
            }
            formattedBody = body[i] + formattedBody;
          }
          return `${formattedBody}-${dv}`;
        }
        return rut;
      },
      
      formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-CL', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric'
        });
      },
      
      formatPrice(price) {
        return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
      },
      
      getStatusText(status) {
        const statusMap = {
          'available': 'Disponible',
          'enrolled': 'Inscrito',
          'pending_payment': 'Pago Pendiente',
          'confirmed': 'Confirmado',
          'cancelled': 'Cancelado'
        };
        return statusMap[status] || status;
      },
      
      selectProgram(program) {
        router.get(route('ecommerce.program-detail', program.id), {
          rut: this.rut
        });
      },
      
      viewProgramDetail(program) {
        router.get(route('ecommerce.program-detail', program.id), {
          rut: this.rut
        });
      }
    }
  };
</script>

<style scoped>
  /* Estilos del título */
  .programs-title {
    color: var(--Colores-OP2-Turquesa, #007E93);
    font-family: Nexa;
    font-size: var(--Numeros-Subtitulo, 24px);
    font-style: normal;
    font-weight: 700;
    line-height: 28px;
    margin-bottom: 1rem;
  }

  /* Estilos para el texto truncado */
  .line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  /* Transiciones suaves */
  .transition-shadow {
    transition: box-shadow 0.3s ease-in-out;
  }

  .transition-colors {
    transition: color 0.2s ease-in-out, background-color 0.2s ease-in-out;
  }
</style>
