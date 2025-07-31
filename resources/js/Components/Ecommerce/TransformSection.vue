<template>
    <div class="transform-section">
        <div class="transform-text-container">
            <div class="text-content">
                <!-- Desktop layout -->
                <div class="desktop-layout">
                    <div class="first-line">
                        <span class="text-normal">Transformamos</span>
                        <img
                            src="@images/home-section/section_img1.png"
                            alt="Persona en columpio"
                            class="inline-image image-1"
                        />
                        <span class="text-normal">cada</span>
                        <span class="text-bold">experiencia</span>
                    </div>
                    <div class="second-line">
                        <span class="text-normal"
                            >en un programa único, fomentando la conexión,
                            respeto</span
                        >
                    </div>
                    <div class="third-line">
                        <span class="text-normal">y la</span>
                        <img
                            src="@images/home-section/section_img2.png"
                            alt="Personas en balsas"
                            class="inline-image image-2"
                        />
                        <span class="text-normal">exploración</span
                        ><span class="text-red-dot">.</span>
                    </div>
                </div>

                <!-- Mobile layout -->
                <div class="mobile-layout">
                    <div class="mobile-line">
                        <span class="text-normal">Transformamos</span>
                        <img
                            src="@images/home-section/section_img1.png"
                            alt="Persona en columpio"
                            class="inline-image image-1"
                        />
                    </div>
                    <div class="mobile-line">
                        <span class="text-normal">cada</span>
                        <span class="text-bold">experiencia</span>
                        <span class="text-normal">en un</span>
                    </div>
                    <div class="mobile-line">
                        <span class="text-normal">programa único, fomentando</span>
                    </div>
                    <div class="mobile-line">
                        <span class="text-normal">la conexión, respeto y la</span>
                    </div>
                    <div class="mobile-line">
                        <img
                            src="@images/home-section/section_img2.png"
                            alt="Personas en balsas"
                            class="inline-image image-2"
                        />
                        <span class="text-normal">exploración</span
                        ><span class="text-red-dot">.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
// No props needed for this component
</script>

<style scoped>
.transform-section {
    width: 100%;
    background-color: #F9F9F9;
    padding: 60px 20px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.transform-text-container {
    text-align: center;
    position: relative;
    max-width: 1200px;
    margin: 0 auto;
}

.text-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

/* Desktop layout */
.desktop-layout {
    display: block;
}

.mobile-layout {
    display: none;
}

.first-line,
.second-line,
.third-line {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px;
}

.mobile-line {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 6px;
}

.text-normal {
    color: #5b5b5b;
    font-family: "Nexa", sans-serif;
    font-size: 40px;
    font-style: normal;
    font-weight: 800;
    line-height: 65px;
    letter-spacing: 0.4px;
    text-align: center;
}

.text-bold {
    color: #5b5b5b;
    font-family: "Nexa", sans-serif;
    font-size: 40px;
    font-style: normal;
    font-weight: 900;
    line-height: 65px;
    letter-spacing: 0.4px;
    position: relative;
    display: inline-block;
}

.text-bold::after {
    content: "";
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 232px;
    height: 20px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='232' height='20' viewBox='0 0 232 20' fill='none'%3E%3Cpath d='M230.701 14.6569L201.292 4.56383L172.38 14.5669L140.856 4.55977L112.334 14.6324L84.5208 4.84506L56.7838 14.8468L28.9706 5.05946L2.04465 16.5856' stroke='%23FFB232' stroke-width='7.04786'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-size: contain;
    z-index: 1;
}

.text-red {
    color: #d54a42;
    font-family: "Nexa", sans-serif;
    font-size: 45px;
    font-style: normal;
    font-weight: 900;
    line-height: 65px;
    letter-spacing: 0.45px;
}

.text-red-dot {
    color: #d54a42;
    font-family: "Nexa", sans-serif;
    font-size: 45px;
    font-style: normal;
    font-weight: 900;
    line-height: 65px;
    letter-spacing: 0.45px;
}

.inline-image {
    display: inline-block;
    vertical-align: middle;
    border-radius: 63px;
    object-fit: cover;
}

.image-1 {
    width: 155px;
    height: 60px;
    flex-shrink: 0;
}

.image-2 {
    width: 165px;
    height: 60px;
    flex-shrink: 0;
}

/* Responsive adjustments for mobile */
@media (max-width: 767px) {
    .transform-section {
        padding: 40px 20px;
    }

    .transform-text-container {
        max-width: 100%;
    }

    .text-content {
        gap: 6px;
    }

    /* Show mobile layout, hide desktop */
    .desktop-layout {
        display: none;
    }

    .mobile-layout {
        display: block;
    }

    .mobile-line {
        gap: 4px;
    }

    .text-normal,
    .text-bold {
        font-size: 18px;
        line-height: 24px;
        letter-spacing: 0.2px;
    }

    .text-red,
    .text-red-dot {
        font-size: 20px;
        line-height: 24px;
        letter-spacing: 0.3px;
    }

    .text-bold::after {
        width: 100px;
        height: 8px;
        bottom: -4px;
    }

    .inline-image {
        border-radius: 25px;
    }

    .image-1 {
        width: 80px;
        height: 32px;
    }

    .image-2 {
        width: 90px;
        height: 32px;
    }
}

/* Responsive adjustments for desktop */
@media (min-width: 768px) {
    .transform-section {
        padding: 80px 40px;
    }

    .text-normal,
    .text-bold {
        font-size: 40px;
        line-height: 65px;
    }

    .text-red,
    .text-red-dot {
        font-size: 45px;
        line-height: 65px;
    }

    .image-1 {
        width: 155px;
        height: 60px;
        flex-shrink: 0;
    }

    .image-2 {
        width: 165px;
        height: 60px;
        flex-shrink: 0;
    }
}
</style>
