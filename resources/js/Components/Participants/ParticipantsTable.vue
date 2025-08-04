<template>
    <div class="bg-white rounded-[20px] overflow-hidden">
        <!-- Table Container -->
        <div class="flex flex-col gap-0">
            <!-- Table Header -->
            <div class="bg-turquesa rounded-t-[20px] px-5 py-[11px] flex items-center justify-between h-[61.51px]">
                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[100px]">
                    RUT
                </div>
                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]">
                    Nombre
                </div>
                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]">
                    Apellido
                </div>
                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]">
                    Institución
                </div>
                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]">
                    Nivel
                </div>
                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]">
                    Programa
                </div>
                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]">
                    Destino
                </div>
                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]">
                    Estado de pago
                </div>
                <div class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]">
                    Total pagado
                </div>
                <!-- Columna de acciones (vacía en header) -->
                <div class="w-[80px]">
                </div>
            </div>

            <!-- Table Body -->
            <div class="flex flex-col h-[574px] overflow-hidden">
                <div 
                    v-for="(participant, index) in participants" 
                    :key="participant.id"
                    :class="[
                        'px-5 py-[14px] flex items-center justify-between',
                        index % 2 === 0 ? 'bg-white' : 'bg-[#f9f9f9]'
                    ]"
                >   
                    <!-- RUT -->
                    <div class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[100px]">
                        {{ formatRut(participant.document_number) }}
                    </div>
                    
                    <!-- Nombre -->
                    <div class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]">
                        {{ participant.first_name }}
                    </div>
                    
                    <!-- Apellido -->
                    <div class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]">
                        {{ participant.last_name }}
                    </div>
                    
                    <!-- Institución -->
                    <div class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]">
                        {{ participant.course?.institution?.name || `ID: ${participant.course?.institution_id || 'N/A'}` }}
                    </div>
                    
                    <!-- Nivel -->
                    <div class="text-[#1c4f4a] font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]">
                        {{ formatEducationLevel(participant) }}
                    </div>
                    
                    <!-- Programa -->
                    <div class="text-[#1c4f4a] font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]">
                        {{ participant.course?.program?.name || 'N/A' }}
                    </div>
                    
                    <!-- Destino -->
                    <div class="text-[#1c4f4a] font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]">
                        {{ participant.course?.program?.destination || 'N/A' }}
                    </div>
                    
                    <!-- Estado de pago -->
                    <div class="flex justify-center items-center w-[120px]">
                        <div 
                            :class="[
                                'rounded-[12px] px-[10px] py-[6px] text-white font-nexa-xbold text-[14px] leading-[13px] text-center flex items-center justify-center',
                                getPaymentStatusClass(participant.status, participant.payment_percentage)
                            ]"
                        >
                            {{ getPaymentStatusText(participant.status, participant.payment_percentage) }}
                        </div>
                    </div>
                    
                    <!-- Total pagado -->
                    <div class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]">
                        $0
                    </div>
                    
                    <!-- Acciones -->
                    <div class="flex gap-2 items-center justify-center">
                        <!-- WhatsApp Button -->
                        <button 
                            @click="$emit('contact-whatsapp', participant)"
                            class="hover:opacity-75 transition-opacity"
                            style="width: 24px; height: 24px; aspect-ratio: 1/1;"
                        >
                            <WhatsAppIcon 
                                :width="24"
                                :height="24"
                                fill-color="#C7C7C7"
                                class="cursor-pointer hover:opacity-75"
                            />
                        </button>
                        
                        <!-- Edit Button -->
                        <button 
                            @click="$emit('edit-participant', participant.id)"
                            class="w-[18px] h-[19px] hover:opacity-75 transition-opacity"
                        >
                            <EditPencilIcon fill-color="#C7C7C7" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { WhatsAppIcon, EditPencilIcon } from "@/Components/Icons";

export default {
    name: "ParticipantsTable",
    components: {
        WhatsAppIcon,
        EditPencilIcon,
    },
    props: {
        participants: {
            type: Array,
            default: () => []
        }
    },
    mounted() {
        // Componente montado
    },
    methods: {
        formatRut(rut) {
            if (!rut) return 'N/A';
            
            // Limpiar el RUT de puntos y guiones
            let rutLimpio = rut.toString().replace(/\./g, '').replace(/-/g, '');
            
            // Separar número y dígito verificador
            let dv = rutLimpio.slice(-1);
            let numero = rutLimpio.slice(0, -1);
            
            // Formatear con puntos y guión
            let numeroFormateado = numero.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            
            return `${numeroFormateado}-${dv}`;
        },
        
        formatEducationLevel(participant) {
            if (!participant.course) return 'N/A';
            return `${participant.course.education_level}\n${participant.course.grade} | ${participant.course.shift}`;
        },
        
        getPaymentStatusClass(status, percentage = 0) {
            switch (status) {
                case 'confirmed':
                    return 'bg-[#4b8d7f]'; // Verde completado
                case 'cancelled':
                    return 'bg-[#1c4f4a]'; // Verde oscuro - Liberado
                case 'pending_payment':
                default:
                    if (percentage >= 80) {
                        return 'bg-[#ffb232]'; // Amarillo 80%
                    } else if (percentage >= 10) {
                        return 'bg-[#d54b44]'; // Rojo 10%
                    } else {
                        return 'bg-[#ffb232]'; // Amarillo por defecto
                    }
            }
        },
        
        getPaymentStatusText(status, percentage = 0) {
            switch (status) {
                case 'confirmed':
                    return 'Completado';
                case 'cancelled':
                    return 'Liberado';
                case 'pending_payment':
                default:
                    return `${percentage || 0}%`;
            }
        },
        
        formatPayment(amount) {
            if (!amount || amount === 0) {
                return '----';
            }
            return `$${parseInt(amount).toLocaleString()}`;
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