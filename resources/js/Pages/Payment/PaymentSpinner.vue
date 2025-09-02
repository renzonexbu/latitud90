<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center">
      <!-- Spinner animado -->
      <svg class="animate-spin h-12 w-12 text-teal-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
      </svg>
      
      <!-- Título dinámico según pasarela -->
      <h2 class="text-xl font-semibold text-gray-700 mb-2">
        {{ spinnerTitle }}
      </h2>
      
      <!-- Mensaje dinámico -->
      <p class="text-sm text-gray-500 mb-4">
        {{ spinnerMessage }}
      </p>
      
      <!-- Información adicional para Khipu -->
      <div v-if="gatewayType === 'khipu' && attempt > 0" class="text-xs text-gray-400">
        Intento {{ attempt }} de {{ maxAttempts }}
      </div>
      
      <!-- Barra de progreso para Khipu -->
      <div v-if="gatewayType === 'khipu'" class="w-48 mx-auto mt-4">
        <div class="bg-gray-200 rounded-full h-2">
          <div 
            class="bg-teal-600 h-2 rounded-full transition-all duration-300"
            :style="{ width: `${(attempt / maxAttempts) * 100}%` }"
          ></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  orderDetailId: { type: Number, required: true },
  gatewayType: { type: String, required: true },
  gatewayData: { type: Object, default: () => ({}) },
  rut: { type: String, default: '' },
  programId: { type: Number, default: 1 },
  sessionId: { type: String, default: '' }
})

const isProcessing = ref(false)

// Títulos y mensajes dinámicos según pasarela
const spinnerTitle = computed(() => {
  switch (props.gatewayType) {
    case 'transbank':
      return 'Confirmando tu pago con Transbank...'
    case 'khipu':
      return 'Verificando tu pago con Khipu...'
    default:
      return 'Confirmando tu pago...'
  }
})

const spinnerMessage = computed(() => {
  switch (props.gatewayType) {
    case 'transbank':
      return 'Estamos confirmando tu transacción. Por favor, no cierres esta página.'
    case 'khipu':
      return 'Estamos consultando el estado de tu transacción. Por favor, no cierres esta página.'
    default:
      return 'Por favor, no cierres esta página.'
  }
})

// Función para confirmar pago (una sola llamada, los reintentos se manejan en el backend)
const confirmPayment = async () => {
  if (isProcessing.value) return
  
  isProcessing.value = true
  
  try {
    console.log(`[PaymentSpinner] Confirmando pago ${props.gatewayType}`, {
      orderDetailId: props.orderDetailId,
      gatewayType: props.gatewayType,
      gatewayData: props.gatewayData,
    })

    const form = new FormData()
    form.append('orderDetailId', String(props.orderDetailId))
    form.append('gatewayType', props.gatewayType)
    
    // Obtener session_id desde localStorage si no está en props
    let sessionId = props.sessionId
    if (!sessionId) {
      sessionId = localStorage.getItem('analytics_session_id')
    }
    
    // Agregar session_id si está disponible
    if (sessionId) {
      form.append('session_id', sessionId)
      console.log('[PaymentSpinner] Session ID enviado:', sessionId)
    } else {
      console.warn('[PaymentSpinner] No se encontró session_id')
    }
    
    // Agregar datos específicos de la pasarela
    Object.entries(props.gatewayData).forEach(([key, value]) => {
      if (value) form.append(key, value)
    })

    const response = await fetch('/payment/confirm', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content,
      },
      body: form,
    })

    let data = null
    try {
      data = await response.json()
    } catch (e) {
      console.error('[PaymentSpinner] Error parsing JSON response:', e)
      data = null
    }

    console.log(`[PaymentSpinner] Respuesta de confirmación:`, {
      status: response.status,
      ok: response.ok,
      data
    })

    // Manejar diferentes estados de respuesta
    if (data) {
      if (data.status === 'approved') {
        // Pago aprobado - redirigir a éxito
        const successUrl = `/payment/success/${props.orderDetailId}`
        const separator = successUrl.includes('?') ? '&' : '?'
        const finalUrl = props.rut ? `${successUrl}${separator}rut=${encodeURIComponent(props.rut)}` : successUrl
        window.location.href = finalUrl
        return
      }
      
      if (data.status === 'rejected' || data.status === 'canceled' || data.status === 'error') {
        // Pago rechazado - redirigir a la página de fallo
        console.log('[PaymentSpinner] Pago rechazado/cancelado/error, redirigiendo a fallo')
        const failureUrl = `/payment/failure/${props.orderDetailId}?status=${data.status}&message=${encodeURIComponent(data.message || 'Pago no pudo ser procesado')}`
        const separator = failureUrl.includes('?') ? '&' : '?'
        const finalUrl = props.rut ? `${failureUrl}${separator}rut=${encodeURIComponent(props.rut)}` : failureUrl
        window.location.href = finalUrl
        return
      }
      
      if (data.status === 'pending_validation') {
        // Pago pendiente de validación - redirigir a fallo con mensaje especial
        console.log('[PaymentSpinner] Pago pendiente de validación')
        const failureUrl = `/payment/failure/${props.orderDetailId}?status=pending_validation&message=${encodeURIComponent(data.message || 'Pago pendiente de validación')}`
        const separator = failureUrl.includes('?') ? '&' : '?'
        const finalUrl = props.rut ? `${failureUrl}${separator}rut=${encodeURIComponent(props.rut)}` : failureUrl
        window.location.href = finalUrl
        return
      }
      
      if (data.status === 'pending') {
        // Pago pendiente - redirigir a fallo
        console.log('[PaymentSpinner] Pago pendiente, redirigiendo a fallo')
        const failureUrl = `/payment/failure/${props.orderDetailId}?status=pending&message=${encodeURIComponent(data.message || 'Pago pendiente')}`
        const separator = failureUrl.includes('?') ? '&' : '?'
        const finalUrl = props.rut ? `${failureUrl}${separator}rut=${encodeURIComponent(props.rut)}` : failureUrl
        window.location.href = finalUrl
        return
      }
      
      // Si es cualquier otro estado no reconocido, redirigir a fallo
      console.log('[PaymentSpinner] Estado no reconocido:', data.status, 'redirigiendo a fallo')
      const failureUrl = `/payment/failure/${props.orderDetailId}?status=unknown&message=${encodeURIComponent(data.message || 'Estado de pago no reconocido')}`
      const separator = failureUrl.includes('?') ? '&' : '?'
      const finalUrl = props.rut ? `${failureUrl}${separator}rut=${encodeURIComponent(props.rut)}` : failureUrl
      window.location.href = finalUrl
    } else {
      // Si no hay data, redirigir a fallo
      console.error('[PaymentSpinner] No se recibió respuesta válida')
      const failureUrl = `/payment/failure/${props.orderDetailId}?status=error`
      const separator = failureUrl.includes('?') ? '&' : '?'
      const finalUrl = props.rut ? `${failureUrl}${separator}rut=${encodeURIComponent(props.rut)}` : failureUrl
      window.location.href = finalUrl
    }

  } catch (error) {
    console.error('[PaymentSpinner] Error durante confirmación:', error)
    
    // En caso de error, redirigir a fallo
    const failureUrl = `/payment/failure/${props.orderDetailId}?status=error`
    const separator = failureUrl.includes('?') ? '&' : '?'
    const finalUrl = props.rut ? `${failureUrl}${separator}rut=${encodeURIComponent(props.rut)}` : failureUrl
    window.location.href = finalUrl
  } finally {
    isProcessing.value = false
  }
}

onMounted(() => {
  // Validar que tenemos los datos necesarios
  if (!props.orderDetailId) {
    console.error('[PaymentSpinner] orderDetailId requerido')
    router.visit('/')
    return
  }

  if (!props.gatewayType || props.gatewayType === 'unknown') {
    console.error('[PaymentSpinner] Tipo de pasarela no válido:', props.gatewayType)
    const failureUrl = `/payment/failure/${props.orderDetailId}`
    const separator = failureUrl.includes('?') ? '&' : '?'
    const finalUrl = props.rut ? `${failureUrl}${separator}rut=${encodeURIComponent(props.rut)}` : failureUrl
    router.visit(finalUrl)
    return
  }

  // Iniciar confirmación
  console.log('[PaymentSpinner] Iniciando confirmación de pago:', {
    orderDetailId: props.orderDetailId,
    gatewayType: props.gatewayType,
    gatewayData: props.gatewayData
  })
  
  confirmPayment()
})
</script>
