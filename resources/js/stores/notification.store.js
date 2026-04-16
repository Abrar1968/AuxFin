import { computed, ref } from 'vue';
import { defineStore } from 'pinia';

const MAX_NOTIFICATIONS = 80;

function resolveNotificationId(item) {
    if (item.id) {
        return String(item.id);
    }

    const payload = item.payload ?? {};
    const stableKeys = [
        'message_id',
        'loan_id',
        'leave_id',
        'invoice_id',
        'payment_id',
        'salary_month_id',
        'report',
        'stream',
        'generated_at',
        'month',
        'reference_number',
        'subject',
    ];

    const stablePart = stableKeys
        .filter((key) => payload[key] !== undefined && payload[key] !== null && String(payload[key]).trim() !== '')
        .map((key) => `${key}:${String(payload[key])}`)
        .join('|');

    if (stablePart) {
        return `${item.type}:${stablePart}`;
    }

    const fallbackMoment = item.createdAt ?? payload.generated_at ?? new Date().toISOString();
    return `${item.type}:${fallbackMoment}`;
}

export const useNotificationStore = defineStore('notification', () => {
    const notifications = ref([]);
    const unreadCount = computed(() => notifications.value.filter((n) => !n.read).length);
    const unreadMessageCount = computed(() => notifications.value.filter((n) => !n.read && String(n.type).startsWith('message.')).length);

    function addNotification(item) {
        const type = String(item.type ?? 'notification');
        const id = resolveNotificationId({ ...item, type });
        const exists = notifications.value.some((row) => row.id === id);

        if (exists) {
            return;
        }

        notifications.value.unshift({
            ...item,
            type,
            read: Boolean(item.read ?? false),
            id,
            createdAt: item.createdAt ?? new Date().toISOString(),
        });

        if (notifications.value.length > MAX_NOTIFICATIONS) {
            notifications.value = notifications.value.slice(0, MAX_NOTIFICATIONS);
        }
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
