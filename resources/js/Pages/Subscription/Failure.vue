<template>
  <div class="min-h-screen bg-gray-50 w-full p-3">
    <!-- Header -->
    <Header class="bg-transparent text-blanco shadow-none"></Header>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto py-8 px-4">
      <!-- Error Icon and Title -->
      <div class="text-center mb-8">
        <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Error en la Suscripción</h1>
        <p class="text-lg text-gray-600">
          No se pudo procesar tu suscripción correctamente.
        </p>
      </div>

      <!-- Error Details Card -->
      <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Detalles del Error</h2>

        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
          <p class="text-red-800">{{ error || 'Hubo un problema al procesar tu suscripción. Por favor, intenta nuevamente.' }}</p>
        </div>

        <div class="space-y-3 text-gray-700">
          <p class="font-medium">¿Qué puedes hacer?</p>
          <ul class="list-disc list-inside space-y-2 text-sm">
            <li>Verifica que tu tarjeta tenga fondos suficientes</li>
            <li>Asegúrate de que los datos de tu tarjeta sean correctos</li>
            <li>Intenta con otro método de pago</li>
            <li>Contacta con tu banco si el problema persiste</li>
          </ul>
        </div>
      </div>

      <!-- Support Information -->
      <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-8">
        <div class="flex items-start">
          <svg class="w-6 h-6 text-blue-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <div>
            <h3 class="text-lg font-semibold text-blue-900 mb-2">¿Necesitas ayuda?</h3>
            <p class="text-blue-800 text-sm mb-2">
              Si el problema persiste, nuestro equipo de soporte está aquí para ayudarte.
            </p>
            <p class="text-blue-800 text-sm">
              Contáctanos a través de nuestros canales de atención al cliente.
            </p>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <button
          @click="tryAgain"
          class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-[#007E93] hover:bg-[#005a6b] transition-colors duration-300"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          Intentar Nuevamente
        </button>

        <button
          @click="goToHome"
          class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-300"
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
import { onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import Header from "@/Components/Ecommerce/Header.vue"
import Footer from "@/Components/Ecommerce/Footer.vue"

const props = defineProps({
  subscription_id: {
    type: Number,
    default: null
  },
  error: {
    type: String,
    default: 'Error desconocido'
  }
})

// Funciones de navegación
const goToHome = () => {
  router.visit('/')
}

const tryAgain = () => {
  // Volver a la página de programas para intentar de nuevo
  router.visit('/programs')
}

// Registro de analytics al montar
onMounted(() => {
  // Auto-scroll to top
  window.scrollTo(0, 0)

  // Registrar fallo en analytics
  recordPaymentFailed()
})

const recordPaymentFailed = () => {
  const sessionId = localStorage.getItem('analytics_session_id')

  if (!sessionId) {
    return
  }

  // Obtener datos del pago desde localStorage
  const storedData = localStorage.getItem('selectedPaymentData')
  let paymentData = {}
  if (storedData) {
    try {
      paymentData = JSON.parse(storedData)
    } catch (e) {
      console.warn('Error parsing stored payment data:', e)
    }
  }

  // Enviar datos de pago fallido al backend
  fetch('/api/analytics/payment-failed', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
    body: JSON.stringify({
      session_id: sessionId,
      program_id: paymentData.programId,
      error_message: props.error,
      subscription_id: props.subscription_id,
    })
  }).catch(error => {
    console.error('Error recording payment failed:', error)
  })
}
</script>
