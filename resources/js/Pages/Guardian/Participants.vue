<template>
  <Head title="Mis Participantes" />

  <GuardianLayout>
    <div class="p-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-[#1C4F4A] mb-2">
          Mis Participantes
        </h1>
        <p class="text-gray-600">
          Administra la información de tus participantes y sus programas
        </p>
      </div>

      <!-- Participants List -->
      <div v-if="participants && participants.length > 0" class="grid gap-6">
        <div
          v-for="participant in participants"
          :key="participant.id"
          class="bg-white rounded-[20px] shadow-md overflow-hidden hover:shadow-lg transition-shadow"
        >
          <!-- Card Header -->
          <div class="px-6 py-5 bg-gradient-to-r from-[#1C4F4A] to-[#007E93]">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center">
                  <span class="text-2xl font-bold text-[#1C4F4A]">
                    {{ getInitials(participant.emergency_contact.participant.name) }}
                  </span>
                </div>
                <div>
                  <h2 class="text-xl font-bold text-white">
                    {{ participant.emergency_contact.participant.name }}
                  </h2>
                  <p class="text-white/80 text-sm">
                    Documento: {{ participant.emergency_contact.participant.document || 'No registrado' }}
                  </p>
                  <p class="text-white/80 text-sm">
                    Tipo: {{ participant.emergency_contact.participant.document_type }}
                  </p>
                  <p class="text-white/80 text-sm">
                    Fecha Nacimiento: {{ formatDate(participant.emergency_contact.participant.birth_date) }}
                  </p>
                </div>
              </div>

              <!-- Badges de permisos -->
              <div class="flex flex-wrap gap-2">
                <span v-if="participant.is_primary" class="px-3 py-1 bg-white/20 backdrop-blur text-white text-xs font-bold rounded-full border border-white/30">
                  Apoderado Principal
                </span>
                <span v-if="participant.can_pay" class="px-3 py-1 bg-white/20 backdrop-blur text-white text-xs font-bold rounded-full border border-white/30">
                  Puede Pagar
                </span>
                <span v-if="participant.can_view_documents" class="px-3 py-1 bg-white/20 backdrop-blur text-white text-xs font-bold rounded-full border border-white/30">
                  Ver Documentos
                </span>
              </div>
            </div>
          </div>

          <!-- Card Body -->
          <div class="p-6">
            <!-- Acciones -->
            <div class="flex flex-wrap gap-3">
              <button
                @click="viewPrograms(participant.emergency_contact.participant.id)"
                class="flex-1 min-w-[200px] bg-[#FBBD51] hover:bg-[#e0a840] text-white font-bold py-3 px-5 rounded-full transition-all hover:shadow-lg"
              >
                Ver Programas Inscritos
              </button>
              <!-- Oculto temporalmente - funcionalidad pendiente -->
              <button v-if="false && participant.can_pay" class="flex-1 min-w-[200px] bg-[#007E93] hover:bg-[#005a6b] text-white font-bold py-3 px-5 rounded-full transition-all hover:shadow-lg">
                Gestionar Pagos
              </button>
              <!-- Oculto temporalmente - funcionalidad pendiente -->
              <button v-if="false && participant.can_view_documents" class="flex-1 min-w-[200px] bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-5 rounded-full transition-all hover:shadow-lg">
                Ver Documentos
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="bg-white rounded-[20px] shadow-md overflow-hidden">
        <div class="px-6 py-16 text-center">
          <div class="text-6xl mb-4">👥</div>
          <h3 class="text-xl font-bold text-[#1C4F4A] mb-2">
            No tienes participantes asociados
          </h3>
          <p class="text-gray-600 mb-6">
            Contacta al administrador para vincular participantes a tu cuenta
          </p>
          <Link
            :href="route('guardian.dashboard')"
            class="inline-block bg-[#FBBD51] hover:bg-[#e0a840] text-white font-bold py-3 px-6 rounded-full transition-all hover:shadow-lg"
          >
            Volver al Dashboard
          </Link>
        </div>
      </div>
    </div>
  </GuardianLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import GuardianLayout from '@/Layouts/GuardianLayout.vue'

const props = defineProps({
  participants: Array
})

const getInitials = (name) => {
  if (!name) return '?'
  const names = name.split(' ')
  if (names.length >= 2) {
    return (names[0][0] + names[1][0]).toUpperCase()
  }
  return name[0].toUpperCase()
}

const formatDate = (date) => {
  if (!date) return 'No registrado'
  const d = new Date(date)
  return d.toLocaleDateString('es-CL', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const viewPrograms = (participantId) => {
  router.visit(route('guardian.participant.programs', participantId))
}
</script>
