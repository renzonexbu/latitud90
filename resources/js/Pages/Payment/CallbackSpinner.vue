<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center">
      <svg class="animate-spin h-10 w-10 text-teal-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
      </svg>
      <h2 class="mt-4 text-gray-700 font-medium">Confirmando tu pago...</h2>
      <p class="text-sm text-gray-500">Por favor, no cierres esta página.</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  orderDetailId: { type: Number, required: true },
  token: { type: String, default: null },
  rut: { type: String, default: '' },
});

onMounted(async () => {
  try {
    const token = props.token || new URLSearchParams(window.location.search).get('token_ws');
    if (!token) {
      const qs = props.rut ? `?rut=${encodeURIComponent(props.rut)}` : ''
      router.visit(`/payment/failure/${props.orderDetailId}${qs}`);
      return;
    }

    const form = new FormData();
    form.append('orderDetailId', String(props.orderDetailId));
    form.append('token_ws', token);

    const response = await fetch('/payment/confirm', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content,
      },
      body: form,
    });

    const data = await response.json();
    if (data && data.redirect) {
      console.log('CallbackSpinner redirect received:', { redirect: data.redirect, rut: props.rut })
      // Asegurar que preserve rut si no viene en redirect
      if (!data.redirect.includes('rut=') && props.rut) {
        const sep = data.redirect.includes('?') ? '&' : '?'
        window.location.href = `${data.redirect}${sep}rut=${encodeURIComponent(props.rut)}`
      } else {
        window.location.href = data.redirect;
      }
    } else {
      console.log('CallbackSpinner no redirect, going to failure', { orderDetailId: props.orderDetailId, rut: props.rut })
      const qs = props.rut ? `?rut=${encodeURIComponent(props.rut)}` : ''
      router.visit(`/payment/failure/${props.orderDetailId}${qs}`);
    }
  } catch (e) {
    console.error('CallbackSpinner error:', e)
    const qs = props.rut ? `?rut=${encodeURIComponent(props.rut)}` : ''
    router.visit(`/payment/failure/${props.orderDetailId}${qs}`);
  }
});
</script>
