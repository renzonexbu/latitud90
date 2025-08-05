<template>
    <div 
        class="bg-gray-800 rounded-[20px] overflow-hidden relative h-[255px] w-[550px] flex-shrink-0 cursor-pointer hover:shadow-xl transition-all duration-300 transform hover:scale-105"
        @click="handleProgramClick"
    >
        <!-- Background Image -->
        <div class="absolute inset-0 bg-gradient-to-br from-gray-900/80 to-gray-800/90">
            <img 
                v-if="getBackgroundImage()" 
                :src="getBackgroundImage()" 
                :alt="program.name"
                class="w-full h-full object-cover opacity-30"
            />
        </div>
        
        <!-- Content -->
        <div class="relative z-10 p-6 h-full flex flex-col justify-between">
            <!-- Top Section -->
            <div class="flex justify-between items-start">
                <div class="space-y-1">
                    <p class="text-white/70 text-xs font-nexa-regular">
                        {{ program.destination }}
                    </p>
                    <h3 class="text-white text-lg font-nexa-bold">
                        {{ program.name }}
                    </h3>
                </div>
                
                <!-- Arrow Icon -->
                <div class="bg-turquesa rounded-full p-2">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17L17 7M17 7H7M17 7V17"></path>
                    </svg>
                </div>
            </div>
            
            <!-- Middle Section -->
            <div class="text-white/70 text-xs font-nexa-regular">
                Fecha: {{ formatDate(program.departure_date) }}
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
                                    {{ program.payment_percentage || 0 }}%
                                </div>

                                <!-- Money Values -->
                                <div
                                    class="flex flex-row items-end justify-end flex-shrink-0 relative"
                                >
                                    <div
                                        class="text-[#4B8D7F] text-left font-nexa text-[18px] font-normal font-bold leading-[22px] relative min-w-0"
                                    >
                                        {{ formatPrice(program.paid_amount || 0) }}
                                    </div>
                                    <div
                                        class="text-[#4B8D7F] text-left font-nexa text-[12px] font-normal font-bold leading-[13px] relative ml-2"
                                    >
                                        /{{ formatPrice(program.total_amount || program.trip_price || 0) }}
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
                                        width: `${program.payment_percentage || 0}%`,
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
import { router } from "@inertiajs/vue3";

export default {
    name: "ProgramCardIndividual",
    props: {
        program: {
            type: Object,
            required: true,
        },
    },
    methods: {
        formatDate(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleDateString('es-CL', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
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
        
        getBackgroundImage() {
            // Obtener la primera imagen del array de imágenes del programa
            if (this.program.images && this.program.images.length > 0) {
                return this.program.images[0].url;
            }
            return null;
        },
        
        handleProgramClick() {
            // Ir al edit del programa
            router.visit(route("admin.programs.edit", this.program.id));
        },
    },
};
</script>

<style scoped>
.font-nexa-regular {
    font-family: 'Nexa-Regular', sans-serif;
    font-weight: 400;
}

.font-nexa-bold {
    font-family: 'Nexa-Bold', sans-serif;
    font-weight: 700;
}

.bg-turquesa {
    background-color: #007e93;
}

.bg-turquesa-dark {
    background-color: #005a6b;
}
</style> 