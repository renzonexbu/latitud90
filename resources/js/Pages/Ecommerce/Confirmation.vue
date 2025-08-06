<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header></header>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Success Message -->
      <div class="text-center mb-8">
        <div
          class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
          <svg
            class="w-8 h-8 text-green-600"
            fill="currentColor"
            viewBox="0 0 20 20">
            <path
              fill-rule="evenodd"
              d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
              clip-rule="evenodd" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          {{
            reservationStatus === "confirmed"
              ? "¡Reserva Confirmada!"
              : "¡Reserva Recibida!"
          }}
        </h1>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
          <span v-if="reservationStatus === 'confirmed'">
            Tu reserva ha sido confirmada exitosamente. Recibirás un email con
            todos los detalles.
          </span>
          <span v-else-if="reservationStatus === 'pending_payment'">
            Hemos recibido tu reserva. Te contactaremos pronto para confirmar el
            pago y finalizar el proceso.
          </span>
          <span v-else>
            Tu reserva está siendo procesada. Te mantendremos informado sobre el
            estado de tu reserva.
          </span>
        </p>
      </div>

      <!-- Progress Steps -->
      <div class="mb-8">
        <div class="flex items-center justify-center space-x-8">
          <div class="flex items-center">
            <div
              class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center">
              <svg
                class="w-5 h-5 text-white"
                fill="currentColor"
                viewBox="0 0 20 20">
                <path
                  fill-rule="evenodd"
                  d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                  clip-rule="evenodd" />
              </svg>
            </div>
            <span class="ml-2 text-sm font-medium text-gray-900">Reserva</span>
          </div>
          <div class="w-16 h-0.5 bg-green-600"></div>
          <div class="flex items-center">
            <div
              class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center">
              <svg
                class="w-5 h-5 text-white"
                fill="currentColor"
                viewBox="0 0 20 20">
                <path
                  fill-rule="evenodd"
                  d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                  clip-rule="evenodd" />
              </svg>
            </div>
            <span class="ml-2 text-sm font-medium text-gray-900">Pago</span>
          </div>
          <div class="w-16 h-0.5 bg-green-600"></div>
          <div class="flex items-center">
            <div
              class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center">
              <svg
                class="w-5 h-5 text-white"
                fill="currentColor"
                viewBox="0 0 20 20">
                <path
                  fill-rule="evenodd"
                  d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                  clip-rule="evenodd" />
              </svg>
            </div>
            <span class="ml-2 text-sm font-medium text-gray-900"
              >Confirmación</span
            >
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Reservation Details -->
        <div class="bg-white rounded-lg shadow-sm">
          <div class="p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
              Detalles de la Reserva
            </h2>

            <!-- Reservation Number -->
            <div class="bg-gray-50 rounded-md p-4 mb-6">
              <div class="text-center">
                <p class="text-sm text-gray-600">Número de Reserva</p>
                <p class="text-2xl font-bold text-indigo-600">
                  {{ reservationNumber }}
                </p>
                <p class="text-xs text-gray-500 mt-1">
                  Guarda este número para futuras consultas
                </p>
              </div>
            </div>

            <!-- Program Information -->
            <div class="space-y-4">
              <div>
                <h3 class="font-medium text-gray-900">Programa</h3>
                <p class="text-gray-600">{{ program.name }}</p>
              </div>

              <div>
                <h3 class="font-medium text-gray-900">Destino</h3>
                <p class="text-gray-600">{{ program.destination }}</p>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <h3 class="font-medium text-gray-900">Fecha de Salida</h3>
                  <p class="text-gray-600">
                    {{ formatDate(program.departure_date) }}
                  </p>
                </div>
                <div>
                  <h3 class="font-medium text-gray-900">Duración</h3>
                  <p class="text-gray-600">
                    {{ program.duration_days }}
                    {{ program.duration_days === 1 ? "día" : "días" }}
                  </p>
                </div>
              </div>

              <div
                v-if="
                  program.return_date &&
                  program.return_date !== program.departure_date
                ">
                <h3 class="font-medium text-gray-900">Fecha de Regreso</h3>
                <p class="text-gray-600">
                  {{ formatDate(program.return_date) }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Passenger Information -->
        <div class="bg-white rounded-lg shadow-sm">
          <div class="p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
              Información del Pasajero
            </h2>

            <div class="space-y-4">
              <div>
                <h3 class="font-medium text-gray-900">Nombre Completo</h3>
                <p class="text-gray-600">{{ passenger.full_name }}</p>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <h3 class="font-medium text-gray-900">Email</h3>
                  <p class="text-gray-600">{{ passenger.email }}</p>
                </div>
                <div>
                  <h3 class="font-medium text-gray-900">Teléfono</h3>
                  <p class="text-gray-600">{{ passenger.phone }}</p>
                </div>
              </div>

              <div>
                <h3 class="font-medium text-gray-900">Documento</h3>
                <p class="text-gray-600">
                  {{ getDocumentTypeLabel(passenger.document_type) }}:
                  {{ passenger.document_number }}
                </p>
              </div>

              <div v-if="passenger.dietary_restrictions">
                <h3 class="font-medium text-gray-900">
                  Restricciones Alimentarias
                </h3>
                <p class="text-gray-600">
                  {{ passenger.dietary_restrictions }}
                </p>
              </div>

              <div v-if="passenger.medical_conditions">
                <h3 class="font-medium text-gray-900">Condiciones Médicas</h3>
                <p class="text-gray-600">{{ passenger.medical_conditions }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Payment Information -->
        <div class="bg-white rounded-lg shadow-sm">
          <div class="p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
              Información de Pago
            </h2>

            <div class="space-y-4">
              <div class="flex justify-between items-center py-2 border-b">
                <span class="text-gray-600">Precio Base</span>
                <span class="font-medium"
                  >${{ formatPrice(passenger.individual_price) }}</span
                >
              </div>

              <div
                v-if="
                  passenger.price_adjustments &&
                  passenger.price_adjustments != 0
                "
                class="flex justify-between items-center py-2 border-b">
                <span class="text-gray-600">
                  Ajuste de Precio
                  <span
                    v-if="passenger.adjustment_reason"
                    class="text-xs text-gray-500 block"
                    >{{ passenger.adjustment_reason }}</span
                  >
                </span>
                <span
                  :class="
                    passenger.price_adjustments > 0
                      ? 'text-red-600'
                      : 'text-green-600'
                  "
                  class="font-medium">
                  {{ passenger.price_adjustments > 0 ? "+" : "" }}${{
                    formatPrice(Math.abs(passenger.price_adjustments))
                  }}
                </span>
              </div>

              <div
                class="flex justify-between items-center py-2 text-lg font-bold">
                <span>Total</span>
                <span class="text-indigo-600"
                  >${{ formatPrice(totalAmount) }}</span
                >
              </div>

              <div class="mt-4">
                <div class="flex items-center space-x-2">
                  <div
                    class="w-3 h-3 rounded-full"
                    :class="statusColors[passenger.status]"></div>
                  <span
                    class="text-sm font-medium"
                    :class="statusTextColors[passenger.status]">
                    {{ getStatusLabel(passenger.status) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Next Steps -->
        <div class="bg-white rounded-lg shadow-sm">
          <div class="p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Próximos Pasos</h2>

            <div class="space-y-4">
              <div class="flex items-start space-x-3">
                <div
                  class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                  <span class="text-indigo-600 text-sm font-bold">1</span>
                </div>
                <div>
                  <h3 class="font-medium text-gray-900">
                    Confirmación por Email
                  </h3>
                  <p class="text-sm text-gray-600">
                    Recibirás un email de confirmación con todos los detalles en
                    los próximos minutos.
                  </p>
                </div>
              </div>

              <div class="flex items-start space-x-3">
                <div
                  class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                  <span class="text-indigo-600 text-sm font-bold">2</span>
                </div>
                <div>
                  <h3 class="font-medium text-gray-900">Documentación</h3>
                  <p class="text-sm text-gray-600">
                    Te enviaremos la documentación necesaria y lista de equipaje
                    recomendado.
                  </p>
                </div>
              </div>

              <div class="flex items-start space-x-3">
                <div
                  class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                  <span class="text-indigo-600 text-sm font-bold">3</span>
                </div>
                <div>
                  <h3 class="font-medium text-gray-900">Contacto Pre-Viaje</h3>
                  <p class="text-sm text-gray-600">
                    Nos pondremos en contacto contigo una semana antes del viaje
                    con detalles finales.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
        <Link
          href="/"
          class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition duration-200 text-center">
          Explorar Más Programas
        </Link>
        <Link
          :href="route('ecommerce.find-reservation')"
          class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-md hover:bg-gray-50 transition duration-200 text-center">
          Buscar Mi Reserva
        </Link>
        <button
          @click="printConfirmation"
          class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-md hover:bg-gray-50 transition duration-200">
          Imprimir Confirmación
        </button>
      </div>

      <!-- Contact Information -->
      <div class="mt-12 bg-indigo-50 rounded-lg p-6 text-center">
        <h3 class="text-lg font-bold text-indigo-900 mb-2">
          ¿Necesitas Ayuda?
        </h3>
        <p class="text-indigo-700 mb-4">
          Nuestro equipo está disponible para ayudarte con cualquier consulta.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <a
            href="mailto:reservas@lat90.cl"
            class="text-indigo-600 hover:text-indigo-500 font-medium">
            📧 reservas@lat90.cl
          </a>
          <a
            href="tel:+56222334455"
            class="text-indigo-600 hover:text-indigo-500 font-medium">
            📞 +56 2 2233 4455
          </a>
          <a
            href="https://wa.me/56912345678"
            class="text-indigo-600 hover:text-indigo-500 font-medium">
            💬 WhatsApp
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import { Link } from "@inertiajs/vue3";
  import Header from "@/Components/Ecommerce/Header.vue";

  export default {
    name: "Confirmation",
    components: {
      Link,
      Header
    },
    props: {
      passenger: {
        type: Object,
        required: true
      },
      program: {
        type: Object,
        required: true
      },
      reservationNumber: {
        type: String,
        required: true
      }
    },
    data() {
      return {
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
        }
      };
    },
    computed: {
      reservationStatus() {
        return this.passenger.status;
      },
      totalAmount() {
        const basePrice = parseFloat(this.passenger.individual_price);
        const adjustments = parseFloat(this.passenger.price_adjustments || 0);
        return basePrice + adjustments;
      }
    },
    methods: {
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
      getDocumentTypeLabel(type) {
        const types = {
          rut: "RUT",
          cedula: "Cédula",
          passport: "Pasaporte"
        };
        return types[type] || type;
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
      printConfirmation() {
        window.print();
      }
    },
    mounted() {
      // Auto-scroll to top
      window.scrollTo(0, 0);
    }
  };
</script>

<style scoped>
  @media print {
    .no-print {
      display: none !important;
    }

    body {
      background: white !important;
    }
  }
</style>
