<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header></header>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Progress Steps -->
      <div class="mb-8">
        <div class="flex items-center justify-center space-x-8">
          <div class="flex items-center">
            <div
              class="w-8 h-8 bg-indigo-600 rounded-full flex items-center justify-center">
              <svg
                class="w-5 h-5 text-white"
                fill="currentColor"
                viewBox="0 0 20 20">
                <path
                  fill-rule="evenodd"
                  d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                  clip-rule="evenodd" />
              </svg>
            </div>
            <span class="ml-2 text-sm font-medium text-gray-900">Reserva</span>
          </div>
          <div class="w-16 h-0.5 bg-indigo-600"></div>
          <div class="flex items-center">
            <div
              class="w-8 h-8 bg-indigo-600 rounded-full flex items-center justify-center">
              <span class="text-white text-sm font-bold">2</span>
            </div>
            <span class="ml-2 text-sm font-medium text-gray-900">Pago</span>
          </div>
          <div class="w-16 h-0.5 bg-gray-300"></div>
          <div class="flex items-center">
            <div
              class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
              <span class="text-gray-600 text-sm font-bold">3</span>
            </div>
            <span class="ml-2 text-sm font-medium text-gray-500"
              >Confirmación</span
            >
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Payment Form -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-lg shadow-sm">
            <div class="p-6">
              <h2 class="text-2xl font-bold text-gray-900 mb-6">
                Método de Pago
              </h2>

              <!-- Payment Method Selection -->
              <div class="space-y-4 mb-8">
                <div
                  v-for="method in paymentMethods"
                  :key="method.id"
                  @click="selectedPaymentMethod = method.id"
                  class="border rounded-lg p-4 cursor-pointer transition-colors"
                  :class="
                    selectedPaymentMethod === method.id
                      ? 'border-indigo-500 bg-indigo-50'
                      : 'border-gray-200 hover:border-gray-300'
                  ">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                      <input
                        type="radio"
                        :value="method.id"
                        v-model="selectedPaymentMethod"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300" />
                      <div>
                        <h3 class="text-lg font-medium text-gray-900">
                          {{ method.name }}
                        </h3>
                        <p class="text-sm text-gray-500">
                          {{ method.description }}
                        </p>
                      </div>
                    </div>
                    <img
                      :src="method.logo"
                      :alt="method.name"
                      class="h-8" />
                  </div>
                </div>
              </div>

              <!-- Payment Form -->
              <form @submit.prevent="processPayment">
                <!-- Transbank Payment -->
                <div
                  v-if="selectedPaymentMethod === 'transbank'"
                  class="space-y-6">
                  <div>
                    <label
                      for="card_number"
                      class="block text-sm font-medium text-gray-700 mb-2"
                      >Número de Tarjeta</label
                    >
                    <input
                      id="card_number"
                      v-model="paymentForm.card_number"
                      type="text"
                      placeholder="1234 5678 9012 3456"
                      maxlength="19"
                      @input="formatCardNumber"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                      :class="{ 'border-red-500': errors.card_number }" />
                    <div
                      v-if="errors.card_number"
                      class="mt-1 text-sm text-red-600">
                      {{ errors.card_number }}
                    </div>
                  </div>

                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label
                        for="expiry_date"
                        class="block text-sm font-medium text-gray-700 mb-2"
                        >Fecha Vencimiento</label
                      >
                      <input
                        id="expiry_date"
                        v-model="paymentForm.expiry_date"
                        type="text"
                        placeholder="MM/YY"
                        maxlength="5"
                        @input="formatExpiryDate"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                        :class="{ 'border-red-500': errors.expiry_date }" />
                      <div
                        v-if="errors.expiry_date"
                        class="mt-1 text-sm text-red-600">
                        {{ errors.expiry_date }}
                      </div>
                    </div>

                    <div>
                      <label
                        for="cvv"
                        class="block text-sm font-medium text-gray-700 mb-2"
                        >CVV</label
                      >
                      <input
                        id="cvv"
                        v-model="paymentForm.cvv"
                        type="text"
                        placeholder="123"
                        maxlength="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                        :class="{ 'border-red-500': errors.cvv }" />
                      <div
                        v-if="errors.cvv"
                        class="mt-1 text-sm text-red-600">
                        {{ errors.cvv }}
                      </div>
                    </div>
                  </div>

                  <div>
                    <label
                      for="cardholder_name"
                      class="block text-sm font-medium text-gray-700 mb-2"
                      >Nombre del Titular</label
                    >
                    <input
                      id="cardholder_name"
                      v-model="paymentForm.cardholder_name"
                      type="text"
                      placeholder="Como aparece en la tarjeta"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                      :class="{ 'border-red-500': errors.cardholder_name }" />
                    <div
                      v-if="errors.cardholder_name"
                      class="mt-1 text-sm text-red-600">
                      {{ errors.cardholder_name }}
                    </div>
                  </div>
                </div>

                <!-- Khipu Payment -->
                <div
                  v-if="selectedPaymentMethod === 'khipu'"
                  class="space-y-6">
                  <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <div class="flex">
                      <div class="flex-shrink-0">
                        <svg
                          class="h-5 w-5 text-blue-400"
                          viewBox="0 0 20 20"
                          fill="currentColor">
                          <path
                            fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd" />
                        </svg>
                      </div>
                      <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                          Transferencia Bancaria con Khipu
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                          <p>
                            Serás redirigido a tu banco para completar el pago
                            de forma segura. El proceso toma solo unos minutos.
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div>
                    <label
                      for="buyer_name"
                      class="block text-sm font-medium text-gray-700 mb-2"
                      >Nombre del Pagador</label
                    >
                    <input
                      id="buyer_name"
                      v-model="paymentForm.buyer_name"
                      type="text"
                      readonly
                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-50" />
                  </div>

                  <div>
                    <label
                      for="buyer_email"
                      class="block text-sm font-medium text-gray-700 mb-2"
                      >Email del Pagador</label
                    >
                    <input
                      id="buyer_email"
                      v-model="paymentForm.buyer_email"
                      type="email"
                      readonly
                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-50" />
                  </div>
                </div>

                <!-- Transfer Payment -->
                <div
                  v-if="selectedPaymentMethod === 'transfer'"
                  class="space-y-6">
                  <div
                    class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                    <div class="flex">
                      <div class="flex-shrink-0">
                        <svg
                          class="h-5 w-5 text-yellow-400"
                          viewBox="0 0 20 20"
                          fill="currentColor">
                          <path
                            fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                        </svg>
                      </div>
                      <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">
                          Transferencia Bancaria Manual
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700">
                          <p>
                            La reserva se confirmará una vez que recibamos tu
                            transferencia. Esto puede tomar 1-2 días hábiles.
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="bg-gray-50 rounded-md p-4">
                    <h4 class="font-medium text-gray-900 mb-3">
                      Datos para Transferencia:
                    </h4>
                    <div class="space-y-2 text-sm">
                      <div><strong>Banco:</strong> Banco de Chile</div>
                      <div><strong>Cuenta:</strong> Cuenta Corriente</div>
                      <div><strong>Número:</strong> 123-45678-90</div>
                      <div><strong>RUT:</strong> 12.345.678-9</div>
                      <div><strong>Beneficiario:</strong> latitud90 SpA</div>
                      <div><strong>Email:</strong> pagos@latitud90.cl</div>
                    </div>
                  </div>
                </div>

                <!-- Submit Button -->
                <div class="mt-8">
                  <button
                    type="submit"
                    :disabled="processing || !selectedPaymentMethod"
                    class="w-full px-6 py-3 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span v-if="processing">Procesando Pago...</span>
                    <span v-else-if="selectedPaymentMethod === 'transfer'"
                      >Confirmar Reserva</span
                    >
                    <span v-else>Pagar ${{ formatPrice(totalAmount) }}</span>
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Order Summary -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow-sm sticky top-8">
            <div class="p-6">
              <h3 class="text-lg font-bold text-gray-900 mb-4">
                Resumen de Reserva
              </h3>

              <!-- Program Info -->
              <div class="border-b pb-4 mb-4">
                <h4 class="font-medium text-gray-900">{{ program.name }}</h4>
                <p class="text-sm text-gray-600 mt-1">
                  {{ program.destination }}
                </p>
                <p class="text-sm text-gray-600">
                  {{ formatDate(program.departure_date) }}
                </p>
                <p class="text-sm text-gray-600">
                  {{ program.duration_days }}
                  {{ program.duration_days === 1 ? "día" : "días" }}
                </p>
              </div>

              <!-- Passenger Info -->
              <div class="border-b pb-4 mb-4">
                <h4 class="font-medium text-gray-900 mb-2">Pasajero</h4>
                <p class="text-sm text-gray-600">{{ passenger.full_name }}</p>
                <p class="text-sm text-gray-600">{{ passenger.email }}</p>
                <p class="text-sm text-gray-600">{{ passenger.phone }}</p>
              </div>

              <!-- Price Breakdown -->
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Precio base</span>
                  <span class="text-gray-900"
                    >${{ formatPrice(passenger.individual_price) }}</span
                  >
                </div>
                <div
                  v-if="
                    passenger.price_adjustments &&
                    passenger.price_adjustments != 0
                  "
                  class="flex justify-between text-sm">
                  <span class="text-gray-600">Ajuste de precio</span>
                  <span
                    :class="
                      passenger.price_adjustments > 0
                        ? 'text-red-600'
                        : 'text-green-600'
                    ">
                    {{ passenger.price_adjustments > 0 ? "+" : "" }}${{
                      formatPrice(Math.abs(passenger.price_adjustments))
                    }}
                  </span>
                </div>
                <div class="border-t pt-2">
                  <div class="flex justify-between text-lg font-bold">
                    <span>Total</span>
                    <span class="text-indigo-600"
                      >${{ formatPrice(totalAmount) }}</span
                    >
                  </div>
                </div>
              </div>

              <!-- Security Notice -->
              <div class="mt-6 p-3 bg-green-50 rounded-md">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <svg
                      class="h-5 w-5 text-green-400"
                      viewBox="0 0 20 20"
                      fill="currentColor">
                      <path
                        fill-rule="evenodd"
                        d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                        clip-rule="evenodd" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <p class="text-sm text-green-800">
                      Tu pago está protegido con encriptación SSL de 256 bits.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import { Link, useForm } from "@inertiajs/vue3";
  import Header from "@/Components/Ecommerce/Header.vue";

  export default {
    name: "Payment",
    components: {
      Link,
      Header
    },
    props: {
      passenger: {
        type: Object,
        required: true
      },
      program: {
        type: Object,
        required: true
      },
      errors: {
        type: Object,
        default: () => ({})
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
      },
      formatCardNumber(event) {
        let value = event.target.value
          .replace(/\s+/g, "")
          .replace(/[^0-9]/gi, "");
        let formattedValue = value.match(/.{1,4}/g)?.join(" ") || value;
        if (formattedValue.length > 19) {
          formattedValue = formattedValue.substr(0, 19);
        }
        this.paymentForm.card_number = formattedValue;
      },
      formatExpiryDate(event) {
        let value = event.target.value.replace(/\D/g, "");
        if (value.length >= 2) {
          value = value.substring(0, 2) + "/" + value.substring(2, 4);
        }
        this.paymentForm.expiry_date = value;
      },
      processPayment() {
        this.paymentForm.payment_method = this.selectedPaymentMethod;
        this.paymentForm.post(
          route("ecommerce.process-payment", {
            payment: this.passenger.id
          })
        );
      }
    },
    data() {
      const paymentForm = useForm({
        payment_method: "transbank",
        // Transbank fields
        card_number: "",
        expiry_date: "",
        cvv: "",
        cardholder_name: "",
        // Khipu fields
        buyer_name: this.passenger.full_name,
        buyer_email: this.passenger.email,
        // Transfer fields
        transfer_reference: ""
      });

      return {
        selectedPaymentMethod: "transbank",
        paymentForm,
        processing: paymentForm.processing,
        paymentMethods: [
          {
            id: "transbank",
            name: "Tarjeta de Crédito/Débito",
            description: "Visa, Mastercard, American Express",
            logo: "/images/transbank-logo.png"
          },
          {
            id: "khipu",
            name: "Transferencia Bancaria",
            description: "Pago directo desde tu banco",
            logo: "/images/khipu-logo.png"
          },
          {
            id: "transfer",
            name: "Transferencia Manual",
            description: "Transferencia bancaria tradicional",
            logo: "/images/bank-transfer.png"
          }
        ]
      };
    },
    computed: {
      totalAmount() {
        const basePrice = parseFloat(this.passenger.individual_price);
        const adjustments = parseFloat(this.passenger.price_adjustments || 0);
        return basePrice + adjustments;
      }
    },
    computed: {
      totalAmount() {
        const basePrice = parseFloat(this.passenger.individual_price);
        const adjustments = parseFloat(this.passenger.price_adjustments || 0);
        return basePrice + adjustments;
      }
    }
  };
</script>
