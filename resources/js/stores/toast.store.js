import { defineStore } from 'pinia';
import { ref } from 'vue';

let nextToastId = 1;

export const useToastStore = defineStore('toast', () => {
    const toasts = ref([]);

    function push({ type = 'info', title = '', message = '', timeout = 4000 }) {
        const id = nextToastId++;
        const toast = {
            id,
            type,
            title,
            message,
        };

        toasts.value.push(toast);

        if (timeout > 0) {
            window.setTimeout(() => remove(id), timeout);
        }

        return id;
    }

    function success(message, title = 'Success') {
        return push({ type: 'success', title, message });
    }

    function error(message, title = 'Error') {
        return push({ type: 'error', title, message, timeout: 6000 });
    }

    function warning(message, title = 'Warning') {
        return push({ type: 'warning', title, message });
    }

    function info(message, title = 'Info') {
        return push({ type: 'info', title, message });
    }

    function remove(id) {
        toasts.value = toasts.value.filter((toast) => toast.id !== id);
    }

    function clear() {
        toasts.value = [];
    }

    return {
        toasts,
        push,
        success,
        error,
        warning,
        info,
        remove,
        clear,
    };
});
