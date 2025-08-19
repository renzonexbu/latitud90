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
                    :key="participant.id + '-' + (getFirstCourseInfo(participant, 'program', 'code') || 'none')"
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
                        {{ capitalizeWords(getFirstCourseInfo(participant, 'institution', 'name') || 'N/A') }}
                    </div>
                    
                    <!-- Nivel -->
                    <div class="text-[#1c4f4a] font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]">
                        {{ formatCourseInfo(participant) }}
                    </div>
                    
                    <!-- Programa -->
                    <div class="text-[#1c4f4a] font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]">
                        {{ capitalizeWords(getFirstCourseInfo(participant, 'program', 'name') || 'N/A') }}
                    </div>
                    
                    
                    
                    <!-- Estado de pago -->
                    <div class="flex justify-center items-center w-[120px]">
                        <div 
                            :class="[
                                'rounded-[12px] px-[10px] py-[6px] text-white font-nexa-xbold text-[14px] leading-[13px] text-center flex items-center justify-center',
                                getPaymentStatusClass(getFirstCoursePivotStatus(participant), getFirstCoursePivotPercentage(participant))
                            ]"
                        >
                            {{ getPaymentStatusText(getFirstCoursePivotStatus(participant), getFirstCoursePivotPercentage(participant)) }}
                        </div>
                    </div>
                    
                    <!-- Total pagado -->
                    <div class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]">
                        {{ formatPaymentPair(getPaidAmount(participant), getTotalDue(participant)) }}
                    </div>
                    
                    <!-- Acciones -->
                    <div class="flex gap-2 items-center justify-center">
                        <!-- WhatsApp Button with Tooltip -->
                        <div class="relative group">
                            <button 
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
                            <!-- Tooltip -->
                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-800 text-white text-sm rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                                {{ formatPhoneNumber(participant) }}
                                <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-800"></div>
                            </div>
                        </div>
                        
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
        
        formatCourseInfo(participant) {
            const firstCourse = this.getFirstCourse(participant);
            if (!firstCourse) return 'N/A';
            const level = this.capitalizeWords(firstCourse.education_level);
            const num = firstCourse.course_number ? `${firstCourse.course_number}°` : '';
            const label = firstCourse.education_level === 'basica' ? 'Básico' : (firstCourse.education_level === 'media' ? 'Medio' : '');
            const courseStr = num ? `${num} ${label}`.trim() : '';
            return [level, courseStr].filter(Boolean).join('\n');
        },
        
        getFirstCourse(participant) {
            if (!participant.courses || participant.courses.length === 0) {
                return null;
            }
            return participant.courses[0];
        },
        
        getFirstCourseInfo(participant, relation, field) {
            const firstCourse = this.getFirstCourse(participant);
            if (!firstCourse || !firstCourse[relation]) {
                return null;
            }
            return firstCourse[relation][field];
        },
        
        getFirstCoursePivotStatus(participant) {
            const firstCourse = this.getFirstCourse(participant);
            if (!firstCourse || !firstCourse.pivot) {
                return 'pending_payment';
            }
            return firstCourse.pivot.status || 'pending_payment';
        },
        
        getStatusLabel(status) {
            const labels = {
                'pending_payment': 'Pendiente de Pago',
                'confirmed': 'Confirmado',
                'cancelled': 'Cancelado'
            };
            return labels[status] || status;
        },
        
        getFirstCoursePivotPercentage(participant) {
            const total = this.getTotalDue(participant);
            const paid = this.getPaidAmount(participant);
            
            if (total <= 0) return 0;
            
            const percentage = Math.round((paid / total) * 100);
            return percentage;
        },
        
        getFirstCoursePivotAmount(participant) {
            const firstCourse = this.getFirstCourse(participant);
            if (!firstCourse || !firstCourse.pivot) {
                return 0;
            }
            
            const individualPrice = parseFloat(firstCourse.pivot.individual_price) || 0;
            const adjustments = parseFloat(firstCourse.pivot.price_adjustments) || 0;
            
            // Por ahora retornar el precio individual, se puede calcular basado en pagos reales
            return individualPrice;
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
        
        getPaidAmount(participant) {
            // Campo auxiliar inyectado desde Index.vue si viene del backend
            if (typeof participant.__paid_amount !== 'undefined') return Number(participant.__paid_amount || 0);
            return 0;
        },

        getTotalDue(participant) {
            if (typeof participant.__total_due !== 'undefined') return Number(participant.__total_due || 0);
            const firstCourse = this.getFirstCourse(participant);
            if (!firstCourse || !firstCourse.pivot) return 0;
            // Usar participant_total_due si está disponible (ya incluye descuentos)
            if (firstCourse.participant_total_due !== undefined) {
                return Number(firstCourse.participant_total_due || 0);
            }
            // Fallback al cálculo antiguo
            const individualPrice = parseFloat(firstCourse.pivot.individual_price) || 0;
            const adjustments = parseFloat(firstCourse.pivot.price_adjustments) || 0;
            return individualPrice + adjustments;
        },

        formatPayment(amount) {
            if (!amount || amount === 0) {
                return '----';
            }
            return `$${parseInt(amount).toLocaleString()}`;
        },
        
        formatPaymentPair(paid, total) {
            const p = isNaN(paid) ? 0 : paid;
            const t = isNaN(total) ? 0 : total;
            return `$${parseInt(p).toLocaleString()} / $${parseInt(t).toLocaleString()}`;
        },
        
        capitalizeWords(string) {
            if (!string) return '';
            return string.split(' ').map(word => 
                word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
            ).join(' ');
        },

        formatPhoneNumber(participant) {
            if (!participant.phone) return 'N/A';
            const phone = participant.phone.replace(/\D/g, ''); // Remove non-digits
            const code = participant.code_phone || '+56';
            
            if (phone.length === 8) {
                return `${code} 9 ${phone.slice(0, 4)} ${phone.slice(4)}`;
            } else if (phone.length === 9) {
                return `${code} 9 ${phone.slice(0, 5)} ${phone.slice(5)}`;
            } else {
                return `${code} ${phone}`;
            }
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