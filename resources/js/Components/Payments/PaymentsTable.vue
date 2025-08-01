<template>
    <div class="rounded-[20px] border border-gray-300 overflow-hidden">
        <!-- Table Header -->
        <div class="bg-turquesa rounded-t-[20px] px-6 py-4 flex items-center justify-between h-[75px]">
            <div class="text-white font-nexa-bold text-sm w-[120px]">
                ID Orden
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[150px]">
                Participante
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[130px]">
                Programa
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[120px]">
                Monto
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[110px]">
                Estado
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[100px]">
                Tarjeta
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[120px]">
                Código Auth.
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[130px]">
                Fecha
            </div>
        </div>

        <!-- Table Body -->
        <div class="flex flex-col">
            <div 
                v-for="(payment, index) in payments" 
                :key="payment.id"
                :class="[
                    'px-6 py-[18px] flex items-center justify-between',
                    index % 2 === 0 ? 'bg-white' : 'bg-gray-50'
                ]"
            >
                <div class="text-verde-oscuro font-nexa-bold text-sm w-[120px]">
                    {{ payment.buy_order }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[150px]">
                    {{ payment.participant }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[130px]">
                    {{ payment.program }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[120px]">
                    ${{ formatAmount(payment.amount) }}
                </div>
                <div class="w-[110px] flex justify-center">
                    <div 
                        :class="[
                            'rounded-xl px-2.5 py-1.5 flex items-center justify-center w-[100px]',
                            getStatusChipClass(payment.status)
                        ]"
                    >
                        <span 
                            :class="[
                                'font-nexa-xbold text-sm text-center',
                                getStatusTextClass(payment.status)
                            ]"
                        >
                            {{ getStatusText(payment.status) }}
                        </span>
                    </div>
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[100px]">
                    <div class="flex flex-col items-center">
                        <span class="text-xs">{{ payment.card_type }}</span>
                        <span class="text-xs text-gray-500">{{ payment.card_number }}</span>
                    </div>
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[120px]">
                    {{ payment.authorization_code || 'N/A' }}
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[130px]">
                    {{ formatDate(payment.transaction_date) }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "PaymentsTable",
    props: {
        payments: {
            type: Array,
            default: () => []
        }
    },
    methods: {
        formatAmount(amount) {
            return amount.toLocaleString('es-CL');
        },
        
        formatDate(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleDateString('es-CL', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        },
        
        getStatusChipClass(status) {
            const classes = {
                'pending': 'bg-[#ffb232]', // Amarillo para pendiente
                'authorized': 'bg-[#4b8d7f]', // Verde para autorizado
                'completed': 'bg-[#1a4b75]', // Azul oscuro para completado
                'failed': 'bg-[#d54a42]', // Rojo para fallido
                'reversed': 'bg-[#9b59b6]', // Morado para reversado
                'nullified': 'bg-[#d9d9d9]' // Gris para anulado
            };
            return classes[status] || 'bg-[#d9d9d9]';
        },
        
        getStatusTextClass(status) {
            return 'text-white';
        },
        
        getStatusText(status) {
            const texts = {
                'pending': 'Pendiente',
                'authorized': 'Autorizado',
                'completed': 'Completado',
                'failed': 'Fallido',
                'reversed': 'Reversado',
                'nullified': 'Anulado'
            };
            return texts[status] || status;
        }
    }
};
</script>

<style scoped>
/* Custom font classes - add these to your Tailwind config or use existing ones */
.font-nexa-bold {
    font-family: 'Nexa-Bold', sans-serif;
    font-weight: 700;
}

.font-nexa-xbold {
    font-family: 'Nexa-XBold', sans-serif;
    font-weight: 400;
}

.text-verde-oscuro {
    color: #1c4f4a;
}

.bg-turquesa {
    background-color: #007e93;
}
</style>