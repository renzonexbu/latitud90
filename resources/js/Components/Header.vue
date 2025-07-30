<template>
    <header class="absolute top-4 left-4 right-4 z-50">
        <nav class="header-off">
            <div class="flex items-center">
                <a href="/" class="capa-1">
                    <img
                        :src="[!logo ? logoSrc : logoColorSrc]"
                        alt="Logo Latitud 90"
                        class="w-full h-full object-contain hidden md:block"
                    />
                    <!-- Logo pequeño para mobile -->
                    <svg class="md:hidden w-11 h-7" width="44" height="30" viewBox="0 0 44 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g clip-path="url(#clip0_2004_4564)">
                            <path d="M12.3855 10.0134L9.91956 8.34999V8.33986L9.91196 8.34492L9.9069 8.33986V8.34999L7.4384 10.0134L4.95978 8.33986L2.48116 10.0134L0 8.33986V10.983L2.48116 12.6566L4.95978 10.983L7.4384 12.6566L9.91196 10.9881L12.3855 12.6566L14.8641 10.983V8.33986L12.3855 10.0134Z" fill="white"/>
                            <path d="M14.8641 14.4337H0V16.7149H14.8641V14.4337Z" fill="white"/>
                            <path d="M12.8667 2.56718C12.0641 2.56718 11.3729 3.04063 11.0539 3.72421C10.7374 3.04063 10.0437 2.56718 9.24366 2.56718C8.44361 2.56718 7.7499 3.04063 7.4309 3.72421C7.11442 3.04063 6.42071 2.56718 5.62067 2.56718C4.82062 2.56718 4.12691 3.04063 3.8079 3.72421C3.4889 3.04063 2.79772 2.56718 1.99514 2.56718C0.891281 2.56718 -0.00244141 3.46091 -0.00244141 4.56477C-0.00244141 5.66863 0.891281 6.56235 1.99514 6.56235C2.79772 6.56235 3.4889 6.08891 3.8079 5.40532C4.12438 6.08891 4.81809 6.56235 5.62067 6.56235C6.42325 6.56235 7.11442 6.08891 7.4309 5.40532C7.74737 6.08891 8.44108 6.56235 9.24366 6.56235C10.0462 6.56235 10.7374 6.08891 11.0539 5.40532C11.3729 6.08891 12.0641 6.56235 12.8667 6.56235C13.9705 6.56235 14.8642 5.66863 14.8642 4.56477C14.8642 3.46091 13.9705 2.56718 12.8667 2.56718Z" fill="white"/>
                            <path d="M3.71667 18.4949C5.01548 18.4949 5.72438 19.0139 6.2434 19.3911C6.65102 19.6873 6.87381 19.8519 7.43334 19.8519C7.99287 19.8519 8.21566 19.6873 8.62328 19.3911C9.13977 19.0113 9.8512 18.4949 11.15 18.4949C12.4488 18.4949 13.1577 19.0139 13.6767 19.3911C14.0844 19.6873 14.3072 19.8519 14.8667 19.8519V22.1153C13.5679 22.1153 12.859 21.5963 12.34 21.2191C11.9323 20.9228 11.7095 20.7583 11.15 20.7583C10.5905 20.7583 10.3652 20.9228 9.96007 21.2191C9.44105 21.5988 8.73215 22.1153 7.43334 22.1153C6.13453 22.1153 5.42563 21.5963 4.90661 21.2191C4.49899 20.9228 4.2762 20.7583 3.71667 20.7583C3.15714 20.7583 2.93181 20.9228 2.52673 21.2191C2.00771 21.5988 1.29881 22.1153 0 22.1153V19.8519C0.559526 19.8519 0.784855 19.6873 1.18994 19.3911C1.70896 19.0113 2.41786 18.4949 3.71667 18.4949Z" fill="white"/>
                            <path d="M18.4467 4.57764e-05H21.7051V9.46135H25.2319V12.3856H18.4467V4.57764e-05Z" fill="white"/>
                            <path d="M33.6146 11.5174C31.9664 12.8821 29.1662 13.2365 27.5181 11.3579C25.1432 8.71727 26.5256 3.64863 30.4068 3.63091C31.6474 3.63091 32.25 4.0208 32.7285 4.51703V3.89674H35.7236V9.78063H36.3616V12.4567H35.5818C34.8197 12.4567 34.0931 12.12 33.6146 11.5174ZM31.0626 9.58568H31.1689C32.25 9.58568 32.7462 9.08945 32.7462 8.09698C32.7462 7.10452 32.2322 6.57285 31.1866 6.57285H31.0803C29.9992 6.57285 29.503 7.06908 29.503 8.06154C29.503 9.054 30.0169 9.58568 31.0626 9.58568Z" fill="white"/>
                            <path d="M39.0199 8.95009V6.55755H37.7439V3.89917H39.0199V1.41801H42.0328V3.89917H44V6.55755H42.0328V9.74761L44 8.95009V11.8743C44 11.8743 42.6708 12.5832 41.7137 12.5832C39.2858 12.5832 39.0199 10.9705 39.0199 8.95009Z" fill="white"/>
                            <path d="M19.5074 24.4193C18.0187 22.7357 18.1073 19.3684 19.7024 17.9151C21.3151 16.2315 25.3558 16.1252 27.0749 17.8797C28.4927 19.2443 28.7409 21.2824 28.1206 23.2673C27.8547 24.0471 27.3939 24.8624 26.685 25.8726L24.0621 29.4879H20.3758L23.5481 25.4827C22.7152 25.713 20.8189 25.908 19.5074 24.4193ZM23.3886 22.8952H23.495C24.7001 22.8952 25.2495 22.3458 25.2495 21.247C25.2495 20.1482 24.6824 19.5633 23.5304 19.5633H23.4064C22.219 19.5633 21.6696 20.1127 21.6696 21.2115C21.6696 22.3103 22.2367 22.8952 23.3886 22.8952Z" fill="white"/>
                            <path d="M31.3789 28.0168C30.4751 26.8471 30.1029 25.4293 30.1029 23.3026C30.1029 21.1759 30.4751 19.7403 31.3789 18.5706C32.3005 17.3301 33.6297 16.6389 35.1007 16.6389C36.5716 16.6389 37.9008 17.3123 38.8578 18.5706C39.744 19.7581 40.1161 21.1759 40.1161 23.3026C40.1161 25.4293 39.744 26.8293 38.8578 28.0168C37.9185 29.2573 36.5894 29.9485 35.1007 29.9485C33.612 29.9485 32.3005 29.2573 31.3789 28.0168ZM35.0652 26.8825H35.1716C36.7489 26.8825 37.0147 25.6419 37.0147 23.338C37.0147 21.0341 36.7489 19.7758 35.1893 19.7758H35.0475C33.4702 19.7758 33.2221 21.0341 33.2221 23.338C33.2221 25.6419 33.4702 26.8825 35.0652 26.8825Z" fill="white"/>
                        </g>
                        <defs>
                            <clipPath id="clip0_2004_4564">
                                <rect width="44" height="29.9486" fill="white"/>
                            </clipPath>
                        </defs>
                    </svg>
                </a>
            </div>

            <!-- Botón menú móvil -->
            <button
                @click="toggleMobileMenu"
                class="md:hidden flex items-center p-2 rounded-md hover:bg-gray-100"
            >
                <svg width="28" height="29" viewBox="0 0 28 29" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.375 8.55762C4.375 8.32555 4.46719 8.10299 4.63128 7.9389C4.79538 7.7748 5.01794 7.68262 5.25 7.68262H22.75C22.9821 7.68262 23.2046 7.7748 23.3687 7.9389C23.5328 8.10299 23.625 8.32555 23.625 8.55762C23.625 8.78968 23.5328 9.01224 23.3687 9.17634C23.2046 9.34043 22.9821 9.43262 22.75 9.43262H5.25C5.01794 9.43262 4.79538 9.34043 4.63128 9.17634C4.46719 9.01224 4.375 8.78968 4.375 8.55762ZM4.375 14.9743C4.375 14.7422 4.46719 14.5197 4.63128 14.3556C4.79538 14.1915 5.01794 14.0993 5.25 14.0993H22.75C22.9821 14.0993 23.2046 14.1915 23.3687 14.3556C23.5328 14.5197 23.625 14.7422 23.625 14.9743C23.625 15.2063 23.5328 15.4289 23.3687 15.593C23.2046 15.7571 22.9821 15.8493 22.75 15.8493H5.25C5.01794 15.8493 4.79538 15.7571 4.63128 15.593C4.46719 15.4289 4.375 15.2063 4.375 14.9743ZM4.375 21.391C4.375 21.1589 4.46719 20.9363 4.63128 20.7722C4.79538 20.6081 5.01794 20.516 5.25 20.516H22.75C22.9821 20.516 23.2046 20.6081 23.3687 20.7722C23.5328 20.9363 23.625 21.1589 23.625 21.391C23.625 21.623 23.5328 21.8456 23.3687 22.0097C23.2046 22.1738 22.9821 22.266 22.75 22.266H5.25C5.01794 22.266 4.79538 22.1738 4.63128 22.0097C4.46719 21.8456 4.375 21.623 4.375 21.391Z" fill="white"/>
                </svg>
            </button>

            <div class="frame-1085 hidden md:flex">
                <div class="frame-1084">
                    <a href="#" class="placeholder">Sobre nosotros</a>
                    <a href="#nuestrosProgramas" class="placeholder"
                        >Nuestros programas</a
                    >
                    <a href="#" class="placeholder">Preguntas frecuentes</a>
                    <a href="#" class="placeholder">Contactanos</a>
                </div>
                <div class="boton-l">
                    <div class="placeholder2">Pagar programa</div>
                </div>
            </div>
        </nav>

        <!-- Menú móvil -->
        <transition name="mobile-menu">
            <div
                v-if="mobileMenuOpen"
                class="fixed inset-0 z-50 bg-white mt-[104px]"
            >
                <div class="container mx-auto px-6 py-8">
                    <ul class="space-y-6">
                        <li>
                            <a
                                href="#"
                                class="hover:text-teal-500 font-medium text-lg block"
                                >Sobre nosotros</a
                            >
                        </li>
                        <li>
                            <a
                                href="#nuestrosProgramas"
                                class="hover:text-teal-500 font-medium text-lg block"
                                >Nuestros programas</a
                            >
                        </li>
                        <li>
                            <a
                                href="#"
                                class="hover:text-teal-500 font-medium text-lg block"
                                >Preguntas frecuentes</a
                            >
                        </li>
                        <li>
                            <a
                                href="#"
                                class="hover:text-teal-500 font-medium text-lg block"
                                >Contactanos</a
                            >
                        </li>
                        <li class="pt-4">
                            <button
                                class="bg-turquesa text-white font-semibold py-3 px-6 w-full rounded-2xl transition-all duration-200 shadow-md"
                            >
                                Pagar programa
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </transition>
    </header>
</template>

<script setup>
import logoSrc from "@images/logo.svg";
import logoColorSrc from "@images/logo-color.svg";
import { ref } from "vue";

defineProps({
    logo: {
        type: Boolean,
    },
});
const mobileMenuOpen = ref(false);

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};
</script>

<style scoped>
.header-off,
.header-off * {
    box-sizing: border-box;
}

.header-off {
    border-radius: 20px;
    margin: 13px 90px;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    height: 104px;
    position: relative;
    overflow: hidden;
}

.capa-1 {
    flex-shrink: 0;
    width: 86px;
    height: 58.54px;
    position: relative;
    overflow: visible;
}

.frame-1085 {
    display: flex;
    flex-direction: row;
    gap: 25px;
    align-items: center;
    justify-content: flex-start;
    flex-shrink: 0;
    position: relative;
}

.frame-1084 {
    display: flex;
    flex-direction: row;
    gap: 42px;
    align-items: center;
    justify-content: flex-start;
    flex-shrink: 0;
    position: relative;
}

.placeholder {
    color: #ffffff;
    text-align: left;
    font-family: var(--cuerpo-de-texto-m-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-m-font-size, 14px);
    line-height: var(--cuerpo-de-texto-m-line-height, 18px);
    font-weight: var(--cuerpo-de-texto-m-font-weight, 700);
    position: relative;
    text-decoration: none;
    transition: color 0.2s ease;
}

.placeholder:hover {
    color: #007e93;
}

.boton-l {
    background: var(--colores-op2-turquesa, #007e93);
    border-radius: 48px;
    padding: 14px 28px 14px 28px;
    display: flex;
    flex-direction: row;
    gap: 10px;
    align-items: center;
    justify-content: flex-start;
    flex-shrink: 0;
    position: relative;
    overflow: hidden;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.boton-l:hover {
    background: #006b7a;
}

.placeholder2 {
    color: #ffffff;
    text-align: left;
    font-family: var(--cuerpo-de-texto-l-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-l-font-size, 16px);
    line-height: var(--cuerpo-de-texto-l-line-height, 22px);
    font-weight: var(--cuerpo-de-texto-l-font-weight, 700);
    position: relative;
}

.mobile-menu-enter-active,
.mobile-menu-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.mobile-menu-enter-from,
.mobile-menu-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}

/* Estilos específicos para mobile */
@media (max-width: 768px) {
    .header-off {
        justify-content: space-between;
        margin: 13px 40px !important;
    }

    .capa-1 {
        order: 1;
        margin-top: 20px;
    }

    .md\:hidden {
        order: 2;
    }

    .frame-1085 {
        display: none !important;
    }
}
</style>
