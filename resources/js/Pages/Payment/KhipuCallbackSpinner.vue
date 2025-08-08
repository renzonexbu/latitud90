<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center">
      <svg class="animate-spin h-10 w-10 text-teal-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
      </svg>
      <h2 class="mt-4 text-gray-700 font-medium">Verificando pago con Khipu...</h2>
      <p class="text-sm text-gray-500">Por favor, no cierres esta página.</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  orderDetailId: { type: Number, required: true },
  paymentId: { type: String, required: true },
})

onMounted(async () => {
  try {
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
    } else {
      router.visit(`/payment/failure/${props.orderDetailId}`)
    }
  } catch (e) {
    router.visit(`/payment/failure/${props.orderDetailId}`)
  }
})
</script>
