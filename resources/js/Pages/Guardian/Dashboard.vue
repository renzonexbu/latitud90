<template>
  <Head title="Dashboard - Pagador" />

  <GuardianLayout>
    <div class="p-8">
      <!-- Welcome Section -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-[#1C4F4A] mb-2">
          Bienvenido, {{ user.name }}
        </h1>
        <p class="text-gray-600">
          Gestiona los programas y pagos de tus participantes
        </p>
      </div>

      <!-- Estado de verificación -->
      <div v-if="!user.email_verified_at" class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
        <div class="flex items-start">
          <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <div>
            <p class="text-yellow-800">
              Tu email no ha sido verificado. Por favor revisa tu bandeja de entrada.
            </p>
          </div>
        </div>
      </div>

      <!-- Participantes Card -->
      <div class="bg-white rounded-[20px] shadow-md overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200">
          <h2 class="text-xl font-bold text-[#1C4F4A]">Mis Participantes</h2>
        </div>

        <div v-if="user.guardian_links && user.guardian_links.length > 0" class="divide-y divide-gray-200">
          <div
            v-for="link in user.guardian_links"
            :key="link.id"
            class="px-6 py-5 hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center justify-between">
              <div class="flex-1">
                <h3 class="text-lg font-bold text-[#1C4F4A] mb-1">
                  {{ link.emergency_contact.participant.name }}
                </h3>
                <p class="text-sm text-gray-600">
                  RUT: {{ link.emergency_contact.participant.document }}
                </p>
              </div>
              <div class="ml-6">
                <Link
                  :href="route('guardian.participants')"
                  class="inline-block bg-[#FBBD51] hover:bg-[#e0a840] text-white font-bold py-2.5 px-5 rounded-full transition-all hover:shadow-lg"
                >
                  Ver Detalles
                </Link>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="px-6 py-16 text-center">
          <div class="text-6xl mb-4">📚</div>
          <h3 class="text-xl font-bold text-[#1C4F4A] mb-2">
            No tienes participantes asociados
          </h3>
          <p class="text-gray-600">
            Contacta al administrador para vincular participantes a tu cuenta
          </p>
        </div>
      </div>

      <!-- Info adicional -->
      <div class="mt-6 bg-blue-50 border-l-4 border-blue-400 p-4 rounded-lg">
        <div class="flex items-start">
          <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
          </svg>
          <div>
            <p class="text-blue-800 text-sm">
              <strong>Información:</strong> Desde este panel podrás gestionar los pagos de tus participantes, ver documentos del programa y más.
            </p>
          </div>
        </div>
      </div>
    </div>
  </GuardianLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import GuardianLayout from '@/Layouts/GuardianLayout.vue'

const props = defineProps({
  user: Object
})
</script>
