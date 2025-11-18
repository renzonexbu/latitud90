<template>
  <Head title="Programas Inscritos" />

  <GuardianLayout>
    <div class="p-8">
      <!-- Header con botón de regreso -->
      <div class="mb-8">
        <button
          @click="$inertia.visit(route('guardian.participants'))"
          class="mb-4 inline-flex items-center text-[#1C4F4A] hover:text-[#007E93] font-semibold transition-colors"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Volver a Participantes
        </button>

        <div class="flex items-center gap-4 mb-4">
          <div class="w-16 h-16 bg-gradient-to-r from-[#1C4F4A] to-[#007E93] rounded-full flex items-center justify-center">
            <span class="text-2xl font-bold text-white">
              {{ getInitials(participant.name) }}
            </span>
          </div>
          <div>
            <h1 class="text-3xl font-bold text-[#1C4F4A]">
              {{ participant.name }}
            </h1>
            <p class="text-gray-600">
              Programas Inscritos
            </p>
          </div>
        </div>
      </div>

      <!-- Grid de Programas -->
      <div v-if="programs && programs.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="program in programs"
          :key="program.id"
          class="bg-white rounded-[20px] shadow-md overflow-hidden hover:shadow-lg transition-all transform hover:-translate-y-1"
        >
          <!-- Imagen del programa -->
          <div class="h-48 bg-gradient-to-br from-[#1C4F4A] to-[#007E93] relative overflow-hidden">
            <img
              v-if="program.image"
              :src="program.image"
              :alt="program.name"
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center">
              <svg class="w-20 h-20 text-white/30" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
              </svg>
            </div>

            <!-- Badge de estado -->
            <div class="absolute top-4 right-4">
              <span
                :class="{
                  'bg-green-500': program.status === 'active',
                  'bg-yellow-500': program.status === 'pending',
                  'bg-red-500': program.status === 'cancelled',
                  'bg-gray-500': program.status === 'completed'
                }"
                class="px-3 py-1 rounded-full text-white text-xs font-bold"
              >
                {{ getStatusLabel(program.status) }}
              </span>
            </div>
          </div>

          <!-- Contenido del card -->
          <div class="p-6">
            <h3 class="text-xl font-bold text-[#1C4F4A] mb-2">
              {{ program.name }}
            </h3>

            <div class="space-y-2 mb-4">
              <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Salida: {{ formatDate(program.departure_date) }}</span>
              </div>

              <div v-if="program.location" class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>{{ program.location }}</span>
              </div>

              <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-bold text-[#007E93]">{{ formatPrice(program.price) }}</span>
              </div>
            </div>

            <!-- Botón de detalle -->
            <button
              @click="viewProgramDetail(program.id)"
              class="w-full bg-[#1C4F4A] hover:bg-[#007E93] text-white font-bold py-3 px-4 rounded-full transition-all hover:shadow-lg"
            >
              Ver Detalles
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="bg-white rounded-[20px] shadow-md overflow-hidden">
        <div class="px-6 py-16 text-center">
          <div class="text-6xl mb-4">📚</div>
          <h3 class="text-xl font-bold text-[#1C4F4A] mb-2">
            No hay programas inscritos
          </h3>
          <p class="text-gray-600 mb-6">
            Este participante aún no tiene programas inscritos
          </p>
          <button
            @click="$inertia.visit(route('guardian.participants'))"
            class="inline-block bg-[#FBBD51] hover:bg-[#e0a840] text-white font-bold py-3 px-6 rounded-full transition-all hover:shadow-lg"
          >
            Volver a Participantes
          </button>
        </div>
      </div>
    </div>
  </GuardianLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import GuardianLayout from '@/Layouts/GuardianLayout.vue'

const props = defineProps({
  participant: Object,
  programs: Array
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
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleDateString('es-CL', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const formatPrice = (price) => {
  if (!price) return 'Gratis'
  return new Intl.NumberFormat('es-CL', {
    style: 'currency',
    currency: 'CLP'
  }).format(price)
}

const getStatusLabel = (status) => {
  const labels = {
    active: 'Activo',
    pending: 'Pendiente',
    cancelled: 'Cancelado',
    completed: 'Completado'
  }
  return labels[status] || status
}

const viewProgramDetail = (programId) => {
  router.visit(route('guardian.participant.program.detail', {
    participant: props.participant.id,
    programCourse: programId
  }))
}
</script>
