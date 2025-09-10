<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header></header>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Page Header -->
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Buscar Mi Reserva</h1>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
          Encuentra tu reserva usando tu número de reserva y email, o busca por
          tus datos personales.
        </p>
      </div>

      <!-- Search Form -->
      <div class="bg-white rounded-lg shadow-sm mb-8">
        <div class="p-6">
          <!-- Search Method Tabs -->
          <div class="flex space-x-1 bg-gray-100 rounded-lg p-1 mb-6">
            <button
              @click="searchMethod = 'reservation'"
              :class="
                searchMethod === 'reservation'
                  ? 'bg-white text-indigo-600 shadow-sm'
                  : 'text-gray-500 hover:text-gray-700'
              "
              class="flex-1 py-2 px-4 text-sm font-medium rounded-md transition-colors">
              Número de Reserva
            </button>
            <button
              @click="searchMethod = 'personal'"
              :class="
                searchMethod === 'personal'
                  ? 'bg-white text-indigo-600 shadow-sm'
                  : 'text-gray-500 hover:text-gray-700'
              "
              class="flex-1 py-2 px-4 text-sm font-medium rounded-md transition-colors">
              Datos Personales
            </button>
          </div>

          <!-- Search by Reservation Number -->
          <div v-if="searchMethod === 'reservation'">
            <form @submit.prevent="searchByReservation">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label
                    for="reservation_number"
                    class="block text-sm font-medium text-gray-700 mb-2">
                    Número de Reserva *
                  </label>
                  <input
                    id="reservation_number"
                    v-model="reservationForm.reservation_number"
                    type="text"
                    required
                    placeholder="RES-2024-001234"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    :class="{ 'border-red-500': errors.reservation_number }" />
                  <div
                    v-if="errors.reservation_number"
                    class="mt-1 text-sm text-red-600">
                    {{ errors.reservation_number }}
                  </div>
                </div>

                <div>
                  <label
                    for="email_reservation"
                    class="block text-sm font-medium text-gray-700 mb-2">
                    Email de Confirmación *
                  </label>
                  <input
                    id="email_reservation"
                    v-model="reservationForm.email"
                    type="email"
                    required
                    placeholder="tu@email.com"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    :class="{ 'border-red-500': errors.email }" />
                  <div
                    v-if="errors.email"
                    class="mt-1 text-sm text-red-600">
                    {{ errors.email }}
                  </div>
                </div>
              </div>

              <div class="mt-6">
                <button
                  type="submit"
                  :disabled="reservationForm.processing"
                  class="w-full md:w-auto px-6 py-3 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                  <span v-if="reservationForm.processing">Buscando...</span>
                  <span v-else>Buscar Reserva</span>
                </button>
              </div>
            </form>
          </div>

          <!-- Search by Personal Data -->
          <div v-if="searchMethod === 'personal'">
            <form @submit.prevent="searchByPersonalData">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label
                    for="full_name"
                    class="block text-sm font-medium text-gray-700 mb-2">
                    Nombre Completo *
                  </label>
                  <input
                    id="full_name"
                    v-model="personalForm.full_name"
                    type="text"
                    required
                    placeholder="Juan Pérez"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    :class="{ 'border-red-500': errors.full_name }" />
                  <div
                    v-if="errors.full_name"
                    class="mt-1 text-sm text-red-600">
                    {{ errors.full_name }}
                  </div>
                </div>

                <div>
                  <label
                    for="email_personal"
                    class="block text-sm font-medium text-gray-700 mb-2">
                    Email *
                  </label>
                  <input
                    id="email_personal"
                    v-model="personalForm.email"
                    type="email"
                    required
                    placeholder="tu@email.com"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    :class="{ 'border-red-500': errors.email }" />
                  <div
                    v-if="errors.email"
                    class="mt-1 text-sm text-red-600">
                    {{ errors.email }}
                  </div>
                </div>

                <div>
                  <label
                    for="document_type"
                    class="block text-sm font-medium text-gray-700 mb-2">
                    Tipo de Documento
                  </label>
                  <select
                    id="document_type"
                    v-model="personalForm.document_type"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Seleccionar tipo</option>
                    <option value="rut">RUT (Chile)</option>
                    <option value="cedula">Cédula</option>
                    <option value="passport">Pasaporte</option>
                  </select>
                </div>

                <div>
                  <label
                    for="document_number"
                    class="block text-sm font-medium text-gray-700 mb-2">
                    Número de Documento
                  </label>
                  <input
                    id="document_number"
                    v-model="personalForm.document_number"
                    type="text"
                    placeholder="12345678-9"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" />
                </div>
              </div>

              <div class="mt-6">
                <button
                  type="submit"
                  :disabled="personalForm.processing"
                  class="w-full md:w-auto px-6 py-3 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                  <span v-if="personalForm.processing">Buscando...</span>
                  <span v-else>Buscar Reservas</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Search Results -->
      <div
        v-if="searchResults.length > 0"
        class="space-y-6">
        <h2 class="text-xl font-bold text-gray-900">Resultados de Búsqueda</h2>

        <div
          v-for="reservation in searchResults"
          :key="reservation.id"
          class="bg-white rounded-lg shadow-sm overflow-hidden">
          <div class="p-6">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <!-- Reservation Header -->
                <div class="flex items-center space-x-4 mb-4">
                  <div>
                    <h3 class="text-lg font-semibold text-gray-900">
                      {{ reservation.program.name }}
                    </h3>
                    <p class="text-sm text-gray-600">
                      Reserva #{{
                        reservation.reservation_number ||
                        `RES-${reservation.id}`
                      }}
                    </p>
                  </div>
                  <div class="flex items-center space-x-2">
                    <div
                      class="w-3 h-3 rounded-full"
                      :class="statusColors[reservation.status]"></div>
                    <span
                      class="text-sm font-medium"
                      :class="statusTextColors[reservation.status]">
                      {{ getStatusLabel(reservation.status) }}
                    </span>
                  </div>
                </div>

                <!-- Reservation Details -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                  <div>
                    <p class="text-sm text-gray-500">Destino</p>
                    <p class="font-medium text-gray-900">
                      {{ reservation.program.destination }}
                    </p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Fecha de Salida</p>
                    <p class="font-medium text-gray-900">
                      {{ formatDate(reservation.program.departure_date) }}
                    </p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Duración</p>
                    <p class="font-medium text-gray-900">
                      {{ reservation.program.duration_days }}
                      {{
                        reservation.program.duration_days === 1 ? "día" : "días"
                      }}
                    </p>
                  </div>
                </div>

                <!-- Passenger Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                  <div>
                    <p class="text-sm text-gray-500">Pasajero</p>
                    <p class="font-medium text-gray-900">
                      {{ reservation.full_name }}
                    </p>
                    <p class="text-sm text-gray-600">{{ reservation.email }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Precio Total</p>
                    <p class="text-xl font-bold text-indigo-600">
                      ${{ formatPrice(getTotalAmount(reservation)) }}
                    </p>
                  </div>
                </div>

                <!-- Payment Status -->
                <div
                  v-if="reservation.payments && reservation.payments.length > 0"
                  class="mb-4">
                  <p class="text-sm text-gray-500 mb-2">Estado de Pagos</p>
                  <div class="space-y-2">
                    <div
                      v-for="payment in reservation.payments"
                      :key="payment.id"
                      class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                      <div>
                        <p class="text-sm font-medium text-gray-900">
                          ${{ formatPrice(payment.amount) }}
                        </p>
                        <p class="text-xs text-gray-600">
                          {{ payment.payment_method }} -
                          {{ formatDate(payment.created_at) }}
                        </p>
                      </div>
                      <div class="flex items-center space-x-2">
                        <div
                          class="w-2 h-2 rounded-full"
                          :class="paymentStatusColors[payment.status]"></div>
                        <span
                          class="text-xs font-medium"
                          :class="paymentStatusTextColors[payment.status]">
                          {{ getPaymentStatusLabel(payment.status) }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap gap-2">
                  <Link
                    :href="route('ecommerce.confirmation', reservation.id)"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 transition duration-200">
                    Ver Detalles
                  </Link>

                  <button
                    v-if="reservation.status === 'pending_payment'"
                    @click="redirectToPayment(reservation)"
                    class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition duration-200">
                    Completar Pago
                  </button>

                  <button
                    @click="downloadReservation(reservation)"
                    class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 transition duration-200">
                    Descargar PDF
                  </button>

                  <button
                    v-if="canCancelReservation(reservation)"
                    @click="showCancelModal(reservation)"
                    class="px-4 py-2 border border-red-300 text-red-700 text-sm font-medium rounded-md hover:bg-red-50 transition duration-200">
                    Cancelar
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- No Results -->
      <div
        v-else-if="hasSearched && searchResults.length === 0"
        class="text-center py-12">
        <div
          class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
          <svg
            class="w-8 h-8 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">
          No se encontraron reservas
        </h3>
        <p class="text-gray-600 mb-4">
          Verifica que la información ingresada sea correcta.
        </p>
        <button
          @click="resetSearch"
          class="text-indigo-600 hover:text-indigo-500 font-medium">
          Intentar nueva búsqueda
        </button>
      </div>

      <!-- Help Section -->
      <div class="mt-12 bg-blue-50 rounded-lg p-6">
        <h3 class="text-lg font-bold text-blue-900 mb-4">
          ¿No encuentras tu reserva?
        </h3>
        <div
          class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-800">
          <div>
            <h4 class="font-medium mb-2">Verifica que:</h4>
            <ul class="space-y-1 list-disc list-inside">
              <li>El número de reserva esté correcto</li>
              <li>El email sea el mismo usado al reservar</li>
              <li>No haya errores de escritura</li>
            </ul>
          </div>
          <div>
            <h4 class="font-medium mb-2">¿Necesitas ayuda?</h4>
            <div class="space-y-1">
              <p>📧 reservas@latitud90.cl</p>
              <p>📞 +56 2 2233 4455</p>
              <p>
                💬
                <a
                  href="https://wa.me/56912345678"
                  class="hover:underline"
                  >WhatsApp</a
                >
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import { Link, useForm } from "@inertiajs/vue3";
  import Header from "@/Components/Ecommerce/Header.vue";

  export default {
    name: "FindReservation",
    components: {
      Link,
      Header
    },
    props: {
      searchResults: {
        type: Array,
        default: () => []
      },
      errors: {
        type: Object,
        default: () => ({})
      }
    },
    data() {
      return {
        searchMethod: "reservation",
        hasSearched: false,
        statusColors: {
          pending_payment: "bg-yellow-400",
          confirmed: "bg-green-400",
          cancelled: "bg-red-400",
          pending_documentation: "bg-blue-400"
        },
        statusTextColors: {
          pending_payment: "text-yellow-800",
          confirmed: "text-green-800",
          cancelled: "text-red-800",
          pending_documentation: "text-blue-800"
        },
        paymentStatusColors: {
          pending: "bg-yellow-400",
          completed: "bg-green-400",
          failed: "bg-red-400",
          cancelled: "bg-gray-400"
        },
        paymentStatusTextColors: {
          pending: "text-yellow-800",
          completed: "text-green-800",
          failed: "text-red-800",
          cancelled: "text-gray-800"
        }
      };
    },
    setup() {
      const reservationForm = useForm({
        reservation_number: "",
        email: ""
      });

      const personalForm = useForm({
        full_name: "",
        email: "",
        document_type: "",
        document_number: ""
      });

      return {
        reservationForm,
        personalForm
      };
    },
    methods: {
      searchByReservation() {
        this.hasSearched = true;
        this.reservationForm.post(route("ecommerce.search-reservation"), {
          preserveState: true,
          preserveScroll: true
        });
      },
      searchByPersonalData() {
        this.hasSearched = true;
        this.personalForm.post(route("ecommerce.search-reservation"), {
          preserveState: true,
          preserveScroll: true
        });
      },
      resetSearch() {
        this.hasSearched = false;
        this.reservationForm.reset();
        this.personalForm.reset();
      },
      formatDate(date) {
        return new Date(date).toLocaleDateString("es-ES", {
          year: "numeric",
          month: "long",
          day: "numeric"
        });
      },
      formatPrice(price) {
        return new Intl.NumberFormat("es-CL", {
          style: "currency",
          currency: "CLP"
        })
          .format(price)
          .replace("CLP", "")
          .trim();
      },
      getStatusLabel(status) {
        const labels = {
          pending_payment: "Pendiente de Pago",
          confirmed: "Confirmado",
          cancelled: "Cancelado",
          pending_documentation: "Pendiente Documentación"
        };
        return labels[status] || status;
      },
      getPaymentStatusLabel(status) {
        const labels = {
          pending: "Pendiente",
          completed: "Completado",
          failed: "Fallido",
          cancelled: "Cancelado"
        };
        return labels[status] || status;
      },
      getTotalAmount(reservation) {
        const basePrice = parseFloat(reservation.individual_price);
        const adjustments = parseFloat(reservation.price_adjustments || 0);
        return basePrice + adjustments;
      },
      redirectToPayment(reservation) {
        this.$inertia.visit(route("ecommerce.payment", reservation.id));
      },
      downloadReservation(reservation) {
        // This would typically generate and download a PDF
        window.print();
      },
      canCancelReservation(reservation) {
        const departureDate = new Date(reservation.program.departure_date);
        const now = new Date();
        const daysDifference = (departureDate - now) / (1000 * 60 * 60 * 24);

        return (
          daysDifference > 7 &&
          ["pending_payment", "confirmed"].includes(reservation.status)
        );
      },
      showCancelModal(reservation) {
        if (
          confirm(
            "¿Estás seguro de que deseas cancelar esta reserva? Esta acción no se puede deshacer."
          )
        ) {
        }
      }
    }
  };
</script>
