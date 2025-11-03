<template>
  <Head title="Verifica tu Email - Apoderado" />

  <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="success-card">
      <!-- Header con logo -->
      <div class="success-header">
        <div class="success-logo">
          <img src="/images/logo-color.png" alt="Latitud90" />
        </div>
      </div>

      <!-- Ícono de éxito -->
      <div class="success-icon-wrapper">
        <div class="success-icon-bg">
          <svg class="success-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76" />
          </svg>
        </div>
      </div>

      <h2 class="success-title">¡Registro Exitoso!</h2>

      <!-- Email info -->
      <div class="email-info-box">
        <p class="email-info-label">Hemos enviado un correo de verificación a:</p>
        <p class="email-info-address">{{ email }}</p>
      </div>

      <p class="success-message">
        Por favor revisa tu bandeja de entrada y haz clic en el enlace de verificación para activar tu cuenta.
      </p>

      <!-- Warning box -->
      <div class="warning-box">
        <svg class="warning-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
        </svg>
        <div class="warning-content">
          <p class="warning-title">El enlace expirará en 24 horas</p>
          <p class="warning-text">No olvides revisar tu carpeta de spam si no lo encuentras.</p>
        </div>
      </div>

      <!-- Reenviar verificación -->
      <div class="resend-section">
        <div class="divider"></div>
        <p class="resend-label">¿No recibiste el correo?</p>
        <form @submit.prevent="resendVerification">
          <button
            type="submit"
            :disabled="form.processing || resendSuccess"
            class="resend-button"
          >
            <svg v-if="!form.processing && !resendSuccess" class="button-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <svg v-if="form.processing" class="button-icon spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-if="resendSuccess" class="button-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span v-if="!resendSuccess">Reenviar correo de verificación</span>
            <span v-else>Correo reenviado exitosamente</span>
          </button>
        </form>
      </div>

      <!-- Link a Home -->
      <div class="home-link-section">
        <Link :href="route('ecommerce.index')" class="back-link">
          ← Volver al inicio
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  email: String
})

const resendSuccess = ref(false)

const form = useForm({
  email: props.email
})

const resendVerification = () => {
  form.post(route('guardian.resend-verification'), {
    preserveScroll: true,
    onSuccess: () => {
      resendSuccess.value = true
      setTimeout(() => {
        resendSuccess.value = false
      }, 3000)
    }
  })
}
</script>

<style scoped>
/* Container principal */
.success-card {
  background: #ffffff;
  border-radius: 20px;
  padding: 40px;
  max-width: 600px;
  width: 100%;
  box-shadow: 0px 4px 11.6px 0px rgba(163, 163, 163, 0.11);
  text-align: center;
}

/* Header */
.success-header {
  margin-bottom: 32px;
}

.success-logo {
  display: flex;
  justify-content: center;
}

.success-logo img {
  height: 60px;
  width: auto;
}

/* Success Icon */
.success-icon-wrapper {
  display: flex;
  justify-content: center;
  margin-bottom: 24px;
}

.success-icon-bg {
  background: #D1FAE5;
  border-radius: 50%;
  padding: 24px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.success-icon {
  width: 64px;
  height: 64px;
  color: #059669;
  stroke-width: 2;
}

/* Title */
.success-title {
  color: #1C4F4A;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 28px;
  line-height: 32px;
  font-weight: 700;
  margin-bottom: 24px;
}

/* Email Info Box */
.email-info-box {
  background: #EFF6FF;
  border-left: 4px solid #3B82F6;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 20px;
  text-align: left;
}

.email-info-label {
  color: #1E40AF;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 14px;
  line-height: 18px;
  margin-bottom: 8px;
}

.email-info-address {
  color: #1E3A8A;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 16px;
  line-height: 20px;
  font-weight: 700;
  word-break: break-all;
}

/* Success Message */
.success-message {
  color: #434343;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 14px;
  line-height: 20px;
  margin-bottom: 24px;
}

/* Warning Box */
.warning-box {
  background: #FEF3C7;
  border-left: 4px solid #F59E0B;
  border-radius: 8px;
  padding: 12px 16px;
  display: flex;
  gap: 12px;
  align-items: flex-start;
  text-align: left;
  margin-bottom: 32px;
}

.warning-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
  color: #D97706;
}

.warning-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.warning-title {
  color: #92400E;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 13px;
  line-height: 16px;
  font-weight: 700;
}

.warning-text {
  color: #92400E;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 12px;
  line-height: 16px;
}

/* Resend Section */
.resend-section {
  margin-top: 32px;
  padding-top: 24px;
}

.divider {
  width: 100%;
  height: 1px;
  background: #e5e5e5;
  margin-bottom: 20px;
}

.resend-label {
  color: #434343;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 14px;
  line-height: 18px;
  margin-bottom: 16px;
}

.resend-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: white;
  border: 1px solid #007E93;
  border-radius: 59px;
  padding: 11px 20px;
  color: #007E93;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 14px;
  line-height: 18px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
}

.resend-button:hover:not(:disabled) {
  background: #007E93;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 126, 147, 0.2);
}

.resend-button:disabled {
  background: #f5f5f5;
  border-color: #C7C7C7;
  color: #C7C7C7;
  cursor: not-allowed;
  transform: none;
}

.button-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Home Link Section */
.home-link-section {
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid #e5e5e5;
}

.back-link {
  color: #434343;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 13px;
  text-decoration: none;
  transition: color 0.2s ease;
}

.back-link:hover {
  color: #007E93;
}

/* Responsive */
@media (max-width: 640px) {
  .success-card {
    padding: 24px;
    margin: 16px;
  }

  .success-title {
    font-size: 24px;
  }

  .success-icon-bg {
    padding: 20px;
  }

  .success-icon {
    width: 48px;
    height: 48px;
  }
}
</style>
