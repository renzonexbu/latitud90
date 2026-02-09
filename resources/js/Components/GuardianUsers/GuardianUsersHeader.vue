<template>
    <div class="py-5 flex flex-row items-center justify-between relative">
        <!-- Left side - Title -->
        <div class="flex flex-row gap-4 items-center justify-start flex-shrink-0 relative">
            <div class="text-[30px] leading-9 font-normal text-turquesa font-nexa-xbold">
                Usuarios Registrados
            </div>
            <div class="w-0 h-[35px] relative overflow-visible">
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
    name: "GuardianUsersHeader",
    props: {
        subtitle: {
            type: String,
            default: "Gestión de apoderados del portal de pagos"
        },
    },
};
</script>
