<template>
    <div class="bg-white rounded-[20px] overflow-hidden">
        <!-- Table Container -->
        <div class="flex flex-col gap-0">
            <!-- Table Header -->
            <div
                class="bg-turquesa rounded-t-[20px] px-5 py-[11px] flex items-center justify-between h-[61.51px]"
            >
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]"
                >
                    Código de Inscripción
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]"
                >
                    Participante
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]"
                >
                    Programa
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Monto Neto
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Total Pagado
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Saldo Pendiente
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[100px]"
                >
                    Estado
                </div>
                <!-- Columna de acciones (vacía en header) -->
                <div class="w-[80px]"></div>
            </div>

            <!-- Table Body -->
            <div class="flex flex-col">
                <div
                    v-for="(account, index) in accounts"
                    :key="account.id"
                    :class="[
                        'px-5 py-[14px] flex items-center justify-between',
                        index % 2 === 0 ? 'bg-white' : 'bg-[#f9f9f9]',
                    ]"
                >
                    <!-- Código de Inscripción -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]"
                    >
                        {{ account.enrollment_code }}
                    </div>

                    <!-- Participante -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]"
                    >
                        {{ account.participant_name }}
                    </div>

                    <!-- Programa -->
                    <div
                        class="text-[#1c4f4a] font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]"
                    >
                        {{ account.program_name }}
                    </div>

                    <!-- Monto Neto -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        ${{ formatPrice(account.net_amount) }}
                    </div>

                    <!-- Total Pagado -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        ${{ formatPrice(account.total_paid) }}
                    </div>

                    <!-- Saldo Pendiente -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        ${{ formatPrice(account.pending_amount) }}
                    </div>

                    <!-- Estado -->
                    <div class="flex justify-center items-center w-[100px]">
                        <div
                            :class="[
                                'rounded-[12px] px-[10px] py-[6px] text-white font-nexa-xbold text-[14px] leading-[13px] text-center flex items-center justify-center',
                                getStatusClass(account.status),
                            ]"
                        >
                            {{ getStatusLabel(account.status) }}
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div
                        class="flex gap-2 items-center justify-center w-[80px]"
                    >
                        <!-- Ver Detalles Button -->
                        <button
                            @click="$emit('view-details', account)"
                            class="w-[18px] h-[19px] hover:opacity-75 transition-opacity flex items-center justify-center"
                        >
                            <svg 
                                xmlns="http://www.w3.org/2000/svg" 
                                width="24" 
                                height="16" 
                                viewBox="0 0 24 16" 
                                fill="none"
                                class="w-[18px] h-[12px]"
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
    </div>
</template>

<script>
import { formatPrice } from '@/utils/paymentUtils';

export default {
    name: "ReportsTable",
    components: {
    },
    props: {
        accounts: {
            type: Array,
            default: () => [],
        },
    },
    methods: {
        formatPrice,
        getStatusClass(status) {
            switch (status) {
                case "paid":
                    return "bg-[#4b8d7f]"; // Verde completado
                case "partial":
                    return "bg-[#ffb232]"; // Amarillo - Pago parcial
                case "pending":
                    return "bg-[#d54b44]"; // Rojo - Pendiente
                case "overdue":
                    return "bg-[#1c4f4a]"; // Verde oscuro - Vencido
                default:
                    return "bg-[#ffb232]"; // Amarillo por defecto
            }
        },

        getStatusLabel(status) {
            switch (status) {
                case "paid":
                    return "Pagado";
                case "partial":
                    return "Parcial";
                case "pending":
                    return "Pendiente";
                case "overdue":
                    return "Vencido";
                default:
                    return "Pendiente";
            }
        },
    },
};
</script>

<style scoped>
/* Custom font classes */
.font-nexa-bold {
    font-family: "Nexa-Bold", sans-serif;
    font-weight: 700;
}

.font-nexa-xbold {
    font-family: "Nexa-XBold", sans-serif;
    font-weight: 400;
}

.text-verde-oscuro {
    color: #1c4f4a;
}

.bg-turquesa {
    background-color: #007e93;
}
</style>
