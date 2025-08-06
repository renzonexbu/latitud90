<template>
    <svg 
        xmlns="http://www.w3.org/2000/svg" 
        width="242" 
        height="12" 
        viewBox="0 0 243 13" 
        fill="none"
        class="accordion-separator"
    >
        <path 
            d="M0.5 12L23.8121 3.1192C29.0858 1.11015 34.9142 1.11015 40.1879 3.1192L55.3121 8.8808C60.5858 10.8898 66.4142 10.8898 71.6879 8.8808L86.8121 3.1192C92.0858 1.11016 97.9141 1.11015 103.188 3.1192L117.805 8.68747C123.382 10.8122 129.567 10.6856 135.053 8.33459L145.596 3.8158C151.29 1.37572 157.727 1.33532 163.451 3.70374L175.415 8.6543C180.623 10.8094 186.441 10.977 191.765 9.12534L208.941 3.15101C214.738 1.1345 221.101 1.51901 226.614 4.21899L242.5 12" 
            stroke="#FFB232" 
            stroke-width="2"
        />
    </svg>
</template>

<script>
export default {
    name: "AccordionSeparator"
}
</script>

<style scoped>
.accordion-separator {
    width: 242px;
    height: 12px;
    flex-shrink: 0;
    stroke: var(--Colores-OP2-Amarillo, #FFB232);
    stroke-width: 2px;
}
</style> 