<template>
    <div class="py-5 flex flex-row items-center justify-between relative">
        <!-- Left side - Breadcrumb and title -->
        <div class="flex flex-row gap-[14.37px] items-center justify-start flex-shrink-0 relative">
            <div class="text-[24px] leading-[28px] font-bold text-turquesa font-nexa-bold">
                Mantenedor
            </div>
            <div class="w-0 h-[29.59px] relative overflow-visible">
                <!-- Vector separator -->
                <div class="w-px h-full bg-gray-300"></div>
            </div>
            <div class="text-[16px] leading-[22px] font-normal text-[#5b5b5b] font-nexa-regular">
                {{ subtitle }}
            </div>
            
            <!-- Additional info separator and details (only show if additionalInfo is provided) -->
            <template v-if="additionalInfo">
                <div class="w-0 h-[29.59px] relative overflow-visible">
                    <!-- Vector separator -->
                    <div class="w-px h-full bg-gray-300"></div>
                </div>
                <div class="text-[16px] leading-[22px] font-normal text-turquesa font-nexa-regular">
                    {{ additionalInfo }}
                </div>
            </template>
        </div>

        <!-- Right side - Action buttons -->
        <div v-if="showActionButton" class="flex flex-row items-center justify-end flex-shrink-0 relative">
            <!-- Action Button Container -->
            <div class="relative">
                <!-- Action Button -->
                <button
                    @click="handleAction"
                    class="bg-turquesa rounded-[112.89px] px-[18px] py-[14px] flex flex-row gap-[11.29px] items-center justify-center hover:bg-turquesa-dark transition-colors"
                >
                    <div class="flex-shrink-0 w-[21.52px] h-[21.52px] relative overflow-hidden aspect-square">
                        <SettingsIcon 
                            class="w-full h-full"
                            stroke-color="white"
                        />
                    </div>
                    <div class="text-white text-left font-nexa-bold text-base leading-[22px] font-bold relative flex items-end justify-start">
                        {{ actionButtonText }}
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { SettingsIcon } from "@/Components/Icons";

export default {
    name: "MaintainerHeader",
    components: {
        SettingsIcon,
    },
    props: {
        subtitle: {
            type: String,
            default: "Panel de administración avanzada"
        },

        showActionButton: {
            type: Boolean,
            default: false
        },

        actionButtonText: {
            type: String,
            default: "Acción del Sistema"
        },

        additionalInfo: {
            type: String,
            default: null
        }
    },
    methods: {
        handleAction() {
            this.$emit('action');
        }
    }
};
</script>
