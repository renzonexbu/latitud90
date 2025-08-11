<template>
    <div
        class="relative w-[376px] h-[262px] rounded-[20px] overflow-hidden shadow-lg cursor-pointer hover:shadow-xl transition-all duration-300 transform hover:scale-105"
        @click="$emit('click', program)"
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

        <!-- Top Section with Program Info and SVG Icon -->
        <div
            class="absolute top-4 left-4 right-4 flex items-start justify-between"
        >
            <!-- Program Info (Left side) -->
            <div class="flex flex-col text-white" style="width: 250px">
                <!-- Destination -->
                <div
                    class="truncate mb-1"
                    style="
                        color: #fff;
                        font-family: Outfit;
                        font-size: 12px;
                        font-weight: 500;
                        line-height: 16px;
                        width: 250px;
                    "
                >
                    {{ program.destination }}
                </div>

                <!-- Program Name -->
                <div
                    class="line-clamp-2 mb-1"
                    style="
                        color: #fff;
                        font-family: Outfit;
                        font-size: 24px;
                        font-weight: 600;
                        line-height: 28px;
                        width: 250px;
                    "
                >
                    {{ program.name }}
                </div>

                <!-- Date -->
                <div
                    class="truncate"
                    style="
                        color: #fff;
                        font-family: Nexa;
                        font-size: 12px;
                        font-weight: 800;
                        line-height: 16px;
                        width: 250px;
                    "
                >
                    Fecha: {{ formatDate(program.departure_date) }}
                </div>
            </div>

            <!-- SVG Icon (Right side) -->
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

        <!-- Content Overlay with Progress Bar -->
        <div
            class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-4"
        >
            <!-- Course Info -->
            <div class="text-white mb-3">
                <div
                    class="flex items-center mt-2 text-xs opacity-90 justify-end"
                >
                    <span v-if="program.course?.institution?.name">{{
                        capitalizeFirst(program.course.institution.name)
                    }}</span>
                    <span
                        v-if="program.course?.course_display && program.course?.institution?.name"
                        class="mx-2"
                        >|</span
                    >
                    <span v-if="program.course?.course_display">{{
                        program.course.course_display
                    }}</span>
                    <span
                        v-if="program.course?.education_level && (program.course?.course_display || program.course?.institution?.name)"
                        class="mx-2"
                        >|</span
                    >
                    <span v-if="program.course?.education_level">{{
                        capitalizeFirst(program.course.education_level)
                    }}</span>
                </div>
            </div>

            <!-- Progress Bar Card -->
            <div
                class="bg-white rounded-[12px] p-[14px_16px] flex flex-col gap-2 items-start justify-start self-stretch flex-shrink-0 relative"
            >
                <div
                    class="flex flex-col gap-[11px] items-start justify-start self-stretch flex-shrink-0 relative"
                >
                    <div
                        class="flex flex-row items-end justify-between self-stretch flex-shrink-0 relative"
                    >
                        <div
                            class="flex flex-col gap-3 items-start justify-start flex-1 relative"
                        >
                            <div
                                class="flex flex-row items-start justify-between self-stretch flex-shrink-0 relative"
                            >
                                <!-- Percentage -->
                                <div
                                    class="text-[#4B8D7F] text-left font-nexa text-[18px] font-normal font-bold leading-[22px] relative"
                                >
                                    {{ program.paymentPercentage }}%
                                </div>

                                <!-- Money Values -->
                                <div
                                    class="flex flex-row items-end justify-end flex-shrink-0 relative"
                                >
                                    <div
                                        class="text-[#4B8D7F] text-left font-nexa text-[18px] font-normal font-bold leading-[22px] relative min-w-0"
                                    >
                                        {{ formatPrice(program.paidAmount) }}
                                    </div>
                                    <div
                                        class="text-[#4B8D7F] text-left font-nexa text-[12px] font-normal font-bold leading-[13px] relative ml-2"
                                    >
                                        /{{ formatPrice(getDisplayTotalAmount()) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div
                                class="rounded-[100px] border-[3px] border-gris-2 bg-white flex h-4 pr-[164px] items-center self-stretch relative overflow-hidden"
                            >
                                <div
                                    class="bg-[#4B8D7F] rounded-[100px] h-4 absolute left-0 top-1/2 translate-y-[-50%] overflow-hidden"
                                    :style="{
                                        width: `${program.paymentPercentage}%`,
                                    }"
                                >
                                    <div
                                        class="flex flex-row items-center justify-start h-auto absolute left-[-3px] top-[-2px] overflow-visible"
                                    >
                                        <!-- Diagonal stripes pattern -->
                                        <svg
                                            width="100%"
                                            height="100%"
                                            viewBox="0 0 100 16"
                                            fill="none"
                                            xmlns="http://www.w3.org/2000/svg"
                                        >
                                            <defs>
                                                <pattern
                                                    id="diagonalHatch"
                                                    patternUnits="userSpaceOnUse"
                                                    width="8"
                                                    height="8"
                                                    patternTransform="rotate(45)"
                                                >
                                                    <line
                                                        x1="0"
                                                        y1="0"
                                                        x2="0"
                                                        y2="8"
                                                        stroke="rgba(255,255,255,0.3)"
                                                        stroke-width="1"
                                                    />
                                                </pattern>
                                            </defs>
                                            <rect
                                                width="100%"
                                                height="100%"
                                                fill="url(#diagonalHatch)"
                                            />
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

            // Si el programa tiene imágenes, usar la primera
            if (this.program.images && this.program.images.length > 0) {
                return this.program.images[0].url;
            }

            // Si no hay imágenes, usar la imagen por defecto
            return "/images/programs/card-viaje-pruebas.png";
        },
    },
    methods: {
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
