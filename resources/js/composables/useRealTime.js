import { useNotificationStore } from '../stores/notification.store';

let adminChannel = null;
let employeeChannel = null;

export function useRealTime() {
    const notifications = useNotificationStore();

    function subscribeAdmin(token = null) {
        const echo = window.EchoNotifications || window.Echo || window.EchoMain;
        if (!echo) return;

        configureAuth(token);
        unsubscribeAdmin();

        adminChannel = echo.private('admin-broadcast');

        bind(adminChannel, 'loan.applied');
        bind(adminChannel, 'leave.applied');
        bind(adminChannel, 'invoice.overdue');
        bind(adminChannel, 'liability.due_soon');
        bind(adminChannel, 'message.new');
    }

    function unsubscribeAdmin() {
        if (!adminChannel) {
            return;
        }

        unbind(adminChannel, 'loan.applied');
        unbind(adminChannel, 'leave.applied');
        unbind(adminChannel, 'invoice.overdue');
        unbind(adminChannel, 'liability.due_soon');
        unbind(adminChannel, 'message.new');

        adminChannel = null;
    }

    function subscribeEmployee(employeeId, token = null) {
        const echo = window.EchoNotifications || window.Echo || window.EchoMain;
        if (!echo || !employeeId) return;

        configureAuth(token);
        unsubscribeEmployee();

        employeeChannel = echo.private(`employee.${employeeId}`);

        bind(employeeChannel, 'salary.processed');
        bind(employeeChannel, 'salary.paid');
        bind(employeeChannel, 'loan.approved');
        bind(employeeChannel, 'loan.rejected');
        bind(employeeChannel, 'leave.decision');
        bind(employeeChannel, 'message.replied');
        bind(employeeChannel, 'message.resolved');
        bind(employeeChannel, 'message.action_taken');
    }

    function unsubscribeEmployee() {
        if (!employeeChannel) {
            return;
        }

        unbind(employeeChannel, 'salary.processed');
        unbind(employeeChannel, 'salary.paid');
        unbind(employeeChannel, 'loan.approved');
        unbind(employeeChannel, 'loan.rejected');
        unbind(employeeChannel, 'leave.decision');
        unbind(employeeChannel, 'message.replied');
        unbind(employeeChannel, 'message.resolved');
        unbind(employeeChannel, 'message.action_taken');

        employeeChannel = null;
    }

    function unsubscribeAll() {
        unsubscribeAdmin();
        unsubscribeEmployee();
    }

    function configureAuth(token = null) {
        if (typeof window.configureEchoAuth === 'function') {
            window.configureEchoAuth(token);
        }
    }

    function bind(channel, eventName) {
        channel
            .listen(`.${eventName}`, (payload) => {
                notifications.addNotification({ type: eventName, payload });
            })
            .listen(eventName, (payload) => {
                notifications.addNotification({ type: eventName, payload });
            });
    }

    function unbind(channel, eventName) {
        channel.stopListening(`.${eventName}`);
        channel.stopListening(eventName);
    }

    return {
        subscribeAdmin,
        subscribeEmployee,
        unsubscribeAdmin,
        unsubscribeEmployee,
        unsubscribeAll,
    };
}
