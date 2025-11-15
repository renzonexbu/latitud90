<template>
    <section class="py-12 bg-[#F9F9F9]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center text-teal-600 mb-12">
                Nuestras experiencias
            </h2>

            <div class="flex gap-4 justify-center items-center">
                <!-- Primera imagen con efecto flip -->
                <div class="w-64 sm:w-80">
                    <div class="flip-card-container" @click="flipCard(0)">
                        <div
                            class="flip-card"
                            :class="{ flipped: flippedCards[0] }"
                        >
                            <!-- Frente de la carta -->
                            <div class="flip-card-front">
                                <div class="overflow-hidden rounded-lg">
                                    <img
                                        src="/images/banners/VIAJE_DE_ESTUDIO.webp"
                                        alt="Viajes de estudio"
                                        class="w-full h-auto object-cover"
                                    />
                                </div>
                            </div>

                            <!-- Reverso de la carta -->
                            <div class="flip-card-back">
                                <div class="flip-card-content">
                                    <!-- Título -->
                                    <div class="flip-card-title">
                                        <span class="flip-title-text"
                                            >Viajes de</span
                                        >
                                        <span class="flip-title-text"
                                            >estudio<span class="flip-title-dot"
                                                >.</span
                                            ></span
                                        >
                                    </div>

                                    <!-- Descripción -->
                                    <div class="flip-card-description">
                                        Planificamos viajes de estudio en Chile
                                        y el extranjero, generando experiencias
                                        únicas de aprendizaje y entretención.
                                    </div>

                                    <!-- Icono SVG -->
                                    <div class="flip-card-icon">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="47"
                                            height="47"
                                            viewBox="0 0 47 47"
                                            fill="none"
                                        >
                                            <path
                                                d="M26.3104 42.9358C27.082 42.5656 27.789 41.9625 28.7779 41.1184L28.9326 40.9892C29.7865 40.2195 30.8788 39.7667 32.0268 39.7065L32.2265 39.6908C33.523 39.587 34.4493 39.5145 35.2561 39.2286C36.1731 38.9047 37.006 38.3797 37.6939 37.6922C38.3817 37.0047 38.9071 36.172 39.2315 35.2552C39.5155 34.4483 39.5879 33.522 39.6917 32.2256L39.7074 32.0259C39.7676 30.8779 40.2204 29.7855 40.9901 28.9317L41.1193 28.779C41.9634 27.788 42.5685 27.0811 42.9367 26.3095C43.3557 25.4319 43.5732 24.4718 43.5732 23.4993C43.5732 22.5268 43.3557 21.5666 42.9367 20.6891C42.5665 19.9175 41.9634 19.2105 41.1193 18.2216L40.9901 18.0669C40.1872 17.1269 39.8073 16.228 39.7074 14.9727L39.6917 14.773C39.5879 13.4765 39.5155 12.5502 39.2295 11.7434C38.9056 10.8264 38.3806 9.9935 37.6931 9.30564C37.0056 8.61779 36.1729 8.09241 35.2561 7.768C34.4493 7.48404 33.523 7.41158 32.2265 7.30779L32.0268 7.29212C30.795 7.19421 29.8922 6.82604 28.9326 6.00942L28.7779 5.88016C27.789 5.03612 27.082 4.431 26.3104 4.06283C25.4329 3.64376 24.4727 3.42626 23.5002 3.42626C22.5277 3.42626 21.5676 3.64376 20.69 4.06283C19.9184 4.43296 19.2115 5.03612 18.2225 5.88016L18.0678 6.00942C17.1278 6.81233 16.2289 7.19225 14.9736 7.29212L14.7739 7.30779C13.4775 7.41158 12.5512 7.48404 11.7443 7.76996C10.8273 8.09392 9.99442 8.61888 9.30656 9.3064C8.6187 9.99392 8.09333 10.8266 7.76892 11.7434C7.48496 12.5502 7.4125 13.4765 7.30871 14.773L7.29304 14.9727C7.19513 16.2045 6.82696 17.1073 6.01034 18.0669L5.88108 18.2216C5.03704 19.2105 4.43192 19.9175 4.06375 20.6891C3.64468 21.5666 3.42718 22.5268 3.42718 23.4993C3.42718 24.4718 3.64468 25.4319 4.06375 26.3095C4.43388 27.0811 5.03704 27.788 5.88108 28.777L6.01034 28.9317C6.77999 29.7855 7.23285 30.8779 7.29304 32.0259L7.30871 32.2256C7.4125 33.522 7.48496 34.4483 7.77087 35.2552C8.09484 36.1722 8.6198 37.0051 9.30732 37.6929C9.99484 38.3808 10.8275 38.9062 11.7443 39.2306C12.5512 39.5145 13.4775 39.587 14.7739 39.6908L14.9736 39.7065C16.1216 39.7666 17.214 40.2195 18.0678 40.9892L18.2225 41.1184C19.2115 41.9625 19.9184 42.5676 20.69 42.9358C21.5676 43.3548 22.5277 43.5723 23.5002 43.5723C24.4727 43.5723 25.4329 43.3548 26.3104 42.9358ZM16.2309 30.7686C15.9558 30.4932 15.8013 30.1199 15.8013 29.7307C15.8013 29.3415 15.9558 28.9682 16.2309 28.6928L26.1851 18.7366L20.0379 18.7366C19.6483 18.7366 19.2748 18.5819 18.9993 18.3064C18.7239 18.031 18.5691 17.6574 18.5691 17.2679C18.5691 16.8783 18.7239 16.5048 18.9993 16.2293C19.2748 15.9539 19.6483 15.7991 20.0379 15.7991L29.7316 15.7991C30.1212 15.7991 30.4947 15.9539 30.7702 16.2293C31.0456 16.5048 31.2004 16.8783 31.2004 17.2679L31.2004 26.9616C31.2004 27.3512 31.0456 27.7247 30.7702 28.0002C30.4947 28.2756 30.1212 28.4304 29.7316 28.4304C29.3421 28.4304 28.9685 28.2756 28.6931 28.0002C28.4176 27.7247 28.2629 27.3512 28.2629 26.9616L28.2629 20.8125L18.3067 30.7686C18.0313 31.0437 17.658 31.1982 17.2688 31.1982C16.8796 31.1982 16.5063 31.0437 16.2309 30.7686Z"
                                                fill="#D3D3D3"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Segunda imagen -->
                <div class="w-64 sm:w-80">
                    <div class="flip-card-container" @click="flipCard(1)">
                        <div
                            class="flip-card"
                            :class="{ flipped: flippedCards[1] }"
                        >
                            <!-- Frente de la carta -->
                            <div class="flip-card-front">
                                <div class="overflow-hidden rounded-lg">
                                    <img
                                        src="/images/banners/CEAL.webp"
                                        alt="Cursos de Educación al aire libre"
                                        class="w-full h-auto object-cover"
                                    />
                                </div>
                            </div>

                            <!-- Reverso de la carta -->
                            <div class="flip-card-back">
                                <div class="flip-card-content">
                                    <!-- Título -->
                                    <div class="flip-card-title">
                                        <span class="flip-title-text"
                                            >Cursos de Educación</span
                                        >
                                        <span class="flip-title-text"
                                            >al aire libre – CEAL<span
                                                class="flip-title-dot"
                                                >.</span
                                            ></span
                                        >
                                    </div>

                                    <!-- Descripción -->
                                    <div class="flip-card-description">
                                        Transformamos el medio natural en una
                                        gran sala de clases, con actividades
                                        educativas que fomenten el desarrollo de
                                        habilidades blandas y trabajo en equipo,
                                        junto a colegios y universidades.
                                    </div>

                                    <!-- Icono SVG -->
                                    <div class="flip-card-icon">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="47"
                                            height="47"
                                            viewBox="0 0 47 47"
                                            fill="none"
                                        >
                                            <path
                                                d="M26.3104 42.9358C27.082 42.5656 27.789 41.9625 28.7779 41.1184L28.9326 40.9892C29.7865 40.2195 30.8788 39.7667 32.0268 39.7065L32.2265 39.6908C33.523 39.587 34.4493 39.5145 35.2561 39.2286C36.1731 38.9047 37.006 38.3797 37.6939 37.6922C38.3817 37.0047 38.9071 36.172 39.2315 35.2552C39.5155 34.4483 39.5879 33.522 39.6917 32.2256L39.7074 32.0259C39.7676 30.8779 40.2204 29.7855 40.9901 28.9317L41.1193 28.779C41.9634 27.788 42.5685 27.0811 42.9367 26.3095C43.3557 25.4319 43.5732 24.4718 43.5732 23.4993C43.5732 22.5268 43.3557 21.5666 42.9367 20.6891C42.5665 19.9175 41.9634 19.2105 41.1193 18.2216L40.9901 18.0669C40.1872 17.1269 39.8073 16.228 39.7074 14.9727L39.6917 14.773C39.5879 13.4765 39.5155 12.5502 39.2295 11.7434C38.9056 10.8264 38.3806 9.9935 37.6931 9.30564C37.0056 8.61779 36.1729 8.09241 35.2561 7.768C34.4493 7.48404 33.523 7.41158 32.2265 7.30779L32.0268 7.29212C30.795 7.19421 29.8922 6.82604 28.9326 6.00942L28.7779 5.88016C27.789 5.03612 27.082 4.431 26.3104 4.06283C25.4329 3.64376 24.4727 3.42626 23.5002 3.42626C22.5277 3.42626 21.5676 3.64376 20.69 4.06283C19.9184 4.43296 19.2115 5.03612 18.2225 5.88016L18.0678 6.00942C17.1278 6.81233 16.2289 7.19225 14.9736 7.29212L14.7739 7.30779C13.4775 7.41158 12.5512 7.48404 11.7443 7.76996C10.8273 8.09392 9.99442 8.61888 9.30656 9.3064C8.6187 9.99392 8.09333 10.8266 7.76892 11.7434C7.48496 12.5502 7.4125 13.4765 7.30871 14.773L7.29304 14.9727C7.19513 16.2045 6.82696 17.1073 6.01034 18.0669L5.88108 18.2216C5.03704 19.2105 4.43192 19.9175 4.06375 20.6891C3.64468 21.5666 3.42718 22.5268 3.42718 23.4993C3.42718 24.4718 3.64468 25.4319 4.06375 26.3095C4.43388 27.0811 5.03704 27.788 5.88108 28.777L6.01034 28.9317C6.77999 29.7855 7.23285 30.8779 7.29304 32.0259L7.30871 32.2256C7.4125 33.522 7.48496 34.4483 7.77087 35.2552C8.09484 36.1722 8.6198 37.0051 9.30732 37.6929C9.99484 38.3808 10.8275 38.9062 11.7443 39.2306C12.5512 39.5145 13.4775 39.587 14.7739 39.6908L14.9736 39.7065C16.1216 39.7666 17.214 40.2195 18.0678 40.9892L18.2225 41.1184C19.2115 41.9625 19.9184 42.5676 20.69 42.9358C21.5676 43.3548 22.5277 43.5723 23.5002 43.5723C24.4727 43.5723 25.4329 43.3548 26.3104 42.9358ZM16.2309 30.7686C15.9558 30.4932 15.8013 30.1199 15.8013 29.7307C15.8013 29.3415 15.9558 28.9682 16.2309 28.6928L26.1851 18.7366L20.0379 18.7366C19.6483 18.7366 19.2748 18.5819 18.9993 18.3064C18.7239 18.031 18.5691 17.6574 18.5691 17.2679C18.5691 16.8783 18.7239 16.5048 18.9993 16.2293C19.2748 15.9539 19.6483 15.7991 20.0379 15.7991L29.7316 15.7991C30.1212 15.7991 30.4947 15.9539 30.7702 16.2293C31.0456 16.5048 31.2004 16.8783 31.2004 17.2679L31.2004 26.9616C31.2004 27.3512 31.0456 27.7247 30.7702 28.0002C30.4947 28.2756 30.1212 28.4304 29.7316 28.4304C29.3421 28.4304 28.9685 28.2756 28.6931 28.0002C28.4176 27.7247 28.2629 27.3512 28.2629 26.9616L28.2629 20.8125L18.3067 30.7686C18.0313 31.0437 17.658 31.1982 17.2688 31.1982C16.8796 31.1982 16.5063 31.0437 16.2309 30.7686Z"
                                                fill="#D3D3D3"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tercera imagen -->
                <div class="w-64 sm:w-80">
                    <div class="flip-card-container" @click="flipCard(2)">
                        <div
                            class="flip-card"
                            :class="{ flipped: flippedCards[2] }"
                        >
                            <!-- Frente de la carta -->
                            <div class="flip-card-front">
                                <div class="overflow-hidden rounded-lg">
                                    <img
                                        src="/images/banners/GIRAS_DEPORTIVAS.webp"
                                        alt="Giras Deportivas"
                                        class="w-full h-auto object-cover"
                                    />
                                </div>
                            </div>

                            <!-- Reverso de la carta -->
                            <div class="flip-card-back">
                                <div class="flip-card-content">
                                    <!-- Título -->
                                    <div class="flip-card-title">
                                        <span class="flip-title-text"
                                            >Giras</span
                                        >
                                        <span class="flip-title-text">
                                            Deportivas<span
                                                class="flip-title-dot"
                                                >.</span
                                        ></span>
                                    </div>

                                    <!-- Descripción -->
                                    <div class="flip-card-description">
                                        Ofrecemos un servicio integral que
                                        abarca desde la planificación
                                        personalizada hasta la asistencia en las
                                        salidas o viajes deportivos de tu
                                        colegio, universidad o club deportivo.
                                    </div>

                                    <!-- Icono SVG -->
                                    <div class="flip-card-icon">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="47"
                                            height="47"
                                            viewBox="0 0 47 47"
                                            fill="none"
                                        >
                                            <path
                                                d="M26.3104 42.9358C27.082 42.5656 27.789 41.9625 28.7779 41.1184L28.9326 40.9892C29.7865 40.2195 30.8788 39.7667 32.0268 39.7065L32.2265 39.6908C33.523 39.587 34.4493 39.5145 35.2561 39.2286C36.1731 38.9047 37.006 38.3797 37.6939 37.6922C38.3817 37.0047 38.9071 36.172 39.2315 35.2552C39.5155 34.4483 39.5879 33.522 39.6917 32.2256L39.7074 32.0259C39.7676 30.8779 40.2204 29.7855 40.9901 28.9317L41.1193 28.779C41.9634 27.788 42.5685 27.0811 42.9367 26.3095C43.3557 25.4319 43.5732 24.4718 43.5732 23.4993C43.5732 22.5268 43.3557 21.5666 42.9367 20.6891C42.5665 19.9175 41.9634 19.2105 41.1193 18.2216L40.9901 18.0669C40.1872 17.1269 39.8073 16.228 39.7074 14.9727L39.6917 14.773C39.5879 13.4765 39.5155 12.5502 39.2295 11.7434C38.9056 10.8264 38.3806 9.9935 37.6931 9.30564C37.0056 8.61779 36.1729 8.09241 35.2561 7.768C34.4493 7.48404 33.523 7.41158 32.2265 7.30779L32.0268 7.29212C30.795 7.19421 29.8922 6.82604 28.9326 6.00942L28.7779 5.88016C27.789 5.03612 27.082 4.431 26.3104 4.06283C25.4329 3.64376 24.4727 3.42626 23.5002 3.42626C22.5277 3.42626 21.5676 3.64376 20.69 4.06283C19.9184 4.43296 19.2115 5.03612 18.2225 5.88016L18.0678 6.00942C17.1278 6.81233 16.2289 7.19225 14.9736 7.29212L14.7739 7.30779C13.4775 7.41158 12.5512 7.48404 11.7443 7.76996C10.8273 8.09392 9.99442 8.61888 9.30656 9.3064C8.6187 9.99392 8.09333 10.8266 7.76892 11.7434C7.48496 12.5502 7.4125 13.4765 7.30871 14.773L7.29304 14.9727C7.19513 16.2045 6.82696 17.1073 6.01034 18.0669L5.88108 18.2216C5.03704 19.2105 4.43192 19.9175 4.06375 20.6891C3.64468 21.5666 3.42718 22.5268 3.42718 23.4993C3.42718 24.4718 3.64468 25.4319 4.06375 26.3095C4.43388 27.0811 5.03704 27.788 5.88108 28.777L6.01034 28.9317C6.77999 29.7855 7.23285 30.8779 7.29304 32.0259L7.30871 32.2256C7.4125 33.522 7.48496 34.4483 7.77087 35.2552C8.09484 36.1722 8.6198 37.0051 9.30732 37.6929C9.99484 38.3808 10.8275 38.9062 11.7443 39.2306C12.5512 39.5145 13.4775 39.587 14.7739 39.6908L14.9736 39.7065C16.1216 39.7666 17.214 40.2195 18.0678 40.9892L18.2225 41.1184C19.2115 41.9625 19.9184 42.5676 20.69 42.9358C21.5676 43.3548 22.5277 43.5723 23.5002 43.5723C24.4727 43.5723 25.4329 43.3548 26.3104 42.9358ZM16.2309 30.7686C15.9558 30.4932 15.8013 30.1199 15.8013 29.7307C15.8013 29.3415 15.9558 28.9682 16.2309 28.6928L26.1851 18.7366L20.0379 18.7366C19.6483 18.7366 19.2748 18.5819 18.9993 18.3064C18.7239 18.031 18.5691 17.6574 18.5691 17.2679C18.5691 16.8783 18.7239 16.5048 18.9993 16.2293C19.2748 15.9539 19.6483 15.7991 20.0379 15.7991L29.7316 15.7991C30.1212 15.7991 30.4947 15.9539 30.7702 16.2293C31.0456 16.5048 31.2004 16.8783 31.2004 17.2679L31.2004 26.9616C31.2004 27.3512 31.0456 27.7247 30.7702 28.0002C30.4947 28.2756 30.1212 28.4304 29.7316 28.4304C29.3421 28.4304 28.9685 28.2756 28.6931 28.0002C28.4176 27.7247 28.2629 27.3512 28.2629 26.9616L28.2629 20.8125L18.3067 30.7686C18.0313 31.0437 17.658 31.1982 17.2688 31.1982C16.8796 31.1982 16.5063 31.0437 16.2309 30.7686Z"
                                                fill="#D3D3D3"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Cuarta imagen -->
                <div class="w-64 sm:w-80">
                    <div class="flip-card-container" @click="flipCard(3)">
                        <div
                            class="flip-card"
                            :class="{ flipped: flippedCards[3] }"
                        >
                            <!-- Frente de la carta -->
                            <div class="flip-card-front">
                                <div class="overflow-hidden rounded-lg">
                                    <img
                                        src="/images/banners/OTRAS_EXPERIENCIAS.webp"
                                        alt="Experiencias personalizadas"
                                        class="w-full h-auto object-cover"
                                    />
                                </div>
                            </div>

                            <!-- Reverso de la carta -->
                            <div class="flip-card-back">
                                <div class="flip-card-content">
                                    <!-- Título -->
                                    <div class="flip-card-title">
                                        <span class="flip-title-text"
                                            >Experiencias</span
                                        >
                                        <span class="flip-title-text"
                                            >a tu medida<span
                                                class="flip-title-dot"
                                                >.</span
                                            ></span
                                        >
                                    </div>

                                    <!-- Descripción -->
                                    <div class="flip-card-description">
                                        Diseñamos programas, eventos o talleres
                                        personalizados, según tus necesidades.
                                        Algunas experiencias son: Summer Camp,
                                        Día de la familia o Kermesse, Campamento
                                        “Padre-Hijo”, entre otros.
                                    </div>

                                    <!-- Icono SVG -->
                                    <div class="flip-card-icon">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="47"
                                            height="47"
                                            viewBox="0 0 47 47"
                                            fill="none"
                                        >
                                            <path
                                                d="M26.3104 42.9358C27.082 42.5656 27.789 41.9625 28.7779 41.1184L28.9326 40.9892C29.7865 40.2195 30.8788 39.7667 32.0268 39.7065L32.2265 39.6908C33.523 39.587 34.4493 39.5145 35.2561 39.2286C36.1731 38.9047 37.006 38.3797 37.6939 37.6922C38.3817 37.0047 38.9071 36.172 39.2315 35.2552C39.5155 34.4483 39.5879 33.522 39.6917 32.2256L39.7074 32.0259C39.7676 30.8779 40.2204 29.7855 40.9901 28.9317L41.1193 28.779C41.9634 27.788 42.5685 27.0811 42.9367 26.3095C43.3557 25.4319 43.5732 24.4718 43.5732 23.4993C43.5732 22.5268 43.3557 21.5666 42.9367 20.6891C42.5665 19.9175 41.9634 19.2105 41.1193 18.2216L40.9901 18.0669C40.1872 17.1269 39.8073 16.228 39.7074 14.9727L39.6917 14.773C39.5879 13.4765 39.5155 12.5502 39.2295 11.7434C38.9056 10.8264 38.3806 9.9935 37.6931 9.30564C37.0056 8.61779 36.1729 8.09241 35.2561 7.768C34.4493 7.48404 33.523 7.41158 32.2265 7.30779L32.0268 7.29212C30.795 7.19421 29.8922 6.82604 28.9326 6.00942L28.7779 5.88016C27.789 5.03612 27.082 4.431 26.3104 4.06283C25.4329 3.64376 24.4727 3.42626 23.5002 3.42626C22.5277 3.42626 21.5676 3.64376 20.69 4.06283C19.9184 4.43296 19.2115 5.03612 18.2225 5.88016L18.0678 6.00942C17.1278 6.81233 16.2289 7.19225 14.9736 7.29212L14.7739 7.30779C13.4775 7.41158 12.5512 7.48404 11.7443 7.76996C10.8273 8.09392 9.99442 8.61888 9.30656 9.3064C8.6187 9.99392 8.09333 10.8266 7.76892 11.7434C7.48496 12.5502 7.4125 13.4765 7.30871 14.773L7.29304 14.9727C7.19513 16.2045 6.82696 17.1073 6.01034 18.0669L5.88108 18.2216C5.03704 19.2105 4.43192 19.9175 4.06375 20.6891C3.64468 21.5666 3.42718 22.5268 3.42718 23.4993C3.42718 24.4718 3.64468 25.4319 4.06375 26.3095C4.43388 27.0811 5.03704 27.788 5.88108 28.777L6.01034 28.9317C6.77999 29.7855 7.23285 30.8779 7.29304 32.0259L7.30871 32.2256C7.4125 33.522 7.48496 34.4483 7.77087 35.2552C8.09484 36.1722 8.6198 37.0051 9.30732 37.6929C9.99484 38.3808 10.8275 38.9062 11.7443 39.2306C12.5512 39.5145 13.4775 39.587 14.7739 39.6908L14.9736 39.7065C16.1216 39.7666 17.214 40.2195 18.0678 40.9892L18.2225 41.1184C19.2115 41.9625 19.9184 42.5676 20.69 42.9358C21.5676 43.3548 22.5277 43.5723 23.5002 43.5723C24.4727 43.5723 25.4329 43.3548 26.3104 42.9358ZM16.2309 30.7686C15.9558 30.4932 15.8013 30.1199 15.8013 29.7307C15.8013 29.3415 15.9558 28.9682 16.2309 28.6928L26.1851 18.7366L20.0379 18.7366C19.6483 18.7366 19.2748 18.5819 18.9993 18.3064C18.7239 18.031 18.5691 17.6574 18.5691 17.2679C18.5691 16.8783 18.7239 16.5048 18.9993 16.2293C19.2748 15.9539 19.6483 15.7991 20.0379 15.7991L29.7316 15.7991C30.1212 15.7991 30.4947 15.9539 30.7702 16.2293C31.0456 16.5048 31.2004 16.8783 31.2004 17.2679L31.2004 26.9616C31.2004 27.3512 31.0456 27.7247 30.7702 28.0002C30.4947 28.2756 30.1212 28.4304 29.7316 28.4304C29.3421 28.4304 28.9685 28.2756 28.6931 28.0002C28.4176 27.7247 28.2629 27.3512 28.2629 26.9616L28.2629 20.8125L18.3067 30.7686C18.0313 31.0437 17.658 31.1982 17.2688 31.1982C16.8796 31.1982 16.5063 31.0437 16.2309 30.7686Z"
                                                fill="#D3D3D3"
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
    </section>
</template>

<script setup>
import { ref } from "vue";

const flippedCards = ref([false, false, false, false]);

const flipCard = (index) => {
    flippedCards.value[index] = !flippedCards.value[index];
};
</script>

<style scoped>
/* Desktop: mostrar 3 cards lado a lado */
@media (min-width: 769px) {
    .flex {
        flex-direction: row;
    }
}

/* Mobile: mostrar 3 cards una debajo de la otra */
@media (max-width: 768px) {
    .flex {
        flex-direction: column !important;
        gap: 2rem !important;
    }

    .w-64 {
        width: 100% !important;
        max-width: 280px !important;
        margin: 0 auto !important;
    }

    /* Reducir altura de cards en mobile */
    .flip-card-container {
        height: 320px !important;
    }

    /* Ajustar imágenes en mobile */
    .flip-card-front img {
        height: 320px !important;
        object-fit: cover !important;
    }

    /* Reducir tamaño de cards en mobile */
    .flip-card-back {
        padding: 15px !important;
    }

    .flip-card-content {
        padding: 15px !important;
    }

    /* Ajustar título en mobile */
    .flip-card-title {
        font-size: 20px !important;
        letter-spacing: 0.309px !important;
        margin-top: 70px !important;
        margin-bottom: 12px !important;
    }

    /* Ajustar descripción en mobile */
    .flip-card-description {
        font-size: 10.812px !important;
        line-height: 13.901px !important;
    }

    /* Ajustar icono SVG en mobile */
    .flip-card-icon {
        width: 36px !important;
        height: 36px !important;
        top: 25px !important;
        right: 15px !important;
    }

    .flip-card-icon svg {
        width: 36px !important;
        height: 36px !important;
    }
}

/* Estilos para el efecto flip */
.flip-card-container {
    width: 100%;
    perspective: 1000px;
    cursor: pointer;
    height: 410px; /* Altura fija para todos los cards */
}

.flip-card {
    position: relative;
    width: 100%;
    height: 100%;
    transform-style: preserve-3d;
    transition: transform 0.6s;
}

.flip-card.flipped {
    transform: rotateY(180deg);
}

.flip-card-front,
.flip-card-back {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    backface-visibility: hidden;
    border-radius: 37px;
}

.flip-card-front {
    transform: rotateY(0deg);
    z-index: 2;
    position: relative;
}

.flip-card-front .overflow-hidden {
    border-radius: 37px;
    height: 100%;
}

.flip-card-front img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 37px;
}

.card-image-scaled {
    object-fit: contain !important;
}

.flip-card-back {
    transform: rotateY(180deg);
    background: #007e93;
    padding: 20px;
    z-index: 1;
    height: 100%;
}

.flip-card-content {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: flex-start;
    padding: 20px;
    height: 100%;
}

.flip-card-icon {
    position: absolute;
    top: 43px;
    right: 30px;
    width: 47px;
    height: 47px;
}

.flip-card-title {
    font-family: "Nexa", sans-serif;
    font-size: 28px;
    font-style: normal;
    font-weight: 900;
    line-height: 96%;
    letter-spacing: 0.4px;
    color: #fff;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    margin-bottom: 15px;
    margin-top: 80px;
    margin-left: 20px;
}

.flip-title-text {
    color: #fff;
    display: inline;
}

.flip-title-dot {
    color: #d54a42;
    display: inline;
}

.flip-card-description {
    color: #fff;
    font-family: "Nexa", sans-serif;
    font-size: 14px;
    font-style: normal;
    font-weight: 400;
    line-height: 18px;
    text-align: justify;
    hyphens: auto;
    max-width: 85%;
    text-justify: inter-word;
    margin: 0 auto;
}
</style>
