<template>
    <div class="py-5 flex flex-row items-center justify-between relative">
        <!-- Left side - Breadcrumb and title -->
        <div class="flex flex-row gap-4 items-center justify-start flex-shrink-0 relative">
            <div class="text-[30px] leading-9 font-normal text-turquesa font-nexa-xbold">
                {{ title }}
            </div>
            <div class="w-0 h-[35px] relative overflow-visible">
                <!-- Vector separator -->
                <div class="w-px h-full bg-gray-300"></div>
            </div>
            <div class="text-[20px] leading-7 font-normal text-[#5b5b5b] font-nexa-regular">
                {{ subtitle }}
            </div>
        </div>

        <!-- Right side - Action buttons -->
        <div class="flex flex-row items-center justify-end flex-shrink-0 relative gap-4">
            <!-- Export Button -->
            <div v-if="showExportButton" class="relative">
                <button
                    @click="handleExport"
                    class="bg-green-500 hover:bg-green-700 rounded-[112.89px] px-[18px] py-[14px] flex flex-row gap-[11.29px] items-center justify-center transition-colors"
                >
                    <div 
                        class="flex-shrink-0 relative overflow-hidden"
                        style="width: 22px; height: 22px; aspect-ratio: 1/1;"
                    >
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                            <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M7 10L12 15L17 10" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 15V3" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="text-white text-left font-nexa-bold text-base leading-[22px] font-bold relative flex items-end justify-start">
                        Exportar reporte
                    </div>
                </button>
            </div>

            <!-- Back to Reports Button -->
            <button
                @click="handleGoBack"
                class="bg-gray-500 hover:bg-gray-700 rounded-[112.89px] px-[18px] py-[14px] flex flex-row gap-[11.29px] items-center justify-center transition-colors"
            >
                <div 
                    class="flex-shrink-0 relative overflow-hidden"
                    style="width: 22px; height: 22px; aspect-ratio: 1/1;"
                >
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                        <path d="M19 12H5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 19L5 12L12 5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="text-white text-left font-nexa-bold text-base leading-[22px] font-bold relative flex items-end justify-start">
                    Volver a Reportes
                </div>
            </button>
        </div>
    </div>
</template>

<script>
import { router } from "@inertiajs/vue3";

export default {
    name: "DailyPaymentsHeader",
    props: {
        title: {
            type: String,
            default: "Reporte de Pagos Diarios"
        },
        subtitle: {
            type: String,
            default: "Pagos realizados diariamente con información detallada"
        },
        showExportButton: {
            type: Boolean,
            default: true
        }
    },
    methods: {
        handleExport() {
            this.$emit('export-clicked');
        },
        handleGoBack() {
            router.visit(route('admin.reports.index'));
        }
    }
};
</script>

<style scoped>
.text-turquesa {
    color: #007e93;
}

.hover\:text-turquesa-dark:hover {
    color: #006b7d;
}
</style>
