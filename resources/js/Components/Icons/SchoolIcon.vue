<template>
    <svg 
        :width="width" 
        :height="height" 
        viewBox="0 0 24 24" 
        fill="none" 
        xmlns="http://www.w3.org/2000/svg"
        :class="className"
    >
        <path 
            d="M12 3L1 9L12 15L21 10.09V17H23V9M5 13.18V17.18L12 21L19 17.18V13.18L12 17L5 13.18Z" 
            :fill="fillColor"
            :stroke="strokeColor"
            :stroke-width="strokeWidth"
        />
    </svg>
</template>

<script>
export default {
    name: "SchoolIcon",
    props: {
        width: {
            type: [String, Number],
            default: 24
        },
        height: {
            type: [String, Number],
            default: 24
        },
        fillColor: {
            type: String,
            default: "currentColor"
        },
        strokeColor: {
            type: String,
            default: "none"
        },
        strokeWidth: {
            type: [String, Number],
            default: 0
        },
        className: {
            type: String,
            default: ""
        }
    }
};
</script>
