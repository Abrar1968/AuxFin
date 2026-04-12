import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { useRealTime } from '../composables/useRealTime';
import { useNotificationStore } from '../stores/notification.store';

function channelMock() {
    return {
        listen: vi.fn().mockReturnThis(),
        stopListening: vi.fn(),
    };
}

describe('useRealTime composable', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        window.configureEchoAuth = vi.fn();
    });

    it('subscribes admin channels and unbinds on unsubscribe', () => {
        const adminChannel = channelMock();
        const insightChannel = channelMock();

        window.Echo = { private: vi.fn(() => adminChannel) };
        window.EchoInsights = { private: vi.fn(() => insightChannel) };

        const rt = useRealTime();
        rt.subscribeAdmin('admin-token');

        expect(window.configureEchoAuth).toHaveBeenCalledWith('admin-token');
        expect(window.Echo.private).toHaveBeenCalledWith('admin-broadcast');
        expect(window.EchoInsights.private).toHaveBeenCalledWith('admin-broadcast');

        rt.unsubscribeAdmin();
        expect(adminChannel.stopListening).toHaveBeenCalled();
        expect(insightChannel.stopListening).toHaveBeenCalled();
    });

    it('subscribes employee channel and pushes notifications via listener', () => {
        const store = useNotificationStore();
        const fakeChannel = channelMock();

        fakeChannel.listen.mockImplementation((eventName, callback) => {
            if (eventName === '.salary.paid') {
                callback({ month: '2026-04' });
            }

            return fakeChannel;
        });

        window.Echo = { private: vi.fn(() => fakeChannel) };
        window.EchoNotifications = null;

        const rt = useRealTime();
        rt.subscribeEmployee(5, 'employee-token');

        expect(window.Echo.private).toHaveBeenCalledWith('employee.5');
        expect(store.notifications.length).toBeGreaterThan(0);
    });
});
