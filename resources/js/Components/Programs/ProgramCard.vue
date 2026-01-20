<template>
    <div
        class="relative w-full max-w-[376px] h-[262px] rounded-[20px] overflow-hidden shadow-lg transition-all duration-300"
        :class="[
            isFullyPaid
                ? 'opacity-75 cursor-not-allowed'
                : 'cursor-pointer hover:shadow-xl transform hover:scale-105'
        ]"
        @click="handleCardClick"
    >
        <!-- Background Image -->
        <img
            :src="programImageUrl"
            :alt="program.name"
            class="w-full h-full object-cover"
            @error="handleImageError"
        />

        <!-- Dark overlay for better text readability -->
        <div class="absolute inset-0 bg-black/30"></div>

        <!-- Top Section with Program Info (solo en modo programCourse) -->
        <div
            v-if="mode === 'programCourse'"
            class="absolute top-4 left-4 right-4 flex items-start justify-between"
        >
            <!-- Program Info (Left side) -->
            <div class="flex flex-col text-white" style="width: 250px">
                <!-- Destination -->
                <div
                    class="truncate mb-1 text-[10px] sm:text-xs"
                    style="
                        color: #fff;
                        font-family: Outfit;
                        font-weight: 500;
                        line-height: 14px;
                        width: 250px;
                    "
                >
                    {{ programDestination }}
                </div>

                <!-- Program Name -->
                <div
                    class="line-clamp-2 mb-1 text-lg sm:text-2xl"
                    style="
                        color: #fff;
                        font-family: Outfit;
                        font-weight: 600;
                        line-height: 22px;
                        width: 250px;
                    "
                >
                    {{ program.name }}
                </div>

                <!-- Date -->
                <div
                    class="truncate text-[10px] sm:text-xs"
                    style="
                        color: #fff;
                        font-family: Nexa;
                        font-weight: 800;
                        line-height: 14px;
                        width: 250px;
                    "
                >
                    Fecha: {{ formatDate(program.departure_date) }}
                </div>
            </div>

            <!-- SVG Icon and Status Badge (Right side) -->
            <div class="flex flex-col items-end gap-2">
                <!-- Cancelled Badge -->
                <div
                    v-if="program.is_cancelled || program.participant_program_status === 'cancelled'"
                    class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800"
                >
                    Cancelado
                </div>
                <!-- Subscription Cancelled Badge -->
                <div
                    v-else-if="program.subscription_cancelled"
                    class="px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800"
                >
                    Suscripción Cancelada
                </div>
                <!-- Status Badge -->
                <div
                    v-else-if="showStatusBadge && program.status"
                    class="px-2 py-1 rounded-full text-xs font-medium"
                    :class="getStatusClass(program.status)"
                >
                    {{ getStatusLabel(program.status) }}
                </div>

                <!-- SVG Icon -->
                <div
                    class="rounded-[50px] bg-turquesa flex p-[11px] justify-center items-center gap-[10px] flex-shrink-0"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="15"
                        height="15"
                        viewBox="0 0 19 19"
                        fill="none"
                    >
                        <path
                            d="M2 17L17 2M17 2H4.72727M17 2V14.2727"
                            stroke="white"
                            stroke-width="2.187"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Top Section with SVG Icon (solo en modo template) -->
        <div
            v-else
            class="absolute top-4 right-4 flex items-start justify-end"
        >
            <!-- SVG Icon and Status Badge -->
            <div class="flex flex-col items-end gap-2">
                <!-- Usage Count Badge (solo si hay program_courses_count) -->
                <div
                    v-if="program.program_courses_count !== undefined"
                    class="px-3 py-1 rounded-full text-xs font-bold bg-turquesa text-white shadow-md"
                >
                    {{ program.program_courses_count }} {{ program.program_courses_count === 1 ? 'uso' : 'usos' }}
                </div>

                <!-- Cancelled Badge -->
                <div
                    v-if="program.is_cancelled || program.participant_program_status === 'cancelled'"
                    class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800"
                >
                    Cancelado
                </div>
                <!-- Subscription Cancelled Badge -->
                <div
                    v-else-if="program.subscription_cancelled"
                    class="px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800"
                >
                    Suscripción Cancelada
                </div>
                <!-- Status Badge -->
                <div
                    v-else-if="showStatusBadge && program.status"
                    class="px-2 py-1 rounded-full text-xs font-medium"
                    :class="getStatusClass(program.status)"
                >
                    {{ getStatusLabel(program.status) }}
                </div>

                <!-- SVG Icon -->
                <div
                    class="rounded-[50px] bg-turquesa flex p-[11px] justify-center items-center gap-[10px] flex-shrink-0"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="15"
                        height="15"
                        viewBox="0 0 19 19"
                        fill="none"
                    >
                        <path
                            d="M2 17L17 2M17 2H4.72727M17 2V14.2727"
                            stroke="white"
                            stroke-width="2.187"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Overlay para modo template (plantillas) -->
        <div
            v-if="mode === 'template'"
            class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-4"
        >
            <!-- Template Name and Destination Card -->
            <div
                class="bg-white rounded-[12px] p-3 sm:p-4 flex flex-col items-center justify-center self-stretch flex-shrink-0 relative gap-1"
            >
                <!-- Template Name -->
                <div
                    class="text-[#007e93] text-center font-outfit text-base sm:text-lg font-bold leading-5 sm:leading-6"
                >
                    {{ program.name }}
                </div>
                <!-- Destination (si existe) -->
                <div
                    v-if="programDestination"
                    class="text-[#4B8D7F] text-center font-outfit text-xs sm:text-sm font-medium leading-4 sm:leading-5"
                >
                    {{ programDestination }}
                </div>
            </div>
        </div>

        <!-- Overlay para modo programCourse (con info completa) -->
        <div
            v-else-if="mode === 'programCourse'"
            class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-4"
        >
            <!-- Course Info -->
            <div class="text-white mb-3">
                <div class="flex items-center mt-2 text-[10px] sm:text-xs opacity-90 justify-end">
                    <span v-if="program.course?.institution?.name">{{
                        capitalizeFirst(program.course.institution.name)
                    }}</span>
                    <span v-if="program.course?.institution?.name && program.course?.education_level" class="mx-2">|</span>
                    <span v-if="program.course?.education_level">{{
                        capitalizeFirst(program.course.education_level)
                    }}</span>
                </div>
            </div>

            <!-- Progress Bar Card -->
            <div
                class="bg-white rounded-[12px] p-3 sm:p-4 flex flex-col gap-2 items-start justify-start self-stretch flex-shrink-0 relative"
            >
                <div class="flex flex-col gap-[11px] items-start justify-start self-stretch flex-shrink-0 relative">
                    <div class="flex flex-row items-end justify-between self-stretch flex-shrink-0 relative">
                        <div class="flex flex-col gap-3 items-start justify-start flex-1 relative">
                            <div class="flex flex-row items-start justify-between self-stretch flex-shrink-0 relative">
                                <!-- Percentage or Installments -->
                                <div class="text-[#4B8D7F] text-left font-nexa text-base sm:text-lg font-normal font-bold leading-5 sm:leading-6 relative">
                                    <span v-if="isFullyPaid" class="text-green-600 font-extrabold">PAGADO</span>
                                    <span v-else-if="program.installments_summary">{{ program.installments_summary }}</span>
                                    <span v-else>{{ program.paymentPercentage || 0 }}%</span>
                                </div>

                                <!-- Money Values -->
                                <div class="flex flex-row items-end justify-end flex-shrink-0 relative">
                                    <div class="text-[#4B8D7F] text-left font-nexa text-base sm:text-lg font-normal font-bold leading-5 sm:leading-6 relative min-w-0">
                                        {{ formatPrice(program.paidAmount || 0) }}
                                    </div>
                                    <div class="text-[#4B8D7F] text-left font-nexa text-[10px] sm:text-xs font-normal font-bold leading-3 sm:leading-4 relative ml-2">
                                        /{{ formatPrice(getDisplayTotalAmount()) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="rounded-[100px] border-[3px] border-gris-2 bg-white flex h-4 pr-[164px] items-center self-stretch relative overflow-hidden">
                                <div
                                    class="bg-[#4B8D7F] rounded-[100px] h-4 absolute left-0 top-1/2 translate-y-[-50%] overflow-hidden"
                                    :style="{ width: `${program.paymentPercentage || 0}%` }"
                                >
                                    <div class="flex flex-row items-center justify-start h-auto absolute left-[-3px] top-[-2px] overflow-visible">
                                        <!-- Diagonal stripes pattern -->
                                        <svg width="100%" height="100%" viewBox="0 0 100 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <defs>
                                                <pattern id="diagonalHatch" patternUnits="userSpaceOnUse" width="8" height="8" patternTransform="rotate(45)">
                                                    <line x1="0" y1="0" x2="0" y2="8" stroke="rgba(255,255,255,0.3)" stroke-width="1" />
                                                </pattern>
                                            </defs>
                                            <rect width="100%" height="100%" fill="url(#diagonalHatch)" />
                                        </svg>
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
export default {
    name: "ProgramCard",
    props: {
        program: {
            type: Object,
            required: true,
        },
        showStatusBadge: {
            type: Boolean,
            default: true,
        },
        // Modo del card: 'template' para plantillas base, 'programCourse' para program_courses con datos completos
        mode: {
            type: String,
            default: 'template', // 'template' | 'programCourse'
        },
    },
    emits: ["click"],
    data() {
        return {
            imageError: false,
        };
    },
    computed: {
        programImageUrl() {
            // Si hay un error de imagen, usar la imagen por defecto
            if (this.imageError) {
                return "/images/programs/card-viaje-pruebas.png";
            }

            // Intentar obtener imágenes de program.program (cuando viene de program_courses) o program.images directamente
            const images = this.program.program?.images || this.program.images;

            // Si el programa tiene imágenes, usar la primera
            if (images && images.length > 0) {
                return images[0].url;
            }

            // Si no hay imágenes, usar la imagen por defecto
            return "/images/programs/card-viaje-pruebas.png";
        },
        programDestination() {
            // El destination puede estar en program.program.destination (cuando viene de program_courses)
            // o directamente en program.destination (cuando es plantilla)
            return this.program.program?.destination || this.program.destination || '';
        },
        isFullyPaid() {
            // Verificar si el programa está completamente pagado
            return (this.program.paymentPercentage || 0) >= 100;
        },
    },
    methods: {
        handleCardClick() {
            // Si el programa está completamente pagado, no emitir el click
            if (this.isFullyPaid) {
                return;
            }
            // Emitir el click si no está pagado
            this.$emit('click', this.program);
        },
        getDisplayTotalAmount() {
            // Siempre mostrar el total a pagar por el participante (base + ajuste)
            const p = this.program;
            if (p.participant_total_due !== undefined && p.participant_total_due !== null) {
                return p.participant_total_due;
            }
            const base = p.participant_amount ?? p.individual_price ?? null;
            const adj = p.participant_adjustments ?? 0;
            if (base !== null) return base + adj;
            return p.totalAmount ?? p.trip_price ?? 0;
        },
        formatPrice(price) {
            return new Intl.NumberFormat("es-CL", {
                style: "currency",
                currency: "CLP",
            })
                .format(price)
                .replace("CLP", "")
                .trim();
        },
        handleImageError() {
            // Si la imagen del programa falla, usar la imagen por defecto
            this.imageError = true;
        },
        capitalizeFirst(string) {
            if (!string) return "";
            return string.charAt(0).toUpperCase() + string.slice(1);
        },
        formatDate(dateString) {
            if (!dateString) return "";
            // 1) Si viene en formato YYYY-MM-DD o YYYY-MM-DD HH:mm:ss, formatear manualmente
            if (typeof dateString === 'string') {
                const match = dateString.match(/^(\d{4})-(\d{2})-(\d{2})/);
                if (match) {
                    const [, y, m, d] = match;
                    return `${d}-${m}-${y}`;
                }
            }
            // 2) Intento seguro con Date
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return "";
            return date.toLocaleDateString("es-CL", {
                day: "2-digit",
                month: "2-digit",
                year: "numeric",
            });
        },
        getStatusLabel(status) {
            const labels = {
                'reserva': 'Reserva',
                'ejecutado': 'Ejecutado',
                'cancelled': 'Cancelado'
            };
            return labels[status] || status;
        },
        getStatusClass(status) {
            const classes = {
                'reserva': 'bg-yellow-100 text-yellow-800',
                'ejecutado': 'bg-blue-100 text-blue-800',
                'cancelled': 'bg-red-100 text-red-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },
    },
};
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Asegurar que los precios no se salgan */
.text-right {
    text-align: right;
}

.whitespace-nowrap {
    white-space: nowrap;
}

.truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
