<template>
    <div class="space-y-4">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Mobile Focal Point -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                    Punto focal Mobile
                    <span class="text-gray-500 font-normal">(Haz clic en la imagen)</span>
                </label>
                <div class="relative group border-2 border-gray-300 rounded-lg overflow-hidden bg-gray-50 cursor-crosshair"
                     @click="handleMobileClick"
                     ref="mobileContainer">
                    <img
                        :src="imageUrl"
                        alt="Vista mobile"
                        class="w-full h-64 object-cover"
                        :style="{ objectPosition: mobileObjectPosition }"
                        @load="onImageLoad"
                    />
                    <!-- Marcador del punto focal -->
                    <div
                        v-if="!isNaN(localMobileX) && !isNaN(localMobileY)"
                        class="absolute w-6 h-6 bg-blue-500 border-2 border-white rounded-full shadow-lg pointer-events-none transform -translate-x-1/2 -translate-y-1/2"
                        :style="{
                            left: `${localMobileX}%`,
                            top: `${localMobileY}%`
                        }"
                    >
                        <div class="absolute inset-0 bg-blue-500 rounded-full animate-ping opacity-75"></div>
                    </div>
                    <div class="absolute top-2 right-2 bg-white/90 px-2 py-1 rounded text-xs font-mono">
                        📱 {{ Math.round(localMobileX) }}%, {{ Math.round(localMobileY) }}%
                    </div>
                </div>
                <p class="text-xs text-gray-500">
                    La imagen se recortará enfocándose en este punto en pantallas móviles
                </p>
            </div>

            <!-- Desktop Focal Point -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                    Punto focal Desktop
                    <span class="text-gray-500 font-normal">(Haz clic en la imagen)</span>
                </label>
                <div class="relative group border-2 border-gray-300 rounded-lg overflow-hidden bg-gray-50 cursor-crosshair"
                     @click="handleDesktopClick"
                     ref="desktopContainer">
                    <img
                        :src="imageUrl"
                        alt="Vista desktop"
                        class="w-full h-64 object-cover"
                        :style="{ objectPosition: desktopObjectPosition }"
                    />
                    <!-- Marcador del punto focal -->
                    <div
                        v-if="!isNaN(localDesktopX) && !isNaN(localDesktopY)"
                        class="absolute w-6 h-6 bg-green-500 border-2 border-white rounded-full shadow-lg pointer-events-none transform -translate-x-1/2 -translate-y-1/2"
                        :style="{
                            left: `${localDesktopX}%`,
                            top: `${localDesktopY}%`
                        }"
                    >
                        <div class="absolute inset-0 bg-green-500 rounded-full animate-ping opacity-75"></div>
                    </div>
                    <div class="absolute top-2 right-2 bg-white/90 px-2 py-1 rounded text-xs font-mono">
                        💻 {{ Math.round(localDesktopX) }}%, {{ Math.round(localDesktopY) }}%
                    </div>
                </div>
                <p class="text-xs text-gray-500">
                    La imagen se recortará enfocándose en este punto en pantallas de escritorio
                </p>
            </div>
        </div>

        <!-- Reset Button -->
        <div class="flex justify-end">
            <button
                type="button"
                @click="resetToCenter"
                class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
            >
                Resetear al centro (50%, 50%)
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    imageUrl: {
        type: String,
        required: true
    },
    mobileX: {
        type: [Number, String],
        default: 50
    },
    mobileY: {
        type: [Number, String],
        default: 50
    },
    desktopX: {
        type: [Number, String],
        default: 50
    },
    desktopY: {
        type: [Number, String],
        default: 50
    }
});

const emit = defineEmits(['update:mobileX', 'update:mobileY', 'update:desktopX', 'update:desktopY']);

const mobileContainer = ref(null);
const desktopContainer = ref(null);

// Local state
const localMobileX = ref(parseFloat(props.mobileX) || 50);
const localMobileY = ref(parseFloat(props.mobileY) || 50);
const localDesktopX = ref(parseFloat(props.desktopX) || 50);
const localDesktopY = ref(parseFloat(props.desktopY) || 50);

// Watch for prop changes
watch(() => props.mobileX, (val) => {
    localMobileX.value = parseFloat(val) || 50;
});
watch(() => props.mobileY, (val) => {
    localMobileY.value = parseFloat(val) || 50;
});
watch(() => props.desktopX, (val) => {
    localDesktopX.value = parseFloat(val) || 50;
});
watch(() => props.desktopY, (val) => {
    localDesktopY.value = parseFloat(val) || 50;
});

// Computed object-position CSS
const mobileObjectPosition = computed(() => {
    return `${localMobileX.value}% ${localMobileY.value}%`;
});

const desktopObjectPosition = computed(() => {
    return `${localDesktopX.value}% ${localDesktopY.value}%`;
});

const handleMobileClick = (event) => {
    const rect = mobileContainer.value.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 100;
    const y = ((event.clientY - rect.top) / rect.height) * 100;

    localMobileX.value = Math.max(0, Math.min(100, x));
    localMobileY.value = Math.max(0, Math.min(100, y));

    emit('update:mobileX', localMobileX.value.toFixed(2));
    emit('update:mobileY', localMobileY.value.toFixed(2));
};

const handleDesktopClick = (event) => {
    const rect = desktopContainer.value.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 100;
    const y = ((event.clientY - rect.top) / rect.height) * 100;

    localDesktopX.value = Math.max(0, Math.min(100, x));
    localDesktopY.value = Math.max(0, Math.min(100, y));

    emit('update:desktopX', localDesktopX.value.toFixed(2));
    emit('update:desktopY', localDesktopY.value.toFixed(2));
};

const resetToCenter = () => {
    localMobileX.value = 50;
    localMobileY.value = 50;
    localDesktopX.value = 50;
    localDesktopY.value = 50;

    emit('update:mobileX', '50.00');
    emit('update:mobileY', '50.00');
    emit('update:desktopX', '50.00');
    emit('update:desktopY', '50.00');
};

const onImageLoad = () => {
    // Asegurar que los valores iniciales están correctos
    if (isNaN(localMobileX.value)) localMobileX.value = 50;
    if (isNaN(localMobileY.value)) localMobileY.value = 50;
    if (isNaN(localDesktopX.value)) localDesktopX.value = 50;
    if (isNaN(localDesktopY.value)) localDesktopY.value = 50;
};
</script>

<style scoped>
@keyframes ping {
    75%, 100% {
        transform: scale(2);
        opacity: 0;
    }
}

.animate-ping {
    animation: ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;
}
</style>
