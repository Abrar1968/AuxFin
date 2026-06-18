import { useNotificationStore } from '../stores/notification.store';

let adminChannel = null;
let adminInsightsChannel = null;
let employeeChannel = null;
let customAdminChannel = null;
let customEmployeeChannel = null;
let customAdminEvents = [];
let customEmployeeEvents = [];

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
    'insight.report.trial_balance',
    'insight.report.balance_sheet',
    'insight.report.cash_flow',
    'insight.report.general_ledger',
    'insight.report.payment_ledger',
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

        const notificationsEcho = window.EchoMain || window.EchoNotifications || window.Echo;
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

        const echo = window.EchoMain || window.EchoNotifications || window.Echo;
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
        unsubscribeCustomAdmin();
        unsubscribeCustomEmployee();
    }

    function subscribeAdminEvents(handlers = {}, token = null) {
        configureAuth(token);
        unsubscribeCustomAdmin();

        const echo = window.EchoMain || window.EchoChat || window.EchoNotifications || window.Echo;
        if (!echo) {
            return;
        }

        customAdminChannel = echo.private('admin-broadcast');
        customAdminEvents = Object.keys(handlers);
        Object.entries(handlers).forEach(([eventName, handler]) => {
            bindHandler(customAdminChannel, eventName, handler);
        });
    }

    function unsubscribeCustomAdmin() {
        if (!customAdminChannel) {
            return;
        }

        customAdminEvents.forEach((eventName) => unbind(customAdminChannel, eventName));
        customAdminChannel = null;
        customAdminEvents = [];
    }

    function subscribeEmployeeEvents(employeeId, handlers = {}, token = null) {
        if (!employeeId) return;

        configureAuth(token);
        unsubscribeCustomEmployee();

        const echo = window.EchoMain || window.EchoChat || window.EchoNotifications || window.Echo;
        if (!echo) {
            return;
        }

        customEmployeeChannel = echo.private(`employee.${employeeId}`);
        customEmployeeEvents = Object.keys(handlers);
        Object.entries(handlers).forEach(([eventName, handler]) => {
            bindHandler(customEmployeeChannel, eventName, handler);
        });
    }

    function unsubscribeCustomEmployee() {
        if (!customEmployeeChannel) {
            return;
        }

        customEmployeeEvents.forEach((eventName) => unbind(customEmployeeChannel, eventName));
        customEmployeeChannel = null;
        customEmployeeEvents = [];
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

    function bindHandler(channel, eventName, handler) {
        channel
            .listen(`.${eventName}`, handler)
            .listen(eventName, handler);
    }

    function unbind(channel, eventName) {
        channel.stopListening(`.${eventName}`);
        channel.stopListening(eventName);
    }

    return {
        subscribeAdmin,
        subscribeEmployee,
        subscribeAdminEvents,
        subscribeEmployeeEvents,
        unsubscribeAdmin,
        unsubscribeEmployee,
        unsubscribeCustomAdmin,
        unsubscribeCustomEmployee,
        unsubscribeAll,
    };
}
