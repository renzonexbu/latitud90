<template>
  <div class="min-h-screen bg-gray-50 w-full p-3">
    <!-- Header -->
    <Header class="bg-transparent text-blanco shadow-none"> </Header>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto py-8 px-4">
      <!-- Success Icon and Title -->
      <div class="text-center mb-8">
        <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">¡Pago Exitoso!</h1>
        <p class="text-lg text-gray-600">
          <span v-if="paymentData.total_installments > 1">
            Tu cuota {{ paymentData.installment_number }} de {{ paymentData.total_installments }} ha sido procesada correctamente.
          </span>
          <span v-else>
            Tu pago ha sido procesado correctamente.
          </span>
        </p>
      </div>

      <!-- Payment Details Card -->
      <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Detalles del Pago</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Información del Programa -->
          <div v-if="paymentData.program">
            <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Información del Programa</h3>
            <div class="space-y-3">
              <div>
                <span class="text-sm text-gray-500">Programa:</span>
                <p class="text-gray-900 font-medium">{{ paymentData.program.name }}</p>
              </div>
              <div>
                <span class="text-sm text-gray-500">Destino:</span>
                <p class="text-gray-900">{{ paymentData.program.destination }}</p>
              </div>
              <div v-if="paymentData.program.departure_date">
                <span class="text-sm text-gray-500">Fecha de Salida:</span>
                <p class="text-gray-900">{{ formatDate(paymentData.program.departure_date) }}</p>
              </div>
            </div>
          </div>

          <!-- Información del Pago -->
          <div>
            <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Información del Pago</h3>
            <div class="space-y-3">
              <div>
                <span class="text-sm text-gray-500">Número de Orden:</span>
                <p class="text-gray-900 font-mono">{{ paymentData.order?.order_number || 'N/A' }}</p>
              </div>
              <div>
                <span class="text-sm text-gray-500">Método de Pago:</span>
                <p class="text-gray-900">{{ getPaymentMethodName(paymentData.gateway_type) }}</p>
              </div>
              <div>
                <span class="text-sm text-gray-500">Monto Pagado:</span>
                <p class="text-gray-900 font-bold text-xl text-green-600">
                  ${{ formatCurrency(paymentData.amount) }}
                </p>
              </div>
              <div v-if="paymentData.transaction_id">
                <span class="text-sm text-gray-500">ID de Transacción:</span>
                <p class="text-gray-900 font-mono text-sm">{{ paymentData.transaction_id }}</p>
              </div>
              <div>
                <span class="text-sm text-gray-500">Fecha y Hora:</span>
                <p class="text-gray-900">{{ formatDateTime(paymentData.order_detail?.paid_at || paymentData.order_detail?.created_at) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Estado del Pago Total (si es en cuotas) -->
      <div v-if="paymentData.total_installments > 1" class="bg-white rounded-xl shadow-lg p-8 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Estado del Pago Total</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="text-center">
            <div class="text-2xl font-bold text-green-600">{{ paymentData.installment_number }}</div>
            <div class="text-sm text-gray-500">Cuota Actual</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-blue-600">{{ paymentData.total_installments }}</div>
            <div class="text-sm text-gray-500">Total de Cuotas</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-gray-600">
              {{ Math.round((paymentData.installment_number / paymentData.total_installments) * 100) }}%
            </div>
            <div class="text-sm text-gray-500">Progreso</div>
          </div>
        </div>

        <!-- Barra de progreso -->
        <div class="mt-6">
          <div class="bg-gray-200 rounded-full h-3">
            <div 
              class="bg-green-600 h-3 rounded-full transition-all duration-500"
              :style="{ width: `${(paymentData.installment_number / paymentData.total_installments) * 100}%` }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <button
          @click="downloadReceipt"
          class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-[#007E93] hover:bg-[#005a6b] transition-colors duration-300"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          Descargar Comprobante
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
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import Header from "@/Components/Ecommerce/Header.vue";
import Footer from "@/Components/Ecommerce/Footer.vue";

const props = defineProps({
  paymentData: {
    type: Object,
    required: true
  },
  rut: {
    type: String,
    default: ''
  }
})

// Funciones de formato
const formatCurrency = (amount) => {
  if (!amount) return "0"
  return new Intl.NumberFormat("es-CL").format(amount)
}

const formatDate = (dateString) => {
  if (!dateString) return "N/A"
  const date = new Date(dateString)
  const day = date.getDate()
  const month = date.getMonth() + 1
  const year = date.getFullYear()
  return `${day} de ${getMonthName(month)} ${year}`
}

const formatDateTime = (dateTimeString) => {
  if (!dateTimeString) return "N/A"
  const date = new Date(dateTimeString)
  return date.toLocaleString("es-CL", {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  })
}

const getMonthName = (month) => {
  const months = [
    "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
    "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
  ]
  return months[month - 1]
}

const getPaymentMethodName = (method) => {
  const methods = {
    'transbank': 'Tarjeta de Crédito/Débito',
    'khipu': 'Transferencia Khipu',
    'debit': 'Tarjeta de Débito',
    'credit': 'Tarjeta de Crédito'
  }
  return methods[method] || method || 'N/A'
}

// Funciones de navegación
const goToHome = () => {
  const homeUrl = props.rut ? `/?rut=${encodeURIComponent(props.rut)}` : '/'
  router.visit(homeUrl)
}



const downloadReceipt = () => {
  // Aquí puedes implementar la descarga del comprobante
  console.log('Descargando comprobante...')
  // Por ahora, mostrar un mensaje
  alert('Función de descarga de comprobante en desarrollo')
}

// Limpiar localStorage al montar
import { onMounted } from 'vue'

onMounted(() => {
  // Auto-scroll to top
  window.scrollTo(0, 0)
  
  // Registrar pago completado en analytics
  recordPaymentCompleted()
  
  // Limpiar localStorage después del pago exitoso
  localStorage.removeItem('selectedPaymentData')
  localStorage.removeItem('paymentFormData')
})

const recordPaymentCompleted = () => {
  // Obtener session_id desde localStorage
  const sessionId = localStorage.getItem('analytics_session_id')
  
  if (!sessionId) {
    console.warn('No se encontró session_id en localStorage')
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
  
  // Enviar datos de pago completado al backend
  fetch('/api/analytics/payment-completed', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
    body: JSON.stringify({
      session_id: sessionId,
      program_id: props.paymentData.program?.id || paymentData.programId,
      participant_rut: props.paymentData.order_detail?.document_number || props.rut,
      payment_data: {
        payment_type: paymentData.paymentType || 'total',
        payment_method: props.paymentData.gateway_type || paymentData.paymentMethod,
        amount: props.paymentData.amount,
        terms_accepted: paymentData.termsAccepted || false,
        transaction_id: props.paymentData.transaction_id,
      }
    })
  }).catch(error => {
    console.error('Error recording payment completed:', error)
  })
}
</script>
