<template>
    <div class="bg-white rounded-[20px] overflow-hidden">
        <!-- Table Container -->
        <div class="flex flex-col gap-0">
            <!-- Table Header -->
            <div
                class="bg-turquesa rounded-t-[20px] px-5 py-[11px] flex items-center justify-between h-[61.51px]"
            >
                <div
                    class="text-white font-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    ID Programa
                </div>
                <div
                    class="text-white font-bold text-[14px] leading-[18px] text-center w-[140px]"
                >
                    N° Autorización
                </div>
                <div
                    class="text-white font-bold text-[14px] leading-[18px] text-center w-[140px]"
                >
                    RUT Participante
                </div>
                <div
                    class="text-white font-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Pago/Devolución
                </div>
                <div
                    class="text-white font-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    N° Boleta
                </div>
                <div
                    class="text-white font-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Forma de Pago
                </div>
                <div
                    class="text-white font-bold text-[14px] leading-[18px] text-center w-[100px]"
                >
                    N° Cuotas
                </div>
                <div
                    class="text-white font-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Fecha de Pago
                </div>
                <div
                    class="text-white font-bold text-[14px] leading-[18px] text-center w-[140px]"
                >
                    Contacto Pagador
                </div>
                <!-- Columna de acciones -->
                <div class="w-[80px]"></div>
            </div>

            <!-- Table Body -->
            <div class="flex flex-col">
                <div
                    v-for="(payment, index) in payments"
                    :key="payment.id"
                    :class="[
                        'px-5 py-[14px] flex items-center justify-between',
                        index % 2 === 0 ? 'bg-white' : 'bg-[#f9f9f9]',
                    ]"
                >
                    <!-- ID Programa -->
                    <div
                        class="text-[#5b5b5b] font-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        {{ payment.program_id }}
                    </div>

                    <!-- N° Autorización -->
                    <div
                        class="text-[#5b5b5b] font-bold text-[14px] leading-[18px] text-center w-[140px]"
                    >
                        {{ payment.authorization_code }}
                    </div>

                    <!-- RUT Participante -->
                    <div
                        class="text-[#5b5b5b] font-bold text-[14px] leading-[18px] text-center w-[140px]"
                    >
                        {{ formatRut(payment.participant_rut) }}
                    </div>

                    <!-- Pago/Devolución -->
                    <div
                        class="text-[#5b5b5b] font-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        <span :class="payment.is_refund ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'">
                            ${{ formatCurrency(payment.payment_amount) }}
                        </span>
                    </div>

                    <!-- N° Boleta -->
                    <div
                        class="text-[#5b5b5b] font-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        {{ payment.invoice_number }}
                    </div>

                    <!-- Forma de Pago -->
                    <div
                        class="text-[#5b5b5b] font-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        {{ payment.payment_method_code }}
                    </div>

                    <!-- N° Cuotas -->
                    <div
                        class="text-[#5b5b5b] font-bold text-[14px] leading-[18px] text-center w-[100px]"
                    >
                        {{ payment.installments_number }}
                    </div>

                    <!-- Fecha de Pago -->
                    <div
                        class="text-[#5b5b5b] font-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        {{ formatDate(payment.payment_date) }}
                    </div>

                    <!-- Contacto Pagador -->
                    <div
                        class="text-[#5b5b5b] font-bold text-[14px] leading-[18px] text-center w-[140px]"
                    >
                        <div class="text-center">
                            <div class="font-semibold">
                                {{ payment.payer_name }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ payment.payer_email }}
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="w-[80px] flex justify-center">
                        <button
                            @click="$emit('view-details', payment)"
                            class="w-[30px] h-[30px] hover:opacity-75 transition-opacity flex items-center justify-center"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="16"
                                viewBox="0 0 24 16"
                                fill="none"
                                class="w-[30px] h-[20px]"
                            >
                                <path
                                    d="M12 2C6 2 2 8 2 8C2 8 6 14 12 14C18 14 22 8 22 8C22 8 18 2 12 2Z"
                                    stroke="#C7C7C7"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="M12 10.5C13.3807 10.5 14.5 9.38071 14.5 8C14.5 6.61929 13.3807 5.5 12 5.5C10.6193 5.5 9.5 6.61929 9.5 8C9.5 9.38071 10.6193 10.5 12 10.5Z"
                                    stroke="#C7C7C7"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensaje cuando no hay datos -->
        <div v-if="payments.length === 0" class="p-8 text-center">
            <div class="text-gray-500">
                <svg
                    class="mx-auto h-12 w-12 text-gray-400 mb-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"
                    ></path>
                </svg>
                <p class="text-lg font-medium">No hay pagos consolidados para mostrar</p>
                <p class="text-sm">
                    Intenta ajustar los filtros o verifica que existan pagos en
                    el sistema
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
  payments: {
    type: Array,
    default: () => []
  }
})

defineEmits(['view-details'])

const formatCurrency = (amount) => {
  const numericValue = Math.round(Number(amount) || 0);
  return numericValue.toLocaleString("es-CL");
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('es-CL')
}

const formatRut = (rut) => {
  if (!rut) return "N/A";
  
  // Limpiar el RUT de puntos y guiones
  let rutLimpio = rut.toString().replace(/\./g, "").replace(/-/g, "");
  
  if (rutLimpio.length < 2) return rut;
  
  // Separar número y dígito verificador
  let dv = rutLimpio.slice(-1);
  let numero = rutLimpio.slice(0, -1);
  
  // Formatear número con puntos
  let numeroFormateado = numero.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  
  // Retornar RUT formateado
  return `${numeroFormateado}-${dv.toUpperCase()}`;
}
</script>
