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
const stage = ref('checking')

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


