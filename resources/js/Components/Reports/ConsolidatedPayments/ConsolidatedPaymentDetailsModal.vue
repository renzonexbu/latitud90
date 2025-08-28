<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="$emit('close')">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white" @click.stop>
      <div class="mt-3">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-medium text-gray-900">Detalles del Pago</h3>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <div v-if="payment" class="space-y-6">
          <!-- Identificación -->
          <div>
            <h4 class="text-md font-semibold text-gray-900 mb-3">Identificación</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Código (Programa)</label>
                <p class="mt-1 text-sm text-gray-900">{{ payment.program_code || payment.program_id }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">RUT Alumno</label>
                <p class="mt-1 text-sm text-gray-900">{{ formatRut(payment.participant_rut || payment.participant_document) }}</p>
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Nombre del Alumno</label>
                <p class="mt-1 text-sm text-gray-900">{{ payment.participant_name || payment.participant_full_name }}</p>
              </div>
            </div>
          </div>

          <!-- Pago -->
          <div>
            <h4 class="text-md font-semibold text-gray-900 mb-3">Pago</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Monto</label>
                <p class="mt-1 text-sm" :class="payment.is_refund ? 'text-red-600 font-medium' : 'text-green-700 font-medium'">
                  ${{ formatCurrency(payment.payment_amount) }}
                </p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Fecha de Pago</label>
                <p class="mt-1 text-sm text-gray-900">{{ formatDate(payment.payment_date) }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Documentos N° Boleta o NC</label>
                <p class="mt-1 text-sm text-gray-900">{{ payment.invoice_number || payment.document_number }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Tipo de Documento</label>
                <p class="mt-1 text-sm text-gray-900">{{ payment.document_type || payment.document_type_code }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Forma de Pago</label>
                <p class="mt-1 text-sm text-gray-900">{{ payment.payment_method_name || payment.payment_method_code }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">N° Cuotas Pagadas</label>
                <p class="mt-1 text-sm text-gray-900">{{ payment.paid_installments || payment.installments_number }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">N° Reserva</label>
                <p class="mt-1 text-sm text-gray-900">{{ payment.reservation_number || payment.buy_order }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Aporte o becas</label>
                <p class="mt-1 text-sm text-gray-900">${{ formatCurrency(payment.external_contribution || payment.scholarship_amount) }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Liberado</label>
                <p class="mt-1 text-sm" :class="payment.released ? 'text-green-600' : 'text-gray-500'">{{ payment.released ? 'Sí' : 'No' }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Valor total prog.</label>
                <p class="mt-1 text-sm text-gray-900">${{ formatCurrency(payment.total_program_value || payment.program_total_value) }}</p>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-8">
          <p class="text-gray-500">No se encontraron detalles para este pago</p>
        </div>

        <div class="flex justify-end mt-6">
          <button @click="$emit('close')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  payment: {
    type: Object,
    default: null
  }
});

defineEmits(['close']);

const formatCurrency = (amount) => {
  const numericValue = Math.round(Number(amount) || 0);
  return numericValue.toLocaleString("es-CL");
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('es-CL', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const formatRut = (rut) => {
  if (!rut) return "N/A";
  let clean = rut.toString().replace(/\./g, "").replace(/-/g, "");
  if (clean.length < 2) return rut;
  let dv = clean.slice(-1);
  let numero = clean.slice(0, -1);
  let numeroFormateado = numero.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  return `${numeroFormateado}-${dv.toUpperCase()}`;
};
</script>


