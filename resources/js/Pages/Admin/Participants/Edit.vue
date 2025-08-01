<template>
  <AdminLayout>
    <Head title="Editar Pasajero" />

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <div class="flex justify-between items-center mb-6">
              <h2 class="text-2xl font-bold">Editar Pasajero</h2>
              <Link
                :href="route('admin.passengers.index')"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                Volver al listado
              </Link>
            </div>

            <form @submit.prevent="submit">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Información Personal -->
                <div class="bg-gray-50 p-6 rounded-lg">
                  <h3 class="text-lg font-semibold mb-4">
                    Información Personal
                  </h3>

                  <div class="mb-4">
                    <label
                      for="first_name"
                      class="block text-sm font-medium text-gray-700 mb-1">
                      Nombre
                    </label>
                    <input
                      id="first_name"
                      v-model="form.first_name"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      :class="{ 'border-red-500': errors?.first_name }" />
                    <div
                      v-if="errors?.first_name"
                      class="text-red-500 text-sm mt-1">
                      {{ errors.first_name }}
                    </div>
                  </div>

                  <div class="mb-4">
                    <label
                      for="last_name"
                      class="block text-sm font-medium text-gray-700 mb-1">
                      Apellido
                    </label>
                    <input
                      id="last_name"
                      v-model="form.last_name"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      :class="{ 'border-red-500': errors?.last_name }" />
                    <div
                      v-if="errors?.last_name"
                      class="text-red-500 text-sm mt-1">
                      {{ errors.last_name }}
                    </div>
                  </div>

                  <div class="mb-4">
                    <label
                      for="email"
                      class="block text-sm font-medium text-gray-700 mb-1">
                      Email
                    </label>
                    <input
                      id="email"
                      v-model="form.email"
                      type="email"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      :class="{ 'border-red-500': errors?.email }" />
                    <div
                      v-if="errors?.email"
                      class="text-red-500 text-sm mt-1">
                      {{ errors.email }}
                    </div>
                  </div>

                  <div class="mb-4">
                    <label
                      for="phone"
                      class="block text-sm font-medium text-gray-700 mb-1">
                      Teléfono
                    </label>
                    <input
                      id="phone"
                      v-model="form.phone"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      :class="{ 'border-red-500': errors?.phone }" />
                    <div
                      v-if="errors?.phone"
                      class="text-red-500 text-sm mt-1">
                      {{ errors.phone }}
                    </div>
                  </div>

                  <div class="mb-4">
                    <label
                      for="document_type"
                      class="block text-sm font-medium text-gray-700 mb-1">
                      Tipo de documento
                    </label>
                    <select
                      id="document_type"
                      v-model="form.document_type"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      :class="{ 'border-red-500': errors?.document_type }">
                      <option value="dni">DNI</option>
                      <option value="passport">Pasaporte</option>
                      <option value="other">Otro</option>
                    </select>
                    <div
                      v-if="errors?.document_type"
                      class="text-red-500 text-sm mt-1">
                      {{ errors.document_type }}
                    </div>
                  </div>

                  <div class="mb-4">
                    <label
                      for="document_number"
                      class="block text-sm font-medium text-gray-700 mb-1">
                      Número de documento
                    </label>
                    <input
                      id="document_number"
                      v-model="form.document_number"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      :class="{ 'border-red-500': errors?.document_number }" />
                    <div
                      v-if="errors?.document_number"
                      class="text-red-500 text-sm mt-1">
                      {{ errors.document_number }}
                    </div>
                  </div>
                </div>

                <!-- Información de Reserva -->
                <div class="bg-gray-50 p-6 rounded-lg">
                  <h3 class="text-lg font-semibold mb-4">
                    Información de Reserva
                  </h3>

                  <div class="mb-4">
                    <label
                      for="program_id"
                      class="block text-sm font-medium text-gray-700 mb-1">
                      Programa
                    </label>
                    <select
                      id="program_id"
                      v-model="form.program_id"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      :class="{ 'border-red-500': errors?.program_id }">
                      <option
                        v-for="program in programs"
                        :key="program.id"
                        :value="program.id">
                        {{
                          program?.name ||
                          program?.title ||
                          "Programa sin nombre"
                        }}
                        ({{
                          program?.duration_days || program?.duration || 0
                        }}
                        días)
                      </option>
                    </select>
                    <div
                      v-if="errors?.program_id"
                      class="text-red-500 text-sm mt-1">
                      {{ errors.program_id }}
                    </div>
                  </div>

                  <div class="mb-4">
                    <label
                      for="adults"
                      class="block text-sm font-medium text-gray-700 mb-1">
                      Número de adultos
                    </label>
                    <input
                      id="adults"
                      v-model="form.adults"
                      type="number"
                      min="1"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      :class="{ 'border-red-500': errors?.adults }" />
                    <div
                      v-if="errors?.adults"
                      class="text-red-500 text-sm mt-1">
                      {{ errors.adults }}
                    </div>
                  </div>

                  <div class="mb-4">
                    <label
                      for="children"
                      class="block text-sm font-medium text-gray-700 mb-1">
                      Número de niños
                    </label>
                    <input
                      id="children"
                      v-model="form.children"
                      type="number"
                      min="0"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      :class="{ 'border-red-500': errors?.children }" />
                    <div
                      v-if="errors?.children"
                      class="text-red-500 text-sm mt-1">
                      {{ errors.children }}
                    </div>
                  </div>

                  <div class="mb-4">
                    <label
                      for="individual_price"
                      class="block text-sm font-medium text-gray-700 mb-1">
                      Precio individual
                    </label>
                    <input
                      id="individual_price"
                      v-model="form.individual_price"
                      type="number"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      :class="{ 'border-red-500': errors?.individual_price }" />
                    <div
                      v-if="errors?.individual_price"
                      class="text-red-500 text-sm mt-1">
                      {{ errors.individual_price }}
                    </div>
                  </div>

                  <div class="mb-4">
                    <label
                      for="status"
                      class="block text-sm font-medium text-gray-700 mb-1">
                      Estado
                    </label>
                    <select
                      id="status"
                      v-model="form.status"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      :class="{ 'border-red-500': errors?.status }">
                      <option value="active">Activo</option>
                      <option value="inactive">Inactivo</option>
                      <option value="withdrawn">Retirado</option>
                    </select>
                    <div
                      v-if="errors?.status"
                      class="text-red-500 text-sm mt-1">
                      {{ errors.status }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Comentarios -->
              <div class="bg-gray-50 p-6 rounded-lg mb-6">
                <h3 class="text-lg font-semibold mb-4">Comentarios</h3>
                <textarea
                  v-model="form.comments"
                  rows="4"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  :class="{ 'border-red-500': errors?.comments }"
                  placeholder="Añade comentarios o notas sobre este pasajero..."></textarea>
                <div
                  v-if="errors?.comments"
                  class="text-red-500 text-sm mt-1">
                  {{ errors.comments }}
                </div>
              </div>

              <!-- Botones de acción -->
              <div class="flex justify-end space-x-4">
                <Link
                  :href="route('admin.passengers.index')"
                  class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                  Cancelar
                </Link>
                <button
                  type="submit"
                  class="px-6 py-3 bg-turquesa text-white rounded-lg hover:bg-blue-700 transition"
                  :disabled="processing">
                  {{ processing ? "Guardando..." : "Guardar cambios" }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
  import { ref } from "vue";
  import { Head, Link, useForm } from "@inertiajs/vue3";
  import AdminLayout from "@/Layouts/AdminLayout.vue";

  const props = defineProps({
    passenger: Object,
    programs: Array,
    errors: Object
  });

  // Validar que existan los campos del modelo y proporcionar valores predeterminados
  const passengerData = props.passenger || {};
  const safePrograms = props.programs || [];

  const form = useForm({
    first_name: passengerData.first_name || "",
    last_name: passengerData.last_name || "",
    email: passengerData.email || "",
    phone: passengerData.phone || "",
    document_type: passengerData.document_type || "dni",
    document_number: passengerData.document_number || "",
    program_id: passengerData.program_id || safePrograms[0]?.id || null,
    adults: passengerData.adults || 1,
    children: passengerData.children || 0,
    individual_price: passengerData.individual_price || 0,
    status: passengerData.status || "active",
    comments: passengerData.comments || ""
  });

  const processing = ref(false);

  const submit = () => {
    processing.value = true;

    // Validar ID para evitar errores
    const passengerId = passengerData.id || 0;

    try {
      form.put(route("admin.passengers.update", passengerId), {
        onSuccess: () => {
          processing.value = false;
        },
        onError: (errors) => {
          processing.value = false;
          console.error("Error al actualizar pasajero:", errors);
        }
      });
    } catch (error) {
      processing.value = false;
      console.error("Error al enviar formulario:", error);
    }
  };
</script>
