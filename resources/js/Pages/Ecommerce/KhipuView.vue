<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center">
    <div class="max-w-2xl w-full bg-white rounded-xl shadow p-8">
      <h2 class="text-2xl font-bold text-gray-900 mb-2" v-if="status==='pending'">Estamos verificando tu pago</h2>
      <h2 class="text-2xl font-bold text-gray-900 mb-2" v-else>No pudimos verificar el pago aún</h2>
      <p class="text-gray-600 mb-6" v-if="status==='pending'">Por favor, no cierres esta página. Esto puede tardar algunos segundos.</p>
      <p class="text-gray-600 mb-6" v-else>La transacción sigue en verificación. Esto puede tomar algunos minutos. Si se debitó el dinero, se confirmará automáticamente.</p>

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

      <div class="flex items-center gap-3" v-if="status!=='pending'">
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
