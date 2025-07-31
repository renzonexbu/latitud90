<template>
  <Head :title="program.name" />

  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header></header>

    <!-- Breadcrumb -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
      <nav
        class="flex"
        aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
          <li>
            <Link
              href="/"
              class="text-gray-400 hover:text-gray-500">
              <svg
                class="flex-shrink-0 h-5 w-5"
                fill="currentColor"
                viewBox="0 0 20 20">
                <path
                  d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
              </svg>
              <span class="sr-only">Inicio</span>
            </Link>
          </li>
          <li>
            <div class="flex items-center">
              <svg
                class="flex-shrink-0 h-5 w-5 text-gray-300"
                fill="currentColor"
                viewBox="0 0 20 20">
                <path
                  fill-rule="evenodd"
                  d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                  clip-rule="evenodd" />
              </svg>
              <span class="ml-4 text-sm font-medium text-gray-500">{{
                program.name
              }}</span>
            </div>
          </li>
        </ol>
      </nav>
    </div>

    <!-- Program Details -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2">
          <!-- Hero Image/Banner -->
          <div
            class="h-96 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center mb-8">
            <div class="text-center text-white">
              <h1 class="text-4xl font-bold">{{ program.name }}</h1>
              <p class="text-indigo-100 mt-2 text-lg">
                {{ formatServiceType(program.service_type) }}
              </p>
              <p class="text-indigo-100 mt-1">{{ program.destination }}</p>
            </div>
          </div>

          <!-- Program Info -->
          <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Descripción</h2>
            <p class="text-gray-700 text-lg leading-relaxed mb-6">
              {{ program.description }}
            </p>

            <!-- Program Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
              <div class="space-y-4">
                <div class="flex items-center">
                  <svg
                    class="h-5 w-5 text-indigo-600 mr-3"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <span class="text-gray-700">
                    <strong>Duración:</strong> {{ program.duration_days }} días
                  </span>
                </div>
                <div class="flex items-center">
                  <svg
                    class="h-5 w-5 text-indigo-600 mr-3"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <span class="text-gray-700">
                    <strong>Salida:</strong>
                    {{ formatDate(program.departure_date) }}
                  </span>
                </div>
                <div class="flex items-center">
                  <svg
                    class="h-5 w-5 text-indigo-600 mr-3"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <span class="text-gray-700">
                    <strong>Regreso:</strong>
                    {{ formatDate(program.return_date) }}
                  </span>
                </div>
              </div>
              <div class="space-y-4">
                <div class="flex items-center">
                  <svg
                    class="h-5 w-5 text-indigo-600 mr-3"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                  <span class="text-gray-700">
                    <strong>Capacidad:</strong> {{ program.capacity }} personas
                  </span>
                </div>
                <div class="flex items-center">
                  <svg
                    class="h-5 w-5 text-green-600 mr-3"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span class="text-gray-700">
                    <strong>Disponibles:</strong>
                    <span
                      :class="
                        program.available_spots > 0
                          ? 'text-green-600'
                          : 'text-red-600'
                      "
                      class="font-semibold ml-1">
                      {{ program.available_spots }} cupos
                    </span>
                  </span>
                </div>
                <div class="flex items-center">
                  <svg
                    class="h-5 w-5 text-indigo-600 mr-3"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <span class="text-gray-700">
                    <strong>Destino:</strong> {{ program.destination }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Includes/Excludes -->
          <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div v-if="program.includes">
                <h3
                  class="text-lg font-semibold text-green-700 mb-4 flex items-center">
                  <svg
                    class="h-5 w-5 mr-2"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Incluye
                </h3>
                <ul class="space-y-2">
                  <li
                    v-for="item in program.includes.split(',')"
                    :key="item"
                    class="flex items-start">
                    <svg
                      class="h-4 w-4 text-green-600 mt-0.5 mr-2 flex-shrink-0"
                      fill="currentColor"
                      viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clip-rule="evenodd" />
                    </svg>
                    <span class="text-gray-700">{{ item.trim() }}</span>
                  </li>
                </ul>
              </div>
              <div v-if="program.excludes">
                <h3
                  class="text-lg font-semibold text-red-700 mb-4 flex items-center">
                  <svg
                    class="h-5 w-5 mr-2"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  No Incluye
                </h3>
                <ul class="space-y-2">
                  <li
                    v-for="item in program.excludes.split(',')"
                    :key="item"
                    class="flex items-start">
                    <svg
                      class="h-4 w-4 text-red-600 mt-0.5 mr-2 flex-shrink-0"
                      fill="currentColor"
                      viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                    </svg>
                    <span class="text-gray-700">{{ item.trim() }}</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Requirements -->
          <div
            v-if="program.requirements"
            class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h3
              class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
              <svg
                class="h-5 w-5 text-indigo-600 mr-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Requisitos
            </h3>
            <p class="text-gray-700">{{ program.requirements }}</p>
          </div>

          <!-- Itinerary -->
          <div
            v-if="program.itinerary"
            class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h3
              class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
              <svg
                class="h-5 w-5 text-indigo-600 mr-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              Itinerario
            </h3>
            <p class="text-gray-700 whitespace-pre-line">
              {{ program.itinerary }}
            </p>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
          <!-- Booking Card -->
          <div class="bg-white rounded-lg shadow-sm p-6 sticky top-8">
            <div class="text-center mb-6">
              <div class="text-3xl font-bold text-indigo-600">
                ${{ formatPrice(program.base_price) }}
              </div>
              <div class="text-gray-500">CLP por persona</div>
            </div>

            <!-- Availability Status -->
            <div
              class="mb-6 p-4 rounded-lg"
              :class="
                program.is_available
                  ? 'bg-green-50 border border-green-200'
                  : 'bg-red-50 border border-red-200'
              ">
              <div class="flex items-center justify-center">
                <svg
                  v-if="program.is_available"
                  class="h-5 w-5 text-green-600 mr-2"
                  fill="currentColor"
                  viewBox="0 0 20 20">
                  <path
                    fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd" />
                </svg>
                <svg
                  v-else
                  class="h-5 w-5 text-red-600 mr-2"
                  fill="currentColor"
                  viewBox="0 0 20 20">
                  <path
                    fill-rule="evenodd"
                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                    clip-rule="evenodd" />
                </svg>
                <span
                  :class="
                    program.is_available ? 'text-green-700' : 'text-red-700'
                  "
                  class="font-semibold">
                  {{
                    program.is_available ? "Disponible" : "Sin Disponibilidad"
                  }}
                </span>
              </div>
              <div
                class="text-center mt-2"
                :class="
                  program.is_available ? 'text-green-600' : 'text-red-600'
                ">
                {{ program.available_spots }}
                {{
                  program.available_spots === 1
                    ? "cupo disponible"
                    : "cupos disponibles"
                }}
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3">
              <Link
                v-if="program.is_available"
                :href="route('ecommerce.reservation', program.id)"
                class="w-full bg-indigo-600 text-white text-center py-3 px-6 rounded-lg hover:bg-indigo-700 transition duration-200 font-semibold flex items-center justify-center">
                <svg
                  class="h-5 w-5 mr-2"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Reservar Ahora
              </Link>
              <button
                v-else
                disabled
                class="w-full bg-gray-300 text-gray-500 text-center py-3 px-6 rounded-lg cursor-not-allowed font-semibold">
                Sin Disponibilidad
              </button>
              <Link
                href="/"
                class="w-full bg-gray-100 text-gray-700 text-center py-3 px-6 rounded-lg hover:bg-gray-200 transition duration-200 font-medium">
                Volver a Programas
              </Link>
            </div>

            <!-- Contact Info -->
            <div class="mt-8 pt-6 border-t border-gray-200">
              <h4 class="font-semibold text-gray-900 mb-3">
                ¿Necesitas ayuda?
              </h4>
              <div class="space-y-2 text-sm text-gray-600">
                <div class="flex items-center">
                  <svg
                    class="h-4 w-4 mr-2"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  info@lat90.com
                </div>
                <div class="flex items-center">
                  <svg
                    class="h-4 w-4 mr-2"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                  +56 2 2345 6789
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Related Programs -->
      <div
        v-if="relatedPrograms.length > 0"
        class="mt-16">
        <h2 class="text-2xl font-bold text-gray-900 mb-8">
          Programas Relacionados
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div
            v-for="relatedProgram in relatedPrograms"
            :key="relatedProgram.id"
            class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition duration-300">
            <div
              class="h-32 bg-gradient-to-r from-indigo-400 to-purple-500 flex items-center justify-center">
              <h3 class="text-white font-semibold text-center px-4">
                {{ relatedProgram.name }}
              </h3>
            </div>
            <div class="p-4">
              <p class="text-sm text-gray-600 mb-2">
                {{ relatedProgram.destination }}
              </p>
              <p class="text-lg font-bold text-indigo-600 mb-3">
                ${{ formatPrice(relatedProgram.base_price) }} CLP
              </p>
              <Link
                :href="route('ecommerce.show', relatedProgram.id)"
                class="w-full bg-indigo-600 text-white text-center py-2 px-4 rounded hover:bg-indigo-700 transition duration-200 text-sm">
                Ver Detalles
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import { Link, Head } from "@inertiajs/vue3";
  import Header from "@/Components/Ecommerce/Header.vue";

  export default {
    components: {
      Link,
      Header
    },
    props: {
      program: Object,
      relatedPrograms: Array
    },
    setup() {
      const formatServiceType = (type) => {
        const types = {
          tours: "Tours",
          excursiones: "Excursiones",
          intercambio: "Intercambios",
          cruceros: "Cruceros"
        };
        return types[type] || type;
      };

      const formatPrice = (price) => {
        return new Intl.NumberFormat("es-CL").format(price);
      };

      const formatDate = (date) => {
        return new Date(date).toLocaleDateString("es-CL", {
          weekday: "long",
          day: "2-digit",
          month: "long",
          year: "numeric"
        });
      };

      return {
        formatServiceType,
        formatPrice,
        formatDate
      };
    }
  };
</script>
