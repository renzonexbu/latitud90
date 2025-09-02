<template>
    <footer
        class="text-white rounded-[20px] w-full"
        style="
            background: var(--Colores-OP2-Turquesa, #007e93);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 40px;
        "
    >
        <!-- Desktop Layout -->
        <div class="desktop-footer" style="padding: 44px 120px; width: 100%">
            <div
                class="flex flex-row items-end justify-between w-full"
                style="align-self: stretch; flex-shrink: 0; position: relative"
            >
                <img
                    :src="images['logo']"
                    alt="Latitud 90 Logo"
                    class="flex-shrink-0"
                    style="
                        width: 99.9px;
                        height: 68px;
                        position: relative;
                        overflow: visible;
                    "
                />

                <div
                    class="flex flex-col gap-4 items-start justify-start flex-shrink-0 relative"
                >
                    <div
                        class="text-left"
                        style="
                            color: var(--Blanco, #fefeff);
                            leading-trim: both;
                            text-edge: cap;
                            font-feature-settings: 'liga' off, 'clig' off;
                            font-family: Nexa;
                            font-size: 18px;
                            font-style: normal;
                            font-weight: 700;
                            line-height: 24px;
                            position: relative;
                            width: 421px;
                            display: flex;
                            align-items: flex-end;
                            justify-content: flex-start;
                        "
                    >
                        Suscríbete a nuestro newsletter
                    </div>
                    <div
                        class="flex flex-row items-center justify-between w-full flex-shrink-0 relative"
                        style="
                            background: var(--colores-neutro-blanco, #ffffff);
                            border-radius: 42px;
                            border: 1px solid
                                var(--colores-neutro-gris-2, #d3d3d3);
                            padding: 8px 8px 8px 18px;
                            box-shadow: var(
                                --neutral-shadow-02-box-shadow,
                                0px 1px 4px 0px rgba(25, 33, 61, 0.08)
                            );
                        "
                    >
                        <div
                            class="flex flex-row gap-1 items-center justify-start flex-shrink-0 relative"
                        >
                            <input
                                v-model="newsletterEmail"
                                type="email"
                                placeholder="Ingresa su email aqui."
                                class="border-none outline-none bg-transparent rounded-full"
                                style="
                                    color: var(--colores-neutro-negro, #434343);
                                    text-align: left;
                                    font-family: 'Nexa-Regular', sans-serif;
                                    font-size: 14px;
                                    line-height: 20px;
                                    font-weight: 400;
                                    position: relative;
                                "
                                @keyup.enter="subscribeToNewsletter"
                            />
                        </div>
                        <button
                            @click="subscribeToNewsletter"
                            :disabled="isSubscribing"
                            class="flex flex-row gap-1 items-center justify-center flex-shrink-0 relative"
                            style="
                                background: var(
                                    --colores-op2-turquesa,
                                    #007e93
                                );
                                border-radius: 53px;
                                padding: 12px;
                            "
                        >
                            <span
                                v-if="!isSubscribing"
                                class="text-center"
                                style="
                                    color: var(--blanco, #fefeff);
                                    font-family: 'Outfit-SemiBold', sans-serif;
                                    font-size: 12px;
                                    line-height: 18px;
                                    font-weight: 600;
                                    position: relative;
                                "
                            >
                                Subscribe
                            </span>
                            <div
                                v-else
                                class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"
                            ></div>
                        </button>
                    </div>
                </div>
            </div>

            <div
                class="flex flex-row items-start justify-between flex-wrap w-full"
                style="
                    row-gap: 28px;
                    align-content: flex-start;
                    align-self: stretch;
                    flex-shrink: 0;
                    position: relative;
                    padding: 40px 0;
                "
            >
                <div
                    class="flex flex-row gap-4 items-center justify-start flex-shrink-0 relative"
                >
                    <div class="flex-shrink-0 w-9 h-9 relative">
                        <div
                            class="absolute left-0 top-0.5 w-9 h-9 rounded-[51px]"
                            style="background: #006e80"
                        ></div>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="18"
                            height="19"
                            viewBox="0 0 18 19"
                            fill="none"
                            class="absolute left-2.5 top-2.5"
                            style="overflow: visible"
                        >
                            <path
                                d="M15.0297 2.01284C15.7917 1.74659 16.5237 2.47859 16.2575 3.24059L11.8137 15.9381C11.525 16.7616 10.3775 16.8081 10.0235 16.0108L7.87921 11.1868L10.8972 8.16809C10.9966 8.06146 11.0507 7.92043 11.0481 7.7747C11.0455 7.62898 10.9865 7.48994 10.8834 7.38688C10.7804 7.28382 10.6413 7.22478 10.4956 7.22221C10.3499 7.21964 10.2088 7.27373 10.1022 7.37309L7.08346 10.3911L2.25946 8.24685C1.46221 7.8921 1.50946 6.74534 2.33221 6.45659L15.0297 2.01284Z"
                                fill="#FEFEFF"
                            />
                        </svg>
                    </div>
                    <div
                        class="flex flex-col gap-1 items-start justify-center flex-shrink-0 relative"
                    >
                        <div
                            class="text-left"
                            style="
                                color: var(--blanco, #fefeff);
                                font-family: var(
                                    --cuerpo-de-texto-m-font-family,
                                    'Nexa-Regular',
                                    sans-serif
                                );
                                font-size: var(
                                    --cuerpo-de-texto-m-font-size,
                                    14px
                                );
                                line-height: var(
                                    --cuerpo-de-texto-m-line-height,
                                    18px
                                );
                                font-weight: var(
                                    --cuerpo-de-texto-m-font-weight,
                                    400
                                );
                                position: relative;
                            "
                        >
                            Email general:
                        </div>
                        <a
                            href="mailto:contacto@latitud90.com"
                            class="text-left underline"
                            style="
                                color: var(--blanco, #fefeff);
                                font-family: var(
                                    --cuerpo-de-texto-m-font-family,
                                    'Nexa-Bold',
                                    sans-serif
                                );
                                font-size: var(
                                    --cuerpo-de-texto-m-font-size,
                                    14px
                                );
                                line-height: var(
                                    --cuerpo-de-texto-m-line-height,
                                    18px
                                );
                                font-weight: var(
                                    --cuerpo-de-texto-m-font-weight,
                                    700
                                );
                                position: relative;
                            "
                        >
                            contacto@latitud90.com
                        </a>
                    </div>
                </div>

                <div
                    class="flex flex-row gap-4 items-center justify-start flex-shrink-0 relative"
                >
                    <div class="flex-shrink-0 w-9 h-9 relative">
                        <div
                            class="absolute left-0 top-0.5 w-9 h-9 rounded-[51px]"
                            style="background: #006e80"
                        ></div>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="18"
                            height="19"
                            viewBox="0 0 18 19"
                            fill="none"
                            class="absolute left-2.5 top-2.5"
                            style="overflow: visible"
                        >
                            <path
                                d="M15.0297 2.01284C15.7917 1.74659 16.5237 2.47859 16.2575 3.24059L11.8137 15.9381C11.525 16.7616 10.3775 16.8081 10.0235 16.0108L7.87921 11.1868L10.8972 8.16809C10.9966 8.06146 11.0507 7.92043 11.0481 7.7747C11.0455 7.62898 10.9865 7.48994 10.8834 7.38688C10.7804 7.28382 10.6413 7.22478 10.4956 7.22221C10.3499 7.21964 10.2088 7.27373 10.1022 7.37309L7.08346 10.3911L2.25946 8.24685C1.46221 7.8921 1.50946 6.74534 2.33221 6.45659L15.0297 2.01284Z"
                                fill="#FEFEFF"
                            />
                        </svg>
                    </div>
                    <div
                        class="flex flex-col gap-1 items-start justify-center flex-shrink-0 relative"
                    >
                        <div
                            class="text-left"
                            style="
                                color: var(--blanco, #fefeff);
                                font-family: var(
                                    --cuerpo-de-texto-m-font-family,
                                    'Nexa-Regular',
                                    sans-serif
                                );
                                font-size: var(
                                    --cuerpo-de-texto-m-font-size,
                                    14px
                                );
                                line-height: var(
                                    --cuerpo-de-texto-m-line-height,
                                    18px
                                );
                                font-weight: var(
                                    --cuerpo-de-texto-m-font-weight,
                                    400
                                );
                                position: relative;
                            "
                        >
                            Viajes de estudio y CEAL:
                        </div>
                        <a
                            href="mailto:educacion@latitud90.com"
                            class="text-left underline"
                            style="
                                color: var(--blanco, #fefeff);
                                font-family: var(
                                    --cuerpo-de-texto-m-font-family,
                                    'Nexa-Bold',
                                    sans-serif
                                );
                                font-size: var(
                                    --cuerpo-de-texto-m-font-size,
                                    14px
                                );
                                line-height: var(
                                    --cuerpo-de-texto-m-line-height,
                                    18px
                                );
                                font-weight: var(
                                    --cuerpo-de-texto-m-font-weight,
                                    700
                                );
                                position: relative;
                            "
                        >
                            educacion@latitud90.com
                        </a>
                    </div>
                </div>

                <div
                    class="flex flex-row gap-4 items-center justify-start flex-shrink-0 relative"
                >
                    <div class="flex-shrink-0 w-9 h-9 relative">
                        <div
                            class="absolute left-0 top-0.5 w-9 h-9 rounded-[51px]"
                            style="background: #006e80"
                        ></div>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="19"
                            height="19"
                            viewBox="0 0 19 19"
                            fill="none"
                            class="absolute left-2.5 top-2.5"
                            style="
                                width: 18px;
                                height: 18px;
                                flex-shrink: 0;
                                overflow: visible;
                            "
                        >
                            <path
                                d="M7.41699 5.17497V15.8662M11.917 2.65497V13.3462M2.91699 6.71397C2.91699 5.56872 2.91699 4.99647 3.21174 4.66272C3.31599 4.54347 3.44274 4.44897 3.58449 4.38372C4.64649 3.89397 6.03849 5.18322 7.16199 5.14347C7.31049 5.13797 7.45724 5.11747 7.60224 5.08197C9.24024 4.68072 10.2662 2.77197 11.9507 2.53947C12.916 2.40447 13.9757 2.98797 14.8787 3.29997C15.6212 3.55647 15.9925 3.68472 16.2047 3.98997C16.417 4.29522 16.417 4.70172 16.417 5.51172V11.8282C16.417 12.9727 16.417 13.5457 16.1222 13.8795C16.0187 13.9972 15.8918 14.0919 15.7495 14.1577C14.6875 14.6475 13.2955 13.359 12.172 13.3987C12.0236 13.4044 11.8761 13.4247 11.7317 13.4595C10.0937 13.8607 9.06774 15.7695 7.38324 16.0027C6.42249 16.1362 3.74799 15.4402 3.12924 14.5515C2.91699 14.2462 2.91699 13.8412 2.91699 13.0297V6.71397Z"
                                stroke="#FEFEFF"
                                stroke-width="1.125"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>
                    <div
                        class="flex flex-col gap-1 items-start justify-center flex-shrink-0 relative"
                    >
                        <div
                            class="text-left"
                            style="
                                color: var(--blanco, #fefeff);
                                font-family: var(
                                    --cuerpo-de-texto-m-font-family,
                                    'Nexa-Regular',
                                    sans-serif
                                );
                                font-size: var(
                                    --cuerpo-de-texto-m-font-size,
                                    14px
                                );
                                line-height: var(
                                    --cuerpo-de-texto-m-line-height,
                                    18px
                                );
                                font-weight: var(
                                    --cuerpo-de-texto-m-font-weight,
                                    400
                                );
                                position: relative;
                            "
                        >
                            Ubicación
                        </div>
                        <div
                            class="text-left"
                            style="
                                color: var(--blanco, #fefeff);
                                font-family: var(
                                    --cuerpo-de-texto-m-font-family,
                                    'Nexa-Bold',
                                    sans-serif
                                );
                                font-size: var(
                                    --cuerpo-de-texto-m-font-size,
                                    14px
                                );
                                line-height: var(
                                    --cuerpo-de-texto-m-line-height,
                                    18px
                                );
                                font-weight: var(
                                    --cuerpo-de-texto-m-font-weight,
                                    700
                                );
                                position: relative;
                            "
                        >
                            Av Carlos Antúnez 1941, Providencia
                        </div>
                    </div>
                </div>

                <div
                    class="flex flex-row gap-4 items-center justify-start flex-shrink-0 relative"
                >
                    <div class="flex-shrink-0 w-9 h-9 relative">
                        <div
                            class="absolute left-0 top-0.5 w-9 h-9 rounded-[51px]"
                            style="background: #006e80"
                        ></div>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="18"
                            height="19"
                            viewBox="0 0 18 19"
                            fill="none"
                            class="absolute left-2.5 top-2.5"
                            style="
                                width: 18px;
                                height: 18px;
                                flex-shrink: 0;
                                overflow: visible;
                            "
                        >
                            <path
                                d="M5.91235 6.77878C5.91081 6.81511 5.91672 6.85137 5.92971 6.88534C5.9427 6.9193 5.9625 6.95025 5.9879 6.97627C6.01329 7.0023 6.04374 7.02286 6.07738 7.03668C6.11101 7.0505 6.14712 7.0573 6.18347 7.05666H7.1116C7.26685 7.05666 7.3906 6.92953 7.41085 6.77541C7.5121 6.03741 8.01835 5.49966 8.9206 5.49966C9.69235 5.49966 10.3988 5.88553 10.3988 6.81366C10.3988 7.52803 9.9781 7.85653 9.31322 8.35603C8.5561 8.90616 7.95647 9.54853 7.99922 10.5914L8.0026 10.8355C8.00378 10.9093 8.03393 10.9797 8.08655 11.0315C8.13917 11.0833 8.21003 11.1123 8.28385 11.1123H9.19622C9.27082 11.1123 9.34235 11.0826 9.3951 11.0299C9.44784 10.9772 9.47747 10.9056 9.47747 10.831V10.7129C9.47747 9.90516 9.7846 9.67003 10.6137 9.04116C11.2988 8.52028 12.0132 7.94203 12.0132 6.72816C12.0132 5.02828 10.5777 4.20703 9.0061 4.20703C7.58072 4.20703 6.01922 4.87078 5.91235 6.77878ZM7.66397 13.2622C7.66397 13.8618 8.1421 14.305 8.80022 14.305C9.48535 14.305 9.95672 13.8618 9.95672 13.2622C9.95672 12.6412 9.48422 12.2047 8.7991 12.2047C8.1421 12.2047 7.66397 12.6412 7.66397 13.2622Z"
                                fill="white"
                            />
                        </svg>
                    </div>
                    <div
                        class="flex flex-col gap-1 items-start justify-center flex-shrink-0 relative"
                    >
                        <div
                            class="text-left"
                            style="
                                color: var(--blanco, #fefeff);
                                font-family: var(
                                    --cuerpo-de-texto-m-font-family,
                                    'Nexa-Regular',
                                    sans-serif
                                );
                                font-size: var(
                                    --cuerpo-de-texto-m-font-size,
                                    14px
                                );
                                line-height: var(
                                    --cuerpo-de-texto-m-line-height,
                                    18px
                                );
                                font-weight: var(
                                    --cuerpo-de-texto-m-font-weight,
                                    400
                                );
                                position: relative;
                            "
                        >
                            Tienes dudas?
                        </div>
                        <a
                            href="#"
                            class="text-left underline"
                            style="
                                color: var(--blanco, #fefeff);
                                font-family: var(
                                    --cuerpo-de-texto-m-font-family,
                                    'Nexa-Bold',
                                    sans-serif
                                );
                                font-size: var(
                                    --cuerpo-de-texto-m-font-size,
                                    14px
                                );
                                line-height: var(
                                    --cuerpo-de-texto-m-line-height,
                                    18px
                                );
                                font-weight: var(
                                    --cuerpo-de-texto-m-font-weight,
                                    700
                                );
                                position: relative;
                            "
                        >
                            Haz click aqui
                        </a>
                    </div>
                </div>
            </div>

            <div
                class="flex flex-col md:flex-row justify-between items-center"
                style="
                    border-top: 1px solid var(--Colores-Neutro-Blanco, #fff);
                    display: flex;
                    padding-top: 34px;
                    justify-content: space-between;
                    align-items: center;
                    align-self: stretch;
                "
            >
                <p
                    class="mb-4 md:mb-0 text-center"
                    style="
                        color: var(--Blanco, #fefeff);
                        text-align: center;
                        font-feature-settings: 'liga' off, 'clig' off;
                        font-family: Nexa;
                        font-size: 16px;
                        font-style: normal;
                        font-weight: 400;
                        line-height: 22px;
                    "
                >
                    Copyright © 2025 Latiud90|
                    <a href="/terminos-y-condiciones" class="underline"
                        >Términos y condiciones</a
                    >
                </p>
                <div class="flex space-x-4">
                    <a
                        href="#"
                        class="hover:text-white transition-colors duration-200"
                    >
                        <img
                            :src="images['facebook-icon']"
                            alt="Facebook"
                            class="h-6 w-6"
                        />
                    </a>
                    <a
                        href="#"
                        class="hover:text-white transition-colors duration-200"
                    >
                        <img
                            :src="images['twitterIcon']"
                            alt="Twitter"
                            class="h-6 w-6"
                        />
                    </a>
                    <a
                        href="#"
                        class="hover:text-white transition-colors duration-200"
                    >
                        <img
                            :src="images['instagram-icon']"
                            alt="Instagram"
                            class="h-6 w-6"
                        />
                    </a>
                    <a
                        href="#"
                        class="hover:text-white transition-colors duration-200"
                    >
                        <img
                            :src="images['linkedin-icon']"
                            alt="LinkedIn"
                            class="h-6 w-6"
                        />
                    </a>
                    <a
                        href="#"
                        class="hover:text-white transition-colors duration-200"
                    >
                        <img
                            :src="images['youtubeIcon']"
                            alt="YouTube"
                            class="h-6 w-6"
                        />
                    </a>
                </div>
            </div>
        </div>

        <!-- Mobile Layout -->
        <div
            class="mobile-footer"
            style="
                padding: 40px 20px 20px 20px;
                display: flex;
                flex-direction: column;
                gap: 45px;
                align-items: flex-start;
                justify-content: flex-start;
                flex-shrink: 0;
                position: relative;
                overflow: hidden;
                width: 100%;
            "
        >
            <!-- Logo and Newsletter Mobile -->
            <div
                style="
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                    align-items: flex-start;
                    justify-content: flex-end;
                    align-self: stretch;
                    flex-shrink: 0;
                    position: relative;
                    width: 100%;
                "
            >
                <div
                    style="
                        display: flex;
                        flex-direction: column;
                        gap: 35px;
                        align-items: flex-start;
                        justify-content: flex-start;
                        align-self: stretch;
                        flex-shrink: 0;
                        position: relative;
                        width: 100%;
                    "
                >
                    <img
                        :src="images['logo']"
                        alt="Latitud 90 Logo"
                        style="
                            flex-shrink: 0;
                            width: 73.46px;
                            height: 50px;
                            position: relative;
                            overflow: visible;
                        "
                    />
                    <div
                        style="
                            display: flex;
                            flex-direction: column;
                            gap: 27px;
                            align-items: flex-start;
                            justify-content: flex-start;
                            align-self: stretch;
                            flex-shrink: 0;
                            position: relative;
                            width: 100%;
                        "
                    >
                        <div
                            style="
                                color: var(--blanco, #fefeff);
                                text-align: left;
                                font-family: 'Nexa-Bold', sans-serif;
                                font-size: 18px;
                                line-height: 24px;
                                font-weight: 700;
                                position: relative;
                                width: 100%;
                                display: flex;
                                align-items: flex-end;
                                justify-content: flex-start;
                            "
                        >
                            Suscríbete a nuestro newsletter
                        </div>
                        <div
                            style="
                                background: var(
                                    --colores-neutro-blanco,
                                    #ffffff
                                );
                                border-radius: 42px;
                                border: 1px solid
                                    var(--colores-neutro-gris-2, #f0f0f0);
                                padding: 8px 8px 8px 18px;
                                display: flex;
                                flex-direction: row;
                                align-items: center;
                                justify-content: space-between;
                                align-self: stretch;
                                flex-shrink: 0;
                                position: relative;
                                box-shadow: var(
                                    --neutral-shadow-02-box-shadow,
                                    0px 1px 4px 0px rgba(25, 33, 61, 0.08)
                                );
                            "
                        >
                            <div
                                style="
                                    display: flex;
                                    flex-direction: row;
                                    gap: 4px;
                                    align-items: center;
                                    justify-content: flex-start;
                                    flex-shrink: 0;
                                    position: relative;
                                "
                            >
                                <input
                                    v-model="newsletterEmail"
                                    type="email"
                                    placeholder="Ingresa su email aqui."
                                    class="border-none outline-none bg-transparent rounded-full"
                                    style="
                                        color: var(
                                            --colores-neutro-negro,
                                            #434343
                                        );
                                        text-align: left;
                                        font-family: 'Nexa-Regular', sans-serif;
                                        font-size: 12px;
                                        line-height: 20px;
                                        font-weight: 400;
                                        position: relative;
                                    "
                                    @keyup.enter="subscribeToNewsletter"
                                />
                            </div>
                            <button
                                @click="subscribeToNewsletter"
                                :disabled="isSubscribing"
                                style="
                                    background: var(
                                        --colores-op2-turquesa,
                                        #007e93
                                    );
                                    border-radius: 53px;
                                    padding: 12px;
                                    display: flex;
                                    flex-direction: row;
                                    gap: 3px;
                                    align-items: center;
                                    justify-content: center;
                                    flex-shrink: 0;
                                    position: relative;
                                "
                            >
                                <span
                                    v-if="!isSubscribing"
                                    style="
                                        color: var(--blanco, #fefeff);
                                        text-align: center;
                                        font-family: 'Outfit-SemiBold',
                                            sans-serif;
                                        font-size: 12px;
                                        line-height: 18px;
                                        font-weight: 600;
                                        position: relative;
                                    "
                                >
                                    Subscribe
                                </span>
                                <div
                                    v-else
                                    class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"
                                ></div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Info Mobile -->
            <div
                style="
                    display: flex;
                    flex-direction: column;
                    gap: 28px;
                    align-items: flex-start;
                    justify-content: flex-start;
                    flex-shrink: 0;
                    position: relative;
                    width: 100%;
                "
            >
                <!-- Email General Mobile -->
                <div
                    style="
                        display: flex;
                        flex-direction: row;
                        gap: 16px;
                        align-items: center;
                        justify-content: flex-start;
                        flex-shrink: 0;
                        position: relative;
                    "
                >
                    <div
                        style="
                            flex-shrink: 0;
                            width: 36px;
                            height: 36px;
                            position: relative;
                        "
                    >
                        <div
                            style="
                                background: #006e80;
                                border-radius: 51px;
                                width: 36px;
                                height: 36px;
                                position: absolute;
                                left: 0px;
                                top: 1.5px;
                            "
                        ></div>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="18"
                            height="19"
                            viewBox="0 0 18 19"
                            fill="none"
                            style="
                                width: 18px;
                                height: 18px;
                                position: absolute;
                                left: 9px;
                                top: 10.5px;
                                overflow: visible;
                            "
                        >
                            <path
                                d="M15.0297 2.01284C15.7917 1.74659 16.5237 2.47859 16.2575 3.24059L11.8137 15.9381C11.525 16.7616 10.3775 16.8081 10.0235 16.0108L7.87921 11.1868L10.8972 8.16809C10.9966 8.06146 11.0507 7.92043 11.0481 7.7747C11.0455 7.62898 10.9865 7.48994 10.8834 7.38688C10.7804 7.28382 10.6413 7.22478 10.4956 7.22221C10.3499 7.21964 10.2088 7.27373 10.1022 7.37309L7.08346 10.3911L2.25946 8.24685C1.46221 7.8921 1.50946 6.74534 2.33221 6.45659L15.0297 2.01284Z"
                                fill="#FEFEFF"
                            />
                        </svg>
                    </div>
                    <div
                        style="
                            display: flex;
                            flex-direction: column;
                            gap: 3px;
                            align-items: flex-start;
                            justify-content: center;
                            flex-shrink: 0;
                            position: relative;
                        "
                    >
                        <div
                            style="
                                color: var(--blanco, #fefeff);
                                text-align: left;
                                font-family: var(
                                    --cuerpo-de-texto-m-font-family,
                                    'Nexa-Regular',
                                    sans-serif
                                );
                                font-size: var(
                                    --cuerpo-de-texto-m-font-size,
                                    12px
                                );
                                line-height: var(
                                    --cuerpo-de-texto-m-line-height,
                                    18px
                                );
                                font-weight: var(
                                    --cuerpo-de-texto-m-font-weight,
                                    400
                                );
                                position: relative;
                            "
                        >
                            Email general:
                        </div>
                        <a
                            href="mailto:contacto@latitud90.com"
                            class="underline"
                            style="
                                color: var(--blanco, #fefeff);
                                text-align: left;
                                font-family: var(
                                    --cuerpo-de-texto-m-font-family,
                                    'Nexa-Bold',
                                    sans-serif
                                );
                                font-size: var(
                                    --cuerpo-de-texto-m-font-size,
                                    12px
                                );
                                line-height: var(
                                    --cuerpo-de-texto-m-line-height,
                                    18px
                                );
                                font-weight: var(
                                    --cuerpo-de-texto-m-font-weight,
                                    700
                                );
                                position: relative;
                            "
                        >
                            contacto@latitud90.com
                        </a>
                    </div>
                </div>

                <!-- Viajes de estudio Mobile -->
                <div
                    style="
                        display: flex;
                        flex-direction: row;
                        gap: 16px;
                        align-items: center;
                        justify-content: flex-start;
                        flex-shrink: 0;
                        position: relative;
                    "
                >
                    <div
                        style="
                            flex-shrink: 0;
                            width: 36px;
                            height: 36px;
                            position: relative;
                        "
                    >
                        <div
                            style="
                                background: #006e80;
                                border-radius: 51px;
                                width: 36px;
                                height: 36px;
                                position: absolute;
                                left: 0px;
                                top: 1.5px;
                            "
                        ></div>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="18"
                            height="19"
                            viewBox="0 0 18 19"
                            fill="none"
                            style="
                                width: 18px;
                                height: 18px;
                                position: absolute;
                                left: 9px;
                                top: 10.5px;
                                overflow: visible;
                            "
                        >
                            <path
                                d="M15.0297 2.01284C15.7917 1.74659 16.5237 2.47859 16.2575 3.24059L11.8137 15.9381C11.525 16.7616 10.3775 16.8081 10.0235 16.0108L7.87921 11.1868L10.8972 8.16809C10.9966 8.06146 11.0507 7.92043 11.0481 7.7747C11.0455 7.62898 10.9865 7.48994 10.8834 7.38688C10.7804 7.28382 10.6413 7.22478 10.4956 7.22221C10.3499 7.21964 10.2088 7.27373 10.1022 7.37309L7.08346 10.3911L2.25946 8.24685C1.46221 7.8921 1.50946 6.74534 2.33221 6.45659L15.0297 2.01284Z"
                                fill="#FEFEFF"
                            />
                        </svg>
                    </div>
                    <div
                        style="
                            display: flex;
                            flex-direction: column;
                            gap: 3px;
                            align-items: flex-start;
                            justify-content: center;
                            flex-shrink: 0;
                            position: relative;
                        "
                    >
                        <div
                            style="
                                color: var(--blanco, #fefeff);
                                text-align: left;
                                font-family: var(
                                    --cuerpo-de-texto-m-font-family,
                                    'Nexa-Regular',
                                    sans-serif
                                );
                                font-size: var(
                                    --cuerpo-de-texto-m-font-size,
                                    12px
                                );
                                line-height: var(
                                    --cuerpo-de-texto-m-line-height,
                                    18px
                                );
                                font-weight: var(
                                    --cuerpo-de-texto-m-font-weight,
                                    400
                                );
                                position: relative;
                            "
                        >
                            Viajes de estudio y CEAL:
                        </div>
                        <a
                            href="mailto:educacion@latitud90.com"
                            class="underline"
                            style="
                                color: var(--blanco, #fefeff);
                                text-align: left;
                                font-family: var(
                                    --cuerpo-de-texto-m-font-family,
                                    'Nexa-Bold',
                                    sans-serif
                                );
                                font-size: var(
                                    --cuerpo-de-texto-m-font-size,
                                    12px
                                );
                                line-height: var(
                                    --cuerpo-de-texto-m-line-height,
                                    18px
                                );
                                font-weight: var(
                                    --cuerpo-de-texto-m-font-weight,
                                    700
                                );
                                position: relative;
                            "
                        >
                            educacion@latitud90.com
                        </a>
                    </div>
                </div>

                <!-- Ubicación Mobile -->
                <div
                    style="
                        display: flex;
                        flex-direction: row;
                        gap: 16px;
                        align-items: center;
                        justify-content: flex-start;
                        flex-shrink: 0;
                        position: relative;
                    "
                >
                    <div
                        style="
                            flex-shrink: 0;
                            width: 36px;
                            height: 36px;
                            position: relative;
                        "
                    >
                        <div
                            style="
                                background: #006e80;
                                border-radius: 51px;
                                width: 36px;
                                height: 36px;
                                position: absolute;
                                left: 0px;
                                top: 1.5px;
                            "
                        ></div>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="19"
                            height="19"
                            viewBox="0 0 19 19"
                            fill="none"
                            style="
                                width: 18px;
                                height: 18px;
                                position: absolute;
                                left: 9px;
                                top: 10.5px;
                                overflow: visible;
                            "
                        >
                            <path
                                d="M7.41699 5.17497V15.8662M11.917 2.65497V13.3462M2.91699 6.71397C2.91699 5.56872 2.91699 4.99647 3.21174 4.66272C3.31599 4.54347 3.44274 4.44897 3.58449 4.38372C4.64649 3.89397 6.03849 5.18322 7.16199 5.14347C7.31049 5.13797 7.45724 5.11747 7.60224 5.08197C9.24024 4.68072 10.2662 2.77197 11.9507 2.53947C12.916 2.40447 13.9757 2.98797 14.8787 3.29997C15.6212 3.55647 15.9925 3.68472 16.2047 3.98997C16.417 4.29522 16.417 4.70172 16.417 5.51172V11.8282C16.417 12.9727 16.417 13.5457 16.1222 13.8795C16.0187 13.9972 15.8918 14.0919 15.7495 14.1577C14.6875 14.6475 13.2955 13.359 12.172 13.3987C12.0236 13.4044 11.8761 13.4247 11.7317 13.4595C10.0937 13.8607 9.06774 15.7695 7.38324 16.0027C6.42249 16.1362 3.74799 15.4402 3.12924 14.5515C2.91699 14.2462 2.91699 13.8412 2.91699 13.0297V6.71397Z"
                                stroke="#FEFEFF"
                                stroke-width="1.125"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>
                    <div
                        style="
                            display: flex;
                            flex-direction: column;
                            gap: 3px;
                            align-items: flex-start;
                            justify-content: center;
                            flex-shrink: 0;
                            position: relative;
                        "
                    >
                        <div
                            style="
                                color: var(--blanco, #fefeff);
                                text-align: left;
                                font-family: var(
                                    --cuerpo-de-texto-m-font-family,
                                    'Nexa-Regular',
                                    sans-serif
                                );
                                font-size: var(
                                    --cuerpo-de-texto-m-font-size,
                                    12px
                                );
                                line-height: var(
                                    --cuerpo-de-texto-m-line-height,
                                    18px
                                );
                                font-weight: var(
                                    --cuerpo-de-texto-m-font-weight,
                                    400
                                );
                                position: relative;
                            "
                        >
                            Ubicación
                        </div>
                        <div
                            style="
                                color: var(--blanco, #fefeff);
                                text-align: left;
                                font-family: var(
                                    --cuerpo-de-texto-m-font-family,
                                    'Nexa-Bold',
                                    sans-serif
                                );
                                font-size: var(
                                    --cuerpo-de-texto-m-font-size,
                                    12px
                                );
                                line-height: var(
                                    --cuerpo-de-texto-m-line-height,
                                    18px
                                );
                                font-weight: var(
                                    --cuerpo-de-texto-m-font-weight,
                                    700
                                );
                                position: relative;
                            "
                        >
                            Av Carlos Antúnez 1941, Providencia
                        </div>
                    </div>
                </div>

                <!-- Tienes dudas Mobile -->
                <div
                    style="
                        display: flex;
                        flex-direction: row;
                        gap: 16px;
                        align-items: center;
                        justify-content: flex-start;
                        flex-shrink: 0;
                        position: relative;
                    "
                >
                    <div
                        style="
                            flex-shrink: 0;
                            width: 36px;
                            height: 36px;
                            position: relative;
                        "
                    >
                        <div
                            style="
                                background: #006e80;
                                border-radius: 51px;
                                width: 36px;
                                height: 36px;
                                position: absolute;
                                left: 0px;
                                top: 1.5px;
                            "
                        ></div>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="18"
                            height="19"
                            viewBox="0 0 18 19"
                            fill="none"
                            style="
                                width: 18px;
                                height: 18px;
                                position: absolute;
                                left: 9px;
                                top: 10.5px;
                                overflow: visible;
                            "
                        >
                            <path
                                d="M5.91235 6.77878C5.91081 6.81511 5.91672 6.85137 5.92971 6.88534C5.9427 6.9193 5.9625 6.95025 5.9879 6.97627C6.01329 7.0023 6.04374 7.02286 6.07738 7.03668C6.11101 7.0505 6.14712 7.0573 6.18347 7.05666H7.1116C7.26685 7.05666 7.3906 6.92953 7.41085 6.77541C7.5121 6.03741 8.01835 5.49966 8.9206 5.49966C9.69235 5.49966 10.3988 5.88553 10.3988 6.81366C10.3988 7.52803 9.9781 7.85653 9.31322 8.35603C8.5561 8.90616 7.95647 9.54853 7.99922 10.5914L8.0026 10.8355C8.00378 10.9093 8.03393 10.9797 8.08655 11.0315C8.13917 11.0833 8.21003 11.1123 8.28385 11.1123H9.19622C9.27082 11.1123 9.34235 11.0826 9.3951 11.0299C9.44784 10.9772 9.47747 10.9056 9.47747 10.831V10.7129C9.47747 9.90516 9.7846 9.67003 10.6137 9.04116C11.2988 8.52028 12.0132 7.94203 12.0132 6.72816C12.0132 5.02828 10.5777 4.20703 9.0061 4.20703C7.58072 4.20703 6.01922 4.87078 5.91235 6.77878ZM7.66397 13.2622C7.66397 13.8618 8.1421 14.305 8.80022 14.305C9.48535 14.305 9.95672 13.8618 9.95672 13.2622C9.95672 12.6412 9.48422 12.2047 8.7991 12.2047C8.1421 12.2047 7.66397 12.6412 7.66397 13.2622Z"
                                fill="white"
                            />
                        </svg>
                    </div>
                    <div
                        style="
                            display: flex;
                            flex-direction: column;
                            gap: 3px;
                            align-items: flex-start;
                            justify-content: center;
                            flex-shrink: 0;
                            position: relative;
                        "
                    >
                        <div
                            style="
                                color: var(--blanco, #fefeff);
                                text-align: left;
                                font-family: var(
                                    --cuerpo-de-texto-m-font-family,
                                    'Nexa-Regular',
                                    sans-serif
                                );
                                font-size: var(
                                    --cuerpo-de-texto-m-font-size,
                                    12px
                                );
                                line-height: var(
                                    --cuerpo-de-texto-m-line-height,
                                    18px
                                );
                                font-weight: var(
                                    --cuerpo-de-texto-m-font-weight,
                                    400
                                );
                                position: relative;
                            "
                        >
                            Tienes dudas?
                        </div>
                        <a
                            href="#"
                            class="underline"
                            style="
                                color: var(--blanco, #fefeff);
                                text-align: left;
                                font-family: var(
                                    --cuerpo-de-texto-m-font-family,
                                    'Nexa-Bold',
                                    sans-serif
                                );
                                font-size: var(
                                    --cuerpo-de-texto-m-font-size,
                                    12px
                                );
                                line-height: var(
                                    --cuerpo-de-texto-m-line-height,
                                    18px
                                );
                                font-weight: var(
                                    --cuerpo-de-texto-m-font-weight,
                                    700
                                );
                                position: relative;
                            "
                        >
                            Haz click aqui
                        </a>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom Mobile -->
            <div
                style="
                    display: flex;
                    flex-direction: column;
                    gap: 27px;
                    align-items: center;
                    justify-content: center;
                    align-self: stretch;
                    flex-shrink: 0;
                    position: relative;
                    width: 100%;
                "
            >
                <div
                    style="
                        border: 1px solid var(--colores-neutro-blanco, #ffffff);
                        border-width: 1px 0px 0px 0px;
                        padding: 23px 0px 0px 0px;
                        display: flex;
                        flex-direction: column;
                        gap: 18px;
                        align-items: center;
                        justify-content: center;
                        align-self: stretch;
                        flex-shrink: 0;
                        position: relative;
                        width: 100%;
                    "
                >
                    <div
                        style="
                            border-radius: 45px;
                            display: flex;
                            flex-direction: row;
                            gap: 16px;
                            align-items: center;
                            justify-content: flex-end;
                            flex-shrink: 0;
                            position: relative;
                        "
                    >
                        <div
                            style="
                                display: flex;
                                flex-direction: row;
                                gap: 16px;
                                align-items: flex-start;
                                justify-content: flex-start;
                                flex-shrink: 0;
                                position: relative;
                            "
                        >
                            <a
                                href="#"
                                class="hover:opacity-80 transition-opacity"
                            >
                                <img
                                    :src="images['facebook-icon']"
                                    alt="Facebook"
                                    style="
                                        border-radius: 66px;
                                        flex-shrink: 0;
                                        width: 24px;
                                        height: 24px;
                                        position: relative;
                                        overflow: visible;
                                    "
                                />
                            </a>
                            <a
                                href="#"
                                class="hover:opacity-80 transition-opacity"
                            >
                                <img
                                    :src="images['twitterIcon']"
                                    alt="Twitter"
                                    style="
                                        border-radius: 66px;
                                        flex-shrink: 0;
                                        width: 24px;
                                        height: 24px;
                                        position: relative;
                                        overflow: visible;
                                    "
                                />
                            </a>
                            <a
                                href="#"
                                class="hover:opacity-80 transition-opacity"
                            >
                                <img
                                    :src="images['instagram-icon']"
                                    alt="Instagram"
                                    style="
                                        border-radius: 66px;
                                        flex-shrink: 0;
                                        width: 24px;
                                        height: 24px;
                                        position: relative;
                                        overflow: visible;
                                    "
                                />
                            </a>
                            <a
                                href="#"
                                class="hover:opacity-80 transition-opacity"
                            >
                                <img
                                    :src="images['linkedin-icon']"
                                    alt="LinkedIn"
                                    style="
                                        border-radius: 66px;
                                        flex-shrink: 0;
                                        width: 24px;
                                        height: 24px;
                                        position: relative;
                                        overflow: hidden;
                                    "
                                />
                            </a>
                            <a
                                href="#"
                                class="hover:opacity-80 transition-opacity"
                            >
                                <img
                                    :src="images['youtubeIcon']"
                                    alt="YouTube"
                                    style="
                                        border-radius: 66px;
                                        flex-shrink: 0;
                                        width: 24px;
                                        height: 24px;
                                        position: relative;
                                        overflow: hidden;
                                    "
                                />
                            </a>
                        </div>
                    </div>
                    <div
                        style="
                            color: var(--blanco, #fefeff);
                            text-align: center;
                            font-family: 'Nexa-Regular', sans-serif;
                            font-size: 16px;
                            line-height: 22px;
                            font-weight: 400;
                            position: relative;
                            align-self: stretch;
                        "
                    >
                        Copyright © 2025 Latiud90
                        <br />
                        <a href="/terminos-y-condiciones" class="underline"
                            >Términos y condiciones</a
                        >
                    </div>
                </div>
            </div>
        </div>
    </footer>
</template>

<script setup>
import { ref } from 'vue';
import images from "@images/index.js";

const newsletterEmail = ref('');
const isSubscribing = ref(false);

const emit = defineEmits(['newsletter-subscribed', 'newsletter-error']);

const subscribeToNewsletter = async () => {
    if (!newsletterEmail.value || isSubscribing.value) return;
    
    isSubscribing.value = true;
    
    try {
        const response = await fetch('/newsletter/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                email: newsletterEmail.value
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            newsletterEmail.value = '';
            // Emitir evento de éxito
            emit('newsletter-subscribed');
        } else {
            // Emitir evento de error
            emit('newsletter-error');
        }
    } catch (error) {
        // Emitir evento de error
        emit('newsletter-error');
    } finally {
        isSubscribing.value = false;
    }
};
</script>

<style scoped>
/* Desktop Footer */
.desktop-footer {
    display: block !important;
}

.mobile-footer {
    display: none !important;
}

/* Mobile Footer */
@media (max-width: 1023px) {
    .desktop-footer {
        display: none !important;
    }

    .mobile-footer {
        display: flex !important;
    }
}
</style>
