<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header></header>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Program Summary -->
      <div class="bg-white rounded-lg shadow-sm mb-8 overflow-hidden">
        <div class="p-6">
          <div class="flex items-start space-x-6">
            <div class="w-32 h-32 bg-gray-200 rounded-lg flex-shrink-0">
              <img
                v-if="program.image_url"
                :src="program.image_url"
                :alt="program.name"
                class="w-full h-full object-cover rounded-lg" />
              <div
                v-else
                class="w-full h-full flex items-center justify-center text-gray-400">
                <svg
                  class="w-12 h-12"
                  fill="currentColor"
                  viewBox="0 0 20 20">
                  <path
                    fill-rule="evenodd"
                    d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"
                    clip-rule="evenodd" />
                </svg>
              </div>
            </div>
            <div class="flex-1">
              <h1 class="text-3xl font-bold text-gray-900 mb-2">
                {{ program.name }}
              </h1>
              <p class="text-gray-600 mb-4">{{ program.description }}</p>
              <div class="grid grid-cols-2 gap-4 text-sm text-gray-500">
                <div>
                  <span class="font-medium">Destino:</span>
                  {{ program.destination }}
                </div>
                <div>
                  <span class="font-medium">Duración:</span>
                  {{ program.duration_days }}
                  {{ program.duration_days === 1 ? "día" : "días" }}
                </div>
                <div>
                  <span class="font-medium">Salida:</span>
                  {{ formatDate(program.departure_date) }}
                </div>
                <div>
                  <span class="font-medium">Precio:</span>
                  <span class="text-2xl font-bold text-indigo-600"
                    >${{ formatPrice(program.base_price) }}</span
                  >
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Reservation Form -->
      <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">
            Datos del Pasajero
          </h2>

          <form @submit.prevent="submitReservation">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Información Personal -->
              <div class="md:col-span-2">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                  Información Personal
                </h3>
              </div>

              <div>
                <label
                  for="full_name"
                  class="block text-sm font-medium text-gray-700 mb-2"
                  >Nombre Completo *</label
                >
                <input
                  id="full_name"
                  v-model="form.full_name"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  :class="{ 'border-red-500': errors.full_name }" />
                <div
                  v-if="errors.full_name"
                  class="mt-1 text-sm text-red-600">
                  {{ errors.full_name }}
                </div>
              </div>

              <div>
                <label
                  for="email"
                  class="block text-sm font-medium text-gray-700 mb-2"
                  >Email *</label
                >
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  :class="{ 'border-red-500': errors.email }" />
                <div
                  v-if="errors.email"
                  class="mt-1 text-sm text-red-600">
                  {{ errors.email }}
                </div>
              </div>

              <div>
                <label
                  for="phone"
                  class="block text-sm font-medium text-gray-700 mb-2"
                  >Teléfono *</label
                >
                <input
                  id="phone"
                  v-model="form.phone"
                  type="tel"
                  required
                  placeholder="+56912345678"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  :class="{ 'border-red-500': errors.phone }" />
                <div
                  v-if="errors.phone"
                  class="mt-1 text-sm text-red-600">
                  {{ errors.phone }}
                </div>
              </div>

              <div>
                <label
                  for="birth_date"
                  class="block text-sm font-medium text-gray-700 mb-2"
                  >Fecha de Nacimiento *</label
                >
                <input
                  id="birth_date"
                  v-model="form.birth_date"
                  type="date"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  :class="{ 'border-red-500': errors.birth_date }" />
                <div
                  v-if="errors.birth_date"
                  class="mt-1 text-sm text-red-600">
                  {{ errors.birth_date }}
                </div>
              </div>

              <!-- Documento de Identidad -->
              <div>
                <label
                  for="document_type"
                  class="block text-sm font-medium text-gray-700 mb-2"
                  >Tipo de Documento *</label
                >
                <select
                  id="document_type"
                  v-model="form.document_type"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  :class="{ 'border-red-500': errors.document_type }">
                  <option value="">Seleccionar tipo</option>
                  <option value="rut">RUT (Chile)</option>
                  <option value="cedula">Cédula</option>
                  <option value="passport">Pasaporte</option>
                </select>
                <div
                  v-if="errors.document_type"
                  class="mt-1 text-sm text-red-600">
                  {{ errors.document_type }}
                </div>
              </div>

              <div>
                <label
                  for="document_number"
                  class="block text-sm font-medium text-gray-700 mb-2"
                  >Número de Documento *</label
                >
                <input
                  id="document_number"
                  v-model="form.document_number"
                  type="text"
                  required
                  :placeholder="documentPlaceholder"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  :class="{ 'border-red-500': errors.document_number }" />
                <div
                  v-if="errors.document_number"
                  class="mt-1 text-sm text-red-600">
                  {{ errors.document_number }}
                </div>
              </div>

              <!-- Información de Contacto -->
              <div class="md:col-span-2">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 mt-6">
                  Información de Contacto
                </h3>
              </div>

              <div class="md:col-span-2">
                <label
                  for="address"
                  class="block text-sm font-medium text-gray-700 mb-2"
                  >Dirección *</label
                >
                <input
                  id="address"
                  v-model="form.address"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  :class="{ 'border-red-500': errors.address }" />
                <div
                  v-if="errors.address"
                  class="mt-1 text-sm text-red-600">
                  {{ errors.address }}
                </div>
              </div>

              <!-- Contacto de Emergencia -->
              <div class="md:col-span-2">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 mt-6">
                  Contacto de Emergencia
                </h3>
              </div>

              <div>
                <label
                  for="emergency_contact_name"
                  class="block text-sm font-medium text-gray-700 mb-2"
                  >Nombre Contacto Emergencia *</label
                >
                <input
                  id="emergency_contact_name"
                  v-model="form.emergency_contact_name"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  :class="{
                    'border-red-500': errors.emergency_contact_name
                  }" />
                <div
                  v-if="errors.emergency_contact_name"
                  class="mt-1 text-sm text-red-600">
                  {{ errors.emergency_contact_name }}
                </div>
              </div>

              <div>
                <label
                  for="emergency_contact_phone"
                  class="block text-sm font-medium text-gray-700 mb-2"
                  >Teléfono Contacto Emergencia *</label
                >
                <input
                  id="emergency_contact_phone"
                  v-model="form.emergency_contact_phone"
                  type="tel"
                  required
                  placeholder="+56987654321"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  :class="{
                    'border-red-500': errors.emergency_contact_phone
                  }" />
                <div
                  v-if="errors.emergency_contact_phone"
                  class="mt-1 text-sm text-red-600">
                  {{ errors.emergency_contact_phone }}
                </div>
              </div>

              <!-- Información Adicional -->
              <div class="md:col-span-2">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 mt-6">
                  Información Adicional
                </h3>
              </div>

              <div>
                <label
                  for="dietary_restrictions"
                  class="block text-sm font-medium text-gray-700 mb-2"
                  >Restricciones Alimentarias</label
                >
                <textarea
                  id="dietary_restrictions"
                  v-model="form.dietary_restrictions"
                  rows="3"
                  placeholder="Vegetariano, vegano, alergias, etc."
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  :class="{
                    'border-red-500': errors.dietary_restrictions
                  }"></textarea>
                <div
                  v-if="errors.dietary_restrictions"
                  class="mt-1 text-sm text-red-600">
                  {{ errors.dietary_restrictions }}
                </div>
              </div>

              <div>
                <label
                  for="medical_conditions"
                  class="block text-sm font-medium text-gray-700 mb-2"
                  >Condiciones Médicas</label
                >
                <textarea
                  id="medical_conditions"
                  v-model="form.medical_conditions"
                  rows="3"
                  placeholder="Condiciones médicas relevantes para el viaje"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  :class="{
                    'border-red-500': errors.medical_conditions
                  }"></textarea>
                <div
                  v-if="errors.medical_conditions"
                  class="mt-1 text-sm text-red-600">
                  {{ errors.medical_conditions }}
                </div>
              </div>
            </div>

            <!-- Terms and Conditions -->
            <div class="mt-8">
              <div class="flex items-start">
                <input
                  id="accept_terms"
                  v-model="form.accept_terms"
                  type="checkbox"
                  required
                  class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" />
                <label
                  for="accept_terms"
                  class="ml-3 text-sm text-gray-700">
                  Acepto los
                  <a
                    href="#"
                    class="text-indigo-600 hover:text-indigo-500"
                    >términos y condiciones</a
                  >
                  y la
                  <a
                    href="#"
                    class="text-indigo-600 hover:text-indigo-500"
                    >política de privacidad</a
                  >
                  *
                </label>
              </div>
              <div
                v-if="errors.accept_terms"
                class="mt-1 text-sm text-red-600">
                {{ errors.accept_terms }}
              </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-8 flex justify-end space-x-4">
              <Link
                :href="route('ecommerce.show', program.id)"
                class="px-6 py-3 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition duration-200">
                Volver
              </Link>
              <button
                type="submit"
                :disabled="processing"
                class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                <span v-if="processing">Procesando...</span>
                <span v-else>Continuar al Pago</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import { Link, useForm } from "@inertiajs/vue3";
  import Header from "@/Components/Ecommerce/Header.vue";

  export default {
    name: "Reservation",
    components: {
      Link,
      Header
    },
    props: {
      program: {
        type: Object,
        required: true
      },
      errors: {
        type: Object,
        default: () => ({})
      }
    },
    setup(props) {
      const form = useForm({
        full_name: "",
        email: "",
        phone: "",
        document_type: "",
        document_number: "",
        birth_date: "",
        address: "",
        emergency_contact_name: "",
        emergency_contact_phone: "",
        dietary_restrictions: "",
        medical_conditions: "",
        accept_terms: false
      });

      const toProperCase = (str) => {
          if (!str) return '';
          return str.trim().split(/\s+/).map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join(' ');
      };

      const submitReservation = () => {
        form.full_name = toProperCase(form.full_name);
        form.emergency_contact_name = toProperCase(form.emergency_contact_name);
        form.post(route("ecommerce.store-reservation", props.program.id));
      };

      return {
        form,
        submitReservation,
        processing: form.processing
      };
    },
    computed: {
      documentPlaceholder() {
        switch (this.form.document_type) {
          case "rut":
            return "12345678-9";
          case "cedula":
            return "12345678";
          case "passport":
            return "A12345678";
          default:
            return "Número de documento";
        }
      }
    },
    methods: {
      formatDate(date) {
        return new Date(date).toLocaleDateString("es-ES", {
          year: "numeric",
          month: "long",
          day: "numeric"
        });
      },
      formatPrice(price) {
        return new Intl.NumberFormat("es-CL", {
          style: "currency",
          currency: "CLP"
        })
          .format(price)
          .replace("CLP", "")
          .trim();
      }
    }
  };
</script>
