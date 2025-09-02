<template>
    <Alerts
        v-if="showAlert"
        :show="showAlert"
        :type="alertType"
        :title="alertTitle"
        :message="alertMessage"
        :auto-close="true"
        :duration="5000"
        @close="closeAlert"
    />
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Alerts from '@/Components/Alerts.vue';
import { useAlerts } from '@/Composables/useAlerts.js';

const page = usePage();
const {
    showAlert,
    alertType,
    alertTitle,
    alertMessage,
    showFromFlash,
    closeAlert
} = useAlerts();

// Detectar flash messages al montar el componente
onMounted(() => {
    if (page.props.flash) {
        showFromFlash(page.props.flash);
    }
});

// Watcher para detectar cambios en flash messages
watch(() => page.props.flash, (newFlash) => {
    if (newFlash) {
        showFromFlash(newFlash);
    }
}, { deep: true });

// Exponer métodos para uso externo
defineExpose({
    showSuccess: (title, message) => {
        alertType.value = 'success';
        alertTitle.value = title;
        alertMessage.value = message;
        showAlert.value = true;
    },
    showError: (title, message) => {
        alertType.value = 'error';
        alertTitle.value = title;
        alertMessage.value = message;
        showAlert.value = true;
    },
    showWarning: (title, message) => {
        alertType.value = 'warning';
        alertTitle.value = title;
        alertMessage.value = message;
        showAlert.value = true;
    },
    showInfo: (title, message) => {
        alertType.value = 'info';
        alertTitle.value = title;
        alertMessage.value = message;
        showAlert.value = true;
    }
});
</script>
