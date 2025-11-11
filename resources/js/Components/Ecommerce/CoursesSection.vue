<template>
    <section
        class="relative overflow-hidden rounded-lg"
        style="height: 320px"
        id="courses"
    >
        <!-- Carrusel de imágenes de fondo -->
        <div class="absolute inset-0">
            <div class="relative w-full h-full">
                <transition-group
                    name="fade"
                    tag="div"
                    class="relative w-full h-full"
                >
                    <div
                        v-for="(banner, index) in banners"
                        :key="index"
                        v-show="currentBanner === index"
                        class="absolute inset-0 bg-cover banner-image"
                        :style="{
                            backgroundImage: `url(${banner.url})`,
                            backgroundPosition: banner.position || 'center',
                        }"
                    ></div>
                </transition-group>
            </div>
        </div>

        <!-- Títulos específicos para cada banner -->
        <div class="absolute z-10 top-56 left-36 mobile-svg">
            <transition-group
                name="fade"
                tag="div"
                class="relative"
            >
                <!-- Banner 0: Norte de Chile -->
                <div v-show="currentBanner === 0" key="0" class="absolute">
                    <h1 class="banner-title">Norte de Chile</h1>
                </div>
                <!-- Banner 1: Sur de Chile -->
                <div v-show="currentBanner === 1" key="1" class="absolute">
                    <h1 class="banner-title">Sur de Chile</h1>
                </div>
                <!-- Banner 2: Perú -->
                <div v-show="currentBanner === 2" key="2" class="absolute">
                    <h1 class="banner-title">Perú</h1>
                </div>
                <!-- Banner 3: Brasil -->
                <div v-show="currentBanner === 3" key="3" class="absolute">
                    <h1 class="banner-title">Brasil</h1>
                </div>
                <!-- Banner 4: Estados Unidos -->
                <div v-show="currentBanner === 4" key="4" class="absolute">
                    <h1 class="banner-title">Estados Unidos</h1>
                </div>
                <!-- Banner 5: Europa -->
                <div v-show="currentBanner === 5" key="5" class="absolute">
                    <h1 class="banner-title">Europa</h1>
                </div>
                <!-- Banner 6: Campamentos -->
                <div v-show="currentBanner === 6" key="6" class="absolute">
                    <h1 class="banner-title">Campamentos</h1>
                </div>
                <!-- Banner 7: Centro de Desafíos Lat90 -->
                <div v-show="currentBanner === 7" key="7" class="absolute">
                    <h1 class="banner-title">Centro de Desafíos Lat90</h1>
                </div>
            </transition-group>
        </div>

        <!-- Botón con estilos personalizados - posicionado absolutamente -->
        <div class="absolute z-10 mobile-button">
            <div class="boton-m">
                <div class="placeholder">
                    <a href="https://www.latitud90.com" target="_blank">
                        Descubre más en nuestra web
                    </a>
                </div>
            </div>
        </div>

        <!-- Indicadores circulares - posicionados en la parte inferior -->
        <div
            class="absolute bottom-8 left-1/2 transform -translate-x-1/2 z-10 mobile-indicators"
        >
            <div class="flex space-x-2">
                <button
                    v-for="(banner, index) in banners"
                    :key="index"
                    @click="setBanner(index)"
                    class="w-3 h-3 focus:outline-none transition-opacity duration-300"
                    :class="{
                        'opacity-100': currentBanner === index,
                        'opacity-50': currentBanner !== index,
                    }"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="12"
                        height="13"
                        viewBox="0 0 12 13"
                        fill="none"
                    >
                        <circle cx="6" cy="6.76953" r="6" fill="#FBBD51" />
                    </svg>
                </button>
            </div>
        </div>
    </section>
</template>

<script>
export default {
    name: "CoursesSection",
    data() {
        return {
            currentBanner: 0,
            banners: [
                { url: '/images/banners/NORTE_CHILE.webp', position: 'center 75%' },
                { url: '/images/banners/SUR_DE_CHILE.webp', position: 'center' },
                { url: '/images/banners/PERÚ.webp', position: 'center' },
                { url: '/images/banners/BRASIL.webp', position: 'center' },
                { url: '/images/banners/USA.webp', position: 'center' },
                { url: '/images/banners/EUROPA.webp', position: 'center' },
                { url: '/images/banners/CAMPAMENTOS.webp', position: 'center' },
                { url: '/images/banners/CEAL.webp', position: 'center 75%' }
            ],
            autoPlayInterval: null,
        };
    },
    mounted() {
        this.startAutoPlay();
    },
    beforeUnmount() {
        this.stopAutoPlay();
    },
    methods: {
        startAutoPlay() {
            this.autoPlayInterval = setInterval(() => {
                this.nextBanner();
            }, 5000); // Cambia cada 5 segundos
        },
        stopAutoPlay() {
            if (this.autoPlayInterval) {
                clearInterval(this.autoPlayInterval);
            }
        },
        nextBanner() {
            this.currentBanner = (this.currentBanner + 1) % this.banners.length;
        },
        setBanner(index) {
            this.currentBanner = index;
            this.stopAutoPlay();
            this.startAutoPlay(); // Reinicia el auto-play
        },
    },
};
</script>

<style scoped>
/* Estilos para el botón personalizado */
.boton-m,
.boton-m * {
    box-sizing: border-box;
}

.boton-m {
    background: var(--colores-op2-rojo, #d54a42);
    border-radius: 51px;
    padding: 18px 28px 18px 28px;
    display: flex;
    flex-direction: row;
    gap: 10px;
    align-items: center;
    justify-content: flex-start;
    height: 50px;
    position: relative;
    overflow: hidden;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

/* Estilos específicos para mobile */
@media (max-width: 767px) {
    .mobile-svg {
        top: 7rem !important;
        left: 1rem !important;
    }

    .mobile-svg svg {
        width: 230px !important;
        height: auto !important;
    }

    .mobile-button {
        bottom: 1rem !important;
        left: 1rem !important;
        right: 1rem !important;
        top: auto !important;
        transform: none !important;
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
    }

    .mobile-button .boton-m {
        justify-content: center !important;
        width: 100% !important;
        max-width: 300px !important;
        margin: 0 auto !important;
    }

    .mobile-indicators {
        display: none !important;
    }
}

/* Estilos para desktop */
@media (min-width: 768px) {
    .mobile-button {
        top: 65% !important;
        right: 10rem !important;
        transform: translateY(-50%) !important;
        bottom: auto !important;
        left: auto !important;
        display: block !important;
        justify-content: flex-start !important;
    }

    .mobile-button .boton-m {
        justify-content: flex-start;
        width: auto;
        max-width: none;
    }
}

.boton-m:hover {
    background: #c23e37;
}

.placeholder {
    color: #ffffff;
    text-align: left;
    font-family: var(--cuerpo-de-texto-xl-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-xl-font-size, 18px);
    line-height: var(--cuerpo-de-texto-xl-line-height, 22px);
    font-weight: var(--cuerpo-de-texto-xl-font-weight, 700);
    position: relative;
    display: flex;
    align-items: flex-end;
    justify-content: flex-start;
}

/* Transiciones para el carrusel */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.8s ease-in-out;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Ajustes para el layout */
section {
    min-height: 500px;
}

/* Oscurecer las imágenes del banner */
.banner-image::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.25);
    pointer-events: none;
}

/* Estilos para el título del banner */
.banner-title {
    color: #FFB232;
    font-family: "Nexa-Bold", sans-serif;
    font-size: 48px;
    font-weight: 900;
    line-height: 1.2;
    text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);
    margin: 0;
}

@media (max-width: 767px) {
    .banner-title {
        font-size: 32px;
    }
}
</style>
