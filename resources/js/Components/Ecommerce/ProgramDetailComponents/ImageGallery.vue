<template>
    <div class="w-full">
        <!-- Título de la Galería -->
        <h2
            class="text-[#434343] text-left font-nexa text-[20px] leading-[28px] font-bold self-stretch"
        >
            Imágenes referenciales
        </h2>

        <!-- Grid de Galería -->
        <div class="grid grid-cols-2 gap-4">
            <div
                v-for="(image, index) in images"
                :key="index"
                :class="[
                    'relative cursor-pointer overflow-hidden rounded-lg transition-transform hover:scale-[1.02]',
                    isOddAndLast(index) ? 'col-span-2' : 'col-span-1',
                ]"
                @click="openLightbox(index)"
            >
                <img
                    :src="image.url"
                    :alt="`${programName} - Imagen ${index + 1}`"
                    :class="[
                        'w-full object-cover',
                        isOddAndLast(index) ? 'h-[400px]' : 'h-[300px]',
                    ]"
                />
                <!-- Overlay en hover -->
                <div
                    class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-10 transition-all"
                ></div>
            </div>
        </div>

        <!-- Lightbox Modal -->
        <Teleport to="body">
            <div
                v-if="lightboxOpen"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-90"
                @click="closeLightbox"
            >
                <!-- Botón Cerrar -->
                <button
                    class="absolute top-4 right-4 z-[10000] text-white hover:text-gray-300 transition-colors p-2"
                    @click.stop="closeLightbox"
                    aria-label="Cerrar galería"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-8 w-8"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>

                <!-- Botón Anterior -->
                <button
                    v-if="images.length > 1"
                    class="absolute left-4 z-[10000] text-white hover:text-gray-300 transition-colors p-2"
                    @click.stop="previousImage"
                    aria-label="Imagen anterior"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-10 w-10"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                </button>

                <!-- Imagen Principal -->
                <div class="relative max-w-7xl max-h-[90vh] mx-4" @click.stop>
                    <img
                        :src="images[currentImageIndex].url"
                        :alt="`${programName} - Imagen ${
                            currentImageIndex + 1
                        }`"
                        class="max-w-full max-h-[90vh] object-contain"
                    />

                    <!-- Contador de imágenes -->
                    <div
                        class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-60 text-white px-4 py-2 rounded-full"
                    >
                        <span class="font-nexa text-sm"
                            >{{ currentImageIndex + 1 }} /
                            {{ images.length }}</span
                        >
                    </div>
                </div>

                <!-- Botón Siguiente -->
                <button
                    v-if="images.length > 1"
                    class="absolute right-4 z-[10000] text-white hover:text-gray-300 transition-colors p-2"
                    @click.stop="nextImage"
                    aria-label="Imagen siguiente"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-10 w-10"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                </button>
            </div>
        </Teleport>
    </div>
</template>

<script>
import { ref } from "vue";

export default {
    name: "ImageGallery",
    props: {
        images: {
            type: Array,
            required: true,
        },
        programName: {
            type: String,
            required: true,
        },
    },
    setup(props) {
        const lightboxOpen = ref(false);
        const currentImageIndex = ref(0);

        const isOddAndLast = (index) => {
            return (
                props.images.length % 2 !== 0 &&
                index === props.images.length - 1
            );
        };

        const openLightbox = (index) => {
            currentImageIndex.value = index;
            lightboxOpen.value = true;
            // Prevenir scroll del body cuando el lightbox está abierto
            document.body.style.overflow = "hidden";
        };

        const closeLightbox = () => {
            lightboxOpen.value = false;
            // Restaurar scroll del body
            document.body.style.overflow = "";
        };

        const nextImage = () => {
            if (currentImageIndex.value < props.images.length - 1) {
                currentImageIndex.value++;
            } else {
                currentImageIndex.value = 0; // Volver al inicio
            }
        };

        const previousImage = () => {
            if (currentImageIndex.value > 0) {
                currentImageIndex.value--;
            } else {
                currentImageIndex.value = props.images.length - 1; // Ir al final
            }
        };

        // Soporte para teclado
        const handleKeydown = (e) => {
            if (!lightboxOpen.value) return;

            if (e.key === "Escape") {
                closeLightbox();
            } else if (e.key === "ArrowRight") {
                nextImage();
            } else if (e.key === "ArrowLeft") {
                previousImage();
            }
        };

        // Registrar listener de teclado
        if (typeof window !== "undefined") {
            window.addEventListener("keydown", handleKeydown);
        }

        return {
            lightboxOpen,
            currentImageIndex,
            isOddAndLast,
            openLightbox,
            closeLightbox,
            nextImage,
            previousImage,
        };
    },
};
</script>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>
