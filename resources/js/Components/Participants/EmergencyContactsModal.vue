<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-[20px] w-[800px] max-h-[90vh] flex flex-col">
      <!-- Header -->
      <div class="flex items-center justify-between p-6 border-b border-gray-200">
        <div class="flex items-center gap-4">
          <div class="w-8 h-8 bg-azul-oscuro rounded-full flex items-center justify-center">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1H5C3.89 1 3 1.89 3 3V21C3 22.11 3.89 23 5 23H19C20.11 23 21 22.11 21 21V9ZM19 21H5V3H13V9H19V21Z" fill="white"/>
            </svg>
          </div>
          <div>
            <h2 class="text-[24px] font-nexa-bold text-gray-800">Apoderado</h2>
            <p class="text-[14px] text-gray-600">{{ getFullName(participant) }}</p>
          </div>
        </div>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z" fill="currentColor"/>
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="p-6 overflow-y-auto flex-1">
        <!-- Messages -->
        <div v-if="errorMessage" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
          {{ errorMessage }}
        </div>
        <div v-if="successMessage" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
          {{ successMessage }}
        </div>

        <!-- Existing Contacts -->
        <div class="mb-6">
          <h3 class="text-[18px] font-nexa-bold text-gray-800 mb-4">Apoderado</h3>
          
          <div v-if="participant.emergency_contacts && participant.emergency_contacts.length > 0" class="space-y-4">
            <div v-for="(contact, index) in participant.emergency_contacts" :key="contact.id" class="border border-gray-200 rounded-lg p-4">
              <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 bg-azul-oscuro rounded-full flex items-center justify-center">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1H5C3.89 1 3 1.89 3 3V21C3 22.11 3.89 23 5 23H19C20.11 23 21 22.11 21 21V9ZM19 21H5V3H13V9H19V21Z" fill="white"/>
                    </svg>
                  </div>
                  <div class="text-left">
                    <div class="font-nexa-bold text-gray-800">Apoderado {{ index + 1 }}</div>
                    <div class="text-sm text-gray-600">{{ contact.name }}</div>
                  </div>
                </div>
                <button 
                  v-if="participant.emergency_contacts && participant.emergency_contacts.length > 1"
                  @click="deleteContact(contact.id)"
                  class="text-red-500 hover:text-red-700 transition-colors"
                  title="Eliminar apoderado"
                >
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" fill="currentColor"/>
                  </svg>
                </button>
                <div 
                  v-else
                  class="text-gray-400 cursor-not-allowed"
                  title="No se puede eliminar el último apoderado"
                >
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" fill="currentColor"/>
                  </svg>
                </div>
              </div>
              
              <!-- Editable Form -->
              <form @submit.prevent="updateContact(contact.id, index)" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                  <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo *</label>
                    <input
                      v-model="contact.name"
                      type="text"
                      required
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-azul-oscuro focus:border-transparent"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input
                      v-model="contact.email"
                      type="email"
                      required
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-azul-oscuro focus:border-transparent"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Documento *</label>
                    <select
                      v-model="contact.document_type"
                      required
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-azul-oscuro focus:border-transparent"
                    >
                      <option value="">Seleccionar tipo</option>
                      <option value="1">RUT (Chile)</option>
                      <option value="2">Pasaporte</option>
                      <option value="3">DNI (Argentina)</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número de Documento *</label>
                    <input
                      v-model="contact.document_number"
                      type="text"
                      :placeholder="getDocumentPlaceholder(contact.document_type)"
                      required
                      @input="(event) => handleDocumentInput(event, contact)"
                      @blur="validateDocument(contact)"
                      :class="[
                        'w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-azul-oscuro focus:border-transparent',
                        contact.documentValidation && contact.documentValidation.isValid === false ? 'border-red-500' : '',
                        contact.documentValidation && contact.documentValidation.isValid === true ? 'border-green-500' : 'border-gray-300'
                      ]"
                    />
                    <span
                      v-if="contact.documentValidation && contact.documentValidation.message"
                      :class="[
                        'text-xs mt-1',
                        contact.documentValidation.isValid === true ? 'text-green-500' : 'text-red-500'
                      ]"
                    >
                      {{ contact.documentValidation.message }}
                    </span>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código de País</label>
                    <select 
                      v-model="contact.code_phone"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-azul-oscuro focus:border-transparent"
                    >
                      <option value="">Seleccionar código</option>
                      <option value="+56">+56 (Chile)</option>
                      <option value="+54">+54 (Argentina)</option>
                      <option value="+57">+57 (Colombia)</option>
                      <option value="+51">+51 (Perú)</option>
                      <option value="+593">+593 (Ecuador)</option>
                      <option value="+58">+58 (Venezuela)</option>
                      <option value="+52">+52 (México)</option>
                      <option value="+34">+34 (España)</option>
                      <option value="+1">+1 (Estados Unidos)</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input 
                      v-model="contact.phone"
                      type="tel"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-azul-oscuro focus:border-transparent"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">País</label>
                    <select 
                      v-model="contact.country"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-azul-oscuro focus:border-transparent"
                    >
                      <option value="">Seleccionar país</option>
                      <option value="CL">Chile</option>
                      <option value="AR">Argentina</option>
                      <option value="CO">Colombia</option>
                      <option value="PE">Perú</option>
                      <option value="EC">Ecuador</option>
                      <option value="VE">Venezuela</option>
                      <option value="MX">México</option>
                      <option value="ES">España</option>
                      <option value="US">Estados Unidos</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Nacimiento</label>
                    <input 
                      v-model="contact.birth_date"
                      type="date"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-azul-oscuro focus:border-transparent"
                    />
                  </div>
                </div>
                
                <div class="col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                  <textarea 
                    v-model="contact.address"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-azul-oscuro focus:border-transparent resize-none"
                  ></textarea>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end gap-3 pt-4">
                  <button 
                    type="submit"
                    :disabled="isSubmitting"
                    class="px-4 py-2 bg-azul-oscuro text-white rounded-lg hover:bg-azul-oscuro-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {{ isSubmitting ? 'Actualizando...' : 'Actualizar Apoderado' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
          
          <div v-else class="text-center py-8">
            <p class="text-gray-500">No hay apoderados registrados.</p>
          </div>
        </div>

        <!-- Add New Contact Button -->
        <div class="mb-6">
          <button
            @click="showNewContactForm = true"
            v-if="!showNewContactForm"
            class="w-full px-4 py-3 bg-azul-oscuro text-white rounded-lg hover:bg-azul-oscuro-dark transition-colors flex items-center justify-center gap-2"
          >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Agregar Nuevo Apoderado
          </button>
        </div>

        <!-- New Contact Form -->
        <div v-if="showNewContactForm" class="border border-gray-200 rounded-lg p-4 bg-gray-50">
          <h4 class="text-[16px] font-nexa-bold text-gray-800 mb-4">Nuevo Apoderado</h4>
          
          <form @submit.prevent="saveNewContact" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo *</label>
                <input
                  v-model="newContact.name"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-azul-oscuro focus:border-transparent"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                <input
                  v-model="newContact.email"
                  type="email"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-azul-oscuro focus:border-transparent"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Documento *</label>
                <select
                  v-model="newContact.document_type"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-azul-oscuro focus:border-transparent"
                >
                  <option value="">Seleccionar tipo</option>
                  <option value="1">RUT (Chile)</option>
                  <option value="2">Pasaporte</option>
                  <option value="3">DNI (Argentina)</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Número de Documento *</label>
                <input
                  v-model="newContact.document_number"
                  type="text"
                  :placeholder="getDocumentPlaceholder(newContact.document_type)"
                  required
                  @input="formatNewContactDocument"
                  @blur="validateNewContactDocument"
                  :class="[
                    'w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-azul-oscuro focus:border-transparent',
                    newContact.documentValidation && newContact.documentValidation.isValid === false ? 'border-red-500' : '',
                    newContact.documentValidation && newContact.documentValidation.isValid === true ? 'border-green-500' : 'border-gray-300'
                  ]"
                />
                <span
                  v-if="newContact.documentValidation && newContact.documentValidation.message"
                  :class="[
                    'text-xs mt-1',
                    newContact.documentValidation.isValid === true ? 'text-green-500' : 'text-red-500'
                  ]"
                >
                  {{ newContact.documentValidation.message }}
                </span>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Código de País</label>
                <select 
                  v-model="newContact.code_phone"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-azul-oscuro focus:border-transparent"
                >
                  <option value="">Seleccionar código</option>
                  <option value="+56">+56 (Chile)</option>
                  <option value="+54">+54 (Argentina)</option>
                  <option value="+57">+57 (Colombia)</option>
                  <option value="+51">+51 (Perú)</option>
                  <option value="+593">+593 (Ecuador)</option>
                  <option value="+58">+58 (Venezuela)</option>
                  <option value="+52">+52 (México)</option>
                  <option value="+34">+34 (España)</option>
                  <option value="+1">+1 (Estados Unidos)</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                <input 
                  v-model="newContact.phone"
                  type="tel"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-azul-oscuro focus:border-transparent"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">País</label>
                <select 
                  v-model="newContact.country"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-azul-oscuro focus:border-transparent"
                >
                  <option value="">Seleccionar país</option>
                  <option value="CL">Chile</option>
                  <option value="AR">Argentina</option>
                  <option value="CO">Colombia</option>
                  <option value="PE">Perú</option>
                  <option value="EC">Ecuador</option>
                  <option value="VE">Venezuela</option>
                  <option value="MX">México</option>
                  <option value="ES">España</option>
                  <option value="US">Estados Unidos</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Nacimiento</label>
                <input 
                  v-model="newContact.birth_date"
                  type="date"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-azul-oscuro focus:border-transparent"
                />
              </div>
            </div>
            
            <div class="col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
              <textarea 
                v-model="newContact.address"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-azul-oscuro focus:border-transparent resize-none"
              ></textarea>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-3 pt-4">
              <button 
                type="button"
                @click="cancelNewContact"
                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
              >
                Cancelar
              </button>
              <button 
                type="submit"
                :disabled="isSubmitting"
                class="px-4 py-2 bg-azul-oscuro text-white rounded-lg hover:bg-azul-oscuro-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ isSubmitting ? 'Guardando...' : 'Guardar Apoderado' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue';
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
const showNewContactForm = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const newContact = ref({
  name: '',
  email: '',
  document_type: '1', // Default to RUT
  code_phone: '+56',
  phone: '',
  country: 'CL',
  birth_date: '',
  address: '',
  document_number: '',
  documentValidation: null
});

const formatDate = (dateString) => {
  if (!dateString) return null;
  const date = new Date(dateString);
  return date.toISOString().split('T')[0];
};

// Watcher para formatear las fechas y documentos cuando se abra el modal
watch(() => props.show, (newValue) => {
  // Limpiar mensajes cuando se abre/cierra el modal
  errorMessage.value = '';
  successMessage.value = '';

  if (newValue && props.participant.emergency_contacts) {
    // Formatear las fechas y documentos de los contactos existentes e inicializar validación
    props.participant.emergency_contacts.forEach(contact => {
      if (contact.birth_date) {
        contact.birth_date = formatDate(contact.birth_date);
      }
      // Inicializar document_type si no existe (default a RUT)
      if (!contact.document_type) {
        contact.document_type = '1';
      } else {
        // Convertir document_type a string para consistencia con el v-model del select
        contact.document_type = contact.document_type.toString();
      }
      // Formatear RUT existente si es tipo RUT y no está formateado
      if (contact.document_type === '1' && contact.document_number && !contact.document_number.includes('.')) {
        formatExistingRut(contact);
      }
      // Inicializar validación de documento si no existe
      if (!contact.documentValidation) {
        contact.documentValidation = null;
      }
    });
  }
}, { immediate: true });

const toProperCase = (str) => {
    if (!str) return '';
    return str.trim().split(/\s+/).map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join(' ');
};

const saveNewContact = () => {
  isSubmitting.value = true;
  newContact.value.name = toProperCase(newContact.value.name);

  const formData = new FormData();
  formData.append('emergency_contacts', JSON.stringify([newContact.value]));
  formData.append('_method', 'PUT');

  router.post(route('admin.participants.update-emergency-contacts', props.participant.id), formData, {
    onSuccess: () => {
      showNewContactForm.value = false;
      resetNewContactForm();
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

const updateContact = (contactId, index) => {
  isSubmitting.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  const contact = props.participant.emergency_contacts[index];
  contact.name = toProperCase(contact.name);
  const formData = new FormData();
  formData.append('contact_id', contactId);
  formData.append('name', contact.name || '');
  formData.append('email', contact.email || '');
  formData.append('document_type', contact.document_type || '1');
  formData.append('document_number', contact.document_number || '');
  formData.append('code_phone', contact.code_phone || '');
  formData.append('phone', contact.phone || '');
  formData.append('country', contact.country || '');
  formData.append('birth_date', contact.birth_date || '');
  formData.append('address', contact.address || '');
  formData.append('_method', 'PUT');

  console.log('Updating contact:', contactId, 'Data:', Object.fromEntries(formData));

  router.post(route('admin.participants.update-emergency-contact', props.participant.id), formData, {
    preserveScroll: true,
    onSuccess: (page) => {
      console.log('Update success:', page);
      successMessage.value = 'Apoderado actualizado exitosamente';
      setTimeout(() => {
        window.location.reload();
      }, 500);
    },
    onError: (errors) => {
      console.error('Update errors:', errors);
      isSubmitting.value = false;
      if (typeof errors === 'object') {
        const errorMessages = Object.values(errors).flat();
        errorMessage.value = errorMessages.join(', ');
      } else {
        errorMessage.value = 'Error al actualizar el apoderado';
      }
    },
    onFinish: () => {
      isSubmitting.value = false;
    }
  });
};

const deleteContact = (contactId) => {
  if (!confirm('¿Estás seguro de que quieres eliminar este apoderado?')) {
    return;
  }

  isSubmitting.value = true;
  
  const formData = new FormData();
  formData.append('contact_id', contactId);
  formData.append('_method', 'DELETE');

  router.post(route('admin.participants.delete-emergency-contact', props.participant.id), formData, {
    onSuccess: () => {
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

const cancelNewContact = () => {
  showNewContactForm.value = false;
  resetNewContactForm();
};

const resetNewContactForm = () => {
  newContact.value = {
    name: '',
    email: '',
    document_type: '1',
    code_phone: '+56',
    phone: '',
    country: 'CL',
    birth_date: '',
    address: '',
    document_number: '',
    documentValidation: null
  };
};

const getFullName = (participant) => {
  if (!participant) return '';

  const firstName = participant.first_name ? participant.first_name.charAt(0).toUpperCase() + participant.first_name.slice(1) : '';
  const secondName = participant.second_name ? participant.second_name.charAt(0).toUpperCase() + participant.second_name.slice(1) : '';
  const firstLastName = participant.first_last_name ? participant.first_last_name.charAt(0).toUpperCase() + participant.first_last_name.slice(1) : '';
  const secondLastName = participant.second_last_name ? participant.second_last_name.charAt(0).toUpperCase() + participant.second_last_name.slice(1) : '';

  return `${firstLastName} ${secondLastName} ${firstName} ${secondName}`.trim();
};

const getDocumentPlaceholder = (documentType) => {
  // Convertir a string para comparación ya que puede venir como número desde la BD
  switch (documentType?.toString()) {
    case '1':
      return '00.000.000-0';
    case '2':
      return 'ABC123456';
    case '3':
      return '12345678';
    default:
      return 'Número de documento';
  }
};

// Funciones para validar RUT de contactos existentes
const formatExistingRut = (contact) => {
  if (!contact.document_number) return;
  
  // Remover todos los caracteres no numéricos excepto K
  let rut = contact.document_number.replace(/[^0-9kK]/g, '').toUpperCase();
  
  if (rut.length > 1) {
    const body = rut.slice(0, -1);
    const dv = rut.slice(-1);

    // Formatear el cuerpo con puntos
    let formattedBody = '';
    for (let i = body.length - 1, j = 0; i >= 0; i--, j++) {
      if (j > 0 && j % 3 === 0) {
        formattedBody = '.' + formattedBody;
      }
      formattedBody = body[i] + formattedBody;
    }

    // Combinar cuerpo formateado con dígito verificador
    const formattedRut = `${formattedBody}-${dv}`;
    
    // Actualizar el valor del contacto
    contact.document_number = formattedRut;
  }
};

const handleDocumentInput = (event, contact) => {
  const inputValue = event.target.value;

  // Si es RUT (tipo 1), formatear con puntos y guión
  // Convertir a string para comparación ya que puede venir como número desde la BD
  if (contact.document_type?.toString() === '1') {
    let rut = inputValue.replace(/[^0-9kK]/g, '').toUpperCase();

    if (rut.length > 0) {
      if (rut.length > 1) {
        const body = rut.slice(0, -1);
        const dv = rut.slice(-1);

        let formattedBody = '';
        for (let i = body.length - 1, j = 0; i >= 0; i--, j++) {
          if (j > 0 && j % 3 === 0) {
            formattedBody = '.' + formattedBody;
          }
          formattedBody = body[i] + formattedBody;
        }

        contact.document_number = `${formattedBody}-${dv}`;
      } else {
        contact.document_number = rut;
      }
    } else {
      contact.document_number = '';
    }
  } else {
    // Para otros tipos de documentos, simplemente guardar el valor en mayúsculas
    contact.document_number = inputValue.toUpperCase();
  }
};

const formatRut = (contact) => {
  if (!contact.document_number) return;
  
  // Remover todos los caracteres no numéricos excepto K
  let rut = contact.document_number.replace(/[^0-9kK]/g, '').toUpperCase();
  
  if (rut.length > 0) {
    // Si tiene más de 1 carácter, separar cuerpo y dígito verificador
    if (rut.length > 1) {
      const body = rut.slice(0, -1);
      const dv = rut.slice(-1);

      // Formatear el cuerpo con puntos
      let formattedBody = '';
      for (let i = body.length - 1, j = 0; i >= 0; i--, j++) {
        if (j > 0 && j % 3 === 0) {
          formattedBody = '.' + formattedBody;
        }
        formattedBody = body[i] + formattedBody;
      }

      // Combinar cuerpo formateado con dígito verificador
      const formattedRut = `${formattedBody}-${dv}`;
      
      // Actualizar el valor del contacto
      contact.document_number = formattedRut;
    } else {
      contact.document_number = rut;
    }
  }
};

const validateDocument = (contact) => {
  if (!contact.document_number) {
    contact.documentValidation = null;
    return;
  }

  // Solo validar si es RUT (tipo 1)
  // Convertir a string para comparación ya que puede venir como número desde la BD
  if (contact.document_type?.toString() === '1') {
    const rut = contact.document_number.replace(/\./g, '').replace(/-/g, '');
    if (!/^[0-9]+[0-9kK]$/.test(rut)) {
      contact.documentValidation = { isValid: false, message: 'Formato de RUT inválido' };
      return;
    }

    const body = rut.slice(0, -1);
    const dv = rut.slice(-1).toUpperCase();

    if (body.length < 7) {
      contact.documentValidation = { isValid: false, message: 'RUT debe tener al menos 7 dígitos' };
      return;
    }

    let sum = 0;
    let factor = 2;
    for (let i = body.length - 1; i >= 0; i--) {
      sum += body[i] * factor;
      factor = factor === 7 ? 2 : factor + 1;
    }

    const dvCalculado = 11 - (sum % 11);
    const dvFinal = dvCalculado === 10 ? 'K' : dvCalculado === 11 ? '0' : dvCalculado.toString();

    contact.documentValidation = {
      isValid: dv === dvFinal,
      message: dv === dvFinal ? 'RUT válido' : 'RUT inválido'
    };
  } else {
    // Para otros tipos de documentos, simplemente validar que no esté vacío
    contact.documentValidation = {
      isValid: contact.document_number.length > 0,
      message: contact.document_number.length > 0 ? 'Documento válido' : 'Documento requerido'
    };
  }
};

const formatNewContactDocument = () => {
  if (!newContact.value.document_number) return;

  // Si es RUT (tipo 1), formatear con puntos y guión
  if (newContact.value.document_type === '1') {
    let rut = newContact.value.document_number.replace(/[^0-9kK]/g, '').toUpperCase();

    if (rut.length > 0) {
      if (rut.length > 1) {
        const body = rut.slice(0, -1);
        const dv = rut.slice(-1);

        let formattedBody = '';
        for (let i = body.length - 1, j = 0; i >= 0; i--, j++) {
          if (j > 0 && j % 3 === 0) {
            formattedBody = '.' + formattedBody;
          }
          formattedBody = body[i] + formattedBody;
        }

        const formattedRut = `${formattedBody}-${dv}`;

        if (newContact.value.document_number !== formattedRut) {
          nextTick(() => {
            newContact.value.document_number = formattedRut;
          });
        }
      } else {
        if (newContact.value.document_number !== rut) {
          nextTick(() => {
            newContact.value.document_number = rut;
          });
        }
      }
    }
  } else {
    // Para otros tipos de documentos, convertir a mayúsculas
    const upperDoc = newContact.value.document_number.toUpperCase();
    if (newContact.value.document_number !== upperDoc) {
      nextTick(() => {
        newContact.value.document_number = upperDoc;
      });
    }
  }
};

const validateNewContactDocument = () => {
  if (!newContact.value.document_number) {
    newContact.value.documentValidation = null;
    return;
  }

  // Solo validar si es RUT (tipo 1)
  if (newContact.value.document_type === '1') {
    const rut = newContact.value.document_number.replace(/\./g, '').replace(/-/g, '');

    if (!/^[0-9]+[0-9kK]$/.test(rut)) {
      newContact.value.documentValidation = { isValid: false, message: 'Formato de RUT inválido' };
      return;
    }

    const body = rut.slice(0, -1);
    const dv = rut.slice(-1).toUpperCase();

    if (body.length < 7) {
      newContact.value.documentValidation = { isValid: false, message: 'RUT debe tener al menos 7 dígitos' };
      return;
    }

    let sum = 0;
    let factor = 2;
    for (let i = body.length - 1; i >= 0; i--) {
      sum += body[i] * factor;
      factor = factor === 7 ? 2 : factor + 1;
    }

    const dvCalculado = 11 - (sum % 11);
    const dvFinal = dvCalculado === 10 ? 'K' : dvCalculado === 11 ? '0' : dvCalculado.toString();

    newContact.value.documentValidation = {
      isValid: dv === dvFinal,
      message: dv === dvFinal ? 'RUT válido' : 'RUT inválido'
    };
  } else {
    // Para otros tipos de documentos, simplemente validar que no esté vacío
    newContact.value.documentValidation = {
      isValid: newContact.value.document_number.length > 0,
      message: newContact.value.document_number.length > 0 ? 'Documento válido' : 'Documento requerido'
    };
  }
};
</script>

<style scoped>
.bg-azul-oscuro {
  background-color: #1a4b75;
}

.bg-azul-oscuro-dark {
  background-color: #0f2d4a;
}

.focus\:ring-azul-oscuro:focus {
  --tw-ring-color: #1a4b75;
}

.font-nexa-bold {
  font-family: "Nexa-Bold", sans-serif;
}
</style> 