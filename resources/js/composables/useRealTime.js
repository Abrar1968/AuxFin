import { useNotificationStore } from '../stores/notification.store';

let adminChannel = null;
let adminInsightsChannel = null;
let employeeChannel = null;

const ADMIN_EVENTS = [
    'loan.applied',
    'leave.applied',
    'invoice.overdue',
    'liability.due_soon',
    'message.new',
];

const ADMIN_INSIGHT_EVENTS = [
    'insight.analytics.overview',
    'insight.analytics.cmgr',
    'insight.analytics.growth',
    'insight.analytics.forecast',
    'insight.analytics.runway',
    'insight.analytics.anomalies',
    'insight.analytics.ar_health',
    'insight.report.profit_loss',
    'insight.report.tax_summary',
    'insight.report.ar_aging',
    'insight.security.audit',
];

const EMPLOYEE_EVENTS = [
    'salary.processed',
    'salary.paid',
    'loan.approved',
    'loan.rejected',
    'leave.decision',
    'message.replied',
    'message.resolved',
    'message.action_taken',
];

export function useRealTime() {
    const notifications = useNotificationStore();

    function subscribeAdmin(token = null) {
        configureAuth(token);
        unsubscribeAdmin();

        const notificationsEcho = window.EchoNotifications || window.Echo || window.EchoMain;
        if (notificationsEcho) {
            adminChannel = notificationsEcho.private('admin-broadcast');
            ADMIN_EVENTS.forEach((eventName) => bind(adminChannel, eventName));
        }

        const insightsEcho = window.EchoInsights;
        if (insightsEcho) {
            adminInsightsChannel = insightsEcho.private('admin-broadcast');
            ADMIN_INSIGHT_EVENTS.forEach((eventName) => bind(adminInsightsChannel, eventName));
        }
    }

    function unsubscribeAdmin() {
        if (adminChannel) {
            ADMIN_EVENTS.forEach((eventName) => unbind(adminChannel, eventName));
            adminChannel = null;
        }

        if (adminInsightsChannel) {
            ADMIN_INSIGHT_EVENTS.forEach((eventName) => unbind(adminInsightsChannel, eventName));
            adminInsightsChannel = null;
        }
    }

    function subscribeEmployee(employeeId, token = null) {
        if (!employeeId) return;

        configureAuth(token);
        unsubscribeEmployee();

        const echo = window.EchoNotifications || window.Echo || window.EchoMain;
        if (!echo) {
            return;
        }

        employeeChannel = echo.private(`employee.${employeeId}`);
        EMPLOYEE_EVENTS.forEach((eventName) => bind(employeeChannel, eventName));
    }

    function unsubscribeEmployee() {
        if (!employeeChannel) {
            return;
        }

        EMPLOYEE_EVENTS.forEach((eventName) => unbind(employeeChannel, eventName));

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
