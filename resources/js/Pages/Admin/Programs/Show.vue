<template>
  <AdminLayout>
    <Head :title="`Programa: ${program?.name || 'Sin nombre'}`" />

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg mb-6">
          <div class="p-6">
            <div class="flex justify-between items-center">
              <div>
                <h2 class="text-2xl font-bold text-gray-900">
                  {{ program?.name || "Sin nombre" }}
                </h2>
                <p class="text-gray-600">
                  {{ program?.destination || "Sin destino" }}
                </p>
              </div>
              <div class="flex space-x-3">
                <Link
                  :href="
                    program?.id ? route('admin.programs.edit', program.id) : '#'
                  "
                  class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 active:bg-yellow-900 focus:outline-none focus:border-yellow-900 focus:ring ring-yellow-300 disabled:opacity-25 transition ease-in-out duration-150">
                  <EditIcon class="w-4 h-4 mr-2" />
                  Editar
                </Link>
                <Link
                  :href="
                    program?.id
                      ? route('admin.programs.passengers', program.id)
                      : '#'
                  "
                  class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                  <PersonsIcon class="w-4 h-4 mr-2" />
                  Pasajeros
                </Link>
                <Link
                  :href="route('admin.programs.index')"
                  class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                  <ChevronLeftIcon class="w-4 h-4 mr-2" />
                  Volver
                </Link>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Columna Principal -->
          <div class="md:col-span-2 space-y-6">
            <!-- Información del programa -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
              <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                  Información del Programa
                </h3>
                <div class="border-t border-gray-200 pt-4">
                  <dl class="divide-y divide-gray-200">
                    <div class="py-3 grid grid-cols-3 gap-4">
                      <dt class="text-sm font-medium text-gray-500">
                        Tipo de Servicio
                      </dt>
                      <dd class="text-sm text-gray-900 col-span-2">
                        <span
                          class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                          {{ program?.service_type || "No especificado" }}
                        </span>
                      </dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4">
                      <dt class="text-sm font-medium text-gray-500">Destino</dt>
                      <dd class="text-sm text-gray-900 col-span-2">
                        {{ program?.destination || "No especificado" }}
                      </dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4">
                      <dt class="text-sm font-medium text-gray-500">
                        Fecha de Inicio
                      </dt>
                      <dd class="text-sm text-gray-900 col-span-2">
                        {{
                          program?.departure_date
                            ? formatDate(program.departure_date)
                            : "Fecha no especificada"
                        }}
                      </dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4">
                      <dt class="text-sm font-medium text-gray-500">
                        Duración
                      </dt>
                      <dd class="text-sm text-gray-900 col-span-2">
                        {{ program?.duration_days || 0 }}
                        {{ program?.duration_days === 1 ? "día" : "días" }}
                      </dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4">
                      <dt class="text-sm font-medium text-gray-500">
                        Precio Base
                      </dt>
                      <dd
                        class="text-sm text-gray-900 font-semibold col-span-2">
                        ${{
                          program?.base_price
                            ? formatPrice(program.base_price)
                            : 0
                        }}
                      </dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4">
                      <dt class="text-sm font-medium text-gray-500">Estado</dt>
                      <dd class="text-sm col-span-2">
                        <span
                          :class="
                            program?.active
                              ? 'bg-green-100 text-green-800'
                              : 'bg-red-100 text-red-800'
                          "
                          class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                          {{ program?.active ? "Activo" : "Inactivo" }}
                        </span>
                      </dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4">
                      <dt class="text-sm font-medium text-gray-500">
                        Descripción
                      </dt>
                      <dd
                        class="text-sm text-gray-900 col-span-2 whitespace-pre-line">
                        {{ program?.description || "Sin descripción" }}
                      </dd>
                    </div>
                    <div
                      v-if="program?.includes"
                      class="py-3 grid grid-cols-3 gap-4">
                      <dt class="text-sm font-medium text-gray-500">Incluye</dt>
                      <dd
                        class="text-sm text-gray-900 col-span-2 whitespace-pre-line">
                        {{ program?.includes }}
                      </dd>
                    </div>
                    <div
                      v-if="program?.not_includes"
                      class="py-3 grid grid-cols-3 gap-4">
                      <dt class="text-sm font-medium text-gray-500">
                        No Incluye
                      </dt>
                      <dd
                        class="text-sm text-gray-900 col-span-2 whitespace-pre-line">
                        {{ program?.not_includes }}
                      </dd>
                    </div>
                    <div
                      v-if="program?.requirements"
                      class="py-3 grid grid-cols-3 gap-4">
                      <dt class="text-sm font-medium text-gray-500">
                        Requisitos
                      </dt>
                      <dd
                        class="text-sm text-gray-900 col-span-2 whitespace-pre-line">
                        {{ program?.requirements }}
                      </dd>
                    </div>
                    <div
                      v-if="program?.additional_info"
                      class="py-3 grid grid-cols-3 gap-4">
                      <dt class="text-sm font-medium text-gray-500">
                        Información Adicional
                      </dt>
                      <dd
                        class="text-sm text-gray-900 col-span-2 whitespace-pre-line">
                        {{ program?.additional_info }}
                      </dd>
                    </div>
                  </dl>
                </div>
              </div>
            </div>

            <!-- Itinerario -->
            <div
              v-if="program?.itinerary"
              class="bg-white overflow-hidden shadow-sm rounded-lg">
              <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                  Itinerario
                </h3>
                <div class="border-t border-gray-200 pt-4">
                  <div
                    class="prose prose-sm max-w-none"
                    v-html="program?.itinerary"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Columna Secundaria -->
          <div class="space-y-6">
            <!-- Imagen del programa -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
              <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                  Imagen Principal
                </h3>
                <div class="mt-2">
                  <div
                    v-if="program?.image_url"
                    class="overflow-hidden rounded-lg">
                    <img
                      :src="`/storage/${program.image_url}`"
                      :alt="program?.name || 'Imagen del programa'"
                      class="w-full h-auto object-cover rounded-lg" />
                  </div>
                  <div
                    v-else
                    class="rounded-lg bg-gray-100 h-48 flex items-center justify-center">
                    <p class="text-gray-500 text-sm">
                      No hay imagen disponible
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Estado de ocupación -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
              <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                  Capacidad y Ocupación
                </h3>
                <div class="mt-4">
                  <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg text-center">
                      <span class="block text-sm text-gray-500"
                        >Capacidad Total</span
                      >
                      <span class="text-2xl font-bold text-gray-700">{{
                        program?.capacity || 0
                      }}</span>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg text-center">
                      <span class="block text-sm text-gray-500"
                        >Lugares Disponibles</span
                      >
                      <span class="text-2xl font-bold text-gray-700">{{
                        program?.available_spots || 0
                      }}</span>
                    </div>
                  </div>
                  <div class="mt-4">
                    <div class="relative pt-1">
                      <div class="overflow-hidden h-4 flex rounded bg-gray-200">
                        <div
                          :style="`width: ${
                            program?.capacity &&
                            program?.available_spots !== undefined
                              ? ((program.capacity - program.available_spots) /
                                  Math.max(1, program.capacity)) *
                                100
                              : 0
                          }%`"
                          class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-turquesa"></div>
                      </div>
                    </div>
                    <div class="text-center mt-2 text-sm text-gray-600">
                      {{
                        program?.capacity &&
                        program?.available_spots !== undefined
                          ? Math.round(
                              ((program.capacity - program.available_spots) /
                                Math.max(1, program.capacity)) *
                                100
                            )
                          : 0
                      }}% ocupado
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Acciones -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
              <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Acciones</h3>
                <div class="space-y-3">
                  <button
                    @click="toggleStatus(program)"
                    :class="[
                      'w-full inline-flex justify-center items-center px-4 py-2 border rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring disabled:opacity-25 transition duration-150 ease-in-out',
                      program?.active
                        ? 'border-red-300 text-red-700 bg-red-50 hover:bg-red-100 focus:border-red-700 focus:ring-red-200'
                        : 'border-green-300 text-green-700 bg-green-50 hover:bg-green-100 focus:border-green-700 focus:ring-green-200'
                    ]">
                    <span v-if="program?.active">
                      <ExclamationIcon class="w-4 h-4 mr-2" />
                      Desactivar Programa
                    </span>
                    <span v-else>
                      <CheckIcon class="w-4 h-4 mr-2" />
                      Activar Programa
                    </span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script>
  import { Head, Link, router } from "@inertiajs/vue3";
  import AdminLayout from "@/Layouts/AdminLayout.vue";
  import {
    EditIcon,
    PersonsIcon,
    ChevronLeftIcon,
    ExclamationIcon,
    CheckIcon
  } from "@/Components/Icons";

  export default {
    name: "ProgramShow",
    components: {
      Head,
      Link,
      AdminLayout,
      EditIcon,
      PersonsIcon,
      ChevronLeftIcon,
      ExclamationIcon,
      CheckIcon
    },
    props: {
      program: {
        type: Object,
        required: true
      }
    },
    methods: {
      formatDate(date) {
        if (!date) return "Fecha no disponible";
        try {
          return new Date(date).toLocaleDateString("es-ES", {
            year: "numeric",
            month: "long",
            day: "numeric"
          });
        } catch (error) {
          console.error("Error al formatear fecha:", error);
          return "Fecha inválida";
        }
      },
      formatPrice(price) {
        if (!price) return "0";
        try {
          return new Intl.NumberFormat("es-CL", {
            style: "currency",
            currency: "CLP"
          })
            .format(price)
            .replace("CLP", "")
            .trim();
        } catch (error) {
          console.error("Error al formatear precio:", error);
          return "0";
        }
      },
      toggleStatus(program) {
        if (!program) {
          alert(
            "No se puede realizar esta acción: información del programa no disponible"
          );
          return;
        }

        if (
          confirm(
            `¿Estás seguro de ${
              program.active ? "desactivar" : "activar"
            } este programa?`
          )
        ) {
          if (!program.id) {
            alert(
              "No se puede completar la acción: ID de programa no disponible"
            );
            return;
          }

          router.patch(
            route("admin.programs.toggle-status", program.id),
            {},
            {
              preserveScroll: true
            }
          );
        }
      }
    }
  };
</script>
