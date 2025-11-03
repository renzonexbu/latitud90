<template>
  <Head title="Dashboard - Apoderado" />

  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="dashboard-header">
      <div class="header-container">
        <div class="header-content">
          <!-- Logo -->
          <div class="header-logo">
            <img src="/images/logo-color.png" alt="Latitud90" />
          </div>

          <!-- User info and actions -->
          <div class="header-actions">
            <div class="user-info">
              <p class="user-name">{{ user.name }}</p>
              <p class="user-email">{{ user.email }}</p>
            </div>
            <Link
              :href="route('guardian.change-password')"
              class="change-password-link"
            >
              Cambiar Contraseña
            </Link>
            <form @submit.prevent="logout">
              <button type="submit" class="logout-button">
                Cerrar Sesión
              </button>
            </form>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
      <!-- Welcome Section -->
      <div class="welcome-section">
        <h1 class="welcome-title">Bienvenido, {{ user.name }}</h1>
        <p class="welcome-subtitle">Gestiona los programas y pagos de tus participantes</p>
      </div>

      <!-- Estado de verificación -->
      <div v-if="!user.email_verified_at" class="verification-warning">
        <svg class="warning-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <div class="warning-content">
          <p class="warning-text">
            Tu email no ha sido verificado. Por favor revisa tu bandeja de entrada.
          </p>
        </div>
      </div>

      <!-- Participantes Card -->
      <div class="participants-card">
        <div class="card-header">
          <h2 class="card-title">Mis Participantes</h2>
        </div>

        <div v-if="user.guardian_links && user.guardian_links.length > 0" class="participants-list">
          <div
            v-for="link in user.guardian_links"
            :key="link.id"
            class="participant-item"
          >
            <div class="participant-info">
              <h3 class="participant-name">
                {{ link.emergency_contact.participant.name }}
              </h3>
              <p class="participant-document">
                RUT: {{ link.emergency_contact.participant.document }}
              </p>
              <div class="participant-badges">
                <span v-if="link.is_primary" class="badge badge-primary">
                  Apoderado Principal
                </span>
                <span v-if="link.can_pay" class="badge badge-success">
                  Puede pagar
                </span>
                <span v-if="link.can_view_documents" class="badge badge-info">
                  Ver documentos
                </span>
              </div>
            </div>
            <div class="participant-actions">
              <button class="view-programs-button">
                Ver Programas
              </button>
            </div>
          </div>
        </div>

        <div v-else class="empty-state">
          <div class="empty-icon">📚</div>
          <h3 class="empty-title">No tienes participantes asociados</h3>
          <p class="empty-text">
            Contacta al administrador para vincular participantes a tu cuenta
          </p>
        </div>
      </div>

      <!-- Info adicional -->
      <div class="info-box">
        <svg class="info-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
        </svg>
        <div class="info-content">
          <p class="info-text">
            <strong>Información:</strong> Desde este panel podrás gestionar los pagos de tus participantes, ver documentos del programa y más.
          </p>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'

const props = defineProps({
  user: Object
})

const logout = () => {
  router.post(route('guardian.logout'))
}
</script>

<style scoped>
/* Header */
.dashboard-header {
  background: #ffffff;
  box-shadow: 0px 2px 8px 0px rgba(163, 163, 163, 0.1);
  position: sticky;
  top: 0;
  z-index: 10;
}

.header-container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 16px 24px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-logo img {
  height: 50px;
  width: auto;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 24px;
}

.user-info {
  text-align: right;
}

.user-name {
  color: #1C4F4A;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 16px;
  line-height: 20px;
  font-weight: 700;
}

.user-email {
  color: #434343;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 13px;
  line-height: 16px;
}

.change-password-link {
  color: #007E93;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 14px;
  text-decoration: none;
  transition: color 0.2s ease;
}

.change-password-link:hover {
  color: #005a6b;
  text-decoration: underline;
}

.logout-button {
  background: #EF4444;
  border: none;
  border-radius: 59px;
  padding: 10px 20px;
  color: #ffffff;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 14px;
  line-height: 18px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
}

.logout-button:hover {
  background: #DC2626;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

/* Main Content */
.main-content {
  max-width: 1280px;
  margin: 0 auto;
  padding: 32px 24px;
}

/* Welcome Section */
.welcome-section {
  margin-bottom: 32px;
}

.welcome-title {
  color: #1C4F4A;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 32px;
  line-height: 36px;
  font-weight: 700;
  margin-bottom: 8px;
}

.welcome-subtitle {
  color: #434343;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 16px;
  line-height: 20px;
}

/* Verification Warning */
.verification-warning {
  background: #FEF3C7;
  border-left: 4px solid #F59E0B;
  border-radius: 8px;
  padding: 12px 16px;
  display: flex;
  gap: 12px;
  align-items: flex-start;
  margin-bottom: 24px;
}

.warning-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
  color: #D97706;
}

.warning-content {
  flex: 1;
}

.warning-text {
  color: #92400E;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 14px;
  line-height: 18px;
}

/* Participants Card */
.participants-card {
  background: #ffffff;
  border-radius: 20px;
  box-shadow: 0px 4px 11.6px 0px rgba(163, 163, 163, 0.11);
  overflow: hidden;
  margin-bottom: 24px;
}

.card-header {
  padding: 24px;
  border-bottom: 1px solid #e5e5e5;
}

.card-title {
  color: #1C4F4A;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 20px;
  line-height: 24px;
  font-weight: 700;
}

/* Participants List */
.participants-list {
  display: flex;
  flex-direction: column;
}

.participant-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid #e5e5e5;
  transition: background 0.2s ease;
}

.participant-item:last-child {
  border-bottom: none;
}

.participant-item:hover {
  background: #f9fafb;
}

.participant-info {
  flex: 1;
}

.participant-name {
  color: #1C4F4A;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 18px;
  line-height: 22px;
  font-weight: 700;
  margin-bottom: 4px;
}

.participant-document {
  color: #434343;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 14px;
  line-height: 18px;
  margin-bottom: 12px;
}

.participant-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.badge {
  padding: 4px 12px;
  border-radius: 59px;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 12px;
  line-height: 16px;
  font-weight: 700;
}

.badge-primary {
  background: #DBEAFE;
  color: #1E40AF;
}

.badge-success {
  background: #D1FAE5;
  color: #065F46;
}

.badge-info {
  background: #E0E7FF;
  color: #3730A3;
}

.participant-actions {
  margin-left: 24px;
}

.view-programs-button {
  background: #FBBD51;
  border: none;
  border-radius: 59px;
  padding: 11px 20px;
  color: #ffffff;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 14px;
  line-height: 18px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
}

.view-programs-button:hover {
  background: #e0a840;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(251, 189, 81, 0.3);
}

/* Empty State */
.empty-state {
  padding: 64px 24px;
  text-align: center;
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 16px;
}

.empty-title {
  color: #1C4F4A;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 20px;
  line-height: 24px;
  font-weight: 700;
  margin-bottom: 8px;
}

.empty-text {
  color: #434343;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 14px;
  line-height: 20px;
}

/* Info Box */
.info-box {
  background: #EFF6FF;
  border-left: 4px solid #3B82F6;
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
  color: #3B82F6;
}

.info-content {
  flex: 1;
}

.info-text {
  color: #1E40AF;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 14px;
  line-height: 18px;
}

.info-text strong {
  font-family: 'Nexa-Bold', sans-serif;
  font-weight: 700;
}

/* Responsive */
@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .header-actions {
    width: 100%;
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .user-info {
    text-align: left;
  }

  .main-content {
    padding: 24px 16px;
  }

  .welcome-title {
    font-size: 24px;
    line-height: 28px;
  }

  .welcome-subtitle {
    font-size: 14px;
  }

  .participant-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .participant-actions {
    margin-left: 0;
    width: 100%;
  }

  .view-programs-button {
    width: 100%;
  }
}
</style>
