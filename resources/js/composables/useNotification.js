import { ref } from 'vue';

const notification = ref({
    show: false,
    type: 'success', // success, error, warning, confirm
    title: '',
    message: '',
    confirmLabel: 'Confirm',
    cancelLabel: 'Cancel',
    onConfirm: null,
    onClose: null,
});

export function useNotification() {
    const notify = (type, message, title = '') => {
        notification.value = {
            show: true,
            type,
            title: title || getDefaultTitle(type),
            message,
            confirmLabel: 'Confirm',
            cancelLabel: 'Cancel',
            onConfirm: null,
            onClose: null,
        };
    };

    const confirmAction = (message, title = 'Confirm Action', onConfirm = null, options = {}) => {
        notification.value = {
            show: true,
            type: 'confirm',
            title,
            message,
            confirmLabel: options.confirmLabel || 'Confirm',
            cancelLabel: options.cancelLabel || 'Cancel',
            onConfirm,
            onClose: options.onClose || null,
        };
    };

    const closeNotification = () => {
        if (notification.value.onClose) {
            notification.value.onClose();
        }
        notification.value.show = false;
    };

    const handleConfirm = () => {
        if (notification.value.onConfirm) {
            notification.value.onConfirm();
        }
        notification.value.show = false;
    };

    return {
        notification,
        notify,
        confirmAction,
        closeNotification,
        handleConfirm,
    };
}

function getDefaultTitle(type) {
    switch (type) {
        case 'success': return 'Success';
        case 'error': return 'Error';
        case 'warning': return 'Warning';
        case 'confirm': return 'Confirm Action';
        default: return '';
    }
}
