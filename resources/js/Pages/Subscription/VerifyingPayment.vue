<template>
  <div class="min-h-screen bg-gray-50 w-full p-3">
    <!-- Header -->
    <Header class="bg-transparent text-blanco shadow-none"></Header>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto py-8 px-4">
      <!-- Success Icon for Subscription Created -->
      <div class="text-center mb-8">
        <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          ¡Suscripción Creada con Éxito!
        </h1>
        <p class="text-lg text-gray-600">
          Tu suscripción ha sido registrada correctamente.
        </p>
      </div>

      <!-- Verification Card -->
      <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
        <!-- Loading State -->
        <div v-if="status === 'verifying'" class="text-center">
          <div class="flex justify-center mb-6">
            <div class="animate-spin rounded-full h-16 w-16 border-4 border-[#007E93] border-t-transparent"></div>
          </div>
          <h2 class="text-xl font-semibold text-gray-900 mb-2">Verificando primer cargo...</h2>
          <p class="text-gray-600 mb-4">
            Estamos confirmando el pago de tu primera cuota. Esto puede tomar unos segundos.
          </p>
          <p class="text-sm text-gray-500">
            Por favor no cierres esta ventana.
          </p>
        </div>

        <!-- Success State -->
        <div v-else-if="status === 'approved'" class="text-center">
          <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
          <h2 class="text-xl font-semibold text-green-700 mb-2">¡Primer cargo aprobado!</h2>
          <p class="text-gray-600">
            Redirigiendo a la página de confirmación...
          </p>
        </div>

        <!-- Failed State -->
        <div v-else-if="status === 'rejected'" class="text-center">
          <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </div>
          <h2 class="text-xl font-semibold text-red-700 mb-2">Primer cargo rechazado</h2>
          <p class="text-gray-600 mb-4">
            {{ errorMessage || 'No se pudo procesar el pago de la primera cuota.' }}
          </p>
          <p class="text-sm text-gray-500">
            Tu suscripción ha sido cancelada. Redirigiendo...
          </p>
        </div>

        <!-- Timeout/Error State -->
        <div v-else-if="status === 'timeout'" class="text-center">
          <div class="mx-auto w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <h2 class="text-xl font-semibold text-yellow-700 mb-2">Verificación en proceso</h2>
          <p class="text-gray-600 mb-4">
            La verificación del pago está tomando más tiempo del esperado.
          </p>
          <p class="text-sm text-gray-500 mb-6">
            Te notificaremos por correo electrónico cuando se confirme el estado de tu pago.
          </p>
          <button
            @click="goToHome"
            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-[#007E93] hover:bg-[#005a6b] transition-colors duration-300"
          >
            Volver al Inicio
          </button>
        </div>
      </div>

      <!-- Subscription Info Card -->
      <div v-if="subscription" class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-8">
        <div class="flex items-start">
          <svg class="w-6 h-6 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <div>
            <h3 class="text-lg font-semibold text-blue-900 mb-2">Información de la Suscripción</h3>
            <div class="text-blue-800 text-sm space-y-1">
              <p><strong>Programa:</strong> {{ subscription.program_name }}</p>
              <p><strong>Participante:</strong> {{ subscription.participant_name }}</p>
              <p><strong>Monto mensual:</strong> ${{ formatCurrency(subscription.amount) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Progress indicator -->
      <div v-if="status === 'verifying'" class="text-center text-sm text-gray-500">
        Intento {{ attempts }} de {{ maxAttempts }}
      </div>
    </div>

    <!-- Footer -->
    <Footer class="rounded-lg"></Footer>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import Header from "@/Components/Ecommerce/Header.vue"
import Footer from "@/Components/Ecommerce/Footer.vue"

const props = defineProps({
  subscription_id: {
    type: Number,
    required: true
  },
  subscription: {
    type: Object,
    default: null
  }
})

const status = ref('verifying') // 'verifying', 'approved', 'rejected', 'timeout'
const errorMessage = ref('')
const attempts = ref(0)
const maxAttempts = 20 // 20 intentos * 3 segundos = 60 segundos máximo
const pollInterval = 3000 // 3 segundos entre cada intento

let pollingTimer = null

const formatCurrency = (amount) => {
  if (!amount) return "0"
  return new Intl.NumberFormat("es-CL").format(amount)
}

const goToHome = () => {
  router.visit('/')
}

const checkFirstChargeStatus = async () => {
  attempts.value++

  try {
    const response = await fetch(`/api/subscription/check-first-charge/${props.subscription_id}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    })

    const data = await response.json()

    if (data.status === 'approved') {
      status.value = 'approved'
      stopPolling()
      // Redirigir a success después de mostrar el mensaje
      setTimeout(() => {
        router.visit(`/subscription/success/${props.subscription_id}`)
      }, 1500)
      return
    }

    if (data.status === 'rejected') {
      status.value = 'rejected'
      errorMessage.value = data.message || 'El pago fue rechazado por la pasarela.'
      stopPolling()
      // Redirigir a failure después de mostrar el mensaje
      setTimeout(() => {
        router.visit(`/subscription/failure/${props.subscription_id}?error=${encodeURIComponent(errorMessage.value)}`)
      }, 3000)
      return
    }

    // Si aún está pendiente/procesando, continuar polling
    if (attempts.value >= maxAttempts) {
      status.value = 'timeout'
      stopPolling()
      return
    }

  } catch (error) {
    console.error('Error checking charge status:', error)

    if (attempts.value >= maxAttempts) {
      status.value = 'timeout'
      stopPolling()
    }
  }
}

const startPolling = () => {
  // Primer check inmediato
  checkFirstChargeStatus()

  // Luego cada 3 segundos
  pollingTimer = setInterval(checkFirstChargeStatus, pollInterval)
}

const stopPolling = () => {
  if (pollingTimer) {
    clearInterval(pollingTimer)
    pollingTimer = null
  }
}

onMounted(() => {
  window.scrollTo(0, 0)
  startPolling()
})

onUnmounted(() => {
  stopPolling()
})
</script>
