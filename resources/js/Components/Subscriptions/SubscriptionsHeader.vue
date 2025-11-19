<template>
    <div class="flex flex-col">
        <!-- Header Principal -->
        <div class="flex items-center justify-between px-8 py-6 bg-white">
            <div>
                <h1 class="text-[32px] leading-10 font-nexa-xbold text-[#1c4f4a]">
                    Suscripciones
                </h1>
                <p v-if="subtitle" class="text-[14px] leading-5 font-nexa-regular text-[#5b5b5b] mt-2">
                    {{ subtitle }}
                </p>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "SubscriptionsHeader",
    props: {
        subtitle: {
            type: String,
            default: "",
        },
    },
};
</script>

<style scoped>
.font-nexa-xbold {
    font-family: "Nexa-XBold", sans-serif;
    font-weight: 400;
}

.font-nexa-regular {
    font-family: "Nexa-Regular", sans-serif;
    font-weight: 400;
}
</style>
