<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-[20px] w-[600px] max-h-[90vh] overflow-y-auto">
      <!-- Header -->
      <div class="flex items-center justify-between p-6 border-b border-gray-200">
        <div class="flex items-center gap-4">
          <div class="w-8 h-8 bg-rojo rounded-full flex items-center justify-center">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="white"/>
            </svg>
          </div>
          <div>
            <h2 class="text-[24px] font-nexa-bold text-gray-800">Editar Condiciones Médicas</h2>
            <p class="text-[14px] text-gray-600">{{ participant.first_name }} {{ participant.last_name }}</p>
          </div>
        </div>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z" fill="currentColor"/>
          </svg>
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="updateMedicalInfo" class="p-6">
        <div class="mb-6">
          <h3 class="text-[18px] font-nexa-bold text-gray-800 mb-4">Información Médica</h3>
          
          <!-- Condiciones médicas -->
          <div class="mb-6">
            <label class="block text-[16px] font-nexa-bold text-gray-700 mb-3">
              Condiciones Médicas
            </label>
            <textarea
              v-model="form.medical_conditions"
              placeholder="Ingrese las condiciones médicas del participante..."
              rows="4"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent resize-none"
              :class="{ 'border-red-500': errors?.medical_conditions }"
            ></textarea>
            <div v-if="errors?.medical_conditions" class="text-red-500 text-sm mt-1">
              {{ errors.medical_conditions }}
            </div>
          </div>

          <!-- Restricciones dietarias -->
          <div class="mb-6">
            <label class="block text-[16px] font-nexa-bold text-gray-700 mb-3">
              Restricciones Dietarias
            </label>
            <textarea
              v-model="form.dietary_restrictions"
              placeholder="Ingrese las restricciones dietarias del participante..."
              rows="3"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent resize-none"
              :class="{ 'border-red-500': errors?.dietary_restrictions }"
            ></textarea>
            <div v-if="errors?.dietary_restrictions" class="text-red-500 text-sm mt-1">
              {{ errors.dietary_restrictions }}
            </div>
          </div>
        </div>

        <!-- Botones -->
        <div class="flex justify-end gap-4 pt-6 border-t border-gray-200">
          <button
            type="button"
            @click="$emit('close')"
            class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
          >
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="isSubmitting"
            class="px-6 py-3 bg-turquesa text-white rounded-lg hover:bg-turquesa-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ isSubmitting ? 'Guardando...' : 'Guardar Cambios' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  participant: {
    type: Object,
    default: () => ({})
  },
  errors: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['close']);

const isSubmitting = ref(false);

const form = ref({
  medical_conditions: '',
  dietary_restrictions: ''
});

// Cargar datos del participante cuando se abre el modal
watch(() => props.participant, (newParticipant) => {
  if (newParticipant && Object.keys(newParticipant).length > 0) {
    form.value = {
      medical_conditions: newParticipant.medical_conditions || '',
      dietary_restrictions: newParticipant.dietary_restrictions || ''
    };
  }
}, { immediate: true, deep: true });

const updateMedicalInfo = () => {
  isSubmitting.value = true;
  
  const formData = new FormData();
  formData.append('medical_conditions', form.value.medical_conditions);
  formData.append('dietary_restrictions', form.value.dietary_restrictions);
  formData.append('_method', 'PUT');

  router.post(route('admin.participants.update-medical-conditions', props.participant.id), formData, {
    onSuccess: () => {
      emit('close');
      window.location.reload();
    },
    onError: (errors) => {
      isSubmitting.value = false;
    },
    onFinish: () => {
      isSubmitting.value = false;
    }
  });
};
</script>

<style scoped>
.bg-rojo {
  background-color: #d54a42;
}

.bg-turquesa {
  background-color: #007e93;
}

.bg-turquesa-dark {
  background-color: #006b7d;
}

.focus\:ring-turquesa:focus {
  --tw-ring-color: #007e93;
}

.font-nexa-bold {
  font-family: "Nexa-Bold", sans-serif;
}
</style> 