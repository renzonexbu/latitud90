<template>
    <svg 
        xmlns="http://www.w3.org/2000/svg" 
        :width="width" 
        :height="height" 
        viewBox="0 0 12 12" 
        fill="none"
        :class="computedClass"
    >
        <path 
            d="M10 2L1.03125 10.9688M1.03125 2L10 10.9688" 
            :stroke="strokeColor" 
            :stroke-width="strokeWidth" 
            stroke-linecap="round" 
            stroke-linejoin="round"
        />
    </svg>
</template>

<script>
export default {
    name: "CrossIcon",
    props: {
        width: {
            type: [String, Number],
            default: "8.969"
        },
        height: {
            type: [String, Number],
            default: "8.969"
        },
        strokeColor: {
            type: String,
            default: "#FFF"
        },
        strokeWidth: {
            type: [String, Number],
            default: "2.07"
        },
        class: {
            type: String,
            default: ""
        }
    },
    computed: {
        computedClass() {
            return `cursor-pointer hover:opacity-75 transition-opacity ${this.class}`;
        }
    }
};
</script>