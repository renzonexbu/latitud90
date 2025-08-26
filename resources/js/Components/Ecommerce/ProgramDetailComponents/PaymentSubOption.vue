<template>
    <div
        class="bg-white rounded-lg border border-[#D3D3D3] px-4 py-3 w-full self-stretch"
        :class="{
            'border-[#1C4F4A]': isSelected,
        }"
    >
        <div class="flex flex-row gap-3 items-start">
            <!-- Radio Button -->
            <div
                class="rounded-[27.12px] border-[0.44px] border-[#1C4F4A] p-[3.5px] cursor-pointer"
                @click="$emit('select')"
            >
                <div
                    class="rounded-[18.38px] p-[4.38px] w-[7px] h-[7px]"
                    :class="{
                        'bg-[#FAB547] opacity-100': isSelected,
                        'opacity-0': !isSelected,
                    }"
                ></div>
            </div>

            <!-- Content -->
            <div class="flex flex-col gap-2 flex-1 min-w-0">
                <div
                    class="text-[#1C4F4A] font-nexa text-sm leading-[18px] whitespace-normal break-words"
                >
                    <span class="font-nexa font-bold">{{ option.label }}</span>
                </div>

                <!-- Description (if exists) -->
                <div
                    v-if="option.description"
                    class="text-[#007E93] font-nexa text-xs leading-[13px] whitespace-normal break-words max-w-full"
                    v-html="formatDescription(option.description)"
                ></div>

                <!-- Warning Message (if exists) -->
                <div
                    v-if="option.warning"
                    class="flex flex-row gap-[8px] items-center"
                >
                    <!-- Warning SVG -->
                    <svg
                        class="w-[17.5px] h-[17.5px] flex-shrink-0"
                        width="19"
                        height="18"
                        viewBox="0 0 19 18"
                        fill="none"
                    >
                        <path
                            d="M9.44049 6.19792V10.2083M9.44049 12.5759V12.2114M5.05091 7.84729C6.96716 4.07385 7.92383 2.1875 9.44049 2.1875C10.9572 2.1875 11.9146 4.07385 13.8301 7.84729L14.0685 8.31688C15.6596 11.4523 16.4558 13.02 15.7361 14.1662C15.0172 15.3125 13.238 15.3125 9.67893 15.3125H9.20206C5.64372 15.3125 3.86383 15.3125 3.14487 14.1662C2.42591 13.02 3.22143 11.4523 4.81247 8.31688L5.05091 7.84729Z"
                            stroke="#D54B44"
                            stroke-width="1.09375"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>

                    <!-- Warning Text -->
                    <span
                        class="text-[#D54A42] font-nexa text-xs leading-[13px] font-extrabold mt-[5px]"
                    >
                        {{ option.warning }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "PaymentSubOption",
    props: {
        option: {
            type: Object,
            required: true,
            validator: (value) => {
                return value.value && value.label;
            },
        },
        isSelected: {
            type: Boolean,
            default: false,
        },
    },
    emits: ["select"],
    methods: {
        formatDescription(description) {
            // Aplicar formato especial para "3, 6 o 12 cuotas sin interés"
            return description.replace(
                /(3, 6 o 12 cuotas sin interés)/g,
                '<span class="font-nexa font-bold">$1</span>'
            );
        },
    },
};
</script>
