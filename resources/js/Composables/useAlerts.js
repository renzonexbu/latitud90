import { ref } from 'vue';

export function useAlerts() {
    const showAlert = ref(false);
    const alertType = ref('success');
    const alertTitle = ref('');
    const alertMessage = ref('');

    const showSuccess = (title, message = '') => {
        showAlertMessage('success', title, message);
    };

    const showError = (title, message = '') => {
        showAlertMessage('error', title, message);
    };

    const showWarning = (title, message = '') => {
        showAlertMessage('warning', title, message);
    };

    const showInfo = (title, message = '') => {
        showAlertMessage('info', title, message);
    };

    const showAlertMessage = (type, title, message) => {
        alertType.value = type;
        alertTitle.value = title;
        alertMessage.value = message;
        showAlert.value = true;
    };

    const closeAlert = () => {
        showAlert.value = false;
    };

    // Método para mostrar alertas desde flash messages de Inertia
    const showFromFlash = (flash) => {
        if (flash.success) {
            showSuccess('Éxito', flash.success);
            return true;
        } else if (flash.error) {
            showError('Error', flash.error);
            return true;
        } else if (flash.message) {
            showInfo('Información', flash.message);
            return true;
        }
        return false;
    };

    return {
        showAlert,
        alertType,
        alertTitle,
        alertMessage,
        showSuccess,
        showError,
        showWarning,
        showInfo,
        showAlertMessage,
        closeAlert,
        showFromFlash
    };
}
