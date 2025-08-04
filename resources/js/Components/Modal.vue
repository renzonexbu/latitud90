<template>
    <div v-show="show" class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50">
        <div class="fixed inset-0 transform transition-all" @click="close">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <div class="mb-6 transform transition-all sm:w-full sm:mx-auto">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg sm:max-w-lg sm:w-full mx-auto" @click.stop>
                <slot />
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const close = () => {
    emit('close');
};
</script> 