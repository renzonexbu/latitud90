<template>
  <MainLayout :step="2">
    <div class="bg-gris-3 min-h-screen py-10">
      <div class="grid lg:grid-cols-6 mx-auto px-4 sm:px-6 lg:px-8 pb-10">
        <!-- Información del viaje destacado -->
        <div class="lg:col-span-4 bg-gris-3 p-8 pt-0 mb-8">
          <div class="flex flex-col gap-8 items-center">
            <div class="w-full">
              <img
                :src="trip.image_url"
                :alt="trip.name"
                class="w-full object-cover rounded-xl" />
            </div>
            <div class="md:w-2/3 w-full">
              <div class="mb-3">
                <p class="text-gray-500 text-sm mb-1">{{ trip.destination }}</p>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                  {{ trip.name }}
                </h2>
                <div
                  class="inline-block bg-teal-600 text-white text-sm rounded-full px-4 py-1">
                  <span>Salida: {{ formatDate(trip.departure_date) }}</span>
                </div>
              </div>

              <div class="mb-6">
                <p class="text-gray-600 text-sm mb-8">
                  {{ trip.description || "Descripción no disponible." }}
                </p>
                <img
                  :src="images['decorative-line']"
                  alt="Decorative line"
                  class="w-full h-4 my-4" />
              </div>

              <div class="mb-6">
                <h3 class="text-lg font-medium mb-4">Pilares</h3>
                <p class="text-gray-600 text-sm mb-5">
                  En el desarrollo de nuestros programas incorporamos de manera
                  transversal 4 pilares fundamentales
                </p>
                <div class="grid lg:grid-cols-2 gap-6 mb-8">
                  <div class="flex flex-col items-center">
                    <span class="text-teal-600 rounded-full mb-1">
                      <img
                        :src="images.programs['mountain']"
                        alt="Decorative line"
                        class="w-full my-4" />
                    </span>
                    <span class="text-xs font-semibold text-gray-700">
                      Aventura
                    </span>
                  </div>
                  <div class="flex flex-col items-center">
                    <span class="text-blue-600 rounded-full mb-1">
                      <img
                        :src="images.programs['book-open']"
                        alt="Decorative line"
                        class="w-full my-4" />
                    </span>
                    <span class="text-xs font-semibold text-gray-700">
                      Educación
                    </span>
                  </div>
                  <div class="flex flex-col items-center">
                    <span class="text-yellow-600 rounded-full mb-1">
                      <img
                        :src="images.programs['sparkles']"
                        alt="Decorative line"
                        class="w-full my-4" />
                    </span>
                    <span class="text-xs font-semibold text-gray-700">
                      Entretenimiento
                    </span>
                  </div>
                  <div class="flex flex-col items-center">
                    <span class="text-green-600 rounded-full mb-1">
                      <img
                        :src="images.programs['shield-check']"
                        alt="Decorative line"
                        class="w-full my-4" />
                    </span>
                    <span class="text-xs font-semibold text-gray-700">
                      Seguridad
                    </span>
                  </div>
                </div>
                <img
                  :src="images['decorative-line']"
                  alt="Decorative line"
                  class="w-full h-4 my-4" />
              </div>

              <div class="mb-8">
                <h3 class="text-lg font-medium mb-4">Qué vamos a hacer</h3>
                <p class="text-gray-600 text-sm mb-6">
                  {{ trip.includes || "Actividades no disponibles." }}
                </p>
                <ul class="text-gray-600 text-sm space-y-2 mb-6">
                  <li>
                    • Vitae consequat solicitudin tristique nunc placerat.
                    Ultrices velit in porttitor adipiscing.
                  </li>
                  <li>
                    • Enim proin eget nulla amet orci amet. Non nisi nulla
                    habitasse commodo celerisque.
                  </li>
                  <li>
                    • Vitae consequat solicitudin tristique nunc placerat.
                    Ultrices velit in porttitor adipiscing.
                  </li>
                  <li>
                    • Enim proin eget nulla amet orci amet. Non nisi nulla
                    habitasse commodo celerisque.
                  </li>
                </ul>
              </div>

              <!-- Botones de acción -->
              <div class="flex flex-wrap gap-4 mb-2">
                <button
                  class="bg-white border border-teal-600 text-teal-600 px-4 py-2 rounded-lg font-medium flex items-center gap-2 hover:bg-teal-50 transition">
                  <i class="fas fa-route"></i> Itinerario
                </button>
                <button
                  class="bg-white border border-teal-600 text-teal-600 px-4 py-2 rounded-lg font-medium flex items-center gap-2 hover:bg-teal-50 transition">
                  <i class="fas fa-umbrella-beach"></i> Cobertura de asistencia
                  en viaje
                </button>
                <button
                  class="bg-white border border-teal-600 text-teal-600 px-4 py-2 rounded-lg font-medium flex items-center gap-2 hover:bg-teal-50 transition">
                  <i class="fas fa-list"></i> Lista de equipo
                </button>
              </div>
            </div>
          </div>

          <!-- Advertencia -->
          <div
            class="mt-8 bg-orange-50 border-l-4 border-orange-400 p-4 rounded">
            <div class="flex items-center gap-2">
              <i class="fas fa-exclamation-triangle text-orange-400"></i>
              <span class="text-xs text-gray-700 font-medium">
                El itinerario o programa puede sufrir modificaciones por razones
                de fuerza mayor (clima, condiciones meteorológicas, pandemia,
                normas sanitarias, cortes de puentes, pasos fronterizos, causas
                de fuerza mayor). Para más información, consulta a nuestro
                correo electrónico
                <a
                  href="mailto:educacion@latitud90.com"
                  class="underline text-teal-600"
                  >educacion@latitud90.com</a
                >.
              </span>
            </div>
          </div>

          <!-- Galería de imágenes -->
          <div class="grid grid-cols-2 gap-4 mt-8">
            <img
              src="/build/images/programs/default.jpg"
              alt="Imagen del viaje"
              class="w-full h-44 object-cover rounded-lg" />
            <img
              src="/build/images/programs/default.jpg"
              alt="Imagen del viaje"
              class="w-full h-44 object-cover rounded-lg" />
            <img
              src="/build/images/programs/default.jpg"
              alt="Imagen del viaje"
              class="w-full h-44 object-cover rounded-lg" />
            <img
              src="/build/images/programs/default.jpg"
              alt="Imagen del viaje"
              class="w-full h-44 object-cover rounded-lg" />
          </div>
        </div>

        <!-- Selección de método de pago -->
        <div
          class="lg:col-span-2 bg-blanco border-2 border-gris-3 rounded-3xl p-6 h-fit self-start">
          <h3 class="text-lg font-semibold text-turquesa mb-6">
            Selecciona la forma de pago
          </h3>
          <p class="text-sm text-gray-600 mb-4">
            ¡Selecciona la forma de pago que mejor se adapte a ti, total o en
            cuotas! Para cualquier consulta, no dudes en escribirnos por
            <a
              href="https://wa.me/?text=Hola%20Latitud90,%20tengo%20una%20consulta%20sobre%20el%20método%20de%20pago."
              target="_blank"
              rel="noopener noreferrer">
              WhatsApp
            </a>
          </p>

          <!-- Opciones de pago -->
          <div class="space-y-4">
            <!-- Pago Total -->
            <div
              class="border rounded-lg p-4 cursor-pointer transition-all duration-200"
              :class="
                selectedPaymentMethod === 'full'
                  ? 'border-teal-600 bg-teal-50'
                  : 'border-gray-200 hover:border-gray-300'
              "
              @click="selectPaymentMethod('full')">
              <div class="flex items-center">
                <input
                  type="radio"
                  id="full_payment"
                  v-model="selectedPaymentMethod"
                  value="full"
                  class="w-4 h-4 text-teal-600 bg-gray-100 border-gray-300 focus:ring-teal-500" />
                <label
                  for="full_payment"
                  class="ml-2 flex-1 cursor-pointer">
                  <p class="font-medium text-gray-900">Pago total</p>
                </label>
                <i class="fas fa-info-circle text-gray-400 ml-2"></i>
              </div>
            </div>

            <!-- Pago con Tarjeta de Débito -->
            <div
              v-if="selectedPaymentMethod === 'full'"
              class="ml-6 bg-gray-50 rounded-lg p-3">
              <div
                class="flex items-center border rounded-lg p-3 mb-2 bg-white">
                <input
                  type="radio"
                  id="debit_payment"
                  name="payment_option"
                  class="w-4 h-4 text-teal-600 bg-gray-100 border-gray-300 focus:ring-teal-500" />
                <label
                  for="debit_payment"
                  class="ml-2 flex-1 cursor-pointer">
                  <p class="text-sm text-gray-900">
                    Pago con Tarjeta de Débito
                  </p>
                </label>
              </div>

              <!-- Pago con Tarjeta de Crédito -->
              <div
                class="flex items-center border rounded-lg p-3 mb-2 bg-white">
                <input
                  type="radio"
                  id="credit_payment"
                  name="payment_option"
                  class="w-4 h-4 text-teal-600 bg-gray-100 border-gray-300 focus:ring-teal-500" />
                <label
                  for="credit_payment"
                  class="ml-2 flex-1 cursor-pointer">
                  <p class="text-sm text-gray-900">
                    Pago con Tarjeta de Crédito
                  </p>
                  <p class="text-xs text-gray-500">
                    3, 6 o 12 cuotas sin interés con cualquier promo bancario
                  </p>
                </label>
              </div>

              <!-- Pago con Transferencia Khipu -->
              <div class="flex items-center border rounded-lg p-3 bg-white">
                <input
                  type="radio"
                  id="khipu_payment"
                  name="payment_option"
                  class="w-4 h-4 text-teal-600 bg-gray-100 border-gray-300 focus:ring-teal-500" />
                <label
                  for="khipu_payment"
                  class="ml-2 flex-1 cursor-pointer">
                  <p class="text-sm text-gray-900">
                    Pago con Transferencia Khipu
                  </p>
                </label>
              </div>
            </div>

            <!-- Pago en Cuotas Latitud90 -->
            <div
              class="border rounded-lg p-4 cursor-pointer transition-all duration-200"
              :class="
                selectedPaymentMethod === 'installments'
                  ? 'border-teal-600 bg-teal-50'
                  : 'border-gray-200 hover:border-gray-300'
              "
              @click="selectPaymentMethod('installments')">
              <div class="flex items-center">
                <input
                  type="radio"
                  id="installment_payment"
                  v-model="selectedPaymentMethod"
                  value="installments"
                  class="w-4 h-4 text-teal-600 bg-gray-100 border-gray-300 focus:ring-teal-500" />
                <label
                  for="installment_payment"
                  class="ml-2 flex-1 cursor-pointer">
                  <p class="font-medium text-gray-900">
                    Mensual | Cuota Lat 90
                  </p>
                </label>
                <i class="fas fa-info-circle text-gray-400 ml-2"></i>
              </div>
            </div>

            <!-- Opciones para Pago Mensual -->
            <div
              v-if="selectedPaymentMethod === 'installments'"
              class="ml-6 mt-2">
              <!-- Alerta informativa -->
              <div class="bg-red-50 p-3 md:p-4 rounded-lg mb-4">
                <div class="flex items-start">
                  <div class="flex-shrink-0 mt-0.5">
                    <i class="fas fa-exclamation-triangle text-red-500 text-sm md:text-base"></i>
                  </div>
                  <div class="ml-2 md:ml-3 flex-1 min-w-0">
                    <p class="text-xs md:text-sm text-red-600 font-medium leading-tight md:leading-normal">
                      Al elegir el Pago Mensual de Cuota LAT90, se
                      <span class="font-bold">desactiva la opción de pago</span>
                      con tarjeta en 3, 6, 9 o 12 cuotas.
                    </p>
                    <p class="text-xs md:text-sm text-red-600 mt-2 leading-tight md:leading-normal">
                      Si luego quieres abonar el total, deberás seleccionar todas
                      las cuotas de LAT90 y
                      <span class="font-bold"
                        >podrás pagar con tarjeta (1 cuota), débito o
                        transferencia.</span
                      >
                    </p>
                  </div>
                </div>
              </div>

              <!-- Opciones de pago para cuotas -->
              <div class="space-y-2 bg-gray-50 rounded-lg p-3">
                <!-- Pago con Tarjeta de Débito -->
                <div
                  class="flex items-center border rounded-lg p-3 mb-2 bg-white">
                  <input
                    type="radio"
                    id="installment_debit_payment"
                    name="installment_payment_option"
                    class="w-4 h-4 text-teal-600 bg-gray-100 border-gray-300 focus:ring-teal-500" />
                  <label
                    for="installment_debit_payment"
                    class="ml-2 flex-1 cursor-pointer">
                    <p class="text-sm text-gray-900">
                      Pago con Tarjeta de Debito
                    </p>
                  </label>
                </div>

                <!-- Pago con Tarjeta de Crédito -->
                <div
                  class="flex items-center border rounded-lg p-3 mb-2 bg-white">
                  <input
                    type="radio"
                    id="installment_credit_payment"
                    name="installment_payment_option"
                    class="w-4 h-4 text-teal-600 bg-gray-100 border-gray-300 focus:ring-teal-500" />
                  <label
                    for="installment_credit_payment"
                    class="ml-2 flex-1 cursor-pointer">
                    <p class="text-sm text-gray-900">
                      Pago con Tarjeta de Credito
                    </p>
                    <p class="text-xs text-red-500 flex items-center gap-1">
                      <i class="fas fa-exclamation-triangle"></i>
                      Solo se efectuará 1 cuota
                    </p>
                  </label>
                </div>

                <!-- Pago con Transferencia Khipu -->
                <div class="flex items-center border rounded-lg p-3 bg-white">
                  <input
                    type="radio"
                    id="installment_khipu_payment"
                    name="installment_payment_option"
                    class="w-4 h-4 text-teal-600 bg-gray-100 border-gray-300 focus:ring-teal-500" />
                  <label
                    for="installment_khipu_payment"
                    class="ml-2 flex-1 cursor-pointer">
                    <p class="text-sm text-gray-900">
                      Pago con Trasferencia Khipu
                    </p>
                  </label>
                </div>
              </div>

              <!-- Selector de cantidad de cuotas -->
              <div class="mt-4">
                <p class="text-sm text-gray-700 mb-2">
                  Elige la cantidad de cuotas para fraccionar
                </p>
                <div class="flex items-center gap-4">
                  <div class="relative">
                    <select
                      v-model="selectedInstallments"
                      class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-8 cursor-pointer">
                      <option
                        v-for="option in installmentOptions"
                        :key="option"
                        :value="option">
                        {{ option }}
                      </option>
                    </select>
                    <div
                      class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                      <i class="fas fa-chevron-down text-xs"></i>
                    </div>
                  </div>
                  <span class="text-sm text-gray-700">
                    Fecha de finalización
                    {{ calculateEndDate(selectedInstallments) }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Faltan pagar -->
          <div class="mt-6 py-2">
            <div class="flex justify-between items-center">
              <span class="font-medium text-gray-700">Faltan pagar</span>
              <span class="font-bold text-xl text-gray-900">$1800</span>
            </div>
          </div>

          <!-- Botón para iniciar pago -->
          <button
            @click="proceedToPayment"
            :disabled="!selectedPaymentMethod"
            class="w-full bg-teal-600 hover:bg-teal-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white py-3 px-6 rounded-full font-medium transition duration-200 mt-4">
            Iniciar pago
          </button>
        </div>
        <a
          href="#"
          class="flex items-center gap-2 mb-4 text-gray-600 hover:text-teal-700 transition-colors">
          <i class="fas fa-arrow-left"></i>
          <span>Volver a seleccionar viaje</span>
        </a>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
  import { ref, defineProps, computed } from "vue";
  import { router } from "@inertiajs/vue3";
  import images from "@images/index.js";
  import MainLayout from "@/Layouts/MainLayout.vue";

  const props = defineProps({
    trip: {
      type: Object,
      required: true
    },
    rut: {
      type: String,
      required: true
    }
  });

  // Estado del componente
  const selectedPaymentMethod = ref("");
  const selectedInstallments = ref(3);
  const installmentOptions = [3, 6, 9, 12];

  // Formatear precios
  const formatPrice = (price) => {
    return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  };

  // Calcular la fecha de finalización de las cuotas
  const calculateEndDate = (months) => {
    const endDate = new Date();
    endDate.setMonth(endDate.getMonth() + parseInt(months));

    // Formato DD/MM/AA
    const day = endDate.getDate().toString().padStart(2, "0");
    const month = (endDate.getMonth() + 1).toString().padStart(2, "0");
    const year = endDate.getFullYear().toString().slice(-2);

    return `${day}/${month}/${year}`;
  };

  // Formatear fechas
  const formatDate = (dateString) => {
    if (!dateString) return "N/A";

    const date = new Date(dateString);
    const day = date.getDate().toString().padStart(2, "0");
    const month = (date.getMonth() + 1).toString().padStart(2, "0");
    const year = date.getFullYear();

    return `${day}-${month}-${year}`;
  };

  // Seleccionar método de pago
  const selectPaymentMethod = (method) => {
    selectedPaymentMethod.value = method;
    if (method === "full") {
      selectedInstallments.value = 1;
    } else if (method === "installments" && !selectedInstallments.value) {
      selectedInstallments.value = 3;
    }
  };

  // Volver al paso anterior
  const goBack = () => {
    router.get(`/buscar-viajes?rut=${props.rut}`);
  };

  // Proceder al pago
  const proceedToPayment = () => {
    if (!selectedPaymentMethod.value) return;

    const paymentData = {
      payment_method: selectedPaymentMethod.value,
      installments: selectedInstallments.value,
      trip_id: props.trip.id,
      passenger_id: props.trip.passenger_id,
      program_id: props.trip.program_id
    };

    router.post("/payment/setup", paymentData);
  };
</script>

<style scoped>
  /* Estilos adicionales si son necesarios */
</style>
