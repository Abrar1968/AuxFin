import { computed, ref } from 'vue';
import { defineStore } from 'pinia';

export const useNotificationStore = defineStore('notification', () => {
    const notifications = ref([]);
    const unreadCount = computed(() => notifications.value.filter((n) => !n.read).length);
    const unreadMessageCount = computed(() => notifications.value.filter((n) => !n.read && String(n.type).startsWith('message.')).length);

    function addNotification(item) {
        const id = item.id ?? `${item.type}:${item.payload?.message_id ?? item.payload?.loan_id ?? item.payload?.leave_id ?? item.payload?.invoice_id ?? Date.now()}`;
        const exists = notifications.value.some((row) => row.id === id);

        if (exists) {
            return;
        }

        notifications.value.unshift({
            ...item,
            read: false,
            id,
            createdAt: item.createdAt ?? new Date().toISOString(),
        });
    }

    function markRead(id) {
        const target = notifications.value.find((n) => n.id === id);
        if (target) {
            target.read = true;
        }
    }

    function markAllRead() {
        notifications.value = notifications.value.map((n) => ({ ...n, read: true }));
    }

    function clear() {
        notifications.value = [];
    }

    return {
        notifications,
        unreadCount,
        unreadMessageCount,
        addNotification,
        markRead,
        markAllRead,
        clear,
    };
});
