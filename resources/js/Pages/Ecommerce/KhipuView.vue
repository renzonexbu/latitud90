<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-6">
    <div class="w-full max-w-lg bg-white rounded-xl border border-gray-200 shadow-sm p-6">
      <div v-if="stage === 'checking'" class="flex flex-col items-center text-center gap-4">
        <svg class="animate-spin h-8 w-8 text-[#007E93]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <h2 class="text-[#007E93] font-outfit text-xl font-semibold">Verificando tu pago…</h2>
        <p class="text-[#5B5B5B] text-sm">Estamos consultando el estado de tu transacción con Khipu. Intento {{ attempt }} de {{ maxAttempts }}.</p>
      </div>

      <div v-else class="flex flex-col items-center text-center gap-4">
        <h2 class="text-[#B45309] font-outfit text-xl font-semibold">No pudimos verificar tu pago</h2>
        <p class="text-[#5B5B5B] text-sm">
          No fue posible confirmar tu pago en este momento. Durante el día te enviaremos una notificación con el resultado.
        </p>
        <div class="flex gap-3 mt-2">
          <button @click="retry" class="px-4 py-2 rounded-full bg-[#FBBD51] text-white">Intentar nuevamente</button>
          <a :href="programsUrl" class="px-4 py-2 rounded-full bg-gray-200 text-gray-700">Volver a Programas</a>
        </div>
      </div>
    </div>
  </div>
  
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'

const props = defineProps({
  orderDetailId: { type: Number, required: true },
  paymentId: { type: String, default: '' },
  paymentData: { type: Object, default: null },
  rut: { type: String, default: '' }
})

const attempt = ref(0)
const maxAttempts = ref(5)
const stage = ref('checking') // 'checking' | 'pending'

const programsUrl = computed(() => {
  const r = props.rut ? `?rut=${encodeURIComponent(props.rut)}` : ''
  return `/programs${r}`
})

onMounted(async () => {
  await poll()
})

async function poll() {
  try {
    while (attempt.value < maxAttempts.value) {
      attempt.value += 1
      const res = await fetch('/khipu/confirm', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ orderDetailId: props.orderDetailId, payment_id: props.paymentId })
      })

      const data = await res.json()
      if (data && data.success && data.redirect) {
        window.location.href = data.redirect
        return
      }
      await new Promise(r => setTimeout(r, 2000))
    }
    stage.value = 'pending'
  } catch (e) {
    stage.value = 'pending'
  }
}

function retry() {
  attempt.value = 0
  stage.value = 'checking'
  poll()
}
</script>

<style scoped>
</style>

<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center">
    <div class="max-w-2xl w-full bg-white rounded-xl shadow p-8">
      <h2 class="text-2xl font-bold text-gray-900 mb-2" v-if="status==='pending'">Estamos verificando tu pago</h2>
      <h2 class="text-2xl font-bold text-gray-900 mb-2" v-else-if="status==='failed'">Aún no pudimos verificar el pago</h2>
      <h2 class="text-2xl font-bold text-gray-900 mb-2" v-else>Pago en verificación</h2>
      <p class="text-gray-600 mb-6" v-if="status==='pending'">Por favor, no cierres esta página. Esto puede tardar algunos segundos.</p>
      <p class="text-gray-600 mb-6" v-else-if="status==='failed'">No pudimos verificar el pago automáticamente. Intentaremos de nuevo.</p>
      <p class="text-gray-600 mb-6" v-else>Tu pago está siendo verificado. Durante el día te informaremos el resultado en tu correo.</p>

      <div v-if="paymentData" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
          <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Información del Programa</h3>
          <div class="mt-3 space-y-2">
            <div>
              <span class="text-sm text-gray-500">Programa:</span>
              <p class="text-gray-900">{{ paymentData.program?.name || 'N/A' }}</p>
            </div>
            <div>
              <span class="text-sm text-gray-500">Destino:</span>
              <p class="text-gray-900">{{ paymentData.program?.destination || 'N/A' }}</p>
            </div>
            <div>
              <span class="text-sm text-gray-500">Fecha de salida:</span>
              <p class="text-gray-900">{{ formatDate(paymentData.program?.departure_date) }}</p>
            </div>
          </div>
        </div>
        <div>
          <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Información del Pago</h3>
          <div class="mt-3 space-y-2">
            <div>
              <span class="text-sm text-gray-500">Número de Orden:</span>
              <p class="text-gray-900 font-mono">{{ paymentData.order_number || 'N/A' }}</p>
            </div>
            <div>
              <span class="text-sm text-gray-500">Método de Pago:</span>
              <p class="text-gray-900">{{ getPaymentMethodName(paymentData.payment_method) }}</p>
            </div>
            <div>
              <span class="text-sm text-gray-500">Monto:</span>
              <p class="text-gray-900 font-bold">${{ formatCurrency(paymentData.amount) }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3" v-if="status==='failed'">
        <button @click="retry" class="px-4 py-2 rounded bg-teal-600 text-white">Reintentar</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  orderDetailId: { type: Number, required: true },
  paymentId: { type: String, default: '' },
  paymentData: { type: Object, default: null },
})

const attempts = ref(0)
const maxAttempts = 5
const status = ref('pending')

const confirm = async () => {
  try {
    attempts.value++

    const form = new FormData()
    form.append('orderDetailId', String(props.orderDetailId))
    form.append('payment_id', props.paymentId)

    const response = await fetch('/khipu/confirm', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content,
      },
      body: form,
    })

    const data = await response.json()

    if (data && data.redirect) {
      window.location.href = data.redirect
      return
    }

    status.value = data?.status || 'pending'

    if (attempts.value < maxAttempts) {
      setTimeout(confirm, 2000)
    } else {
      status.value = 'failed'
    }
  } catch (e) {
    if (attempts.value < maxAttempts) {
      setTimeout(confirm, 2000)
    } else {
      status.value = 'failed'
    }
  }
}

const retry = () => {
  attempts.value = 0
  status.value = 'pending'
  confirm()
}

onMounted(() => {
  if (!props.paymentId) {
    router.visit(`/payment/failure/${props.orderDetailId}`)
  } else {
    confirm()
  }
})
</script>

<script>
export default {
  methods: {
    formatCurrency(amount) {
      if (!amount) return '0'
      return new Intl.NumberFormat('es-CL').format(amount)
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A'
      const date = new Date(dateString)
      const day = date.getDate()
      const month = date.getMonth() + 1
      const year = date.getFullYear()
      const months = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']
      return `${day} de ${months[month-1]} ${year}`
    },
    getPaymentMethodName(method) {
      const methods = { debit: 'Tarjeta de Débito', credit: 'Tarjeta de Crédito', khipu: 'Transferencia Khipu' }
      return methods[method] || method || 'N/A'
    },
  }
}
</script>
