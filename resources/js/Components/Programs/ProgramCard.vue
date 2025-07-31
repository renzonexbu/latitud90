<template>
    <div class="relative w-[376px] h-[262px] rounded-[20px] overflow-hidden shadow-lg">
        <!-- Background Image -->
        <img 
            src="/images/programs/card-viaje-pruebas.png" 
            alt="Programa de viaje"
            class="w-full h-full object-cover"
        />
        
        <!-- SVG Icon in top-right corner -->
        <div class="absolute top-4 right-4 rounded-[50px] bg-turquesa flex p-[11px] justify-center items-center gap-[10px]">
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

        <!-- Content Overlay with Progress Bar -->
        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-4">
            <!-- Program Info -->
            <div class="text-white mb-3">
                <h3 class="text-lg font-bold mb-2">{{ program.name }}</h3>
                <div class="flex items-center justify-between text-sm">
                    <span>{{ program.destination }}</span>
                    <span class="font-bold">{{ formatPrice(program.price) }}</span>
                </div>
                <div class="flex items-center mt-2 text-xs opacity-90">
                    <span>{{ program.duration }} días</span>
                    <span class="mx-2">•</span>
                    <span>{{ program.participants }} participantes</span>
                </div>
            </div>

            <!-- Progress Bar Card -->
            <div class="bg-white rounded-[12px] p-[14px_16px] flex flex-col gap-2 items-start justify-start self-stretch flex-shrink-0 relative">
                <div class="flex flex-col gap-[11px] items-start justify-start self-stretch flex-shrink-0 relative">
                    <div class="flex flex-row items-end justify-between self-stretch flex-shrink-0 relative">
                        <div class="flex flex-col gap-3 items-start justify-start flex-1 relative">
                            <div class="flex flex-row items-start justify-between self-stretch flex-shrink-0 relative">
                                <!-- Percentage -->
                                <div class="text-[#4B8D7F] text-left font-nexa text-[18px] font-normal font-bold leading-[22px] relative">
                                    {{ program.paymentPercentage }}%
                                </div>
                                
                                <!-- Money Values -->
                                <div class="flex flex-row items-end justify-end flex-shrink-0 relative">
                                    <div class="text-[#4B8D7F] text-left font-nexa text-[20px] font-normal font-bold leading-[24px] relative w-[83px] h-[22px]">
                                        {{ formatPrice(program.paidAmount) }}
                                    </div>
                                    <div class="text-[#4B8D7F] text-left font-nexa text-[12px] font-normal font-bold leading-[13px] relative w-[53px] h-[12px] ml-6">
                                        /{{ formatPrice(program.totalAmount) }}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Progress Bar -->
                            <div class="rounded-[100px] border-[3px] border-gris-2 bg-white flex h-4 pr-[164px] items-center self-stretch relative overflow-hidden">
                                <div 
                                    class="bg-[#4B8D7F] rounded-[100px] h-4 absolute left-0 top-1/2 translate-y-[-50%] overflow-hidden"
                                    :style="{ width: `${program.paymentPercentage}%` }"
                                >
                                    <div class="flex flex-row items-center justify-start h-auto absolute left-[-3px] top-[-2px] overflow-visible">
                                        <!-- Diagonal stripes pattern -->
                                        <svg width="100%" height="100%" viewBox="0 0 100 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <defs>
                                                <pattern id="diagonalHatch" patternUnits="userSpaceOnUse" width="8" height="8" patternTransform="rotate(45)">
                                                    <line x1="0" y1="0" x2="0" y2="8" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
                                                </pattern>
                                            </defs>
                                            <rect width="100%" height="100%" fill="url(#diagonalHatch)"/>
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
            required: true
        }
    },
    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat("es-CL", {
                style: "currency",
                currency: "CLP",
            })
                .format(price)
                .replace("CLP", "")
                .trim();
        }
    }
};
</script> 