<template>
    <div class="bg-[#F9F9F9] rounded-[13.61px] p-[20px] overflow-hidden">
        <div class="flex flex-col gap-[24px]">
            <!-- Header with Radio Button, Title and SVG -->
            <div class="flex flex-row items-center justify-between">
                <div class="flex flex-row items-center gap-[20px]">
                    <!-- Radio Button -->
                    <div class="flex-shrink-0">
                        <div
                            class="rounded-full border-[0.71px] border-[#1C4F4A] p-[5.67px] cursor-pointer w-[22.69px] h-[22.69px] flex items-center justify-center"
                            @click="$emit('select')"
                        >
                            <div
                                class="bg-[#FAB547] rounded-full w-[11.35px] h-[11.35px]"
                                :class="{
                                    'opacity-100': isSelected,
                                    'opacity-0': !isSelected,
                                }"
                            ></div>
                        </div>
                    </div>

                    <!-- Title -->
                    <h3
                        class="text-[#434343] font-outfit-medium text-[20px] leading-[69.69px] font-medium"
                    >
                        {{ title }}
                    </h3>
                </div>

                <!-- Info Circle SVG -->
                <div class="flex-shrink-0">
                    <svg
                        class="w-[18px] h-[18px]"
                        width="18"
                        height="18"
                        viewBox="0 0 18 18"
                        fill="none"
                    >
                        <path
                            d="M9 16.5C13.1421 16.5 16.5 13.1421 16.5 9C16.5 4.85786 13.1421 1.5 9 1.5C4.85786 1.5 1.5 4.85786 1.5 9C1.5 13.1421 4.85786 16.5 9 16.5Z"
                            stroke="#434343"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                        <path
                            d="M9 12V9"
                            stroke="#434343"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                        <path
                            d="M8.99219 6H9.00019"
                            stroke="#434343"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </div>
            </div>

            <!-- Accordion Content -->
            <transition name="accordion-slide">
                <div v-if="isAccordionOpen" class="flex flex-col gap-[9px]">
                    <slot name="accordion-content"></slot>
                </div>
            </transition>
        </div>
    </div>
</template>

<script>
export default {
    name: "PaymentOption",
    props: {
        isSelected: {
            type: Boolean,
            default: false,
        },
        isAccordionOpen: {
            type: Boolean,
            default: false,
        },
        title: {
            type: String,
            required: true,
        },
    },
    emits: ["select"],
};
</script>

<style scoped>
.accordion-slide-enter-active,
.accordion-slide-leave-active {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    transform-origin: top;
    overflow: visible;
}

.accordion-slide-enter-from {
    opacity: 0;
    max-height: 0;
    transform: translateY(-20px) scaleY(0.8);
    padding-top: 0;
    padding-bottom: 0;
}

.accordion-slide-enter-to {
    opacity: 1;
    max-height: 5000px;
    transform: translateY(0) scaleY(1);
    padding-top: 20px;
    padding-bottom: 30px;
}

.accordion-slide-leave-from {
    opacity: 1;
    max-height: 5000px;
    transform: translateY(0) scaleY(1);
    padding-top: 20px;
    padding-bottom: 30px;
}

.accordion-slide-leave-to {
    opacity: 0;
    max-height: 0;
    transform: translateY(-20px) scaleY(0.8);
    padding-top: 0;
    padding-bottom: 0;
}
</style>
