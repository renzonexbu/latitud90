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
    </div>
</template>

<script>
export default {
    name: "ReportsHeader",
    props: {
        title: {
            type: String,
            default: "Reporte"
        },
        subtitle: {
            type: String,
            default: "Estado de Cuenta Parcial"
        }
    }
};
</script>
