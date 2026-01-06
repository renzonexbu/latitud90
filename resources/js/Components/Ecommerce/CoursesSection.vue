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
        <div class="absolute z-10 top-20 left-36 mobile-svg">
            <transition-group name="fade" tag="div" class="relative">
                <div
                    v-for="(banner, index) in banners"
                    :key="index"
                    v-show="currentBanner === index"
                    class="absolute"
                    :class="`banner-${index}`"
                >
                    <h1 class="banner-title">
                        <span class="title-main"
                            >Viaja <span class="title-a">a</span></span
                        >
                        <br />
                        <span class="title-main-2"
                            >{{ banner.destino
                            }}<span class="title-dot">.</span></span
                        >
                    </h1>
                </div>
            </transition-group>
        </div>

        <!-- Botón con estilos personalizados - posicionado absolutamente -->
        <div class="absolute z-10 mobile-button">
            <div class="boton-m">
                <div class="placeholder">
                    <a :href="buttonUrl" target="_blank">
                        {{ buttonText }}
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
    props: {
        content: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            currentBanner: 0,
            isMobile: false,
            defaultBanners: [
                {
                    url: "/images/banners/NORTE_CHILE.webp",
                    position: "center 75%",
                    destino: "Norte de Chile",
                },
                {
                    url: "/images/banners/SUR_DE_CHILE.webp",
                    position: "center",
                    destino: "Sur de Chile",
                },
                {
                    url: "/images/banners/PERU.webp",
                    position: "center",
                    destino: "Perú",
                },
                {
                    url: "/images/banners/BRASIL.webp",
                    position: "center",
                    destino: "Brasil",
                },
                {
                    url: "/images/banners/USA.webp",
                    position: "center",
                    destino: "Estados Unidos",
                },
                {
                    url: "/images/banners/EUROPA.webp",
                    position: "center",
                    destino: "Europa",
                },
                {
                    url: "/images/banners/CAMPAMENTOS.webp",
                    position: "center",
                    destino: "Campamentos",
                },
                {
                    url: "/images/banners/CEAL.webp",
                    position: "center 75%",
                    destino: "Centro de Desafíos Lat90",
                },
            ],
            autoPlayInterval: null,
        };
    },
    computed: {
        banners() {
            return this.defaultBanners.map((defaultBanner, index) => {
                const imageKey = `banner_${index + 1}_imagen`;
                const destinoKey = `banner_${index + 1}_destino`;
                const imageValue =
                    this.content[imageKey]?.value || this.content[imageKey];
                const destinoValue =
                    this.content[destinoKey]?.value || this.content[destinoKey];

                // Obtener datos de la imagen del content
                const imageData = this.content[imageKey];

                // Obtener puntos focales
                const mobileX = imageData?.focal_point_mobile_x ?? 50;
                const mobileY = imageData?.focal_point_mobile_y ?? 50;
                const desktopX = imageData?.focal_point_desktop_x ?? 50;
                const desktopY = imageData?.focal_point_desktop_y ?? 50;

                // Calcular position según dispositivo
                const x = this.isMobile ? mobileX : desktopX;
                const y = this.isMobile ? mobileY : desktopY;
                const position = imageData ? `${x}% ${y}%` : defaultBanner.position;

                return {
                    url: this.getImageUrl(imageValue, defaultBanner.url),
                    position: position,
                    destino: destinoValue || defaultBanner.destino,
                };
            });
        },
        buttonText() {
            return (
                this.content.boton_texto?.value ||
                this.content.boton_texto ||
                "Descubre más en nuestra web"
            );
        },
        buttonUrl() {
            return (
                this.content.boton_url?.value ||
                this.content.boton_url ||
                "https://www.latitud90.com"
            );
        },
    },
    mounted() {
        this.checkMobile();
        window.addEventListener('resize', this.checkMobile);
        this.startAutoPlay();
    },
    beforeUnmount() {
        window.removeEventListener('resize', this.checkMobile);
        this.stopAutoPlay();
    },
    methods: {
        checkMobile() {
            this.isMobile = window.innerWidth < 768;
        },
        getImageUrl(value, defaultValue) {
            if (!value) return defaultValue;
            if (value.startsWith("http")) return value;
            if (value.startsWith("/")) return value;
            if (value.startsWith("site-content/")) return `/storage/${value}`;
            return value;
        },
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
    background: rgb(0, 126, 147);
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
        left: 0.5rem !important;
        right: 0.5rem !important;
        max-width: calc(100vw - 1rem) !important;
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

    .mobile-button .placeholder {
        font-size: 14px !important;
        line-height: 18px !important;
    }

    .mobile-indicators {
        display: none !important;
    }
}

/* Estilos para desktop */
@media (min-width: 768px) {
    .mobile-svg {
        top: 37% !important;
        transform: translateY(-50%) !important;
    }

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
    background: #01788d;
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
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.45);
    pointer-events: none;
}

/* Estilos para el título del banner */
.banner-title {
    display: block;
    margin: 0;
}

.title-main {
    color: #fff;
    font-family: "Nexa", sans-serif;
    font-size: 70px;
    font-style: normal;
    font-weight: 900;
    line-height: 150%;
    display: inline;
    text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);
    white-space: nowrap;
}
.title-main-2 {
    color: #fff;
    font-family: "Nexa", sans-serif;
    font-size: 70px;
    font-style: normal;
    font-weight: 900;
    line-height: 150%;
    display: inline-block;
    text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);
    white-space: nowrap;
    position: relative;
    transform: translate(20px, -20px);
    z-index: 9;
}

.title-a {
    color: #d54a42;
    font-family: "FONTSPRING DEMO - Quincy CF Text", sans-serif;
    font-size: 65px;
    font-style: normal;
    font-weight: 400;
    line-height: 150%;
    display: inline-block;
    text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);
    position: relative;
    transform: translate(-40px, 35px);
    z-index: 10;
}

.title-dot {
    color: #d54a42;
    font-family: "Nexa", sans-serif;
    font-size: 70px;
    font-style: normal;
    font-weight: 900;
    line-height: 150%;
    display: inline;
    text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);
}

@media (max-width: 767px) {
    .title-main {
        font-size: 52px;
    }
    .title-main-2 {
        display: inline-block;
        position: relative;
        transform: translate(15px, -5px);
        font-size: 52px;
        white-space: normal !important;
        max-width: calc(100vw - 3rem) !important;
    }

    .title-a {
        font-size: 50px;
        transform: translate(-25px, 25px);
        z-index: 10;
    }

    .title-dot {
        font-size: 52px;
    }

    /* Tamaños específicos para títulos largos */
    .banner-4 .title-main-2,  /* Estados Unidos */
    .banner-7 .title-main-2   /* Centro de Desafíos Lat90 */ {
        font-size: 38px !important;
    }

    /* Estilos para Campamentos - todos los elementos proporcionales */
    .banner-6 .title-main {
        font-size: 42px !important;
    }

    .banner-6 .title-a {
        font-size: 40px !important;
        transform: translate(-20px, 20px) !important;
    }

    .banner-6 .title-main-2 {
        font-size: 42px !important;
        transform: translate(8px, -5px) !important;
        max-width: calc(100vw - 2rem) !important;
        white-space: normal !important;
        word-break: normal !important;
    }

    .banner-6 .title-dot {
        font-size: 42px !important;
    }

    .banner-4 .title-dot,
    .banner-7 .title-dot {
        font-size: 38px !important;
    }
}
</style>
