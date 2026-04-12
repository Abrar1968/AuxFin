import { beforeEach, describe, expect, it } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { useNotificationStore } from '../stores/notification.store';

describe('notification store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('tracks unread and prevents duplicate notification ids', () => {
        const store = useNotificationStore();

        store.addNotification({ type: 'message.new', payload: { message_id: 11 } });
        store.addNotification({ type: 'message.new', payload: { message_id: 11 } });

        expect(store.notifications.length).toBe(1);
        expect(store.unreadCount).toBe(1);
        expect(store.unreadMessageCount).toBe(1);
    });

    it('supports markRead and markAllRead', () => {
        const store = useNotificationStore();

        store.addNotification({ id: 'a', type: 'loan.applied', payload: {} });
        store.addNotification({ id: 'b', type: 'message.replied', payload: {} });

        store.markRead('a');
        expect(store.unreadCount).toBe(1);

        store.markAllRead();
        expect(store.unreadCount).toBe(0);
    });
});
