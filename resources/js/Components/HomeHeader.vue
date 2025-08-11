<template>
    <div class="w-full flex items-center justify-between p-4">
        <div class="flex items-center" :style="{ gap: '12px' }">
            <span
                :style="{
                    color: 'var(--Colores-OP2-Turquesa, #007E93)',
                    fontFamily: 'Nexa',
                    fontSize: 'var(--Numeros-Titulo, 30px)',
                    fontStyle: 'normal',
                    fontWeight: 800,
                    lineHeight: '36px',
                }"
            >
                Bienvenidos a Lat90
            </span>
            <span :style="subtitleStyle"> | </span>
            <span :style="subtitleStyle"> Home </span>
        </div>

        <div class="flex items-center" :style="{ gap: '11.289px' }">
            <button
                type="button"
                class="relative flex items-center justify-center p-[14px] rounded-[112.894px]"
                :style="{ background: 'var(--Colores-OP2-Turquesa, #007E93)' }"
                aria-label="Botón 1"
                @click="goToRoute('admin.programs.create')"
            >
                <span
                    class="absolute -top-1 -right-1 w-[21.289px] h-[21.289px] flex-shrink-0"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="22"
                        height="22"
                        viewBox="0 0 22 22"
                        fill="none"
                        class="w-[21.289px] h-[21.289px]"
                    >
                        <circle
                            cx="11.3553"
                            cy="10.6447"
                            r="10.6447"
                            fill="#FBBD51"
                        />
                        <path
                            d="M4.58154 9.49118H9.74326V4.36133H12.2923V9.49118H17.5177V11.9446H12.2923V17.2019H9.74326V11.9446H4.58154V9.49118Z"
                            fill="#F9F9F9"
                        />
                    </svg>
                </span>
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="23"
                    height="23"
                    viewBox="0 0 23 23"
                    fill="none"
                    class="w-[21.517px] h-[21.517px]"
                >
                    <path
                        d="M14.2803 6.08947H8.65934M14.2803 6.08947C16.9297 6.08947 18.2553 6.08947 19.0779 6.91295C19.9014 7.73549 19.9014 9.06111 19.9014 11.7105V14.0526C19.9014 16.7019 19.9014 18.0276 19.0779 18.8501C18.2553 19.6736 16.9297 19.6736 14.2803 19.6736H8.65934C6.00997 19.6736 4.68435 19.6736 3.86181 18.8501C3.03833 18.0276 3.03833 16.7019 3.03833 14.0526V11.7105C3.03833 9.06111 3.03833 7.73549 3.86181 6.91295C4.68435 6.08947 6.00997 6.08947 8.65934 6.08947M14.2803 6.08947V5.62105C14.2803 4.29543 14.2804 3.63309 13.8681 3.22275C13.4569 2.81055 12.7955 2.81055 11.4698 2.81055C10.1442 2.81055 9.48188 2.81055 9.07155 3.22275C8.65934 3.63402 8.65934 4.29637 8.65934 5.62105V6.08947"
                        stroke="white"
                        stroke-width="1.40525"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M19.9014 9.83621C18.9075 9.83621 17.9544 9.4414 17.2516 8.73864C16.5488 8.03587 16.154 7.08272 16.154 6.08887M3.03833 15.9256C4.03219 15.9256 4.98534 16.3204 5.6881 17.0232C6.39086 17.726 6.78567 18.6791 6.78567 19.673M3.03833 9.83621C4.03219 9.83621 4.98534 9.4414 5.6881 8.73864C6.39086 8.03587 6.78567 7.08272 6.78567 6.08887M19.9014 15.9256C18.9075 15.9256 17.9544 16.3204 17.2516 17.0232C16.5488 17.726 16.154 18.6791 16.154 19.673M13.8119 10.3046V10.314M8.89355 11.2415L8.19092 11.9441L8.89355 12.6467L9.59618 11.9441L8.89355 11.2415ZM13.3435 15.7383L11.9383 15.4572L13.0625 14.333L13.3435 15.7383Z"
                        stroke="white"
                        stroke-width="1.40525"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </button>

            <button
                type="button"
                class="relative flex items-center justify-center p-[14px] rounded-[112.894px]"
                :style="{ background: 'var(--Colores-OP2-Turquesa, #007E93)' }"
                aria-label="Botón 2"
                @click="
                    goToRouteWithParams('admin.courses.index', {
                        openCreate: 1,
                    })
                "
            >
                <span
                    class="absolute -top-1 -right-1 w-[21.289px] h-[21.289px] flex-shrink-0"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="22"
                        height="22"
                        viewBox="0 0 22 22"
                        fill="none"
                        class="w-[21.289px] h-[21.289px]"
                    >
                        <circle
                            cx="11.3553"
                            cy="10.6447"
                            r="10.6447"
                            fill="#FBBD51"
                        />
                        <path
                            d="M4.58154 9.49118H9.74326V4.36133H12.2923V9.49118H17.5177V11.9446H12.2923V17.2019H9.74326V11.9446H4.58154V9.49118Z"
                            fill="#F9F9F9"
                        />
                    </svg>
                </span>
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="23"
                    height="23"
                    viewBox="0 0 23 23"
                    fill="none"
                    class="w-[21.517px] h-[21.517px]"
                >
                    <g clip-path="url(#clip0_840_1694)">
                        <path
                            d="M11.4724 3.46482V7.55309M6.49574 12.7956V21.6515M11.4693 18.2718V21.716M16.4428 12.7956V21.6515M11.5661 5.1155C12.9417 5.21847 14.1789 5.21847 15.556 5.1155C15.6805 3.97816 15.6805 2.9484 15.556 1.8126C14.228 1.7109 12.8941 1.7109 11.5661 1.8126C11.4412 2.91002 11.4412 4.01807 11.5661 5.1155ZM6.46192 13.3351C5.31229 13.3612 4.59453 13.3966 3.41723 13.4396C2.55501 13.4719 1.82496 14.0943 1.70815 14.9443C1.4023 17.1867 1.4023 17.8061 1.70815 20.0485C1.82496 20.8984 2.55347 21.5209 3.41723 21.5531C9.22689 21.7698 13.7102 21.7698 19.5214 21.5531C20.3836 21.5193 21.1136 20.8984 21.2289 20.0485C21.5363 17.8061 21.5363 17.1867 21.2289 14.9443C21.1136 14.0943 20.3836 13.4719 19.5229 13.4396C18.3441 13.3966 17.6324 13.3612 16.4828 13.3351C16.4586 12.8522 16.4294 12.3696 16.3952 11.8873C16.1216 6.11605 6.82464 6.10068 6.55107 11.8781C6.51623 12.3771 6.48549 12.8627 6.46192 13.3351Z"
                            stroke="white"
                            stroke-width="1.53695"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                        <path
                            d="M11.4692 14.1728C12.6419 14.1728 13.3013 13.5166 13.3013 12.3516C13.3013 11.1865 12.6404 10.5303 11.4692 10.5303C10.2966 10.5303 9.63721 11.1865 9.63721 12.3516C9.63721 13.5166 10.2966 14.1728 11.4692 14.1728Z"
                            stroke="white"
                            stroke-width="1.53695"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </g>
                    <defs>
                        <clipPath id="clip0_840_1694">
                            <rect
                                width="21.5172"
                                height="21.5172"
                                fill="white"
                                transform="translate(0.710693 0.966797)"
                            />
                        </clipPath>
                    </defs>
                </svg>
            </button>

            <button
                type="button"
                class="relative flex items-center justify-center p-[14px] rounded-[112.894px]"
                :style="{ background: 'var(--Colores-OP2-Turquesa, #007E93)' }"
                aria-label="Botón 3"
                @click="
                    goToRouteWithParams('admin.participants.index', {
                        openCreate: 1,
                    })
                "
            >
                <span
                    class="absolute -top-1 -right-1 w-[21.289px] h-[21.289px] flex-shrink-0"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="22"
                        height="22"
                        viewBox="0 0 22 22"
                        fill="none"
                        class="w-[21.289px] h-[21.289px]"
                    >
                        <circle
                            cx="11.3553"
                            cy="10.6447"
                            r="10.6447"
                            fill="#FBBD51"
                        />
                        <path
                            d="M4.58154 9.49118H9.74326V4.36133H12.2923V9.49118H17.5177V11.9446H12.2923V17.2019H9.74326V11.9446H4.58154V9.49118Z"
                            fill="#F9F9F9"
                        />
                    </svg>
                </span>
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="23"
                    height="23"
                    viewBox="0 0 23 23"
                    fill="none"
                    class="w-[21.517px] h-[21.517px]"
                >
                    <path
                        d="M8.96201 12.3999C10.9871 12.3999 12.6287 10.7582 12.6287 8.73313C12.6287 6.70805 10.9871 5.06641 8.96201 5.06641C6.93694 5.06641 5.29529 6.70805 5.29529 8.73313C5.29529 10.7582 6.93694 12.3999 8.96201 12.3999Z"
                        stroke="white"
                        stroke-width="1.83336"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M15.3788 17.8999C15.3788 14.8621 12.5059 12.3999 8.96205 12.3999C5.41817 12.3999 2.54529 14.8621 2.54529 17.8999M14.4621 12.3999C15.0748 12.3998 15.6778 12.2463 16.2158 11.9532C16.7539 11.6601 17.2099 11.2369 17.5423 10.7222C17.8746 10.2075 18.0727 9.61767 18.1184 9.00668C18.1642 8.39569 18.056 7.78299 17.8039 7.22455C17.5519 6.66611 17.1639 6.17974 16.6754 5.80988C16.1869 5.44001 15.6136 5.19845 15.0077 5.10725C14.4018 5.01606 13.7828 5.07813 13.2071 5.28782C12.6314 5.4975 12.1174 5.8481 11.7121 6.30759"
                        stroke="white"
                        stroke-width="1.83336"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M20.879 17.9007C20.879 14.8629 18.0061 12.4007 14.4622 12.4007C13.7224 12.4007 12.5344 12.1321 11.7122 11.2686"
                        stroke="white"
                        stroke-width="1.83336"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </button>
        </div>
    </div>
</template>

<script>
import { router } from "@inertiajs/vue3";

export default {
    name: "HomeHeader",
    computed: {
        subtitleStyle() {
            return {
                color: "var(--Colores-Neutro-Gris-4, #5B5B5B)",
                fontFamily: "Nexa",
                fontSize: "var(--Numeros-Subtitulo-S, 20px)",
                fontStyle: "normal",
                fontWeight: 400,
                lineHeight: "28px",
            };
        },
    },
    methods: {
        goToRoute(name) {
            try {
                router.visit(route(name));
            } catch (e) {
                // fallback simple
                router.visit("/");
            }
        },
        goToRouteWithParams(name, params) {
            try {
                router.visit(route(name, params));
            } catch (e) {
                router.visit(route(name));
            }
        },
    },
};
</script>
