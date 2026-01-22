<template>
  <Head title="Vista de Pruebas - Pagador" />

  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="test-header">
      <div class="header-container">
        <div class="header-content">
          <div class="header-logo">
            <img src="/images/logo-color.png" alt="Latitud90" />
          </div>
          <div class="header-actions">
            <Link :href="route('guardian.dashboard')" class="dashboard-link">
              ← Volver al Dashboard
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
      <div class="test-title-section">
        <h1 class="test-title">🧪 Vista de Pruebas</h1>
        <p class="test-subtitle">Información del usuario autenticado y sus relaciones</p>
      </div>

      <!-- User Info Card -->
      <div class="info-card">
        <div class="card-header">
          <h2 class="card-title">👤 Información del Usuario</h2>
        </div>
        <div class="card-content">
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">ID:</span>
              <span class="info-value">{{ user.id }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Nombre:</span>
              <span class="info-value">{{ user.name }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Email:</span>
              <span class="info-value">{{ user.email }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Documento:</span>
              <span class="info-value">{{ user.document }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Teléfono:</span>
              <span class="info-value">{{ user.phone_code }} {{ user.phone }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Estado:</span>
              <span class="badge" :class="getStatusClass(user.status)">
                {{ user.status }}
              </span>
            </div>
            <div class="info-item">
              <span class="info-label">Email Verificado:</span>
              <span class="badge" :class="user.email_verified_at ? 'badge-success' : 'badge-warning'">
                {{ user.email_verified_at ? 'Sí' : 'No' }}
              </span>
            </div>
            <div class="info-item">
              <span class="info-label">Último Login:</span>
              <span class="info-value">{{ formatDate(user.last_login_at) }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">IP Último Login:</span>
              <span class="info-value">{{ user.last_login_ip || 'N/A' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Creado:</span>
              <span class="info-value">{{ formatDate(user.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Location Info Card -->
      <div class="info-card">
        <div class="card-header">
          <h2 class="card-title">📍 Información de Ubicación</h2>
        </div>
        <div class="card-content">
          <div class="info-grid">
            <div class="info-item" v-if="user.country">
              <span class="info-label">País:</span>
              <span class="info-value">{{ user.country.name }}</span>
            </div>
            <div class="info-item" v-if="user.region">
              <span class="info-label">Región:</span>
              <span class="info-value">{{ user.region.name }}</span>
            </div>
            <div class="info-item" v-if="user.comune">
              <span class="info-label">Comuna:</span>
              <span class="info-value">{{ user.comune.name }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Guardian Links Card -->
      <div class="info-card">
        <div class="card-header">
          <h2 class="card-title">🔗 Enlaces con Participantes</h2>
        </div>
        <div class="card-content">
          <div v-if="user.guardian_links && user.guardian_links.length > 0">
            <div v-for="link in user.guardian_links" :key="link.id" class="link-item">
              <div class="link-header">
                <h3 class="link-title">Enlace #{{ link.id }}</h3>
                <span class="badge" :class="getLinkStatusClass(link.invitation_status)">
                  {{ link.invitation_status }}
                </span>
              </div>
              <div class="link-details">
                <div class="detail-row">
                  <span class="detail-label">Emergency Contact ID:</span>
                  <span class="detail-value">{{ link.emergency_contact_id }}</span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Código Invitación:</span>
                  <span class="detail-value">{{ link.invitation_code }}</span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Pagador Principal:</span>
                  <span class="badge" :class="link.is_primary ? 'badge-primary' : 'badge-secondary'">
                    {{ link.is_primary ? 'Sí' : 'No' }}
                  </span>
                </div>
                <div class="permissions-grid">
                  <div class="permission-item">
                    <span class="permission-icon" :class="link.can_pay ? 'active' : 'inactive'">
                      {{ link.can_pay ? '✅' : '❌' }}
                    </span>
                    <span class="permission-text">Puede Pagar</span>
                  </div>
                  <div class="permission-item">
                    <span class="permission-icon" :class="link.can_view_documents ? 'active' : 'inactive'">
                      {{ link.can_view_documents ? '✅' : '❌' }}
                    </span>
                    <span class="permission-text">Ver Documentos</span>
                  </div>
                  <div class="permission-item">
                    <span class="permission-icon" :class="link.can_view_itinerary ? 'active' : 'inactive'">
                      {{ link.can_view_itinerary ? '✅' : '❌' }}
                    </span>
                    <span class="permission-text">Ver Itinerario</span>
                  </div>
                  <div class="permission-item">
                    <span class="permission-icon" :class="link.can_receive_notifications ? 'active' : 'inactive'">
                      {{ link.can_receive_notifications ? '✅' : '❌' }}
                    </span>
                    <span class="permission-text">Recibir Notificaciones</span>
                  </div>
                  <div class="permission-item">
                    <span class="permission-icon" :class="link.can_update_emergency_contact ? 'active' : 'inactive'">
                      {{ link.can_update_emergency_contact ? '✅' : '❌' }}
                    </span>
                    <span class="permission-text">Actualizar Contacto</span>
                  </div>
                </div>
                <div class="detail-row" v-if="link.emergency_contact">
                  <span class="detail-label">Participante:</span>
                  <span class="detail-value">
                    {{ link.emergency_contact.participant ? link.emergency_contact.participant.name : 'No cargado' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="empty-state">
            <p>No hay enlaces con participantes</p>
          </div>
        </div>
      </div>

      <!-- Raw JSON Card -->
      <div class="info-card">
        <div class="card-header">
          <h2 class="card-title">📄 Datos Raw (JSON)</h2>
        </div>
        <div class="card-content">
          <pre class="json-viewer">{{ JSON.stringify(user, null, 2) }}</pre>
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

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString('es-CL')
}

const getStatusClass = (status) => {
  const classes = {
    'active': 'badge-success',
    'pending_payment': 'badge-warning',
    'suspended': 'badge-danger'
  }
  return classes[status] || 'badge-secondary'
}

const getLinkStatusClass = (status) => {
  const classes = {
    'pending': 'badge-warning',
    'accepted': 'badge-success',
    'rejected': 'badge-danger'
  }
  return classes[status] || 'badge-secondary'
}
</script>

<style scoped>
/* Header */
.test-header {
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
  gap: 16px;
}

.dashboard-link {
  color: #007E93;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 14px;
  text-decoration: none;
  transition: color 0.2s ease;
}

.dashboard-link:hover {
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

.test-title-section {
  margin-bottom: 32px;
}

.test-title {
  color: #1C4F4A;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 32px;
  line-height: 36px;
  font-weight: 700;
  margin-bottom: 8px;
}

.test-subtitle {
  color: #434343;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 16px;
  line-height: 20px;
}

/* Info Card */
.info-card {
  background: #ffffff;
  border-radius: 20px;
  box-shadow: 0px 4px 11.6px 0px rgba(163, 163, 163, 0.11);
  overflow: hidden;
  margin-bottom: 24px;
}

.card-header {
  padding: 20px 24px;
  border-bottom: 1px solid #e5e5e5;
  background: #f9fafb;
}

.card-title {
  color: #1C4F4A;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 18px;
  line-height: 22px;
  font-weight: 700;
}

.card-content {
  padding: 24px;
}

/* Info Grid */
.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-label {
  color: #6b7280;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 12px;
  line-height: 16px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-value {
  color: #1C4F4A;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 14px;
  line-height: 18px;
  font-weight: 700;
}

/* Badges */
.badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 59px;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 12px;
  line-height: 16px;
  font-weight: 700;
  width: fit-content;
}

.badge-success {
  background: #D1FAE5;
  color: #065F46;
}

.badge-warning {
  background: #FEF3C7;
  color: #92400E;
}

.badge-danger {
  background: #FEE2E2;
  color: #991B1B;
}

.badge-primary {
  background: #DBEAFE;
  color: #1E40AF;
}

.badge-secondary {
  background: #E5E7EB;
  color: #374151;
}

/* Link Item */
.link-item {
  background: #f9fafb;
  border: 1px solid #e5e5e5;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 16px;
}

.link-item:last-child {
  margin-bottom: 0;
}

.link-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.link-title {
  color: #1C4F4A;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 16px;
  line-height: 20px;
  font-weight: 700;
}

.link-details {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.detail-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.detail-label {
  color: #6b7280;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 13px;
  line-height: 16px;
  min-width: 150px;
}

.detail-value {
  color: #1C4F4A;
  font-family: 'Nexa-Bold', sans-serif;
  font-size: 13px;
  line-height: 16px;
  font-weight: 700;
}

/* Permissions Grid */
.permissions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
  margin-top: 8px;
  padding: 12px;
  background: white;
  border-radius: 8px;
}

.permission-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.permission-icon {
  font-size: 16px;
}

.permission-text {
  color: #434343;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 13px;
  line-height: 16px;
}

/* JSON Viewer */
.json-viewer {
  background: #1e293b;
  color: #e2e8f0;
  padding: 20px;
  border-radius: 8px;
  overflow-x: auto;
  font-family: 'Courier New', monospace;
  font-size: 12px;
  line-height: 18px;
  max-height: 600px;
  overflow-y: auto;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 32px;
  color: #6b7280;
  font-family: 'Nexa-Regular', sans-serif;
  font-size: 14px;
}

/* Responsive */
@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    gap: 16px;
  }

  .header-actions {
    width: 100%;
    justify-content: space-between;
  }

  .main-content {
    padding: 24px 16px;
  }

  .test-title {
    font-size: 24px;
    line-height: 28px;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .permissions-grid {
    grid-template-columns: 1fr;
  }
}
</style>
