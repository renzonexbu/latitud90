<template>
  <AdminLayout>
    <Head :title="`Pasajeros - ${program.name}`" />

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg mb-6">
          <div class="p-6">
            <div class="flex justify-between items-center">
              <div>
                <h2 class="text-2xl font-bold text-gray-900">
                  Pasajeros del Programa
                </h2>
                <p class="text-gray-600">
                  {{ program.code }} - {{ program.name }} - {{ program.destination }}
                </p>
                <div
                  class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                  <span>Salida: {{ formatDate(program.departure_date) }}</span>
                  <span
                    >Duración: {{ program.duration_days }}
                    {{ program.duration_days === 1 ? "día" : "días" }}</span
                  >
                  <span
                    >Capacidad: {{ passengers.total }}/{{
                      program.capacity
                    }}</span
                  >
                </div>
              </div>
              <Link
                :href="route('admin.programs.index')"
                class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition ease-in-out duration-150">
                <svg
                  class="w-4 h-4 mr-2"
                  fill="currentColor"
                  viewBox="0 0 20 20">
                  <path
                    fill-rule="evenodd"
                    d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"
                    clip-rule="evenodd" />
                </svg>
                Volver a Programas
              </Link>
            </div>
          </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
          <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div
                    class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg
                      class="w-5 h-5 text-blue-600"
                      fill="currentColor"
                      viewBox="0 0 20 20">
                      <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Total Pasajeros
                    </dt>
                    <dd class="text-lg font-medium text-gray-900">
                      {{ passengers.total }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div
                    class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                    <svg
                      class="w-5 h-5 text-green-600"
                      fill="currentColor"
                      viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Confirmados
                    </dt>
                    <dd class="text-lg font-medium text-gray-900">
                      {{ confirmedCount }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div
                    class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                    <svg
                      class="w-5 h-5 text-yellow-600"
                      fill="currentColor"
                      viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Pendientes
                    </dt>
                    <dd class="text-lg font-medium text-gray-900">
                      {{ pendingCount }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div
                    class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                    <svg
                      class="w-5 h-5 text-indigo-600"
                      fill="currentColor"
                      viewBox="0 0 20 20">
                      <path
                        d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Lugares Disponibles
                    </dt>
                    <dd class="text-lg font-medium text-gray-900">
                      {{ program.capacity - passengers.total }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Passengers Table -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
          <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">
              Lista de Pasajeros
            </h3>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-turquesa text-blanco rounded-lg">
                <tr>
                  <th
                    scope="col"
                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                    Pasajero
                  </th>
                  <th
                    scope="col"
                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                    Contacto
                  </th>
                  <th
                    scope="col"
                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                    Documento
                  </th>
                  <th
                    scope="col"
                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                    Precio
                  </th>
                  <th
                    scope="col"
                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                    Estado Pago
                  </th>
                  <th
                    scope="col"
                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                    Estado
                  </th>
                  <th
                    scope="col"
                    class="relative px-6 py-3">
                    <span class="sr-only">Acciones</span>
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr
                  v-for="passenger in passengers.data"
                  :key="passenger.id"
                  class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-10 w-10">
                        <div
                          class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                          <span class="text-sm font-medium text-gray-700">
                            {{ getInitials(passenger.full_name) }}
                          </span>
                        </div>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">
                          {{ passenger.full_name }}
                        </div>
                        <div class="text-sm text-gray-500">
                          Reg: {{ formatDate(passenger.registration_date) }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">
                      {{ passenger.email }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ passenger.phone }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div>{{ passenger.document_type.toUpperCase() }}</div>
                    <div class="text-gray-500">
                      {{ passenger.document_number }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div class="font-medium">
                      ${{ formatPrice(passenger.individual_price) }}
                    </div>
                    <div
                      v-if="passenger.price_adjustments !== 0"
                      :class="
                        passenger.price_adjustments > 0
                          ? 'text-red-600'
                          : 'text-green-600'
                      "
                      class="text-xs">
                      {{ passenger.price_adjustments > 0 ? "+" : "" }}${{
                        formatPrice(passenger.price_adjustments)
                      }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-1">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                          <div
                            class="bg-blue-600 h-2 rounded-full"
                            :style="{
                              width: getPaymentProgress(passenger) + '%'
                            }"></div>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                          ${{ formatPrice(getTotalPaid(passenger)) }} / ${{
                            formatPrice(getTotalAmount(passenger))
                          }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      :class="getStatusClass(passenger.status)"
                      class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                      {{ getStatusLabel(passenger.status) }}
                    </span>
                  </td>
                  <td
                    class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <Link
                      :href="route('admin.passengers.show', passenger.id)"
                      class="text-indigo-600 hover:text-indigo-900">
                      Ver
                    </Link>
                    <Link
                      :href="route('admin.passengers.edit', passenger.id)"
                      class="text-yellow-600 hover:text-yellow-900">
                      Editar
                    </Link>
                    <Link
                      :href="route('admin.passengers.payments', passenger.id)"
                      class="text-green-600 hover:text-green-900">
                      Pagos
                    </Link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div
            v-if="passengers.links && passengers.links.length > 3"
            class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
              <div class="flex-1 flex justify-between sm:hidden">
                <Link
                  v-if="passengers.prev_page_url"
                  :href="passengers.prev_page_url"
                  class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                  Anterior
                </Link>
                <Link
                  v-if="passengers.next_page_url"
                  :href="passengers.next_page_url"
                  class="relative ml-3 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                  Siguiente
                </Link>
              </div>
              <div
                class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                  <p class="text-sm text-gray-700">
                    Mostrando
                    <span class="font-medium">{{ passengers.from }}</span> a
                    <span class="font-medium">{{ passengers.to }}</span> de
                    <span class="font-medium">{{ passengers.total }}</span>
                    resultados
                  </p>
                </div>
                <div>
                  <nav
                    class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
                    aria-label="Pagination">
                    <template
                      v-for="link in passengers.links"
                      :key="link.label">
                      <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                          'relative inline-flex items-center px-2 py-2 border text-sm font-medium',
                          link.active
                            ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                        ]"
                        v-html="link.label" />
                      <span
                        v-else
                        :class="[
                          'relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-300 cursor-default'
                        ]"
                        v-html="link.label" />
                    </template>
                  </nav>
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
  import { Head, Link } from "@inertiajs/vue3";
  import AdminLayout from "@/Layouts/AdminLayout.vue";

  export default {
    name: "ProgramPassengers",
    components: {
      Head,
      Link,
      AdminLayout
    },
    props: {
      program: {
        type: Object,
        required: true
      },
      passengers: {
        type: Object,
        required: true
      }
    },
    computed: {
      confirmedCount() {
        return this.passengers.data.filter((p) => p.status === "confirmed")
          .length;
      },
      pendingCount() {
        return this.passengers.data.filter(
          (p) => p.status === "pending_payment"
        ).length;
      }
    },
    methods: {
      formatDate(date) {
        return new Date(date).toLocaleDateString("es-ES", {
          year: "numeric",
          month: "short",
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
      },
      getInitials(name) {
        return name
          .split(" ")
          .map((word) => word.charAt(0))
          .join("")
          .toUpperCase()
          .substring(0, 2);
      },
      getStatusClass(status) {
        const classes = {
          confirmed: "bg-green-100 text-green-800",
          pending_payment: "bg-yellow-100 text-yellow-800",
          cancelled: "bg-red-100 text-red-800"
        };
        return classes[status] || "bg-gray-100 text-gray-800";
      },
      getStatusLabel(status) {
        const labels = {
          confirmed: "Confirmado",
          pending_payment: "Pendiente de Pago",
          cancelled: "Cancelado"
        };
        return labels[status] || status;
      },
      getTotalPaid(passenger) {
        return passenger.payments
          ? passenger.payments
              .filter(
                (p) => p.status === "completed" || p.status === "approved"
              )
              .reduce((sum, payment) => sum + parseFloat(payment.amount), 0)
          : 0;
      },
      getTotalAmount(passenger) {
        return (
          parseFloat(passenger.individual_price) +
          parseFloat(passenger.price_adjustments || 0)
        );
      },
      getPaymentProgress(passenger) {
        const total = this.getTotalAmount(passenger);
        const paid = this.getTotalPaid(passenger);
        return total > 0 ? Math.round((paid / total) * 100) : 0;
      }
    }
  };
</script>
