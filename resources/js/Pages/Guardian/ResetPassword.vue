<template>
  <Head title="Restablecer Contraseña - Apoderado" />

  <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="reset-password-card">
      <!-- Header -->
      <div class="reset-password-header">
        <div class="reset-password-logo">
          <img src="/images/logo-color.png" alt="Latitud90" />
        </div>
        <h2 class="reset-password-title">Restablecer Contraseña</h2>
        <p class="reset-password-subtitle">
          Ingresa tu nueva contraseña
        </p>
      </div>

      <!-- Errores -->
      <div v-if="$page.props.errors.error" class="error-alert">
        <div class="alert-content">
          <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          <p class="alert-text">{{ $page.props.errors.error }}</p>
        </div>
      </div>

      <!-- Formulario -->
      <form class="reset-password-form" @submit.prevent="submit">
        <input type="hidden" name="user_id" :value="userId" />
        <input type="hidden" name="token" :value="token" />

        <!-- Nueva Contraseña -->
        <div class="field-wrapper">
          <label for="password" class="field-label">
            Nueva Contraseña <span class="required">*</span>
          </label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            required
            placeholder="Mínimo 8 caracteres"
            class="input-text"
            :class="{ 'border-red-500': $page.props.errors.password }"
          />
          <span v-if="$page.props.errors.password" class="error-message">
            {{ $page.props.errors.password }}
          </span>
        </div>

        <!-- Confirmar Nueva Contraseña -->
        <div class="field-wrapper">
          <label for="password_confirmation" class="field-label">
            Confirmar Nueva Contraseña <span class="required">*</span>
          </label>
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            required
            placeholder="Repite tu contraseña"
            class="input-text"
          />
        </div>

        <!-- Info de seguridad -->
        <div class="info-box">
          <svg class="info-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
          </svg>
          <div class="info-content">
            <p class="info-title">Recomendaciones:</p>
            <ul class="info-list">
              <li>Usa al menos 8 caracteres</li>
              <li>Combina letras, números y símbolos</li>
              <li>No uses información personal</li>
            </ul>
          </div>
        </div>

        <!-- Botón de Enviar -->
        <div class="submit-section">
          <button
            type="submit"
            :disabled="form.processing"
            class="submit-button"
          >
            <span v-if="!form.processing">Restablecer Contraseña</span>
            <span v-else class="submit-loading">
              <svg class="spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Procesando...
            </span>
          </button>
        </div>

        <!-- Links adicionales -->
        <div class="additional-links">
          <div class="divider"></div>
          <Link :href="route('guardian.login')" class="back-link">
            ← Volver a iniciar sesión
          </Link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
  userId: Number,
  token: String
})

const form = useForm({
  user_id: props.userId,
  token: props.token,
  password: '',
  password_confirmation: ''
})

const submit = () => {
  form.post(route('guardian.reset-password.post'), {
    preserveScroll: true
  })
}
</script>

<style scoped>
/* Container principal */
.reset-password-card {
  background: #ffffff;
  border-radius: 20px;
  padding: 40px;
  max-width: 500px;
  width: 100%;
  box-shadow: 0px 4px 11.6px 0px rgba(163, 163, 163, 0.11);
}

/* Header */
.reset-password-header {
  text-align: center;
  margin-bottom: 32px;
}

.reset-password-logo {
  margin-bottom: 24px;
}

.reset-password-logo img {
  height: 60px;
  width: auto;
}

.reset-password-title {
  color: #1C4F4A;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 24px;
  line-height: 28px;
  font-weight: 700;
  margin-bottom: 8px;
}

.reset-password-subtitle {
  color: #434343;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 14px;
  line-height: 18px;
}

/* Alerts */
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

.error-alert .alert-icon {
  color: #dc2626;
}

.error-alert .alert-text {
  color: #991b1b;
  font-size: 14px;
  line-height: 20px;
}

/* Formulario */
.reset-password-form {
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

/* Info Box */
.info-box {
  background: #D1FAE5;
  border-left: 4px solid #10B981;
  border-radius: 8px;
  padding: 12px 16px;
  display: flex;
  gap: 12px;
  align-items: flex-start;
}

.info-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
  color: #059669;
}

.info-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-title {
  color: #065F46;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 13px;
  line-height: 16px;
  font-weight: 700;
}

.info-list {
  list-style: none;
  padding: 0;
  margin: 0;
  color: #065F46;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 12px;
  line-height: 16px;
}

.info-list li {
  padding-left: 12px;
  position: relative;
  margin-top: 2px;
}

.info-list li::before {
  content: '•';
  position: absolute;
  left: 0;
  font-weight: bold;
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
  .reset-password-card {
    padding: 24px;
    margin: 16px;
  }

  .reset-password-title {
    font-size: 20px;
  }

  .reset-password-subtitle {
    font-size: 12px;
  }
}
</style>
