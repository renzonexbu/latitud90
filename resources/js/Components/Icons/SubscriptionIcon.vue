<template>
    <svg
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
    >
        <!-- Icono de calendario con flechas de renovación -->
        <!-- Rectángulo del calendario -->
        <rect
            x="3"
            y="5"
            width="18"
            height="16"
            rx="2"
            :stroke="strokeColor"
            stroke-width="2"
            fill="none"
        />
        <!-- Línea superior del calendario -->
        <line
            x1="3"
            y1="9"
            x2="21"
            y2="9"
            :stroke="strokeColor"
            stroke-width="2"
        />
        <!-- Ganchos superiores -->
        <line
            x1="7"
            y1="3"
            x2="7"
            y2="7"
            :stroke="strokeColor"
            stroke-width="2"
            stroke-linecap="round"
        />
        <line
            x1="17"
            y1="3"
            x2="17"
            y2="7"
            :stroke="strokeColor"
            stroke-width="2"
            stroke-linecap="round"
        />
        <!-- Símbolo de renovación circular en el centro -->
        <path
            d="M8 14C8 12.3431 9.34315 11 11 11H13C14.6569 11 16 12.3431 16 14C16 15.6569 14.6569 17 13 17H12"
            :stroke="strokeColor"
            stroke-width="1.5"
            stroke-linecap="round"
        />
        <!-- Flecha -->
        <path
            d="M10 17L12 19L10 21"
            :stroke="strokeColor"
            stroke-width="1.5"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
    </svg>
</template>

<script>
export default {
    name: "SubscriptionIcon",
    props: {
        size: {
            type: [String, Number],
            default: "24",
        },
        strokeColor: {
            type: String,
            default: "currentColor",
        },
    },
};
</script>
