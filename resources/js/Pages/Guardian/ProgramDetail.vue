<template>
  <Head title="Detalle del Programa" />

  <GuardianLayout>
    <div class="p-8">
      <!-- Header con botón de regreso -->
      <div class="mb-8">
        <button
          @click="$inertia.visit(route('guardian.participant.programs', participant.id))"
          class="mb-4 inline-flex items-center text-[#1C4F4A] hover:text-[#007E93] font-semibold transition-colors"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Volver a Programas
        </button>

        <div class="flex items-start gap-6 mb-6">
          <!-- Imagen del programa -->
          <div class="w-64 h-48 rounded-[20px] overflow-hidden shadow-lg flex-shrink-0">
            <img
              v-if="program.image"
              :src="program.image"
              :alt="program.name"
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full bg-gradient-to-br from-[#1C4F4A] to-[#007E93] flex items-center justify-center">
              <svg class="w-20 h-20 text-white/30" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
              </svg>
            </div>
          </div>

          <!-- Información del programa -->
          <div class="flex-1">
            <h1 class="text-3xl font-bold text-[#1C4F4A] mb-2">
              {{ program.name }}
            </h1>
            <p class="text-lg text-gray-600 mb-4">
              {{ participant.name }}
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div class="flex items-center text-gray-700">
                <svg class="w-5 h-5 mr-2 text-[#007E93]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>{{ program.destination }}</span>
              </div>

              <div class="flex items-center text-gray-700">
                <svg class="w-5 h-5 mr-2 text-[#007E93]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Salida: {{ formatDate(program.departure_date) }}</span>
              </div>
            </div>

            <!-- Badge de tipo de pago -->
            <div v-if="program.payment_type && program.payment_type !== 'none'" class="mb-4">
              <span
                class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold"
                :class="getPaymentTypeBadgeClass"
              >
                <svg v-if="program.payment_type === 'subscription'" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <svg v-else-if="program.payment_type === 'full_payment'" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                {{ getPaymentTypeLabel }}
              </span>
              <span
                v-if="program.subscription_cancelled"
                class="ml-2 inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-red-100 text-red-800"
              >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Cancelada
              </span>
            </div>

            <!-- Resumen de pago -->
            <div class="bg-gradient-to-r from-[#1C4F4A] to-[#007E93] rounded-[20px] p-6 text-white">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <p class="text-sm opacity-90 mb-1">Total del Programa</p>
                  <p class="text-2xl font-bold">{{ formatPrice(program.price) }}</p>
                </div>
                <div>
                  <p class="text-sm opacity-90 mb-1">Pagado</p>
                  <p class="text-2xl font-bold">{{ formatPrice(program.paid_amount) }}</p>
                </div>
                <div>
                  <p class="text-sm opacity-90 mb-1">Pendiente</p>
                  <p class="text-2xl font-bold">{{ formatPrice(program.pending_amount) }}</p>
                </div>
              </div>

              <!-- Barra de progreso -->
              <div class="mt-4">
                <div class="flex justify-between text-sm mb-2">
                  <span>Progreso de Pago</span>
                  <span>{{ program.payment_percentage }}%</span>
                </div>
                <div class="w-full bg-white/20 rounded-full h-3 overflow-hidden">
                  <div
                    class="bg-[#FBBD51] h-full rounded-full transition-all duration-300"
                    :style="{ width: `${program.payment_percentage}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Información de suscripción -->
      <div
        v-if="program.has_subscription"
        class="mb-6 rounded-[20px] p-6 border"
        :class="isSubscriptionCancelled ? 'bg-red-50 border-red-200' : 'bg-blue-50 border-blue-200'"
      >
        <div class="flex items-center mb-3">
          <!-- Icono de check para activa, X para cancelada -->
          <svg v-if="!isSubscriptionCancelled" class="w-6 h-6 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <svg v-else class="w-6 h-6 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <h2 class="text-xl font-bold" :class="isSubscriptionCancelled ? 'text-red-900' : 'text-blue-900'">
            {{ isSubscriptionCancelled ? 'Suscripción Cancelada' : 'Suscripción Activa' }}
          </h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
          <div>
            <p class="font-semibold mb-1" :class="isSubscriptionCancelled ? 'text-red-600' : 'text-blue-600'">Estado</p>
            <p :class="isSubscriptionCancelled ? 'text-red-900' : 'text-blue-900'">
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="getSubscriptionStatusClass"
              >
                {{ formatSubscriptionStatus(program.subscription.status) }}
              </span>
            </p>
          </div>
          <div>
            <p class="font-semibold mb-1" :class="isSubscriptionCancelled ? 'text-red-600' : 'text-blue-600'">Método de Pago</p>
            <p :class="isSubscriptionCancelled ? 'text-red-900' : 'text-blue-900'">{{ formatPaymentMethod(program.subscription.payment_method) }}</p>
          </div>
          <div>
            <p class="font-semibold mb-1" :class="isSubscriptionCancelled ? 'text-red-600' : 'text-blue-600'">Fecha de Creación</p>
            <p :class="isSubscriptionCancelled ? 'text-red-900' : 'text-blue-900'">{{ formatDate(program.subscription.created_at) }}</p>
          </div>
        </div>
        <!-- Mensaje adicional si está cancelada -->
        <div v-if="isSubscriptionCancelled" class="mt-4 p-3 bg-red-100 rounded-lg">
          <p class="text-red-800 text-sm">
            <strong>Nota:</strong> Esta suscripción ha sido cancelada. Los cobros automáticos ya no se realizarán.
          </p>
        </div>
      </div>

      <!-- Lista de Mensualidades -->
      <div class="bg-white rounded-[20px] shadow-md overflow-hidden">
        <div class="px-6 py-5 bg-gradient-to-r from-[#1C4F4A] to-[#007E93] text-white">
          <h2 class="text-2xl font-bold">Mensualidades</h2>
          <p class="text-sm opacity-90 mt-1">
            {{ program.paid_installments }} de {{ program.total_installments }} cuotas pagadas
          </p>
        </div>

        <div v-if="program.installments && program.installments.length > 0" class="divide-y divide-gray-200">
          <div
            v-for="installment in program.installments"
            :key="installment.id"
            class="px-6 py-4 hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center justify-between">
              <!-- Número de cuota y monto -->
              <div class="flex-1">
                <div class="flex items-center gap-3">
                  <div
                    class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white"
                    :class="installment.is_paid ? 'bg-green-500' : 'bg-gray-400'"
                  >
                    {{ installment.installment_number }}
                  </div>
                  <div>
                    <p class="font-semibold text-gray-900">
                      Cuota {{ installment.installment_number }} de {{ program.total_installments }}
                    </p>
                    <p class="text-sm text-gray-600">
                      Vencimiento: {{ formatDate(installment.due_date) }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Monto -->
              <div class="text-right mr-6">
                <p class="text-xl font-bold text-[#1C4F4A]">
                  {{ formatPrice(installment.amount) }}
                </p>
              </div>

              <!-- Estado -->
              <div class="text-right min-w-[120px]">
                <span
                  class="px-4 py-2 rounded-full text-sm font-semibold"
                  :class="{
                    'bg-green-100 text-green-800': isInstallmentPaid(installment),
                    'bg-yellow-100 text-yellow-800': !isInstallmentPaid(installment) && isUpcoming(installment.due_date),
                    'bg-red-100 text-red-800': !isInstallmentPaid(installment) && isPastDue(installment.due_date)
                  }"
                >
                  {{ getInstallmentStatusLabel(installment) }}
                </span>
                <p v-if="installment.paid_at" class="text-xs text-gray-500 mt-1">
                  Pagado el {{ formatDate(installment.paid_at) }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty state -->
        <div v-else class="px-6 py-16 text-center">
          <div class="text-6xl mb-4">💳</div>
          <h3 class="text-xl font-bold text-[#1C4F4A] mb-2">
            No hay mensualidades registradas
          </h3>
          <p class="text-gray-600 mb-6">
            Este programa aún no tiene un plan de cuotas configurado
          </p>
          <a
            :href="getPaymentUrl()"
            class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-[#1C4F4A] to-[#007E93] text-white font-semibold rounded-full hover:opacity-90 transition-opacity shadow-lg"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            Ir a Pagar
          </a>
        </div>
      </div>
    </div>
  </GuardianLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import GuardianLayout from '@/Layouts/GuardianLayout.vue'

const props = defineProps({
  participant: Object,
  program: Object
})

// Verificar si la suscripción está cancelada
const isSubscriptionCancelled = computed(() => {
  if (!props.program?.subscription?.status) return false
  const status = props.program.subscription.status.toLowerCase()
  return status === 'cancelada' || status === 'cancelled' || status === 'canceled'
})

// Label para el tipo de pago
const getPaymentTypeLabel = computed(() => {
  const type = props.program?.payment_type
  if (type === 'subscription') {
    return props.program?.subscription_cancelled ? 'Suscripción' : 'Suscripción Activa'
  }
  if (type === 'full_payment') {
    return 'Pago Completo'
  }
  if (type === 'installments') {
    return 'Plan de Cuotas'
  }
  return 'Sin pago'
})

// Clases CSS para el badge de tipo de pago
const getPaymentTypeBadgeClass = computed(() => {
  const type = props.program?.payment_type
  if (type === 'subscription') {
    return props.program?.subscription_cancelled
      ? 'bg-red-100 text-red-800'
      : 'bg-blue-100 text-blue-800'
  }
  if (type === 'full_payment') {
    return 'bg-green-100 text-green-800'
  }
  if (type === 'installments') {
    return 'bg-purple-100 text-purple-800'
  }
  return 'bg-gray-100 text-gray-800'
})

// Clases CSS para el badge de estado
const getSubscriptionStatusClass = computed(() => {
  if (!props.program?.subscription?.status) return 'bg-gray-100 text-gray-800'
  const status = props.program.subscription.status.toLowerCase()

  if (status === 'activa' || status === 'active') {
    return 'bg-green-100 text-green-800'
  }
  if (status === 'cancelada' || status === 'cancelled' || status === 'canceled') {
    return 'bg-red-100 text-red-800'
  }
  if (status === 'pendiente' || status === 'pending') {
    return 'bg-yellow-100 text-yellow-800'
  }
  if (status === 'suspendida' || status === 'suspended') {
    return 'bg-orange-100 text-orange-800'
  }
  return 'bg-gray-100 text-gray-800'
})

// Formatear estado de suscripción para mostrar
const formatSubscriptionStatus = (status) => {
  if (!status) return 'Desconocido'
  const statusMap = {
    'activa': 'Activa',
    'active': 'Activa',
    'cancelada': 'Cancelada',
    'cancelled': 'Cancelada',
    'canceled': 'Cancelada',
    'pendiente': 'Pendiente',
    'pending': 'Pendiente',
    'suspendida': 'Suspendida',
    'suspended': 'Suspendida'
  }
  return statusMap[status.toLowerCase()] || status
}

const formatPrice = (price) => {
  if (!price && price !== 0) return 'N/A'
  return new Intl.NumberFormat('es-CL', {
    style: 'currency',
    currency: 'CLP'
  })
    .format(price)
    .replace('CLP', '')
    .trim()
}

// Helper para normalizar fechas sin hora (YYYY-MM-DD) evitando problemas de timezone
const normalizeDate = (dateString) => {
  if (!dateString) return null
  // Si es solo fecha (YYYY-MM-DD), agregar T12:00:00 para evitar
  // que el cambio de timezone afecte el día mostrado
  if (typeof dateString === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
    return dateString + 'T12:00:00'
  }
  return dateString
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(normalizeDate(dateString))
  if (isNaN(date.getTime())) return 'N/A'
  return date.toLocaleDateString('es-CL', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const isUpcoming = (dueDate) => {
  if (!dueDate) return false
  const today = new Date()
  const due = new Date(normalizeDate(dueDate))
  return due > today
}

const isPastDue = (dueDate) => {
  if (!dueDate) return false
  const today = new Date()
  const due = new Date(normalizeDate(dueDate))
  return due < today
}

// Verificar si una cuota está pagada por cualquier indicador
const isInstallmentPaid = (installment) => {
  return installment.is_paid || installment.status === 'paid' || !!installment.paid_at
}

const getInstallmentStatusLabel = (installment) => {
  if (isInstallmentPaid(installment)) {
    return 'Pagado'
  }
  if (isPastDue(installment.due_date)) {
    return 'Vencido'
  }
  return 'Pendiente'
}

const formatPaymentMethod = (paymentMethodJson) => {
  if (!paymentMethodJson) return 'N/A'

  try {
    // Si ya es un objeto, usarlo directamente, sino parsearlo
    const paymentMethod = typeof paymentMethodJson === 'string'
      ? JSON.parse(paymentMethodJson)
      : paymentMethodJson

    const brand = paymentMethod.brand || 'Tarjeta'
    const last4 = paymentMethod.last4CardDigit || '****'

    return `${brand} •••• ${last4}`
  } catch (e) {
    console.error('Error parsing payment method:', e)
    return 'Tarjeta registrada'
  }
}

// Generar URL para ir a pagar en el ecommerce
const getPaymentUrl = () => {
  const programId = props.program?.id
  const token = props.participant?.ecommerce_token

  if (!programId || !token) {
    return '#'
  }

  return `/programs/${programId}?token=${encodeURIComponent(token)}`
}
</script>
