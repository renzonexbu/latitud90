<template>
    <svg 
        xmlns="http://www.w3.org/2000/svg" 
        :width="width" 
        :height="height" 
        viewBox="0 0 23 22" 
        fill="none"
        :class="class"
        :style="{ aspectRatio: '1/1' }"
    >
        <path 
            d="M8.67106 11.916C10.6961 11.916 12.3378 10.2743 12.3378 8.24924C12.3378 6.22417 10.6961 4.58252 8.67106 4.58252C6.64598 4.58252 5.00433 6.22417 5.00433 8.24924C5.00433 10.2743 6.64598 11.916 8.67106 11.916Z" 
            :stroke="strokeColor" 
            :stroke-width="strokeWidth" 
            stroke-linecap="round" 
            stroke-linejoin="round"
        />
        <path 
            d="M15.0878 17.416C15.0878 14.3782 12.2149 11.916 8.67104 11.916C5.12715 11.916 2.25427 14.3782 2.25427 17.416M14.1711 11.916C14.7838 11.9159 15.3867 11.7624 15.9248 11.4693C16.4629 11.1762 16.9189 10.753 17.2513 10.2383C17.5836 9.72358 17.7817 9.13378 17.8274 8.52279C17.8731 7.9118 17.765 7.2991 17.5129 6.74066C17.2609 6.18222 16.8729 5.69585 16.3844 5.32599C15.8959 4.95613 15.3226 4.71456 14.7167 4.62337C14.1108 4.53217 13.4918 4.59425 12.9161 4.80393C12.3404 5.01361 11.8264 5.36422 11.4211 5.82371" 
            :stroke="strokeColor" 
            :stroke-width="strokeWidth" 
            stroke-linecap="round" 
            stroke-linejoin="round"
        />
        <path 
            d="M20.5879 17.4164C20.5879 14.3785 17.715 11.9163 14.1711 11.9163C13.4314 11.9163 12.2433 11.6477 11.4211 10.7842" 
            :stroke="strokeColor" 
            :stroke-width="strokeWidth" 
            stroke-linecap="round" 
            stroke-linejoin="round"
        />
    </svg>
</template>

<script>
export default {
    name: "ParticipantIcon",
    props: {
        width: {
            type: [String, Number],
            default: 22
        },
        height: {
            type: [String, Number],
            default: 22
        },
        strokeColor: {
            type: String,
            default: "white"
        },
        strokeWidth: {
            type: [String, Number],
            default: "1.83336"
        },
        class: {
            type: String,
            default: ""
        }
    }
};
</script>