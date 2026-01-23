<template>
  <div class="min-h-screen bg-gray-50 w-full p-3">
    <!-- Header -->
    <Header class="bg-transparent text-blanco shadow-none"></Header>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto py-8 px-4">
      <!-- Success Icon and Title -->
      <div class="text-center mb-8">
        <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          {{ payment_type === 'monthly' ? '¡Suscripción Exitosa!' : '¡Pago Exitoso!' }}
        </h1>
        <p class="text-lg text-gray-600">
          {{ successMessage }}
        </p>
      </div>

      <!-- Payment Details Card -->
      <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Detalles del Pago</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Información de la Suscripción (si es monthly) -->
          <div v-if="payment_type === 'monthly' && subscription">
            <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Información de la Suscripción</h3>
            <div class="space-y-3">
              <div>
                <span class="text-sm text-gray-500">Programa:</span>
                <p class="text-gray-900 font-medium">{{ subscription.program_name }}</p>
              </div>
              <div>
                <span class="text-sm text-gray-500">Participante:</span>
                <p class="text-gray-900">{{ subscription.participant_name }}</p>
              </div>
              <div>
                <span class="text-sm text-gray-500">Estado:</span>
                <p class="text-gray-900">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    {{ subscription.status }}
                  </span>
                </p>
              </div>
              <div>
                <span class="text-sm text-gray-500">Monto mensual:</span>
                <p class="text-gray-900 font-bold text-xl text-green-600">
                  ${{ formatCurrency(subscription.amount) }}
                </p>
              </div>
            </div>
          </div>

          <!-- Información del Pago Total (si es total) -->
          <div v-if="payment_type === 'total' && order">
            <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Información del Participante</h3>
            <div class="space-y-3">
              <div>
                <span class="text-sm text-gray-500">Participante:</span>
                <p class="text-gray-900 font-medium">{{ order.participant_name }}</p>
              </div>
              <div>
                <span class="text-sm text-gray-500">Monto Total:</span>
                <p class="text-gray-900 font-bold text-xl text-green-600">
                  ${{ formatCurrency(order.final_amount || order.total_amount) }}
                </p>
              </div>
            </div>
          </div>

          <!-- Información de la Orden -->
          <div v-if="order">
            <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Información de la Orden</h3>
            <div class="space-y-3">
              <div>
                <span class="text-sm text-gray-500">Número de Orden:</span>
                <p class="text-gray-900 font-mono">{{ order.order_number }}</p>
              </div>
              <div>
                <span class="text-sm text-gray-500">Monto Total:</span>
                <p class="text-gray-900 font-bold text-xl text-green-600">
                  ${{ formatCurrency(order.total_amount) }}
                </p>
              </div>
              <div>
                <span class="text-sm text-gray-500">Estado:</span>
                <p class="text-gray-900">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium" :class="getStatusClass(order.status)">
                    {{ getStatusText(order.status) }}
                  </span>
                </p>
              </div>
              <div>
                <span class="text-sm text-gray-500">Fecha y Hora:</span>
                <p class="text-gray-900">{{ formatDateTime(new Date()) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Información adicional para suscripciones -->
      <div v-if="payment_type === 'monthly'" class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-8">
        <div class="flex items-start">
          <svg class="w-6 h-6 text-blue-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <div>
            <h3 class="text-lg font-semibold text-blue-900 mb-2">Información importante sobre tu suscripción</h3>
            <ul class="text-blue-800 space-y-1 text-sm">
              <li>• Los próximos cobros se realizarán automáticamente cada mes</li>
              <li>• Recibirás una notificación antes de cada cobro</li>
              <li>• Puedes gestionar tu suscripción desde tu panel de guardian</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Mensaje de Email -->
      <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-8">
        <div class="flex items-start">
          <svg class="w-6 h-6 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
          </svg>
          <div>
            <h3 class="text-lg font-semibold text-blue-900 mb-2">Confirmacion por correo</h3>
            <p class="text-blue-800 text-sm">
              En unos minutos recibiras un correo electronico con la confirmacion de tu pago y los documentos asociados (comprobante y/o boleta).
            </p>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <button
          @click="goToHome"
          class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-[#007E93] hover:bg-[#005a6b] transition-colors duration-300"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
          </svg>
          Volver al Inicio
        </button>
      </div>
    </div>

    <!-- Footer -->
    <Footer class="rounded-lg"></Footer>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import Header from "@/Components/Ecommerce/Header.vue"
import Footer from "@/Components/Ecommerce/Footer.vue"

const props = defineProps({
  payment_type: {
    type: String,
    required: true,
    validator: (value) => ['monthly', 'total'].includes(value)
  },
  subscription: {
    type: Object,
    default: null
  },
  order: {
    type: Object,
    default: null
  },
  message: {
    type: String,
    default: ''
  }
})

// Mensaje de éxito según el tipo de pago
const successMessage = computed(() => {
  if (props.message) {
    return props.message
  }

  if (props.payment_type === 'monthly') {
    return 'Tu suscripción ha sido activada correctamente. El primer pago ha sido procesado.'
  }

  return 'Tu pago ha sido procesado correctamente.'
})

// Funciones de formato
const formatCurrency = (amount) => {
  if (!amount) return "0"
  return new Intl.NumberFormat("es-CL").format(amount)
}

const formatDateTime = (date) => {
  if (!date) return "N/A"
  return new Date(date).toLocaleString("es-CL", {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  })
}

const getStatusText = (status) => {
  const statuses = {
    'pending': 'Pendiente',
    'processing': 'En Proceso',
    'paid': 'Pagado',
    'completed': 'Completado',
    'cancelled': 'Cancelado'
  }
  return statuses[status] || status
}

const getStatusClass = (status) => {
  const classes = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'processing': 'bg-blue-100 text-blue-800',
    'paid': 'bg-green-100 text-green-800',
    'completed': 'bg-green-100 text-green-800',
    'cancelled': 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

// Funciones de navegación
const goToHome = () => {
  router.visit('/')
}

const downloadReceipt = () => {
  if (!props.order?.id) {
    alert('No se pudo generar el comprobante')
    return
  }

  // Generar URL para descargar comprobante
  const url = `/payment/receipt/download?order_id=${props.order.id}`
  window.open(url, '_blank')
}

// Limpiar localStorage al montar
onMounted(() => {
  // Auto-scroll to top
  window.scrollTo(0, 0)

  // Limpiar localStorage después del pago exitoso
  localStorage.removeItem('selectedPaymentData')
  localStorage.removeItem('paymentFormData')
})
</script>
