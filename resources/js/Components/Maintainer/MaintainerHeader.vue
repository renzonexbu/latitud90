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

        <!-- Right side - Back button -->
        <div v-if="showBackButton" class="flex flex-row items-center justify-end flex-shrink-0 relative">
            <!-- Back Button Container -->
            <div class="relative">
                <!-- Back Button -->
                <button
                    @click="goBackToIndex"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-[112.89px] px-[18px] py-[14px] flex flex-row gap-[11.29px] items-center justify-center transition-colors border border-gray-300"
                >
                    <div class="flex-shrink-0 w-[21.52px] h-[21.52px] relative overflow-hidden aspect-square">
                        <svg class="w-full h-full text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </div>
                    <div class="text-gray-700 text-left font-nexa-bold text-base leading-[22px] font-bold relative flex items-end justify-start">
                        Volver
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "MaintainerHeader",
    props: {
        subtitle: {
            type: String,
            default: "Panel de administración avanzada"
        },

        additionalInfo: {
            type: String,
            default: null
        },

        showBackButton: {
            type: Boolean,
            default: false
        }
    },
    methods: {
        goBackToIndex() {
            this.$inertia.visit(route('admin.maintainer.index'));
        }
    }
};
</script>
