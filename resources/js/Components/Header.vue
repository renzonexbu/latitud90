<template>
    <header class="absolute top-4 left-4 right-4 z-50">
        <nav class="header-off">
            <div class="flex items-center">
                <a href="/" class="capa-1">
                    <img
                        :src="[!logo ? logoSrc : logoColorSrc]"
                        alt="Logo Latitud 90"
                        class="w-full h-full object-contain"
                    />
                </a>
            </div>

            <!-- Botón menú móvil -->
            <button
                @click="toggleMobileMenu"
                class="md:hidden flex items-center p-2 rounded-md hover:bg-gray-100"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 6h16M4 12h16M4 18h16"
                    />
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
</style>
