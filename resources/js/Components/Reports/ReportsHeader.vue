<template>
    <div class="py-5 flex flex-row items-center justify-between relative">
        <!-- Left side - Breadcrumb and title -->
        <div class="flex flex-row gap-4 items-center justify-start flex-shrink-0 relative">
            <div class="text-[30px] leading-9 font-normal text-turquesa font-nexa-xbold">
                Reportes
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
        <div class="flex flex-row items-center justify-end flex-shrink-0 relative">
            <!-- Back to Reports Button -->
            <Link
                :href="route('admin.reports.index')"
                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition-colors"
            >
                Volver a Reportes
            </Link>
        </div>
    </div>
</template>

<script>
import { Link } from "@inertiajs/vue3";

export default {
    name: "ReportsHeader",
    components: {
        Link,
    },
    props: {
        subtitle: {
            type: String,
            default: "Estado de Cuenta Parcial"
        }
    }
};
</script>
