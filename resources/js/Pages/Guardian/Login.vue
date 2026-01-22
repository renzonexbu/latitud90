<template>
  <Head title="Iniciar Sesión - Pagador" />

  <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="login-card">
      <!-- Header -->
      <div class="login-header">
        <div class="login-logo">
          <img src="/images/logo-color.png" alt="Latitud90" />
        </div>
        <h2 class="login-title">Iniciar Sesión</h2>
        <p class="login-subtitle">
          Acceso para pagadores
        </p>
      </div>

      <!-- Mensaje de éxito -->
      <div v-if="$page.props.flash.success" class="success-alert">
        <div class="alert-content">
          <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
          <p class="alert-text">{{ $page.props.flash.success }}</p>
        </div>
      </div>

      <!-- Errores -->
      <div v-if="$page.props.errors.error" class="error-alert">
        <div class="alert-content">
          <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          <p class="alert-text">{{ $page.props.errors.error }}</p>
        </div>

        <!-- Reenviar verificación si es necesario -->
        <div v-if="$page.props.flash.requires_verification" class="mt-3">
          <form @submit.prevent="resendVerification">
            <input type="hidden" name="email" :value="$page.props.flash.email">
            <button
              type="submit"
              class="text-sm text-red-700 underline hover:text-red-900"
            >
              Reenviar email de verificación
            </button>
          </form>
        </div>
      </div>

      <!-- Formulario -->
      <form class="login-form" @submit.prevent="submit">
        <!-- Email -->
        <div class="field-wrapper">
          <label for="email" class="field-label">
            Email <span class="required">*</span>
          </label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            placeholder="tu@email.com"
            class="input-text"
            :class="{ 'border-red-500': $page.props.errors.email }"
          />
          <span v-if="$page.props.errors.email" class="error-message">
            {{ $page.props.errors.email }}
          </span>
        </div>

        <!-- Contraseña -->
        <div class="field-wrapper">
          <label for="password" class="field-label">
            Contraseña <span class="required">*</span>
          </label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            required
            placeholder="Tu contraseña"
            class="input-text"
            :class="{ 'border-red-500': $page.props.errors.password }"
          />
          <span v-if="$page.props.errors.password" class="error-message">
            {{ $page.props.errors.password }}
          </span>
        </div>

        <!-- Recordarme y Olvidé contraseña -->
        <div class="flex-between">
          <div class="checkbox-wrapper">
            <input
              id="remember"
              v-model="form.remember"
              type="checkbox"
              class="custom-checkbox"
            />
            <label for="remember" class="checkbox-label-inline">
              Recordarme
            </label>
          </div>

          <Link
            :href="route('guardian.forgot-password')"
            class="action-link"
          >
            ¿Olvidaste tu contraseña?
          </Link>
        </div>

        <!-- Botón de Enviar -->
        <div class="submit-section">
          <button
            type="submit"
            :disabled="form.processing"
            class="submit-button"
          >
            <span v-if="!form.processing">Iniciar Sesión</span>
            <span v-else class="submit-loading">
              <svg class="spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Iniciando...
            </span>
          </button>
        </div>

        <!-- Links adicionales -->
        <div class="additional-links">
          <div class="divider"></div>
          <p class="link-text">
            ¿No tienes una cuenta?
            <Link :href="props.token ? `/guardian/register?token=${props.token}` : route('guardian.register')" class="action-link">
              Regístrate aquí
            </Link>
          </p>
          <Link :href="route('ecommerce.index')" class="back-link">
            ← Volver al inicio
          </Link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'

const props = defineProps({
  token: {
    type: String,
    default: null
  }
})

const form = useForm({
  email: '',
  password: '',
  remember: false
})

const submit = () => {
  form.post(route('guardian.login.post'), {
    preserveScroll: true
  })
}

const resendVerification = () => {
  router.post(route('guardian.resend-verification'), {
    email: form.email
  })
}
</script>

<style scoped>
/* Container principal */
.login-card {
  background: #ffffff;
  border-radius: 20px;
  padding: 40px;
  max-width: 500px;
  width: 100%;
  box-shadow: 0px 4px 11.6px 0px rgba(163, 163, 163, 0.11);
}

/* Header */
.login-header {
  text-align: center;
  margin-bottom: 32px;
}

.login-logo {
  margin-bottom: 24px;
}

.login-logo img {
  height: 60px;
  width: auto;
}

.login-title {
  color: #1C4F4A;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 24px;
  line-height: 28px;
  font-weight: 700;
  margin-bottom: 8px;
}

.login-subtitle {
  color: #434343;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 14px;
  line-height: 18px;
}

/* Alerts */
.success-alert {
  background: #d1fae5;
  border-left: 4px solid #10b981;
  border-radius: 8px;
  padding: 12px 16px;
  margin-bottom: 24px;
}

.error-alert {
  background: #fee2e2;
  border-left: 4px solid #ef4444;
  border-radius: 8px;
  padding: 12px 16px;
  margin-bottom: 24px;
}

.alert-content {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.alert-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.success-alert .alert-icon {
  color: #059669;
}

.error-alert .alert-icon {
  color: #dc2626;
}

.success-alert .alert-text {
  color: #065f46;
  font-size: 14px;
  line-height: 20px;
}

.error-alert .alert-text {
  color: #991b1b;
  font-size: 14px;
  line-height: 20px;
}

/* Formulario */
.login-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* Field Wrapper */
.field-wrapper {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.field-label {
  color: #434343;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 14px;
  line-height: 18px;
  font-weight: normal;
}

.required {
  color: #ef4444;
}

/* Input Styles */
.input-text {
  width: 100%;
  height: 46px;
  background: white;
  border-radius: 8px;
  border: 1px solid #5B5B5B;
  padding: 12px 16px;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 12px;
  line-height: 18px;
  font-weight: bold;
  outline: none;
  transition: all 0.2s ease;
}

.input-text::placeholder {
  color: #c7c7c7;
  font-weight: normal;
}

.input-text:focus {
  border-color: #FBBD51;
  box-shadow: 0 0 0 3px rgba(251, 189, 81, 0.1);
}

.input-text.border-red-500 {
  border-color: #ef4444;
}

.input-text.border-red-500:focus {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

/* Error Message */
.error-message {
  color: #dc2626;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 12px;
  line-height: 16px;
  margin-top: -4px;
}

/* Checkbox */
.flex-between {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 8px;
}

.checkbox-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
}

.custom-checkbox {
  width: 16px;
  height: 16px;
  border-radius: 3px;
  accent-color: #FBBD51;
  cursor: pointer;
}

.checkbox-label-inline {
  color: #434343;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 14px;
  cursor: pointer;
}

/* Submit Section */
.submit-section {
  margin-top: 8px;
}

.submit-button {
  background: #FBBD51;
  border-radius: 59px;
  border: none;
  padding: 11px 20px;
  width: 100%;
  color: #ffffff;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 16px;
  line-height: 20px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.submit-button:hover:not(:disabled) {
  background: #e0a840;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(251, 189, 81, 0.3);
}

.submit-button:disabled {
  background: #C7C7C7;
  cursor: not-allowed;
  transform: none;
}

.submit-loading {
  display: flex;
  align-items: center;
  gap: 8px;
}

.spinner {
  width: 20px;
  height: 20px;
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

/* Additional Links */
.additional-links {
  margin-top: 24px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  align-items: center;
}

.divider {
  width: 100%;
  height: 1px;
  background: #e5e5e5;
  margin-bottom: 8px;
}

.link-text {
  color: #434343;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 14px;
  line-height: 18px;
  text-align: center;
}

.action-link {
  color: #007E93;
  font-family: 'Nexa-Bold', sans-serif;
  text-decoration: none;
  transition: color 0.2s ease;
  font-size: 14px;
}

.action-link:hover {
  color: #005a6b;
  text-decoration: underline;
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
  .login-card {
    padding: 24px;
    margin: 16px;
  }

  .login-title {
    font-size: 20px;
  }

  .login-subtitle {
    font-size: 12px;
  }
}
</style>
